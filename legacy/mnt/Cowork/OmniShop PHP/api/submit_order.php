<?php
/**
 * API: Submit Order
 * POST /api/submit-order
 * Expects JSON body, returns JSON response.
 *
 * Flow:
 *   1. Parse + validate JSON input
 *   2. Create order in DB (transaction)
 *   3. Generate PDF invoice (FPDF)
 *   4. Save PDF to storage/invoices/
 *   5. Email customer (PDF attached)
 *   6. Email admin notification (BCC'd on customer email too)
 *   7. Return { success: true, order_id: "OMN-..." }
 */

if (!defined('BASE_PATH')) { exit; }

require_once BASE_PATH . '/includes/db.php';
require_once BASE_PATH . '/includes/events.php';
require_once BASE_PATH . '/includes/invoice.php';
require_once BASE_PATH . '/includes/email.php';
require_once BASE_PATH . '/includes/catalog.php';

// ── Accept JSON only ──────────────────────────────────────────────────────────
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit;
}

$raw = file_get_contents('php://input');
if (!$raw) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Empty request body.']);
    exit;
}

$data = json_decode($raw, true);
if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON.']);
    exit;
}

// ── Validate required fields ──────────────────────────────────────────────────
$required = ['event_slug', 'contact_name', 'company_name', 'booth_number', 'email', 'items'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => "Missing required field: $field"]);
        exit;
    }
}

if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Invalid email address.']);
    exit;
}

if (!is_array($data['items']) || count($data['items']) === 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Order must contain at least one item.']);
    exit;
}

// ── Validate event ────────────────────────────────────────────────────────────
$eventSlug = trim($data['event_slug']);
$event     = get_event($eventSlug);
if (!$event) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Unknown event.']);
    exit;
}

// ── Sanitise and re-calculate totals server-side ──────────────────────────────
// (Never trust client-submitted prices — look up from catalog)
$catalogProducts = get_catalog_products();
$catalogMap      = [];
foreach ($catalogProducts as $p) {
    $catalogMap[$p['code']] = $p;
}

$validatedItems = [];
$subtotal       = 0.0;

foreach ($data['items'] as $clientItem) {
    $code     = trim($clientItem['product_code'] ?? '');
    $qty      = max(1, (int)($clientItem['quantity'] ?? 1));
    $colorName = trim($clientItem['color_name'] ?? '');

    if (!$code) continue;

    // Look up authoritative price from catalog
    if (isset($catalogMap[$code])) {
        $catProd   = $catalogMap[$code];
        $unitPrice = (float)($catProd['price'] ?? 0);
        $isPoa     = !empty($catProd['is_poa']);
        $prodName  = $catProd['name'];
    } else {
        // Product might be admin-only (not in static catalog); trust client price
        $unitPrice = (float)($clientItem['unit_price'] ?? 0);
        $isPoa     = false;
        $prodName  = trim($clientItem['product_name'] ?? $code);
    }

    if ($isPoa) {
        // POA items cannot be ordered through the self-service catalog
        continue;
    }

    $lineTotal  = round($unitPrice * $qty, 2);
    $subtotal  += $lineTotal;

    $validatedItems[] = [
        'product_code' => $code,
        'product_name' => $prodName,
        'color_name'   => $colorName,
        'category'     => $clientItem['category']   ?? '',
        'dimensions'   => $clientItem['dimensions'] ?? '',
        'quantity'     => $qty,
        'unit_price'   => $unitPrice,
        'total_price'  => $lineTotal,
    ];
}

if (count($validatedItems) === 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'No valid items in order.']);
    exit;
}

$vatRate = defined('VAT_RATE') ? (float)VAT_RATE : 16.0;
$vat     = round($subtotal * ($vatRate / 100), 2);
$total   = round($subtotal + $vat, 2);

// ── Build order data ──────────────────────────────────────────────────────────
$orderData = [
    'event_slug'   => $eventSlug,
    'contact_name' => trim($data['contact_name']),
    'company_name' => trim($data['company_name']),
    'booth_number' => trim($data['booth_number']),
    'email'        => strtolower(trim($data['email'])),
    'phone'        => trim($data['phone'] ?? ''),
    'notes'        => trim($data['notes'] ?? ''),
    'subtotal'     => $subtotal,
    'vat'          => $vat,
    'total'        => $total,
];

// ── Create order in DB ────────────────────────────────────────────────────────
try {
    $orderId = create_order($orderData, $validatedItems);
} catch (\Exception $e) {
    error_log('[OmniShop] create_order failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to save order. Please try again.']);
    exit;
}

// ── Reload order from DB (for confirmed data) ─────────────────────────────────
$orderRecord = get_order($orderId);
if (!$orderRecord) {
    // Fallback: use what we built
    $order = array_merge($orderData, ['order_id' => $orderId, 'created_at' => date('Y-m-d H:i:s')]);
    $items = $validatedItems;
} else {
    $order = $orderRecord['order'];
    $items = $orderRecord['items'];
}

// ── Get settings ──────────────────────────────────────────────────────────────
$settings = get_all_settings();

// ── Generate PDF invoice ──────────────────────────────────────────────────────
$pdfBytes = false;
try {
    $pdfBytes = generate_invoice($order, $items, $event, $settings);
    // Save to disk for later download from admin
    save_invoice_pdf($orderId, $pdfBytes);
} catch (\Exception $e) {
    error_log('[OmniShop] PDF generation failed: ' . $e->getMessage());
    // Non-fatal: order is saved, email will be sent without PDF
}

// ── Send customer email ───────────────────────────────────────────────────────
$emailOk = false;
try {
    $emailOk = send_order_confirmation_email(
        $order,
        $items,
        $event,
        $settings,
        $pdfBytes ?: ''
    );
} catch (\Exception $e) {
    error_log('[OmniShop] Customer email failed: ' . $e->getMessage());
}

// ── Send admin notification ───────────────────────────────────────────────────
try {
    send_admin_notification_email($order, $items, $event, $settings);
} catch (\Exception $e) {
    error_log('[OmniShop] Admin notification email failed: ' . $e->getMessage());
}

// ── Respond success ───────────────────────────────────────────────────────────
echo json_encode([
    'success'  => true,
    'order_id' => $orderId,
    'total'    => $total,
    'email_ok' => $emailOk,
]);
exit;

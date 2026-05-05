<?php
/**
 * Admin — Download PDF Invoice for any order
 * Route: /admin/invoice/{order_id}
 * Generates (or retrieves cached) PDF and streams it.
 */
if (!defined('BASE_PATH')) { exit; }
require_admin_auth();
require_once BASE_PATH . '/includes/invoice.php';
require_once BASE_PATH . '/includes/events.php';

$orderId   = $_GET['order_id'] ?? '';
$orderData = $orderId ? get_order($orderId) : null;

if (!$orderData) {
    http_response_code(404);
    echo 'Order not found.';
    exit;
}

$order    = $orderData['order'];
$items    = $orderData['items'];
$event    = get_event($order['event_slug'] ?? '');
$settings = get_all_settings();

// ── Try cached PDF first ──────────────────────────────────────────────────────
$cachedPath = BASE_PATH . '/storage/invoices/invoice-'
    . preg_replace('/[^A-Z0-9\-]/', '', strtoupper($orderId)) . '.pdf';

if (file_exists($cachedPath)) {
    $pdfBytes = file_get_contents($cachedPath);
} else {
    // Regenerate
    try {
        $pdfBytes = generate_invoice($order, $items, $event, $settings);
        save_invoice_pdf($orderId, $pdfBytes);
    } catch (\Exception $e) {
        http_response_code(500);
        echo 'PDF generation failed: ' . htmlspecialchars($e->getMessage());
        exit;
    }
}

$filename = 'Invoice-' . preg_replace('/[^A-Z0-9\-]/', '', strtoupper($orderId)) . '.pdf';

// Inline or download?
$disposition = isset($_GET['download']) ? 'attachment' : 'inline';

header('Content-Type: application/pdf');
header("Content-Disposition: $disposition; filename=\"$filename\"");
header('Content-Length: ' . strlen($pdfBytes));
header('Cache-Control: private, max-age=3600');

echo $pdfBytes;
exit;

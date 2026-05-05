<?php
/**
 * API: Update Order Status
 * POST /api/update-status
 * Expects JSON: { order_id, status }
 * Admin auth required.
 */
if (!defined('BASE_PATH')) { exit; }
require_admin_auth();
require_once BASE_PATH . '/includes/email.php';
require_once BASE_PATH . '/includes/events.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit;
}

$raw  = file_get_contents('php://input');
$data = $raw ? json_decode($raw, true) : $_POST;

$orderId   = trim($data['order_id'] ?? '');
$newStatus = trim($data['status']   ?? '');
$statuses  = get_order_statuses();

if (!$orderId || !$newStatus) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'order_id and status are required.']);
    exit;
}

if (!isset($statuses[$newStatus])) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Invalid status: ' . $newStatus]);
    exit;
}

$orderData = get_order($orderId);
if (!$orderData) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Order not found.']);
    exit;
}

update_order_status($orderId, $newStatus);

// Send payment email if transitioning to paid
$emailSent = false;
if ($newStatus === 'paid') {
    $settings = get_all_settings();
    $event    = get_event($orderData['order']['event_slug'] ?? '');
    // Reload updated order
    $updated  = get_order($orderId);
    if ($updated && $event) {
        $emailSent = send_payment_received_email($updated['order'], $event, $settings);
    }
}

echo json_encode([
    'success'    => true,
    'order_id'   => $orderId,
    'new_status' => $newStatus,
    'email_sent' => $emailSent,
]);
exit;

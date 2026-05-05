<?php
/**
 * Admin — Order Detail Page
 * Route: /admin/order/{order_id}
 */
if (!defined('BASE_PATH')) { exit; }
require_admin_auth();
require_once BASE_PATH . '/includes/admin_layout.php';
require_once BASE_PATH . '/includes/events.php';
require_once BASE_PATH . '/includes/invoice.php';
require_once BASE_PATH . '/includes/email.php';

$orderId   = $_GET['order_id'] ?? '';
$orderData = $orderId ? get_order($orderId) : null;

if (!$orderData) {
    http_response_code(404);
    admin_header('Order Not Found', 'orders');
    echo '<div class="alert alert-danger">Order not found: ' . e($orderId) . '</div>';
    echo '<a href="' . SITE_URL . '/admin" class="btn btn-outline">← Back to Dashboard</a>';
    admin_footer();
    exit;
}

$order     = $orderData['order'];
$items     = $orderData['items'];
$event     = get_event($order['event_slug'] ?? '');
$settings  = get_all_settings();
$statuses  = get_order_statuses();

// ── Handle POST actions ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_status') {
        $newStatus = $_POST['status'] ?? '';
        if (isset($statuses[$newStatus])) {
            update_order_status($orderId, $newStatus);
            // Send payment confirmed email if transitioning to paid
            if ($newStatus === 'paid') {
                $updatedData = get_order($orderId);
                if ($updatedData) {
                    send_payment_received_email($updatedData['order'], $event, $settings);
                }
            }
            set_flash('success', 'Status updated to: ' . $statuses[$newStatus]);
        } else {
            set_flash('danger', 'Invalid status.');
        }
        redirect('/admin/order/' . urlencode($orderId));
    }

    if ($action === 'resend_invoice') {
        // Regenerate and resend
        try {
            $pdfBytes = generate_invoice($order, $items, $event, $settings);
            save_invoice_pdf($orderId, $pdfBytes);
            $emailOk  = send_order_confirmation_email($order, $items, $event, $settings, $pdfBytes);
            if ($emailOk) {
                set_flash('success', 'Invoice re-sent to ' . $order['email']);
            } else {
                set_flash('warning', 'Email may not have been sent. Check SMTP settings.');
            }
        } catch (\Exception $ex) {
            set_flash('danger', 'Failed to resend invoice: ' . $ex->getMessage());
        }
        redirect('/admin/order/' . urlencode($orderId));
    }
}

// Reload after potential status update
$orderData = get_order($orderId);
$order     = $orderData['order'];
$items     = $orderData['items'];

admin_header('Order: ' . $orderId, 'orders');
?>

<?php admin_flash(); ?>

<div style="margin-bottom:16px;">
  <a href="<?= SITE_URL ?>/admin<?= $order['event_slug'] ? '?event=' . e($order['event_slug']) : '' ?>"
     class="btn btn-outline btn-sm">← Back to Orders</a>
  <a href="<?= SITE_URL ?>/admin/invoice/<?= urlencode($orderId) ?>"
     class="btn btn-secondary btn-sm" target="_blank" style="margin-left:6px;">
    📄 Download Invoice PDF
  </a>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">

  <!-- Order info -->
  <div class="card">
    <div class="card-header">Order Details</div>
    <div class="card-body">
      <table style="width:100%;font-size:13px;border-collapse:collapse;">
        <tr><td style="color:#6E6E6E;padding:4px 0;width:40%;">Order Reference</td>
            <td><strong style="font-family:monospace;"><?= e($order['order_id']) ?></strong></td></tr>
        <tr><td style="color:#6E6E6E;padding:4px 0;">Event</td>
            <td><?= e($event ? $event['name'] : $order['event_slug']) ?></td></tr>
        <tr><td style="color:#6E6E6E;padding:4px 0;">Status</td>
            <td><span class="badge badge-<?= status_badge_class($order['status']) ?>">
              <?= e(ucfirst(str_replace('_', ' ', $order['status']))) ?></span></td></tr>
        <tr><td style="color:#6E6E6E;padding:4px 0;">Order Date</td>
            <td><?= e(date('d M Y H:i', strtotime($order['created_at']))) ?></td></tr>
        <?php if (!empty($order['notes'])): ?>
        <tr><td style="color:#6E6E6E;padding:4px 0;vertical-align:top;">Notes</td>
            <td style="font-style:italic;"><?= e($order['notes']) ?></td></tr>
        <?php endif; ?>
      </table>
    </div>
  </div>

  <!-- Customer info -->
  <div class="card">
    <div class="card-header">Customer Details</div>
    <div class="card-body">
      <table style="width:100%;font-size:13px;border-collapse:collapse;">
        <tr><td style="color:#6E6E6E;padding:4px 0;width:40%;">Company</td>
            <td><strong><?= e($order['company_name']) ?></strong></td></tr>
        <tr><td style="color:#6E6E6E;padding:4px 0;">Contact</td>
            <td><?= e($order['contact_name']) ?></td></tr>
        <tr><td style="color:#6E6E6E;padding:4px 0;">Stand / Booth</td>
            <td><strong><?= e($order['booth_number']) ?></strong></td></tr>
        <tr><td style="color:#6E6E6E;padding:4px 0;">Email</td>
            <td><a href="mailto:<?= e($order['email']) ?>"><?= e($order['email']) ?></a></td></tr>
        <tr><td style="color:#6E6E6E;padding:4px 0;">Phone</td>
            <td><?= !empty($order['phone'])
                    ? '<a href="https://wa.me/' . preg_replace('/[^0-9]/', '', $order['phone']) . '">' . e($order['phone']) . '</a>'
                    : '—' ?></td></tr>
      </table>
    </div>
  </div>
</div>

<!-- Items table -->
<div class="card mb-2">
  <div class="card-header">Items Ordered</div>
  <div class="card-body" style="padding:0;">
    <table class="admin-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Product</th>
          <th>Colour</th>
          <th style="text-align:right;">Unit Price</th>
          <th style="text-align:center;">Qty</th>
          <th style="text-align:right;">Line Total</th>
        </tr>
      </thead>
      <tbody>
        <?php $n = 0; foreach ($items as $item): $n++; ?>
        <tr>
          <td style="color:#6E6E6E;"><?= $n ?></td>
          <td>
            <strong><?= e($item['product_name']) ?></strong>
            <?php if (!empty($item['product_code'])): ?>
              <span style="font-size:10px;color:#6E6E6E;margin-left:4px;">(<?= e($item['product_code']) ?>)</span>
            <?php endif; ?>
          </td>
          <td><?= e($item['color_name'] ?: '—') ?></td>
          <td style="text-align:right;">$<?= number_format((float)$item['unit_price'], 2) ?></td>
          <td style="text-align:center;"><?= (int)$item['quantity'] ?></td>
          <td style="text-align:right;font-weight:600;">$<?= number_format((float)$item['total_price'], 2) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr>
          <td colspan="5" style="text-align:right;padding:8px;color:#6E6E6E;">Subtotal</td>
          <td style="text-align:right;padding:8px;">$<?= number_format((float)$order['subtotal'], 2) ?></td>
        </tr>
        <tr>
          <td colspan="5" style="text-align:right;padding:4px 8px;color:#6E6E6E;">VAT (<?= defined('VAT_RATE') ? (int)VAT_RATE : 16 ?>%)</td>
          <td style="text-align:right;padding:4px 8px;">$<?= number_format((float)$order['vat'], 2) ?></td>
        </tr>
        <tr>
          <td colspan="5" style="text-align:right;padding:10px 8px;font-weight:800;color:#0A9696;font-size:15px;">TOTAL (USD)</td>
          <td style="text-align:right;padding:10px 8px;font-weight:800;color:#0A9696;font-size:15px;">
            $<?= number_format((float)$order['total'], 2) ?>
          </td>
        </tr>
      </tfoot>
    </table>
  </div>
</div>

<!-- Action panels -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">

  <!-- Update status -->
  <div class="card">
    <div class="card-header">Update Status</div>
    <div class="card-body">
      <form method="POST" action="/admin/order/<?= urlencode($orderId) ?>">
        <input type="hidden" name="action" value="update_status">
        <div class="form-group">
          <label>New Status</label>
          <select name="status" class="form-control">
            <?php foreach ($statuses as $sk => $sv): ?>
            <option value="<?= e($sk) ?>" <?= ($order['status'] === $sk) ? 'selected' : '' ?>>
              <?= e($sv) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="btn btn-primary btn-sm">Update Status</button>
      </form>
    </div>
  </div>

  <!-- Re-send invoice -->
  <div class="card">
    <div class="card-header">Invoice Actions</div>
    <div class="card-body">
      <p style="font-size:13px;color:#6E6E6E;margin-bottom:12px;">
        Regenerate and re-send the PDF invoice to <strong><?= e($order['email']) ?></strong>
      </p>
      <form method="POST" action="/admin/order/<?= urlencode($orderId) ?>">
        <input type="hidden" name="action" value="resend_invoice">
        <button type="submit" class="btn btn-secondary btn-sm"
                onclick="return confirm('Re-send invoice to <?= e($order['email']) ?>?');">
          📧 Re-send Invoice
        </button>
      </form>
      <a href="<?= SITE_URL ?>/admin/invoice/<?= urlencode($orderId) ?>"
         class="btn btn-outline btn-sm" style="margin-top:8px;display:inline-block;"
         target="_blank">
        📄 Download PDF
      </a>
    </div>
  </div>
</div>

<?php admin_footer(); ?>

<?php
/**
 * Order Confirmation Page
 */
$orderId   = $_GET['order_id'] ?? '';
$orderData = $orderId ? get_order($orderId) : null;

if (!$orderData) {
    http_response_code(404);
    echo "<h2 style='font-family:Arial;padding:40px;color:#0A9696'>Order not found.</h2>";
    exit;
}

$order     = $orderData['order'];
$items     = $orderData['items'];
$eventSlug = $order['event_slug'] ?? 'solarandstorage';
$event     = get_event($eventSlug);
$settings  = get_all_settings();
$companyWA = $settings['company_whatsapp'] ?? '+254731001723';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Order Confirmed — <?= e($order['order_id']) ?></title>
  <link rel="stylesheet" href="<?= SITE_URL ?>/static/css/style.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body style="background:#f5f5f5;">
<div class="confirmation-page">
  <div class="confirmation-icon">✅</div>
  <h1>Order Confirmed!</h1>
  <div class="order-ref"><?= e($order['order_id']) ?></div>

  <p style="color:#6E6E6E;margin-bottom:20px;">
    Thank you, <strong><?= e($order['contact_name']) ?></strong>!
    Your order has been received and a PDF invoice has been sent to
    <strong><?= e($order['email']) ?></strong>.
  </p>

  <div class="confirmation-details">
    <div style="margin-bottom:10px;">
      <strong><?= e($event ? $event['name'] : '') ?></strong><br>
      Stand: <strong><?= e($order['booth_number']) ?></strong> &nbsp;·&nbsp;
      Company: <strong><?= e($order['company_name']) ?></strong>
    </div>
    <table style="width:100%;font-size:13px;border-collapse:collapse;">
      <thead>
        <tr style="font-size:11px;color:#6E6E6E;text-transform:uppercase;">
          <td style="padding:5px 0;">Item</td>
          <td style="text-align:right;padding:5px 0;">Qty</td>
          <td style="text-align:right;padding:5px 0;">Total</td>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $item): ?>
        <tr style="border-bottom:1px solid #c0e0e0;">
          <td style="padding:6px 0;">
            <strong><?= e($item['product_name']) ?></strong>
            <?php if ($item['color_name']): ?>
              <span style="color:#6E6E6E;font-size:11px;"> — <?= e($item['color_name']) ?></span>
            <?php endif; ?>
          </td>
          <td style="text-align:right;padding:6px 0;"><?= (int)$item['quantity'] ?></td>
          <td style="text-align:right;padding:6px 0;">$<?= number_format($item['total_price'], 2) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr>
          <td colspan="2" style="text-align:right;padding:8px 0;color:#555;">Subtotal</td>
          <td style="text-align:right;padding:8px 0;">$<?= number_format($order['subtotal'], 2) ?></td>
        </tr>
        <tr>
          <td colspan="2" style="text-align:right;padding:4px 0;color:#555;">VAT (16%)</td>
          <td style="text-align:right;padding:4px 0;">$<?= number_format($order['vat'], 2) ?></td>
        </tr>
        <tr>
          <td colspan="2" style="text-align:right;padding:8px 0;font-weight:800;color:#0A9696;font-size:15px;">TOTAL (USD)</td>
          <td style="text-align:right;padding:8px 0;font-weight:800;color:#0A9696;font-size:15px;">
            $<?= number_format($order['total'], 2) ?>
          </td>
        </tr>
      </tfoot>
    </table>
  </div>

  <p style="font-size:13px;color:#555;margin-bottom:20px;">
    Your invoice has been emailed. Payment instructions are included in the invoice.<br>
    For questions, WhatsApp us:
    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $companyWA) ?>"
       style="color:#0A9696;font-weight:600;">
      <?= e($companyWA) ?>
    </a>
  </p>

  <a href="<?= SITE_URL ?>/<?= e($eventSlug) ?>" class="btn btn-outline">
    ← Return to Catalogue
  </a>

  <div style="margin-top:30px;font-size:11px;color:#aaa;">
    OmniSpace 3D Events Ltd · www.omnispace3d.com<br>
    Order reference: <?= e($order['order_id']) ?> · Placed: <?= e($order['created_at']) ?>
  </div>
</div>
</body>
</html>

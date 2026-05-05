<?php
/**
 * Admin — Packing List by Booth / Stand
 * Route: /admin/packing-booth/{event_slug}
 */
if (!defined('BASE_PATH')) { exit; }
require_admin_auth();
require_once BASE_PATH . '/includes/admin_layout.php';

$eventSlug = $_GET['event_slug'] ?? '';
$event     = $eventSlug ? get_event($eventSlug) : null;

if (!$event) {
    redirect('/admin');
}

$boothList = get_packing_list_by_booth($eventSlug);
$eventName = $event['name'];

admin_header('Packing by Booth — ' . $eventName, 'orders');
?>

<div style="margin-bottom:16px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
  <a href="<?= SITE_URL ?>/admin?event=<?= e($eventSlug) ?>" class="btn btn-outline btn-sm">← Orders</a>
  <a href="<?= SITE_URL ?>/admin/packing/<?= e($eventSlug) ?>" class="btn btn-secondary btn-sm">📋 View by Category</a>
  <a href="<?= SITE_URL ?>/admin/export/<?= e($eventSlug) ?>" class="btn btn-secondary btn-sm">⬇ Export CSV</a>
  <button onclick="window.print()" class="btn btn-outline btn-sm">🖨️ Print</button>
  <span style="margin-left:auto;font-size:13px;color:#6E6E6E;">
    <strong><?= count($boothList) ?></strong> booths
  </span>
</div>

<div class="card">
  <div class="card-header">
    🏢 Packing by Booth — <?= e($eventName) ?>
    <span style="font-size:12px;font-weight:400;margin-left:8px;color:#D6F0EF;">
      <?= e($event['venue'] ?? '') ?> &nbsp;|&nbsp; <?= e($event['dates'] ?? '') ?>
    </span>
  </div>
  <div class="card-body" style="padding:0;">

    <?php if (empty($boothList)): ?>
      <div style="padding:30px;text-align:center;color:#6E6E6E;">No orders yet for this event.</div>
    <?php else: ?>

    <?php foreach ($boothList as $booth): ?>
    <div class="packing-category">
      <div class="packing-cat-header" style="display:flex;justify-content:space-between;align-items:center;">
        <span>
          Stand <?= e($booth['booth_number']) ?>
          <span style="font-size:13px;font-weight:600;margin-left:8px;">
            — <?= e($booth['company_name']) ?>
          </span>
        </span>
        <span style="font-size:12px;font-weight:400;opacity:0.8;">
          Order: <a href="<?= SITE_URL ?>/admin/order/<?= urlencode($booth['order_id']) ?>"
                    style="color:#D6F0EF;"><?= e($booth['order_id']) ?></a>
          &nbsp;|&nbsp;
          Total: $<?= number_format((float)($booth['order_total'] ?? 0), 2) ?>
        </span>
      </div>
      <table class="admin-table packing-table">
        <thead>
          <tr>
            <th>Product</th>
            <th>Colour</th>
            <th style="text-align:center;">Qty</th>
            <th style="text-align:right;">Unit Price</th>
            <th style="text-align:right;">Line Total</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($booth['items'] as $item): ?>
          <tr>
            <td>
              <strong><?= e($item['product_name']) ?></strong>
              <?php if (!empty($item['product_code'])): ?>
                <span style="font-size:10px;color:#6E6E6E;"> (<?= e($item['product_code']) ?>)</span>
              <?php endif; ?>
            </td>
            <td><?= e($item['color_name'] ?: '—') ?></td>
            <td style="text-align:center;font-weight:700;"><?= (int)$item['quantity'] ?></td>
            <td style="text-align:right;">$<?= number_format((float)$item['unit_price'], 2) ?></td>
            <td style="text-align:right;font-weight:600;">$<?= number_format((float)$item['total_price'], 2) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr>
            <td colspan="4" style="text-align:right;color:#6E6E6E;">Booth Total (incl. VAT)</td>
            <td style="text-align:right;font-weight:800;color:#0A9696;">
              $<?= number_format((float)($booth['order_total'] ?? 0), 2) ?>
            </td>
          </tr>
        </tfoot>
      </table>
    </div>
    <?php endforeach; ?>

    <?php endif; ?>
  </div>
</div>

<div style="margin-top:10px;font-size:11px;color:#6E6E6E;text-align:center;" class="print-footer">
  Generated <?= date('d M Y H:i') ?> · OmniSpace 3D Events Ltd · OmniShop Admin
</div>

<?php admin_footer(); ?>

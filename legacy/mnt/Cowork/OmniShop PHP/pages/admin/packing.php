<?php
/**
 * Admin — Packing List by Category
 * Route: /admin/packing/{event_slug}
 */
if (!defined('BASE_PATH')) { exit; }
require_admin_auth();
require_once BASE_PATH . '/includes/admin_layout.php';

$eventSlug = $_GET['event_slug'] ?? '';
$event     = $eventSlug ? get_event($eventSlug) : null;

if (!$event) {
    redirect('/admin');
}

$packingList = get_packing_list($eventSlug);
$totalItems  = array_sum(array_map(fn($cat) => array_sum(array_column($cat['items'], 'total_qty')), $packingList));
$eventName   = $event['name'];

admin_header('Packing List — ' . $eventName, 'orders');
?>

<div style="margin-bottom:16px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
  <a href="<?= SITE_URL ?>/admin?event=<?= e($eventSlug) ?>" class="btn btn-outline btn-sm">← Orders</a>
  <a href="<?= SITE_URL ?>/admin/packing-booth/<?= e($eventSlug) ?>" class="btn btn-secondary btn-sm">🏢 View by Booth</a>
  <a href="<?= SITE_URL ?>/admin/export/<?= e($eventSlug) ?>" class="btn btn-secondary btn-sm">⬇ Export CSV</a>
  <button onclick="window.print()" class="btn btn-outline btn-sm">🖨️ Print</button>
  <span style="margin-left:auto;font-size:13px;color:#6E6E6E;">
    Total unique items: <strong><?= count($packingList) ?> categories</strong>,
    <strong><?= $totalItems ?></strong> units
  </span>
</div>

<div class="card">
  <div class="card-header">
    📋 Packing List — <?= e($eventName) ?>
    <span style="font-size:12px;font-weight:400;margin-left:8px;color:#D6F0EF;">
      <?= e($event['venue'] ?? '') ?> &nbsp;|&nbsp; <?= e($event['dates'] ?? '') ?>
    </span>
  </div>
  <div class="card-body" style="padding:0;">

    <?php if (empty($packingList)): ?>
      <div style="padding:30px;text-align:center;color:#6E6E6E;">No orders yet for this event.</div>
    <?php else: ?>

    <?php foreach ($packingList as $cat): ?>
    <div class="packing-category">
      <div class="packing-cat-header">
        <?= e($cat['category_name']) ?>
        <span style="font-size:12px;font-weight:400;margin-left:8px;opacity:0.8;">
          (<?= count($cat['items']) ?> products)
        </span>
      </div>
      <table class="admin-table packing-table">
        <thead>
          <tr>
            <th>Product</th>
            <th>Colour</th>
            <th style="text-align:center;">Total Qty</th>
            <th># Orders</th>
            <th>Stands</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($cat['items'] as $item): ?>
          <tr>
            <td>
              <strong><?= e($item['product_name']) ?></strong>
              <?php if (!empty($item['product_code'])): ?>
                <span style="font-size:10px;color:#6E6E6E;"> (<?= e($item['product_code']) ?>)</span>
              <?php endif; ?>
            </td>
            <td><?= e($item['color_name'] ?: '—') ?></td>
            <td style="text-align:center;font-weight:800;font-size:16px;color:#0A9696;">
              <?= (int)$item['total_qty'] ?>
            </td>
            <td><?= (int)$item['order_count'] ?></td>
            <td style="font-size:11px;color:#6E6E6E;">
              <?= e(implode(', ', array_filter(array_unique(explode(',', $item['booth_numbers'] ?? ''))))) ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
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

<?php
/**
 * Admin — Stock Limits
 * Route: /admin/stock
 */
if (!defined('BASE_PATH')) { exit; }
require_admin_auth();
if (!can_edit_products()) { redirect('/admin'); }
require_once BASE_PATH . '/includes/admin_layout.php';
require_once BASE_PATH . '/includes/events.php';

$events  = get_events();
$filterEvent = $_GET['event'] ?? (array_key_first($events) ?: '');

// ── Handle save ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ev   = trim($_POST['event_slug'] ?? '');
    $data = $_POST['limits'] ?? [];
    
    require_once BASE_PATH . '/includes/catalog.php';
    $allProducts = get_merged_products();
    $prodMap = [];
    foreach ($allProducts as $p) { $prodMap[$p['code']] = $p; }

    foreach ($data as $code => $limit) {
        $code  = strtoupper(trim($code));
        $limit = (int)$limit;
        if ($limit >= 0 && $code !== 'NEW_CODE' && $code !== '') {
            $name = $prodMap[$code]['name'] ?? $code;
            $cat  = $prodMap[$code]['category_id'] ?? 'other';
            set_stock_level($code, $name, $cat, $limit);
        }
    }
    set_flash('success', 'Stock limits saved.');
    redirect('/admin/stock?event=' . urlencode($ev));
}

// ── Load stock data ───────────────────────────────────────────────────────────
$stockLevels = get_stock_levels(); // Takes no arguments
$stockUsage  = $filterEvent ? get_stock_usage($filterEvent)  : [];

// Build a combined list: code => [limit, used, available]
$stockData = [];
foreach ($stockLevels as $row) {
    $code = $row['product_code'];
    $stockData[$code] = [
        'limit'     => (int)($row['stock_limit'] ?? 0),
        'used'      => (int)($stockUsage[$code] ?? 0),
        'name'      => $row['product_name'] ?? $code,
    ];
    $stockData[$code]['available'] = max(0, $stockData[$code]['limit'] - $stockData[$code]['used']);
}

// Also add usage for products not in limits
foreach ($stockUsage as $code => $used) {
    if (!isset($stockData[$code])) {
        $stockData[$code] = ['limit' => 0, 'used' => (int)$used, 'available' => 0, 'name' => $code];
    }
}

admin_header('Stock Limits', 'stock');
?>

<?php admin_flash(); ?>

<p style="color:#6E6E6E;font-size:13px;margin-bottom:16px;">
  Set maximum order quantities per product per event. Leave at 0 for unlimited.
  Current ordered quantities are shown for reference.
</p>

<!-- Event selector -->
<div style="margin-bottom:16px;">
  <?php foreach ($events as $ev): ?>
  <a href="<?= SITE_URL ?>/admin/stock?event=<?= e($ev['slug']) ?>"
     class="btn btn-<?= $filterEvent === $ev['slug'] ? 'primary' : 'outline' ?> btn-sm"
     style="margin-right:6px;">
    <?= e($ev['short_name']) ?>
  </a>
  <?php endforeach; ?>
</div>

<?php if ($filterEvent): ?>
<div class="card">
  <div class="card-header">
    Stock Limits — <?= e(get_event($filterEvent)['name'] ?? $filterEvent) ?>
  </div>
  <div class="card-body" style="padding:0;">
    <form method="POST" action="<?= SITE_URL ?>/admin/stock">
      <input type="hidden" name="event_slug" value="<?= e($filterEvent) ?>">

      <?php if (empty($stockData)): ?>
        <div style="padding:24px;text-align:center;color:#6E6E6E;">
          No stock limits set yet. Add a product code below to create a limit.
        </div>
      <?php else: ?>
      <table class="admin-table">
        <thead>
          <tr>
            <th>Product Code</th>
            <th>Product Name</th>
            <th style="text-align:center;">Max Quantity (0 = unlimited)</th>
            <th style="text-align:center;">Ordered So Far</th>
            <th style="text-align:center;">Remaining</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($stockData as $code => $row):
            $pct = $row['limit'] > 0 ? min(100, round($row['used'] / $row['limit'] * 100)) : 0;
            $pctColor = $pct >= 90 ? '#c00' : ($pct >= 70 ? '#e67e22' : '#0A9696');
          ?>
          <tr>
            <td style="font-family:monospace;font-size:12px;"><?= e($code) ?></td>
            <td><?= e($row['name']) ?></td>
            <td style="text-align:center;">
              <input type="number" name="limits[<?= e($code) ?>]" class="form-control"
                     value="<?= (int)$row['limit'] ?>" min="0" max="9999"
                     style="width:80px;text-align:center;margin:0 auto;">
            </td>
            <td style="text-align:center;font-weight:700;color:<?= $pctColor ?>;">
              <?= (int)$row['used'] ?>
              <?php if ($row['limit'] > 0): ?>
                <span style="font-size:10px;font-weight:400;color:#999;">(<?= $pct ?>%)</span>
              <?php endif; ?>
            </td>
            <td style="text-align:center;color:<?= $row['limit'] > 0 && $row['available'] === 0 ? '#c00' : '#0A9696' ?>;">
              <?= $row['limit'] > 0 ? $row['available'] : '∞' ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>

      <!-- Add new limit -->
      <div style="padding:14px;border-top:1px solid #D6F0EF;background:#f9fffe;">
        <strong style="font-size:13px;">Add / Update a Product Limit:</strong>
        <div style="display:flex;gap:10px;margin-top:8px;align-items:center;">
          <input type="text" name="limits[NEW_CODE]" id="new-code-input"
                 class="form-control" style="font-family:monospace;max-width:140px;text-transform:uppercase;"
                 placeholder="PRODUCT CODE">
          <input type="number" name="limits_new" id="new-limit-input"
                 class="form-control" style="max-width:100px;" min="1" placeholder="Max qty">
          <small style="color:#6E6E6E;">Type the product code exactly as it appears in the catalog.</small>
        </div>
      </div>

      <div style="padding:14px;">
        <button type="submit" class="btn btn-primary">Save Stock Limits</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<script>
// Fix "new code" input to write into limits array properly
document.addEventListener('DOMContentLoaded', function() {
    var codeInput  = document.getElementById('new-code-input');
    var limitInput = document.getElementById('new-limit-input');
    if (!codeInput || !limitInput) return;
    codeInput.addEventListener('input', function() {
        var code = this.value.trim().toUpperCase();
        limitInput.name = 'limits[' + code + ']';
    });
});
</script>

<?php admin_footer(); ?>

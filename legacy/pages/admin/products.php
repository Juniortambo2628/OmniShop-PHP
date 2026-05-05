<?php
/**
 * Admin — Products (Unified Catalog View)
 * Route: /admin/products
 *
 * Shows ALL products: built-in catalog + any DB overrides/additions.
 * Susan can edit any product or add new ones directly from this page.
 */
if (!defined('BASE_PATH')) { exit; }
require_admin_auth();
if (!can_edit_products()) { redirect('/admin'); }
require_once BASE_PATH . '/includes/admin_layout.php';
require_once BASE_PATH . '/includes/catalog.php';

// ── Category labels (matching catalog.php category_id values) ──────────────
$categoryLabels = [
    'sofas'       => 'Sofas & Armchairs',
    'chairs'      => 'Chairs',
    'stools'      => 'Stools',
    'tables'      => 'Tables',
    'counters'    => 'Counters & Reception',
    'displays'    => 'Display & Shelving',
    'lighting'    => 'Lighting',
    'accessories' => 'Accessories',
    'flooring'    => 'Flooring',
    'structures'  => 'Structures & Backdrops',
    'av'          => 'AV & Tech',
    'other'       => 'Other',
];

// ── Load data ─────────────────────────────────────────────────────────────
$catalogProducts = get_catalog_products();   // built-in hardcoded list
try {
    $dbProducts = get_admin_products();       // DB overrides + additions
} catch (Exception $e) {
    $dbProducts = [];
}

// Index DB products by prod_id (for override lookup) and by their own id
$dbByProdId = [];   // prod_id (= catalog code) → DB row
$dbById     = [];   // DB primary id → DB row  (for edit/delete links)
$dbAdditions = [];  // DB rows that are NOT overrides (pure new products)

foreach ($dbProducts as $dp) {
    $dbById[(int)$dp['id']] = $dp;
    if (!empty($dp['is_override'])) {
        $dbByProdId[$dp['prod_id']] = $dp;
    } else {
        $dbAdditions[] = $dp;
    }
}

// ── Merge catalog + overrides into a display list ─────────────────────────
// Each entry: catalog data merged with DB data if override exists
$filterCat = $_GET['cat'] ?? '';
$filterQ   = trim($_GET['q'] ?? '');

$displayProducts = [];
foreach ($catalogProducts as $cp) {
    $override = $dbByProdId[$cp['id']] ?? null;
    $entry = [
        'source'      => $override ? 'modified' : 'builtin',
        'db_id'       => $override ? (int)$override['id'] : null,
        'code'        => $override['code']        ?? $cp['code'],
        'name'        => $override['name']        ?? $cp['name'],
        'category_id' => $override['category_id'] ?? $cp['category_id'],
        'price'       => $override['price']        ?? $cp['price'],
        'price_display'=> $override['price_display'] ?? $cp['price_display'],
        'is_poa'      => $override ? (bool)$override['is_poa']    : $cp['is_poa'],
        'is_active'   => $override ? (bool)$override['is_active'] : true,
        'dimensions'  => $override['dimensions']  ?? $cp['dimensions'],
        'colors'      => $override ? (json_decode($override['colors_json'] ?? '[]', true) ?: []) : ($cp['colors'] ?? []),
        'catalog_id'  => $cp['id'],  // original catalog id for creating override
    ];
    $displayProducts[] = $entry;
}
// Append DB-only additions
foreach ($dbAdditions as $dp) {
    $displayProducts[] = [
        'source'       => 'custom',
        'db_id'        => (int)$dp['id'],
        'code'         => $dp['code'],
        'name'         => $dp['name'],
        'category_id'  => $dp['category_id'],
        'price'        => $dp['price'],
        'price_display'=> $dp['price_display'],
        'is_poa'       => (bool)$dp['is_poa'],
        'is_active'    => (bool)$dp['is_active'],
        'dimensions'   => $dp['dimensions'],
        'colors'       => json_decode($dp['colors_json'] ?? '[]', true) ?: [],
        'catalog_id'   => null,
    ];
}

// Apply category filter
if ($filterCat) {
    $displayProducts = array_values(array_filter($displayProducts,
        fn($p) => ($p['category_id'] === $filterCat)
    ));
}
// Apply search filter
if ($filterQ) {
    $q = strtolower($filterQ);
    $displayProducts = array_values(array_filter($displayProducts,
        fn($p) => str_contains(strtolower($p['code']), $q)
               || str_contains(strtolower($p['name']), $q)
    ));
}

// ── Stats ─────────────────────────────────────────────────────────────────
$totalCatalog  = count($catalogProducts);
$totalModified = count($dbByProdId);
$totalCustom   = count($dbAdditions);

// ── Unique categories for tabs ─────────────────────────────────────────────
$allCats = [];
foreach ($catalogProducts as $cp) {
    $cid = $cp['category_id'];
    if (!isset($allCats[$cid])) $allCats[$cid] = 0;
    $allCats[$cid]++;
}
foreach ($dbAdditions as $dp) {
    $cid = $dp['category_id'] ?: 'other';
    if (!isset($allCats[$cid])) $allCats[$cid] = 0;
    $allCats[$cid]++;
}

admin_header('Products', 'products');
?>

<?php admin_flash(); ?>

<!-- ── Stats bar ────────────────────────────────────────────────────────────── -->
<div style="display:flex;gap:12px;margin-bottom:16px;flex-wrap:wrap;">
  <div style="background:#0A9696;color:#fff;border-radius:8px;padding:12px 20px;min-width:120px;">
    <div style="font-size:24px;font-weight:700;line-height:1;"><?= $totalCatalog ?></div>
    <div style="font-size:12px;opacity:.85;margin-top:2px;">Standard products</div>
  </div>
  <div style="background:<?= $totalModified ? '#19AFAC' : '#D6F0EF' ?>;color:<?= $totalModified ? '#fff' : '#0A9696' ?>;border-radius:8px;padding:12px 20px;min-width:120px;">
    <div style="font-size:24px;font-weight:700;line-height:1;"><?= $totalModified ?></div>
    <div style="font-size:12px;opacity:.85;margin-top:2px;">Modified</div>
  </div>
  <div style="background:<?= $totalCustom ? '#19AFAC' : '#D6F0EF' ?>;color:<?= $totalCustom ? '#fff' : '#0A9696' ?>;border-radius:8px;padding:12px 20px;min-width:120px;">
    <div style="font-size:24px;font-weight:700;line-height:1;"><?= $totalCustom ?></div>
    <div style="font-size:12px;opacity:.85;margin-top:2px;">Custom additions</div>
  </div>
  <div style="margin-left:auto;display:flex;align-items:center;">
    <a href="<?= SITE_URL ?>/admin/products/add" class="btn btn-primary">
      + Add New Product
    </a>
  </div>
</div>

<!-- ── Search + category tabs ───────────────────────────────────────────────── -->
<div style="background:#fff;border:1px solid #E0E0E0;border-radius:8px;padding:14px 16px;margin-bottom:16px;">
  <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
    <form method="GET" style="display:flex;gap:8px;align-items:center;flex:1;min-width:220px;">
      <?php if ($filterCat): ?>
        <input type="hidden" name="cat" value="<?= e($filterCat) ?>">
      <?php endif; ?>
      <input type="text" name="q" class="form-control" style="max-width:280px;"
             placeholder="Search by code or name…" value="<?= e($filterQ) ?>">
      <button type="submit" class="btn btn-outline btn-sm">Search</button>
      <?php if ($filterQ || $filterCat): ?>
        <a href="<?= SITE_URL ?>/admin/products" class="btn btn-outline btn-sm">Clear filters</a>
      <?php endif; ?>
    </form>
  </div>
  <!-- Category tabs -->
  <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:12px;">
    <a href="<?= SITE_URL ?>/admin/products<?= $filterQ ? '?q='.urlencode($filterQ) : '' ?>"
       style="padding:5px 12px;border-radius:20px;font-size:12px;text-decoration:none;
              background:<?= !$filterCat ? '#0A9696' : '#F5F5F5' ?>;
              color:<?= !$filterCat ? '#fff' : '#333' ?>;border:1px solid <?= !$filterCat ? '#0A9696' : '#DDD' ?>;">
      All (<?= $totalCatalog + $totalCustom ?>)
    </a>
    <?php foreach ($allCats as $cid => $cnt):
      $label = $categoryLabels[$cid] ?? ucfirst($cid);
      $active = ($filterCat === $cid);
    ?>
    <a href="<?= SITE_URL ?>/admin/products?cat=<?= urlencode($cid) ?><?= $filterQ ? '&q='.urlencode($filterQ) : '' ?>"
       style="padding:5px 12px;border-radius:20px;font-size:12px;text-decoration:none;
              background:<?= $active ? '#0A9696' : '#F5F5F5' ?>;
              color:<?= $active ? '#fff' : '#333' ?>;border:1px solid <?= $active ? '#0A9696' : '#DDD' ?>;">
      <?= e($label) ?> (<?= $cnt ?>)
    </a>
    <?php endforeach; ?>
  </div>
</div>

<!-- ── Products table ────────────────────────────────────────────────────────── -->
<div class="card">
  <div class="card-header">
    <?php if ($filterCat): ?>
      <?= e($categoryLabels[$filterCat] ?? ucfirst($filterCat)) ?>
    <?php else: ?>
      All Products
    <?php endif; ?>
    — <?= count($displayProducts) ?> shown
    <span style="font-size:11px;font-weight:400;margin-left:12px;color:#D6F0EF;">
      🟢 Standard &nbsp;|&nbsp; 🟡 Modified &nbsp;|&nbsp; 🔵 Custom addition
    </span>
  </div>
  <div class="card-body" style="padding:0;">
    <?php if (empty($displayProducts)): ?>
      <div style="padding:30px;text-align:center;color:#6E6E6E;">
        No products found. <?php if ($filterQ || $filterCat): ?>
          <a href="<?= SITE_URL ?>/admin/products">Clear filters</a>
        <?php endif; ?>
      </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
      <table class="admin-table">
        <thead>
          <tr>
            <th style="width:24px;"></th>
            <th>Code</th>
            <th>Name</th>
            <th>Category</th>
            <th style="text-align:right;">Price</th>
            <th>Colours</th>
            <th style="text-align:center;">Active</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($displayProducts as $p):
            $srcColour = match($p['source']) {
                'modified' => '#FFA500',
                'custom'   => '#19AFAC',
                default    => '#28A745',
            };
            $srcTip = match($p['source']) {
                'modified' => 'Standard product — you have modified it',
                'custom'   => 'Your custom addition (not in standard catalog)',
                default    => 'Standard catalog product',
            };
            $catLabel = $categoryLabels[$p['category_id']] ?? ucfirst($p['category_id'] ?? '—');
          ?>
          <tr style="<?= !$p['is_active'] ? 'opacity:0.5;' : '' ?>">
            <td style="text-align:center;" title="<?= e($srcTip) ?>">
              <span style="display:inline-block;width:10px;height:10px;border-radius:50%;
                           background:<?= $srcColour ?>;"></span>
            </td>
            <td style="font-family:monospace;font-size:12px;font-weight:600;">
              <?= e($p['code']) ?>
            </td>
            <td>
              <strong><?= e($p['name']) ?></strong>
              <?php if ($p['source'] === 'modified'): ?>
                <span style="font-size:10px;color:#FFA500;margin-left:6px;">● modified</span>
              <?php elseif ($p['source'] === 'custom'): ?>
                <span style="font-size:10px;color:#19AFAC;margin-left:6px;">● custom</span>
              <?php endif; ?>
              <?php if ($p['dimensions']): ?>
                <div style="font-size:11px;color:#6E6E6E;margin-top:2px;"><?= e($p['dimensions']) ?></div>
              <?php endif; ?>
            </td>
            <td style="font-size:12px;color:#6E6E6E;"><?= e($catLabel) ?></td>
            <td style="text-align:right;white-space:nowrap;">
              <?php if ($p['is_poa']): ?>
                <em style="color:#6E6E6E;font-size:12px;">POA</em>
              <?php else: ?>
                <strong>$<?= number_format((float)$p['price'], 2) ?></strong>
              <?php endif; ?>
            </td>
            <td style="font-size:11px;max-width:160px;">
              <?php
              $colors = $p['colors'];
              if (empty($colors)): ?>
                <span style="color:#bbb;">—</span>
              <?php else:
                $names = array_column($colors, 'name');
                echo e(implode(', ', $names));
              endif; ?>
            </td>
            <td style="text-align:center;">
              <?php if ($p['db_id']): ?>
                <!-- DB record exists — direct toggle -->
                <form method="POST" action="<?= SITE_URL ?>/api/toggle-product" style="display:inline;">
                  <input type="hidden" name="product_id" value="<?= (int)$p['db_id'] ?>">
                  <button type="submit" class="btn btn-sm <?= $p['is_active'] ? 'btn-secondary' : 'btn-outline' ?>"
                          style="padding:2px 8px;font-size:11px;"
                          title="<?= $p['is_active'] ? 'Click to hide from catalog' : 'Click to show in catalog' ?>">
                    <?= $p['is_active'] ? '✓ Visible' : '✗ Hidden' ?>
                  </button>
                </form>
              <?php else: ?>
                <!-- Built-in product, no override — hide button creates a hidden override -->
                <form method="POST" action="<?= SITE_URL ?>/api/hide-catalog-product" style="display:inline;">
                  <input type="hidden" name="catalog_id"   value="<?= e($p['catalog_id']) ?>">
                  <input type="hidden" name="catalog_code" value="<?= e($p['code']) ?>">
                  <input type="hidden" name="catalog_name" value="<?= e($p['name']) ?>">
                  <input type="hidden" name="catalog_cat"  value="<?= e($p['category_id']) ?>">
                  <button type="submit" class="btn btn-sm btn-secondary"
                          style="padding:2px 8px;font-size:11px;"
                          title="Click to hide from catalog"
                          onclick="return confirm('Hide <?= e(addslashes($p['name'])) ?> from the catalog?\nYou can show it again at any time.')">
                    ✓ Visible
                  </button>
                </form>
              <?php endif; ?>
            </td>
            <td style="white-space:nowrap;">
              <?php if ($p['source'] === 'builtin'): ?>
                <!-- Standard catalog product, no customisation yet -->
                <a href="<?= SITE_URL ?>/admin/products/add?from_catalog=<?= urlencode($p['catalog_id']) ?>"
                   class="btn btn-outline btn-sm" title="Customise price, name, colours…">Edit</a>
                <!-- No delete for standard products — use the Visible toggle to hide instead -->

              <?php elseif ($p['source'] === 'modified'): ?>
                <!-- Catalog product with customisations -->
                <a href="<?= SITE_URL ?>/admin/products/<?= (int)$p['db_id'] ?>/edit"
                   class="btn btn-outline btn-sm">Edit</a>
                <form method="POST" action="<?= SITE_URL ?>/api/reset-product-override" style="display:inline;"
                      onsubmit="return confirm('Restore <?= e(addslashes($p['name'])) ?> to its original catalog values?\nYour changes will be lost.');">
                  <input type="hidden" name="product_id" value="<?= (int)$p['db_id'] ?>">
                  <button type="submit" class="btn btn-outline btn-sm"
                          title="Undo all your changes and restore catalog defaults"
                          style="color:#FFA500;border-color:#FFA500;">↩ Undo edits</button>
                </form>

              <?php else: ?>
                <!-- Custom product added by you — can fully delete -->
                <a href="<?= SITE_URL ?>/admin/products/<?= (int)$p['db_id'] ?>/edit"
                   class="btn btn-outline btn-sm">Edit</a>
                <form method="POST" action="<?= SITE_URL ?>/api/delete-product" style="display:inline;"
                      onsubmit="return confirm('Permanently delete <?= e(addslashes($p['name'])) ?>?\nThis cannot be undone.');">
                  <input type="hidden" name="product_id" value="<?= (int)$p['db_id'] ?>">
                  <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                </form>

              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- ── Legend ──────────────────────────────────────────────────────────────── -->
<div style="margin-top:12px;padding:14px 16px;background:#F9FAFB;border:1px solid #E0E0E0;border-radius:8px;font-size:12px;color:#555;line-height:1.7;">
  <strong style="color:#333;font-size:13px;">Quick guide</strong><br>
  <span style="color:#28A745;">●</span> <strong>Standard</strong> — built-in catalog product.
    <em>Edit</em> to change price/name/colours. Click <em>Visible</em> to hide it from the catalog.<br>
  <span style="color:#FFA500;">●</span> <strong>Modified</strong> — you've customised a standard product.
    <em>Edit</em> to change it further. <em>↩ Undo edits</em> restores original values. Click <em>Visible/Hidden</em> to show or hide.<br>
  <span style="color:#19AFAC;">●</span> <strong>Custom</strong> — a product you added (not in the standard catalog).
    <em>Edit</em> or <em>Delete</em> it. Click <em>Visible/Hidden</em> to show or hide.<br>
  <strong>+ Add New Product</strong> at the top adds a completely new item to your catalog.
</div>

<?php admin_footer(); ?>

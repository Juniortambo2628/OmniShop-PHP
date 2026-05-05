<?php
/**
 * Admin — Add / Edit Product
 * Routes:
 *   /admin/products/add                    → new custom product
 *   /admin/products/add?from_catalog=ID    → edit a built-in catalog product (creates override)
 *   /admin/products/{id}/edit              → edit existing DB product (override or custom)
 */
if (!defined('BASE_PATH')) { exit; }
require_admin_auth();
if (!can_edit_products()) { redirect('/admin'); }
require_once BASE_PATH . '/includes/admin_layout.php';
require_once BASE_PATH . '/includes/catalog.php';

$productId    = (int)($_GET['product_id'] ?? 0);
$fromCatalog  = trim($_GET['from_catalog'] ?? '');  // catalog product id to pre-fill from
$isEdit       = $productId > 0;
$product      = $isEdit ? get_admin_product($productId) : null;

if ($isEdit && !$product) {
    set_flash('danger', 'Product not found.');
    redirect('/admin/products');
}

// Pre-fill from catalog product if requested
$catalogProduct = null;
if (!$isEdit && $fromCatalog) {
    $allCatalog = get_catalog_products();
    foreach ($allCatalog as $cp) {
        if ($cp['id'] === $fromCatalog) {
            $catalogProduct = $cp;
            break;
        }
    }
}

$categories = get_categories();
$errors     = [];

// ── Category labels ────────────────────────────────────────────────────────
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

// ── Handle form submission ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code          = strtoupper(trim($_POST['code']          ?? ''));
    $name          = trim($_POST['name']          ?? '');
    $categoryId    = trim($_POST['category_id']   ?? '');
    $price         = trim($_POST['price']         ?? '0');
    $unit          = trim($_POST['unit']          ?? 'per event');
    $dimensions    = trim($_POST['dimensions']    ?? '');
    $description   = trim($_POST['description']   ?? '');
    $isPoa         = isset($_POST['is_poa'])    ? 1 : 0;
    $isActive      = isset($_POST['is_active']) ? 1 : 0;
    $isOverride    = (int)($_POST['is_override']  ?? 0);
    $overrideProdId= trim($_POST['override_prod_id'] ?? '');

    // Build colors array from posted rows
    $colorNames = $_POST['color_names'] ?? [];
    $colorIds   = $_POST['color_ids']   ?? [];
    $colors = [];
    foreach ($colorNames as $i => $cname) {
        $cname = trim($cname);
        if ($cname === '') continue;
        // Generate an id from the name if not supplied
        $cid = strtolower(trim($colorIds[$i] ?? ''));
        if (!$cid) $cid = preg_replace('/[^a-z0-9]/', '', strtolower($cname));
        $colors[] = ['id' => $cid ?: ('col'.($i+1)), 'name' => $cname];
    }

    if (!$code)     $errors[] = 'Product code is required.';
    if (!$name)     $errors[] = 'Product name is required.';
    if (!$isPoa && (!is_numeric($price) || (float)$price < 0)) {
        $errors[] = 'Please enter a valid price (or tick Price on Request).';
    }

    if (empty($errors)) {
        $data = [
            'code'          => $code,
            'name'          => $name,
            'category_id'   => $categoryId,
            'price'         => $isPoa ? 0 : (float)$price,
            'price_display' => $isPoa ? 'POA' : ('$' . number_format((float)$price, 2)),
            'unit'          => $unit,
            'dimensions'    => $dimensions,
            'description'   => $description,
            'is_poa'        => $isPoa,
            'is_active'     => $isActive,
            'is_override'   => $isOverride ? 1 : 0,
            'colors'        => $colors,
        ];

        if ($isEdit) {
            update_admin_product($productId, $data);
            set_flash('success', "Product \"$name\" updated.");
        } else {
            if ($isOverride && $overrideProdId) {
                $data['prod_id'] = $overrideProdId;
            } else {
                // Generate a prod_id for new custom products
                $data['prod_id'] = 'CUSTOM_' . strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $code));
            }
            create_admin_product($data, get_admin_display_name());
            set_flash('success', "Product \"$name\" " . ($isOverride ? "customised." : "added."));
        }
        redirect('/admin/products');
    }
}

// ── Determine initial field values ─────────────────────────────────────────
// Priority: POST data > existing DB product > catalog pre-fill > blank
function pv(string $key, $dbVal, $catVal = '') {
    if (isset($_POST[$key])) return $_POST[$key];
    if ($dbVal !== null && $dbVal !== '') return $dbVal;
    return $catVal;
}
$fCode       = pv('code',        $product['code'] ?? null,        $catalogProduct['code'] ?? '');
$fName       = pv('name',        $product['name'] ?? null,        $catalogProduct['name'] ?? '');
$fCat        = pv('category_id', $product['category_id'] ?? null, $catalogProduct['category_id'] ?? '');
$fPrice      = pv('price',       $product['price'] ?? null,       $catalogProduct['price'] ?? '');
$fUnit       = pv('unit',        $product['unit'] ?? null,        $catalogProduct['unit'] ?? 'per event');
$fDimensions = pv('dimensions',  $product['dimensions'] ?? null,  $catalogProduct['dimensions'] ?? '');
$fDescription= pv('description', $product['description'] ?? null, $catalogProduct['description'] ?? '');
$fIsPoa      = isset($_POST['is_poa'])
    ? (bool)$_POST['is_poa']
    : ($isEdit ? (bool)$product['is_poa'] : false);
$fIsActive   = isset($_POST['is_active'])
    ? (bool)$_POST['is_active']
    : ($isEdit ? (bool)$product['is_active'] : true);

// Colors: POST > DB > catalog
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fColors = $colors ?? [];
} elseif ($isEdit) {
    $fColors = $product['colors'] ?? [];
} elseif ($catalogProduct) {
    $fColors = $catalogProduct['colors'] ?? [];
} else {
    $fColors = [];
}

$isOverrideMode = !$isEdit && $catalogProduct !== null;
$title = $isEdit
    ? 'Edit Product: ' . ($product['code'] ?? '')
    : ($catalogProduct ? 'Customise: ' . ($catalogProduct['name'] ?? '') : 'Add New Product');

admin_header($title, 'products');
?>

<?php admin_flash(); ?>
<?php if ($errors): ?>
  <div class="alert alert-danger" style="margin-bottom:16px;">
    <?php foreach ($errors as $err): ?>
      <div><?= e($err) ?></div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<a href="<?= SITE_URL ?>/admin/products" class="btn btn-outline btn-sm" style="margin-bottom:16px;">
  ← Back to Products
</a>

<?php if ($isOverrideMode): ?>
  <div style="background:#FFF8E1;border:1px solid #FFD54F;border-radius:8px;padding:12px 16px;margin-bottom:16px;font-size:13px;color:#555;">
    <strong>Customising a standard product.</strong>
    Your changes will override the catalog version for all events.
    The original can be restored at any time from the Products page.
  </div>
<?php endif; ?>

<div class="card">
  <div class="card-header"><?= e($title) ?></div>
  <div class="card-body">
    <form method="POST"
          action="<?= SITE_URL ?>/admin/products/<?= $isEdit ? $productId . '/edit' : 'add' ?>">

      <!-- Hidden fields for override tracking -->
      <?php if ($isOverrideMode): ?>
        <input type="hidden" name="is_override"      value="1">
        <input type="hidden" name="override_prod_id" value="<?= e($catalogProduct['id']) ?>">
      <?php else: ?>
        <input type="hidden" name="is_override"      value="<?= $isEdit ? (int)($product['is_override'] ?? 0) : 0 ?>">
      <?php endif; ?>

      <!-- ── Row 1: Code + Name ──────────────────────────────────────────── -->
      <div style="display:grid;grid-template-columns:180px 1fr;gap:16px;margin-bottom:16px;">
        <div class="form-group" style="margin:0;">
          <label>Product Code <span style="color:#c00;">*</span></label>
          <input type="text" name="code" class="form-control" required
                 value="<?= e($fCode) ?>"
                 placeholder="e.g. SOF01"
                 style="font-family:monospace;text-transform:uppercase;letter-spacing:1px;"
                 <?= $isOverrideMode ? 'readonly style="font-family:monospace;text-transform:uppercase;letter-spacing:1px;background:#F5F5F5;"' : '' ?>>
          <?php if (!$isOverrideMode && !$isEdit): ?>
            <small style="color:#6E6E6E;">UPPERCASE. If it matches an existing code, it will override that product.</small>
          <?php endif; ?>
        </div>
        <div class="form-group" style="margin:0;">
          <label>Product Name <span style="color:#c00;">*</span></label>
          <input type="text" name="name" class="form-control" required
                 value="<?= e($fName) ?>"
                 placeholder="e.g. Atlanta 2-Seater Sofa">
        </div>
      </div>

      <!-- ── Row 2: Category + Price + Unit ────────────────────────────── -->
      <div style="display:grid;grid-template-columns:1fr 140px 140px;gap:16px;margin-bottom:16px;">
        <div class="form-group" style="margin:0;">
          <label>Category</label>
          <select name="category_id" class="form-control">
            <option value="">— Select category —</option>
            <?php foreach ($categoryLabels as $cid => $clabel): ?>
              <option value="<?= e($cid) ?>"
                <?= ($fCat === $cid) ? 'selected' : '' ?>>
                <?= e($clabel) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group" style="margin:0;">
          <label>Price (USD)</label>
          <input type="number" name="price" class="form-control" step="0.01" min="0"
                 value="<?= e($fPrice) ?>"
                 placeholder="0.00"
                 id="price-field"
                 <?= $fIsPoa ? 'disabled' : '' ?>>
          <small style="color:#6E6E6E;">Leave blank if POA.</small>
        </div>
        <div class="form-group" style="margin:0;">
          <label>Unit</label>
          <select name="unit" class="form-control">
            <?php foreach (['per event', 'per day', 'per unit', 'per set', 'per sqm', 'per piece'] as $u): ?>
              <option value="<?= $u ?>" <?= ($fUnit === $u) ? 'selected' : '' ?>><?= $u ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <!-- ── Row 3: Dimensions + Description ───────────────────────────── -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
        <div class="form-group" style="margin:0;">
          <label>Dimensions <span style="font-weight:400;color:#6E6E6E;">(optional)</span></label>
          <input type="text" name="dimensions" class="form-control"
                 value="<?= e($fDimensions) ?>"
                 placeholder="e.g. L143×W87×H90cm">
        </div>
        <div class="form-group" style="margin:0;">
          <label>Description <span style="font-weight:400;color:#6E6E6E;">(optional)</span></label>
          <input type="text" name="description" class="form-control"
                 value="<?= e($fDescription) ?>"
                 placeholder="Short description shown in catalog">
        </div>
      </div>

      <!-- ── Row 4: Options ─────────────────────────────────────────────── -->
      <div style="display:flex;gap:24px;margin-bottom:16px;">
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
          <input type="checkbox" name="is_poa" id="poa-check"
                 <?= $fIsPoa ? 'checked' : '' ?>
                 onchange="document.getElementById('price-field').disabled=this.checked">
          <span><strong>Price on Request (POA)</strong> — hides the price, shows "Contact us"</span>
        </label>
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
          <input type="checkbox" name="is_active"
                 <?= $fIsActive ? 'checked' : '' ?>>
          <span><strong>Active</strong> — visible in the catalog</span>
        </label>
      </div>

      <!-- ── Colour options ─────────────────────────────────────────────── -->
      <div class="form-group">
        <label>
          Available Colours
          <span style="font-weight:400;color:#6E6E6E;font-size:12px;">
            — customers will choose from this list when ordering
          </span>
        </label>
        <div id="colours-list" style="display:flex;flex-direction:column;gap:8px;max-width:400px;">
          <?php
          if (empty($fColors)):
          ?>
          <!-- Empty row placeholder -->
          <div class="colour-row" style="display:flex;align-items:center;gap:8px;">
            <input type="text" name="color_names[]" class="form-control"
                   style="max-width:200px;" placeholder="e.g. White">
            <input type="hidden" name="color_ids[]" value="">
            <button type="button" onclick="removeColourRow(this)"
                    style="background:none;border:none;color:#c00;font-size:18px;cursor:pointer;line-height:1;"
                    title="Remove">✕</button>
          </div>
          <?php else:
            foreach ($fColors as $col):
          ?>
          <div class="colour-row" style="display:flex;align-items:center;gap:8px;">
            <input type="text" name="color_names[]" class="form-control"
                   style="max-width:200px;"
                   placeholder="e.g. White"
                   value="<?= e($col['name'] ?? '') ?>">
            <input type="hidden" name="color_ids[]" value="<?= e($col['id'] ?? '') ?>">
            <button type="button" onclick="removeColourRow(this)"
                    style="background:none;border:none;color:#c00;font-size:18px;cursor:pointer;line-height:1;"
                    title="Remove">✕</button>
          </div>
          <?php endforeach; endif; ?>
        </div>
        <button type="button" onclick="addColourRow()"
                class="btn btn-outline btn-sm" style="margin-top:8px;">
          + Add Colour
        </button>
        <div style="margin-top:6px;font-size:11px;color:#6E6E6E;">
          Leave empty if this product doesn't come in colour options (e.g. chrome finish only).
          Image filenames: <code>CODE.jpg</code> for default, <code>CODE-white.jpg</code> for specific colour.
        </div>
      </div>

      <!-- ── Submit ─────────────────────────────────────────────────────── -->
      <div style="display:flex;gap:10px;margin-top:8px;">
        <button type="submit" class="btn btn-primary">
          <?php if ($isEdit): ?>Save Changes
          <?php elseif ($isOverrideMode): ?>Save Customisation
          <?php else: ?>Add Product<?php endif; ?>
        </button>
        <a href="<?= SITE_URL ?>/admin/products" class="btn btn-outline">Cancel</a>
      </div>

    </form>
  </div>
</div>

<script>
function addColourRow() {
    const list = document.getElementById('colours-list');
    const div  = document.createElement('div');
    div.className = 'colour-row';
    div.style.cssText = 'display:flex;align-items:center;gap:8px;';
    div.innerHTML =
        '<input type="text" name="color_names[]" class="form-control" ' +
        '       style="max-width:200px;" placeholder="e.g. Black">' +
        '<input type="hidden" name="color_ids[]" value="">' +
        '<button type="button" onclick="removeColourRow(this)" ' +
        '        style="background:none;border:none;color:#c00;font-size:18px;cursor:pointer;line-height:1;" ' +
        '        title="Remove">✕</button>';
    list.appendChild(div);
    div.querySelector('input[type=text]').focus();
}

function removeColourRow(btn) {
    const rows = document.querySelectorAll('#colours-list .colour-row');
    if (rows.length <= 1) {
        // Clear instead of removing the last row
        btn.closest('.colour-row').querySelector('input[type=text]').value = '';
        return;
    }
    btn.closest('.colour-row').remove();
}
</script>

<?php admin_footer(); ?>

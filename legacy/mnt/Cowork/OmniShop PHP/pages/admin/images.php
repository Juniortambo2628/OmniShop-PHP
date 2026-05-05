<?php
/**
 * Admin — Product Image Upload (Rewritten)
 * Route: /admin/images
 *
 * Features:
 *  - Product dropdown (select product → auto-names file correctly)
 *  - Colour dropdown (for colour-variant images)
 *  - Server-side image compression (max 1200px wide, 85% JPEG quality)
 *  - Batch upload (multiple pre-named files at once)
 *  - Gallery with search + filter (All / Missing / Has image)
 */
if (!defined('BASE_PATH')) { exit; }
require_admin_auth();
if (!can_edit_products()) { redirect('/admin'); }
require_once BASE_PATH . '/includes/admin_layout.php';
require_once BASE_PATH . '/includes/catalog.php';

$imagesDir = BASE_PATH . '/static/images/products/';
$imagesUrl = SITE_URL . '/static/images/products/';

if (!is_dir($imagesDir)) { mkdir($imagesDir, 0755, true); }

// ── Helper: compress and save an uploaded image ────────────────────────────
function compress_and_save(string $tmpPath, string $destPath, int $maxW = 1200, int $quality = 85): bool
{
    $info = @getimagesize($tmpPath);
    if (!$info) {
        // Not a recognised image — just move as-is
        return move_uploaded_file($tmpPath, $destPath) || rename($tmpPath, $destPath);
    }

    [$w, $h, $type] = $info;

    // Only resize if wider than $maxW
    if ($w > $maxW) {
        $newW = $maxW;
        $newH = (int)round($h * $maxW / $w);
    } else {
        $newW = $w;
        $newH = $h;
    }

    // Create source image
    $src = match($type) {
        IMAGETYPE_JPEG => @imagecreatefromjpeg($tmpPath),
        IMAGETYPE_PNG  => @imagecreatefrompng($tmpPath),
        IMAGETYPE_WEBP => @imagecreatefromwebp($tmpPath),
        IMAGETYPE_GIF  => @imagecreatefromgif($tmpPath),
        default        => false,
    };

    if (!$src) {
        // GD can't handle it — just copy
        return copy($tmpPath, $destPath);
    }

    $dst = imagecreatetruecolor($newW, $newH);

    // Preserve transparency for PNG
    if ($type === IMAGETYPE_PNG) {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
    }

    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $w, $h);
    imagedestroy($src);

    // Save as JPEG for all types (smaller file), PNG stays PNG
    if ($type === IMAGETYPE_PNG) {
        $ok = imagepng($dst, $destPath, 7);
    } else {
        $ok = imagejpeg($dst, $destPath, $quality);
    }
    imagedestroy($dst);
    return $ok;
}

// ── Helper: get file extension for saving ─────────────────────────────────
function image_ext(string $mimeType): string
{
    return match($mimeType) {
        'image/png'  => 'png',
        'image/webp' => 'jpg',  // convert webp → jpg
        'image/gif'  => 'gif',
        default      => 'jpg',
    };
}

// ── Load all catalog products (for dropdown) ───────────────────────────────
$allProducts = get_catalog_products();
// Build a map: code => product for quick lookup
$productMap  = [];
foreach ($allProducts as $p) {
    $productMap[$p['code']] = $p;
}
// Readable labels for category_id values
$categoryLabels = [
    'sofas'      => 'Sofas & Armchairs',
    'chairs'     => 'Chairs',
    'stools'     => 'Stools',
    'tables'     => 'Tables',
    'counters'   => 'Counters & Reception',
    'displays'   => 'Display & Shelving',
    'lighting'   => 'Lighting',
    'accessories'=> 'Accessories',
    'flooring'   => 'Flooring',
    'structures' => 'Structures & Backdrops',
    'av'         => 'AV & Tech',
    'other'      => 'Other',
];

// ── Scan existing images ───────────────────────────────────────────────────
function scan_images(string $dir): array
{
    $images = [];
    if (!is_dir($dir)) return $images;
    foreach (glob($dir . '*.{jpg,jpeg,png,webp,gif}', GLOB_BRACE) as $f) {
        $base = strtoupper(pathinfo($f, PATHINFO_FILENAME));
        $images[$base] = [
            'filename' => basename($f),
            'url'      => '',  // set below
            'size'     => filesize($f),
        ];
    }
    return $images;
}

$existingImages = scan_images($imagesDir);
foreach ($existingImages as $base => &$img) {
    $img['url'] = $imagesUrl . $img['filename'];
}
unset($img);

// Count products that have at least a default image
$productsWithImage = 0;
foreach ($allProducts as $p) {
    $code = strtoupper($p['code']);
    if (isset($existingImages[$code])) {
        $productsWithImage++;
    }
}
$totalProducts = count($allProducts);

// ── Flash/error messages ───────────────────────────────────────────────────
$uploadResults = [];

// ── Handle single product upload (product dropdown method) ─────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['upload_type'] ?? '') === 'single') {
    $productCode = strtoupper(trim($_POST['product_code'] ?? ''));
    $colourId    = trim($_POST['colour_id'] ?? '');
    $file        = $_FILES['product_image'] ?? null;

    if (!$productCode) {
        set_flash('danger', 'Please select a product first.');
    } elseif (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        set_flash('danger', 'Upload error — please choose a file and try again.');
    } elseif (!in_array($file['type'], ['image/jpeg','image/png','image/webp','image/gif'])) {
        set_flash('danger', 'Only JPG, PNG, WEBP, or GIF images are accepted.');
    } elseif ($file['size'] > 10 * 1024 * 1024) {
        set_flash('danger', 'File too large — max 10 MB.');
    } else {
        $ext      = image_ext($file['type']);
        $filename = $colourId
            ? strtoupper($productCode) . '-' . strtolower($colourId) . '.' . $ext
            : strtoupper($productCode) . '.' . $ext;

        if (compress_and_save($file['tmp_name'], $imagesDir . $filename)) {
            $sizeKB = round(filesize($imagesDir . $filename) / 1024);
            $prodName = $productMap[$productCode]['name'] ?? $productCode;
            set_flash('success', "✓ Image uploaded for {$prodName}" .
                ($colourId ? " ({$colourId})" : '') . " → {$filename} ({$sizeKB} KB)");
        } else {
            set_flash('danger', 'Failed to save image. Check folder permissions on the server.');
        }
    }
    redirect('/admin/images');
}

// ── Handle batch upload (pre-named files) ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['upload_type'] ?? '') === 'batch') {
    $files = $_FILES['batch_images'] ?? [];
    if (empty($files['name'][0])) {
        set_flash('danger', 'No files selected for batch upload.');
    } else {
        $ok = 0; $fail = 0;
        $count = count($files['name']);
        for ($i = 0; $i < $count; $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) { $fail++; continue; }
            $origName = $files['name'][$i];
            $mimeType = $files['type'][$i];
            if (!in_array($mimeType, ['image/jpeg','image/png','image/webp','image/gif'])) { $fail++; continue; }

            // Use the original filename (user must name correctly: CODE.jpg or CODE-colorid.jpg)
            $safeName = sanitize_filename(pathinfo($origName, PATHINFO_FILENAME))
                      . '.' . strtolower(pathinfo($origName, PATHINFO_EXTENSION) ?: 'jpg');

            if (compress_and_save($files['tmp_name'][$i], $imagesDir . $safeName)) {
                $ok++;
            } else {
                $fail++;
            }
        }
        $msg = "Batch upload complete: {$ok} uploaded";
        if ($fail) $msg .= ", {$fail} failed";
        if ($ok > 0) {
            set_flash('success', $msg . '. Images compressed and saved.');
        } else {
            set_flash('danger', $msg . '. Check file names and types.');
        }
    }
    redirect('/admin/images');
}

// ── Handle delete ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_image'])) {
    $del = sanitize_filename($_POST['delete_image']);
    if ($del && file_exists($imagesDir . $del)) {
        unlink($imagesDir . $del);
        set_flash('success', 'Deleted: ' . $del);
    }
    redirect('/admin/images');
}

// ── Gallery filter + search ────────────────────────────────────────────────
$filterMode   = $_GET['filter'] ?? 'all';   // all | missing | has_image
$filterSearch = strtoupper(trim($_GET['q'] ?? ''));

admin_header('Product Images', 'images');
?>

<?php admin_flash(); ?>

<!-- ── STATS BAR ──────────────────────────────────────────────────────── -->
<div style="display:flex;gap:16px;margin-bottom:20px;flex-wrap:wrap;align-items:center;">
  <div class="stat-card" style="flex:0 0 auto;padding:12px 20px;">
    <div class="stat-value" style="font-size:28px;"><?= $productsWithImage ?> <span style="font-size:14px;font-weight:400;color:#6E6E6E;">/ <?= $totalProducts ?></span></div>
    <div class="stat-label">Products with images</div>
  </div>
  <div class="stat-card" style="flex:0 0 auto;padding:12px 20px;">
    <div class="stat-value" style="font-size:28px;"><?= count($existingImages) ?></div>
    <div class="stat-label">Total image files (incl. colour variants)</div>
  </div>
  <div class="stat-card" style="flex:0 0 auto;padding:12px 20px;">
    <div class="stat-value" style="font-size:28px;"><?= $totalProducts - $productsWithImage ?></div>
    <div class="stat-label">Products missing images</div>
  </div>
</div>

<!-- ── UPLOAD: PRODUCT DROPDOWN METHOD ────────────────────────────────── -->
<div class="card mb-2">
  <div class="card-header">📷 Upload a Product Image</div>
  <div class="card-body">
    <p style="font-size:13px;color:#6E6E6E;margin-bottom:16px;">
      Select a product from the list — the file will be named and saved automatically.
      Images are compressed to reduce page load time.
    </p>
    <form method="POST" enctype="multipart/form-data"
          style="display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:12px;align-items:flex-end;">
      <input type="hidden" name="upload_type" value="single">

      <div class="form-group" style="margin:0;">
        <label>Product <span style="color:#c00;">*</span></label>
        <select name="product_code" id="product-select" class="form-control" required>
          <option value="">— Select a product —</option>
          <?php
          $lastCat = '';
          foreach ($allProducts as $p):
            $catKey = $p['category_id'] ?? 'other';
            $cat    = $categoryLabels[$catKey] ?? ucfirst($catKey);
            if ($cat !== $lastCat):
              if ($lastCat !== '') echo '</optgroup>';
              echo '<optgroup label="' . htmlspecialchars($cat) . '">';
              $lastCat = $cat;
            endif;
            $hasImg = isset($existingImages[strtoupper($p['code'])]) ? ' ✓' : '';
          ?>
          <option value="<?= e($p['code']) ?>"
                  data-colors='<?= htmlspecialchars(json_encode($p['colors'] ?? []), ENT_QUOTES) ?>'>
            <?= e($p['code']) ?> — <?= e($p['name']) ?><?= $hasImg ?>
          </option>
          <?php endforeach; if ($lastCat) echo '</optgroup>'; ?>
        </select>
      </div>

      <div class="form-group" style="margin:0;">
        <label>Colour <span style="font-weight:400;color:#6E6E6E;">(optional)</span></label>
        <select name="colour_id" id="colour-select" class="form-control">
          <option value="">Default (all colours)</option>
        </select>
      </div>

      <div class="form-group" style="margin:0;">
        <label>Image File <span style="font-weight:400;color:#6E6E6E;">(JPG/PNG, max 10MB)</span></label>
        <input type="file" name="product_image" class="form-control"
               accept="image/jpeg,image/png,image/webp" required>
      </div>

      <div>
        <button type="submit" class="btn btn-primary">⬆ Upload Image</button>
      </div>
    </form>

    <div id="filename-preview" style="display:none;margin-top:10px;padding:8px 12px;
         background:#D6F0EF;border-radius:4px;font-size:12px;color:#0A5050;">
      Will be saved as: <strong id="filename-preview-text"></strong>
    </div>

    <div style="background:#f9fffe;border:1px solid #D6F0EF;border-radius:6px;
                padding:10px 14px;font-size:12px;color:#0A5050;margin-top:14px;">
      <strong>Tip:</strong> Images are automatically compressed to web-friendly size.
      The ✓ next to a product name means it already has a default image — uploading again will replace it.
    </div>
  </div>
</div>

<!-- ── UPLOAD: BATCH (pre-named files) ────────────────────────────────── -->
<div class="card mb-2">
  <div class="card-header" style="cursor:pointer;" onclick="toggleBatch()">
    📦 Batch Upload (multiple files at once)
    <span id="batch-toggle" style="float:right;font-size:12px;font-weight:400;">▼ Show</span>
  </div>
  <div id="batch-panel" style="display:none;">
    <div class="card-body">
      <div class="alert alert-info" style="font-size:12px;margin-bottom:12px;">
        <strong>For batch upload, files must already be named correctly:</strong><br>
        <code>SOF01.jpg</code> = default image for product SOF01 &nbsp;|&nbsp;
        <code>SOF01-white.jpg</code> = white colour variant<br>
        This is ideal for uploading photos from the old Python version of the site,
        which already has correctly-named files.
      </div>
      <form method="POST" enctype="multipart/form-data"
            style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
        <input type="hidden" name="upload_type" value="batch">
        <div class="form-group" style="flex:1;min-width:280px;margin:0;">
          <label>Select Multiple Image Files</label>
          <input type="file" name="batch_images[]" class="form-control"
                 accept="image/jpeg,image/png,image/webp" multiple required>
          <small style="color:#6E6E6E;">Hold Ctrl (Windows) or Cmd (Mac) to select multiple files</small>
        </div>
        <div>
          <button type="submit" class="btn btn-primary">⬆ Upload All</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ── GALLERY ─────────────────────────────────────────────────────────── -->
<div class="card">
  <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
    <span>Product Catalog — Image Status</span>
    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
      <form method="GET" style="display:flex;gap:6px;">
        <input type="text" name="q" class="form-control" style="width:180px;font-family:monospace;"
               placeholder="Search product code…" value="<?= e($filterSearch) ?>">
        <input type="hidden" name="filter" value="<?= e($filterMode) ?>">
        <button type="submit" class="btn btn-outline btn-sm">Search</button>
      </form>
      <a href="?filter=all" class="btn btn-sm <?= $filterMode === 'all' ? 'btn-primary' : 'btn-outline' ?>">All</a>
      <a href="?filter=missing" class="btn btn-sm <?= $filterMode === 'missing' ? 'btn-primary' : 'btn-outline' ?>">
        Missing (<?= $totalProducts - $productsWithImage ?>)
      </a>
      <a href="?filter=has_image" class="btn btn-sm <?= $filterMode === 'has_image' ? 'btn-primary' : 'btn-outline' ?>">
        Has Image (<?= $productsWithImage ?>)
      </a>
    </div>
  </div>
  <div class="card-body" style="padding:16px;">
    <?php
    // Build display list
    $displayProducts = [];
    foreach ($allProducts as $p) {
      $code    = strtoupper($p['code']);
      $hasImg  = isset($existingImages[$code]);
      // Apply search
      if ($filterSearch && strpos($code, $filterSearch) === false
          && stripos($p['name'], $filterSearch) === false) continue;
      // Apply filter
      if ($filterMode === 'missing'   && $hasImg)  continue;
      if ($filterMode === 'has_image' && !$hasImg) continue;
      $displayProducts[] = ['product' => $p, 'code' => $code, 'hasImg' => $hasImg];
    }
    ?>

    <?php if (empty($displayProducts)): ?>
      <p style="color:#6E6E6E;text-align:center;padding:20px;">No products match your filter.</p>
    <?php else: ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:10px;">
      <?php foreach ($displayProducts as $row):
        $p      = $row['product'];
        $code   = $row['code'];
        $hasImg = $row['hasImg'];
        $imgUrl = $hasImg ? $existingImages[$code]['url'] : '';
        $imgFile = $hasImg ? $existingImages[$code]['filename'] : '';
        $sizeKB  = $hasImg ? round($existingImages[$code]['size'] / 1024) : 0;
      ?>
      <div style="border:1px solid <?= $hasImg ? '#D6F0EF' : '#fdd' ?>;border-radius:6px;
                  overflow:hidden;text-align:center;background:<?= $hasImg ? '#fff' : '#fff9f9' ?>;">

        <!-- Image preview -->
        <div style="height:90px;background:#f5f5f5;display:flex;align-items:center;
                    justify-content:center;overflow:hidden;position:relative;">
          <?php if ($hasImg): ?>
            <img src="<?= e($imgUrl) ?>" alt="<?= e($code) ?>"
                 style="max-width:100%;max-height:90px;object-fit:contain;"
                 onerror="this.parentElement.innerHTML='<span style=\'color:#ccc;font-size:10px;\'>No preview</span>'">
          <?php else: ?>
            <span style="color:#ddd;font-size:28px;">📷</span>
          <?php endif; ?>

          <!-- Code badge -->
          <span style="position:absolute;top:4px;left:4px;background:<?= $hasImg ? '#0A9696' : '#e74c3c' ?>;
                       color:#fff;font-size:9px;font-weight:700;padding:1px 5px;border-radius:3px;
                       font-family:monospace;">
            <?= e($code) ?>
          </span>
        </div>

        <!-- Product name -->
        <div style="padding:4px 6px;font-size:10px;color:#333;
                    white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"
             title="<?= e($p['name']) ?>">
          <?= e($p['name']) ?>
        </div>

        <!-- Size + actions -->
        <?php if ($hasImg): ?>
        <div style="font-size:9px;color:#6E6E6E;margin-bottom:3px;"><?= $sizeKB ?> KB</div>
        <form method="POST" style="margin-bottom:5px;"
              onsubmit="return confirm('Delete <?= e(addslashes($imgFile)) ?>?');">
          <input type="hidden" name="delete_image" value="<?= e($imgFile) ?>">
          <button type="submit" class="btn btn-danger btn-sm"
                  style="font-size:9px;padding:1px 6px;">Delete</button>
        </form>
        <?php else: ?>
        <div style="font-size:9px;color:#e74c3c;margin-bottom:8px;font-weight:600;">No image</div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<script>
// ── Populate colour dropdown when product is selected ─────────────────────
document.getElementById('product-select').addEventListener('change', function() {
  var sel    = this;
  var option = sel.options[sel.selectedIndex];
  var colors = [];
  try { colors = JSON.parse(option.getAttribute('data-colors') || '[]'); } catch(e) {}

  var colSel = document.getElementById('colour-select');
  colSel.innerHTML = '<option value="">Default (all colours)</option>';
  colors.forEach(function(c) {
    var opt = document.createElement('option');
    opt.value       = c.id;
    opt.textContent = c.name;
    colSel.appendChild(opt);
  });

  updateFilenamePreview();
});

document.getElementById('colour-select').addEventListener('change', updateFilenamePreview);

function updateFilenamePreview() {
  var code   = document.getElementById('product-select').value;
  var colour = document.getElementById('colour-select').value;
  var prev   = document.getElementById('filename-preview');
  var txt    = document.getElementById('filename-preview-text');

  if (!code) { prev.style.display = 'none'; return; }

  var filename = colour ? code.toUpperCase() + '-' + colour.toLowerCase() + '.jpg'
                        : code.toUpperCase() + '.jpg';
  txt.textContent = filename;
  prev.style.display = 'block';
}

// ── Toggle batch panel ────────────────────────────────────────────────────
function toggleBatch() {
  var panel  = document.getElementById('batch-panel');
  var toggle = document.getElementById('batch-toggle');
  if (panel.style.display === 'none') {
    panel.style.display = 'block';
    toggle.textContent  = '▲ Hide';
  } else {
    panel.style.display = 'none';
    toggle.textContent  = '▼ Show';
  }
}
</script>

<?php admin_footer(); ?>

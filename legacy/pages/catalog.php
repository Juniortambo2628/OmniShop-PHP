<?php
/**
 * Main Catalog Storefront
 */
require_once BASE_PATH . '/includes/catalog.php';

$eventSlug  = $_GET['event'] ?? 'solarandstorage';
$event      = get_event($eventSlug);
if (!$event) { http_response_code(404); echo "Event not found"; exit; }

$categories    = get_categories();
$products      = get_merged_products();
$productImages = get_product_images();
$settings      = get_all_settings();

// Prepare product images as JSON for JS
$imagesJson   = json_encode($productImages);
$productsJson = json_encode($products);
$categoriesJson = json_encode($categories);

$companyName  = $settings['company_name']    ?? 'OmniSpace 3D Events Ltd';
$companyPhone = $settings['company_phone']   ?? '+254 731 001 723';
$companyWA    = $settings['company_whatsapp']?? '+254731001723';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($event['name']) ?> — Exhibitor Services Catalogue</title>
  <link rel="stylesheet" href="<?= SITE_URL ?>/static/css/style.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>

<!-- TOP NAV -->
<nav class="topnav">
  <div class="topnav-logo">
    <img src="<?= SITE_URL ?>/static/images/logo_white_background.jpg" alt="OmniSpace"
         style="height:32px;" onerror="this.style.display='none'">
  </div>
  <div class="topnav-title"><?= e($event['name']) ?></div>
  <div class="topnav-actions">
    <a href="<?= SITE_URL ?>/<?= e($eventSlug) ?>/checkout" id="nav-checkout-btn" style="display:none;"
       class="btn btn-outline" style="color:#fff;border-color:#fff;">
      🛒 View Order (<span id="nav-cart-count">0</span>)
    </a>
    <a href="<?= SITE_URL ?>/<?= e($eventSlug) ?>/login"
       onclick="clearAuth();return true;" style="font-size:12px;">Logout</a>
  </div>
</nav>

<!-- DEADLINES BANNER -->
<?php if (!empty($event['deadlines'])): ?>
<div class="deadlines-bar">
  <strong>⏰ Order Deadlines:</strong>
  <div class="deadline-items">
    <?php foreach ($event['deadlines'] as $dl): ?>
      <span class="deadline-item"><strong><?= e($dl['category']) ?>:</strong> <?= e($dl['deadline']) ?></span>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- CATALOG LAYOUT -->
<div class="catalog-shell">

  <!-- SIDEBAR -->
  <aside class="cat-sidebar" id="cat-sidebar">
    <h3>Categories</h3>
    <?php foreach ($categories as $cat):
      $count = count(array_filter($products, fn($p) => $p['category_id'] === $cat['id']));
      if ($count === 0) continue;
    ?>
    <div class="cat-link" data-cat="<?= e($cat['id']) ?>">
      <span class="cat-icon"><?= $cat['icon'] ?></span>
      <span><?= e($cat['name']) ?></span>
      <span class="cat-count"><?= $count ?></span>
    </div>
    <?php endforeach; ?>
  </aside>

  <!-- MAIN CONTENT -->
  <main class="catalog-main" id="catalog-main">
    <?php foreach ($categories as $cat):
      $catProds = array_filter($products, fn($p) => $p['category_id'] === $cat['id']);
      if (empty($catProds)) continue;
    ?>
    <section class="catalog-section" id="section-<?= e($cat['id']) ?>">
      <h2 class="section-header"><?= e($cat['icon']) ?> <?= e($cat['name']) ?></h2>
      <div class="product-grid">
        <?php foreach ($catProds as $prod):
          $code    = e($prod['code']);
          $prodId  = e($prod['id']);
          $colors  = $prod['colors'] ?? [];
          $isPoa   = !empty($prod['is_poa']);

          // Find available images
          $imgMap = $productImages[$prod['code']] ?? $productImages[$prod['id']] ?? [];
          $defaultImg = $imgMap['default'] ?? (count($imgMap) ? reset($imgMap) : null);
        ?>
        <div class="product-card" data-product-id="<?= $prodId ?>"
             data-product-code="<?= $code ?>"
             data-product-name="<?= e($prod['name']) ?>"
             data-price="<?= $isPoa ? 0 : (float)$prod['price'] ?>"
             data-price-display="<?= e($prod['price_display']) ?>"
             data-category="<?= e($cat['id']) ?>"
             data-category-name="<?= e($cat['name']) ?>"
             data-dimensions="<?= e($prod['dimensions'] ?? '') ?>"
             data-is-poa="<?= $isPoa ? '1' : '0' ?>"
             data-colors='<?= htmlspecialchars(json_encode($colors), ENT_QUOTES) ?>'>

          <!-- Product image -->
          <div class="product-img-wrap" id="img-wrap-<?= $prodId ?>">
            <?php if ($defaultImg): ?>
              <img src="<?= SITE_URL ?>/static/images/products/<?= e($defaultImg) ?>"
                   alt="<?= e($prod['name']) ?>"
                   id="prod-img-<?= $prodId ?>"
                   onerror="this.parentElement.innerHTML='<div class=\'no-image-placeholder\'>📦</div>'">
            <?php else: ?>
              <div class="no-image-placeholder">📦</div>
            <?php endif; ?>
          </div>

          <div class="product-info">
            <div class="product-code"><?= $code ?></div>
            <div class="product-name"><?= e($prod['name']) ?></div>
            <?php if ($prod['dimensions']): ?>
              <div class="product-dims"><?= e($prod['dimensions']) ?></div>
            <?php endif; ?>
            <div class="product-price <?= $isPoa ? 'poa' : '' ?>">
              <?= $isPoa ? 'Price on Request' : e($prod['price_display']) ?>
              <?php if (!$isPoa && $prod['unit'] !== 'per event'): ?>
                <span style="font-size:10px;color:#6E6E6E;font-weight:400;"> / <?= e($prod['unit']) ?></span>
              <?php endif; ?>
            </div>

            <!-- Colour switcher -->
            <?php if (count($colors) > 1): ?>
            <div class="color-switcher" id="colors-<?= $prodId ?>">
              <?php foreach ($colors as $i => $col): ?>
              <button class="color-btn <?= $i === 0 ? 'active' : '' ?>"
                      data-color-id="<?= e($col['id']) ?>"
                      data-color-name="<?= e($col['name']) ?>"
                      onclick="selectColor(this, '<?= $prodId ?>')">
                <?= e($col['name']) ?>
              </button>
              <?php endforeach; ?>
            </div>
            <?php elseif (count($colors) === 1): ?>
            <div style="font-size:11px;color:#6E6E6E;margin-bottom:8px;">
              Color: <strong><?= e($colors[0]['name']) ?></strong>
            </div>
            <?php endif; ?>

            <!-- Add to order -->
            <div class="add-to-order" id="add-<?= $prodId ?>">
              <?php if (!$isPoa): ?>
              <div class="qty-controls">
                <button class="qty-btn" onclick="changeQty('<?= $prodId ?>', -1)">−</button>
                <input type="number" class="qty-input" id="qty-<?= $prodId ?>" value="1" min="1" max="999">
                <button class="qty-btn" onclick="changeQty('<?= $prodId ?>', 1)">+</button>
              </div>
              <button class="btn-add-to-order" id="btn-<?= $prodId ?>"
                      onclick="addToOrder('<?= $prodId ?>')">
                + Add to Order
              </button>
              <?php else: ?>
              <p style="font-size:11px;color:#6E6E6E;margin-top:8px;">
                Contact us for pricing:<br>
                <a href="tel:<?= preg_replace('/[^+0-9]/', '', $companyPhone) ?>">
                  <?= e($companyPhone) ?>
                </a>
              </p>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endforeach; ?>
    <div style="height:80px;"></div><!-- spacer for cart bar -->
  </main>
</div>

<!-- CART BAR -->
<div class="cart-bar hidden" id="cart-bar">
  <div>
    <div class="cart-count" id="cart-count-label">0 items</div>
    <div class="cart-total" id="cart-total-label">$0.00</div>
  </div>
  <button class="btn-checkout" onclick="goToCheckout()">Review & Submit Order →</button>
</div>

<!-- Pass data to JS -->
<script>
var SITE_URL    = '<?= SITE_URL ?>';
var EVENT_SLUG  = '<?= e($eventSlug) ?>';
var VAT_RATE    = <?= VAT_RATE ?>;
var PRODUCTS    = <?= $productsJson ?>;
var CATEGORIES  = <?= $categoriesJson ?>;
var IMAGES      = <?= $imagesJson ?>;
</script>
<script src="<?= SITE_URL ?>/static/js/catalog.js"></script>
</body>
</html>

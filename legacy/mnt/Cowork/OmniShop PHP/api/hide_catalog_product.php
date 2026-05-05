<?php
/**
 * API: Hide a built-in catalog product by creating an inactive DB override.
 * POST /api/hide-catalog-product
 * Expects form POST: { catalog_id, catalog_code, catalog_name, catalog_cat }
 *
 * This does NOT delete — it creates a DB record with is_override=1, is_active=0
 * so the product is hidden from the public catalog but can be shown again at any time
 * using the Visible/Hidden toggle on the Products page.
 *
 * Admin auth required (can_edit_products).
 */
if (!defined('BASE_PATH')) { exit; }
require_admin_auth();
if (!can_edit_products()) {
    set_flash('danger', 'Access denied.');
    redirect('/admin');
}

$catalogId   = trim($_POST['catalog_id']   ?? '');
$catalogCode = strtoupper(trim($_POST['catalog_code'] ?? ''));
$catalogName = trim($_POST['catalog_name'] ?? '');
$catalogCat  = trim($_POST['catalog_cat']  ?? '');

if (!$catalogId || !$catalogCode) {
    set_flash('danger', 'Missing product information.');
    redirect('/admin/products');
}

// Check if an override already exists for this catalog_id
try {
    $pdo  = get_pdo();
    $stmt = $pdo->prepare('SELECT id, is_active FROM products WHERE prod_id = ? AND is_override = 1 LIMIT 1');
    $stmt->execute([$catalogId]);
    $existing = $stmt->fetch();

    if ($existing) {
        // Already has an override — just toggle it to inactive
        toggle_product_active((int)$existing['id'], false);
        set_flash('success', '"' . htmlspecialchars($catalogName) . '" is now hidden from the catalog.');
    } else {
        // No override yet — create a hidden one
        // We need the full catalog product data to populate the record
        require_once BASE_PATH . '/includes/catalog.php';
        $catalogProducts = get_catalog_products();
        $catalogProduct  = null;
        foreach ($catalogProducts as $cp) {
            if ($cp['id'] === $catalogId) {
                $catalogProduct = $cp;
                break;
            }
        }

        $data = [
            'prod_id'      => $catalogId,
            'code'         => $catalogCode,
            'name'         => $catalogProduct['name']        ?? $catalogName,
            'category_id'  => $catalogProduct['category_id'] ?? $catalogCat,
            'price'        => $catalogProduct['price']        ?? 0,
            'price_display'=> $catalogProduct['price_display'] ?? '',
            'unit'         => $catalogProduct['unit']         ?? 'per event',
            'dimensions'   => $catalogProduct['dimensions']   ?? '',
            'description'  => $catalogProduct['description']  ?? '',
            'is_poa'       => $catalogProduct['is_poa']       ?? false,
            'is_active'    => false,   // ← hidden
            'is_override'  => true,
            'colors'       => $catalogProduct['colors']       ?? [],
        ];

        create_admin_product($data, get_admin_display_name());
        set_flash('success', '"' . htmlspecialchars($catalogName) . '" is now hidden from the catalog. You can show it again using the Visible/Hidden toggle.');
    }
} catch (Exception $e) {
    set_flash('danger', 'Error hiding product: ' . htmlspecialchars($e->getMessage()));
}

redirect('/admin/products');

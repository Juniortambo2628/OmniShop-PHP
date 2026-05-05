<?php
/**
 * API: Reset a DB product override — removes the customisation so the
 *      built-in catalog entry is used again.
 * POST /api/reset-product-override
 * Expects form POST: { product_id }
 * Admin auth required (can_edit_products).
 */
if (!defined('BASE_PATH')) { exit; }
require_admin_auth();
if (!can_edit_products()) {
    set_flash('danger', 'Access denied.');
    redirect('/admin');
}

$productId = (int)($_POST['product_id'] ?? 0);

if (!$productId) {
    set_flash('danger', 'Invalid product ID.');
    redirect('/admin/products');
}

// Only allow resetting overrides — not pure custom additions
$prod = get_admin_product($productId);
if (!$prod) {
    set_flash('danger', 'Product not found.');
    redirect('/admin/products');
}

if (empty($prod['is_override'])) {
    set_flash('danger', 'Cannot reset a custom product — use Delete instead.');
    redirect('/admin/products');
}

delete_admin_product($productId);
set_flash('success', 'Product "' . htmlspecialchars($prod['name']) . '" reset to catalog defaults.');
redirect('/admin/products');

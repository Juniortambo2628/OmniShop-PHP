<?php
/**
 * API: Toggle Product Active/Inactive
 * POST /api/toggle-product
 * Expects form POST: { product_id }
 */
if (!defined('BASE_PATH')) { exit; }
require_admin_auth();
if (!can_edit_products()) { redirect('/admin'); }

$productId = (int)($_POST['product_id'] ?? 0);
if ($productId) {
    toggle_product_active($productId);
    set_flash('success', 'Product visibility updated.');
}
redirect('/admin/products');

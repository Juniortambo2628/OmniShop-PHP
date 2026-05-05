<?php
/**
 * API: Delete Admin Product Override
 * POST /api/delete-product
 * Expects form POST: { product_id }
 * Admin auth required (can_edit_products).
 */
if (!defined('BASE_PATH')) { exit; }
require_admin_auth();
if (!can_edit_products()) {
    http_response_code(403);
    if ($_SERVER['HTTP_ACCEPT'] === 'application/json') {
        echo json_encode(['success' => false, 'error' => 'Access denied.']);
    } else {
        redirect('/admin');
    }
    exit;
}

$productId = (int)($_POST['product_id'] ?? 0);

if (!$productId) {
    set_flash('danger', 'Invalid product ID.');
    redirect('/admin/products');
}

delete_admin_product($productId);
set_flash('success', 'Product deleted.');
redirect('/admin/products');

<?php
/**
 * OmniShop Helper Functions
 */

function redirect($url) {
    header('Location: ' . SITE_URL . $url);
    exit;
}

function redirect_back($fallback = '/') {
    $ref = $_SERVER['HTTP_REFERER'] ?? (SITE_URL . $fallback);
    header('Location: ' . $ref);
    exit;
}

function e($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

function format_currency($amount, $decimals = 2) {
    return '$' . number_format((float)$amount, $decimals);
}

function format_date($dateStr) {
    if (!$dateStr) return '—';
    try {
        $dt = new DateTime($dateStr);
        return $dt->format('d M Y, H:i');
    } catch (Exception $e) {
        return $dateStr;
    }
}

function generate_order_id($companyName, $pdo) {
    // OMN-SSL2026-ABC-001 style
    $prefix = preg_replace('/[^A-Za-z]/', '', $companyName);
    $prefix = strtoupper(substr($prefix, 0, 3)) ?: 'UNK';
    $seq    = get_next_order_number($pdo);
    return sprintf('OMN-OS2026-%s-%03d', $prefix, $seq);
}

function get_product_images() {
    $imgDir = IMAGES_PATH;
    $images = [];
    if (!is_dir($imgDir)) return $images;

    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $files   = scandir($imgDir);
    foreach ($files as $fname) {
        $ext = strtolower(pathinfo($fname, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) continue;
        $stem = strtoupper(pathinfo($fname, PATHINFO_FILENAME));
        // Detect CODE-COLORID pattern: last segment is 1-2 digits
        if (preg_match('/^(.+)-(\d{1,2})$/', $stem, $m)) {
            $code    = $m[1];
            $colorId = str_pad($m[2], 2, '0', STR_PAD_LEFT);
            $images[$code][$colorId] = $fname;
        } else {
            $images[$stem]['default'] = $fname;
        }
    }
    return $images;
}

function status_badge_class($status) {
    $map = [
        'Pending'   => 'badge-warning',
        'Approved'  => 'badge-info',
        'Invoiced'  => 'badge-primary',
        'Fulfilled' => 'badge-success',
        'Cancelled' => 'badge-danger',
    ];
    return $map[$status] ?? 'badge-secondary';
}

function json_response($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function json_error($message, $code = 400) {
    json_response(['error' => $message, 'success' => false], $code);
}

function sanitize_filename($name) {
    return preg_replace('/[^A-Za-z0-9_\-\.]/', '', $name);
}

function is_poa_product($product) {
    return !empty($product['is_poa']);
}

function get_order_statuses() {
    return ['Pending', 'Approved', 'Invoiced', 'Fulfilled', 'Cancelled'];
}

function get_role_labels() {
    return [
        'super_admin'    => 'Super Admin',
        'product_editor' => 'Product Editor',
        'order_manager'  => 'Order Manager',
    ];
}

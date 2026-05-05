<?php
/**
 * OmniShop Front Controller
 * ─────────────────────────────────────────────────────────────────────────────
 * All requests come here. We parse the URL and route to the right page.
 */

require_once __DIR__ . '/config.php';
require_once BASE_PATH . '/includes/db.php';
require_once BASE_PATH . '/includes/auth.php';
require_once BASE_PATH . '/includes/events.php';
require_once BASE_PATH . '/includes/helpers.php';

// Start session
session_name('omnishop_admin');
session_set_cookie_params(SESSION_LIFETIME);
session_start();

// Parse the request URI
$requestUri  = $_SERVER['REQUEST_URI'] ?? '/';
$scriptName  = dirname($_SERVER['SCRIPT_NAME']);
if ($scriptName !== '/') {
    $requestUri = substr($requestUri, strlen($scriptName));
}
$requestUri = '/' . ltrim($requestUri, '/');

// Strip query string
$path = parse_url($requestUri, PHP_URL_PATH);
$path = '/' . trim($path, '/');

$method = $_SERVER['REQUEST_METHOD'];

// ── Route matching ─────────────────────────────────────────────────────────────

// Root → redirect to first available event
if ($path === '/' || $path === '') {
    redirect('/solarandstorage');
}

// API routes (JSON, called by JavaScript)
if (strpos($path, '/api/') === 0) {
    if ($path === '/api/submit-order' && $method === 'POST') {
        require BASE_PATH . '/api/submit_order.php';
    } elseif ($path === '/api/update-status' && $method === 'POST') {
        require BASE_PATH . '/api/update_status.php';
    } elseif ($path === '/api/delete-product' && $method === 'POST') {
        require BASE_PATH . '/api/delete_product.php';
    } elseif ($path === '/api/reset-product-override' && $method === 'POST') {
        require BASE_PATH . '/api/reset_product_override.php';
    } elseif ($path === '/api/toggle-product' && $method === 'POST') {
        require BASE_PATH . '/api/toggle_product.php';
    } elseif ($path === '/api/hide-catalog-product' && $method === 'POST') {
        require BASE_PATH . '/api/hide_catalog_product.php';
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Not found']);
    }
    exit;
}

// Admin routes
if (strpos($path, '/admin') === 0) {
    $adminPath = substr($path, 6); // strip '/admin'
    $adminPath = '/' . ltrim($adminPath, '/');

    switch (true) {
        case ($adminPath === '/' || $adminPath === ''):
            redirect('/admin/dashboard');
            break;
        case ($adminPath === '/login'):
            require BASE_PATH . '/pages/admin/login.php';
            break;
        case ($adminPath === '/logout'):
            require BASE_PATH . '/pages/admin/logout.php';
            break;
        case ($adminPath === '/dashboard'):
            require_admin_auth();
            require BASE_PATH . '/pages/admin/dashboard.php';
            break;
        case (preg_match('#^/order/([^/]+)$#', $adminPath, $m)):
            require_admin_auth();
            $_GET['order_id'] = $m[1];
            require BASE_PATH . '/pages/admin/order.php';
            break;
        case (preg_match('#^/packing/([^/]+)$#', $adminPath, $m)):
            require_admin_auth();
            $_GET['event'] = $m[1];
            require BASE_PATH . '/pages/admin/packing.php';
            break;
        case (preg_match('#^/packing-booth/([^/]+)$#', $adminPath, $m)):
            require_admin_auth();
            $_GET['event'] = $m[1];
            require BASE_PATH . '/pages/admin/packing_booth.php';
            break;
        case ($adminPath === '/products'):
            require_admin_auth();
            require BASE_PATH . '/pages/admin/products.php';
            break;
        case ($adminPath === '/products/add'):
            require_admin_auth();
            // Pass from_catalog and product_id via $_GET (already in query string)
            require BASE_PATH . '/pages/admin/product_form.php';
            break;
        case (preg_match('#^/products/(\d+)/edit$#', $adminPath, $m)):
            require_admin_auth();
            $_GET['product_id'] = $m[1];
            require BASE_PATH . '/pages/admin/product_form.php';
            break;
        case ($adminPath === '/images'):
            require_admin_auth();
            require BASE_PATH . '/pages/admin/images.php';
            break;
        case ($adminPath === '/settings'):
            require_admin_auth();
            require BASE_PATH . '/pages/admin/settings.php';
            break;
        case ($adminPath === '/users'):
            require_admin_auth();
            require BASE_PATH . '/pages/admin/users.php';
            break;
        case ($adminPath === '/stock'):
            require_admin_auth();
            require BASE_PATH . '/pages/admin/stock.php';
            break;
        case (preg_match('#^/export/([^/]+)$#', $adminPath, $m)):
            require_admin_auth();
            $_GET['event'] = $m[1];
            require BASE_PATH . '/pages/admin/export.php';
            break;
        case (preg_match('#^/invoice/([^/]+)$#', $adminPath, $m)):
            require_admin_auth();
            $_GET['order_id'] = $m[1];
            require BASE_PATH . '/pages/admin/invoice_download.php';
            break;
        default:
            redirect('/admin/dashboard');
    }
    exit;
}

// Storefront routes: /{event_slug}/...
if (preg_match('#^/([a-z0-9_-]+)(/(.*))?$#', $path, $m)) {
    $eventSlug = $m[1];
    $subPath   = isset($m[3]) ? '/' . $m[3] : '/';

    // Check event exists
    $events = get_events();
    if (!isset($events[$eventSlug])) {
        http_response_code(404);
        echo "<h2>404 — Event not found</h2>";
        exit;
    }

    $_GET['event'] = $eventSlug;

    switch (true) {
        case ($subPath === '/' || $subPath === ''):
            require_catalog_auth($eventSlug);
            require BASE_PATH . '/pages/catalog.php';
            break;
        case ($subPath === '/login'):
            require BASE_PATH . '/pages/login.php';
            break;
        case ($subPath === '/checkout'):
            require_catalog_auth($eventSlug);
            require BASE_PATH . '/pages/checkout.php';
            break;
        case (preg_match('#^/confirmation/([^/]+)$#', $subPath, $cm)):
            $_GET['order_id'] = $cm[1];
            require BASE_PATH . '/pages/confirmation.php';
            break;
        default:
            redirect('/' . $eventSlug);
    }
    exit;
}

// Fallback
http_response_code(404);
echo "<h2>404 — Page not found</h2>";

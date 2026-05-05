<?php
/**
 * Admin Panel — Shared Layout Helpers
 * Call admin_header($title, $activeNav) at top, admin_footer() at bottom.
 */

if (!defined('BASE_PATH')) { exit; }

function admin_header(string $title = 'Admin', string $activeNav = ''): void
{
    $user = get_admin_user();
    $role = get_admin_role();
    $name = htmlspecialchars($user['display_name'] ?? $user['username'] ?? 'Admin');
    $isSuperAdmin = ($role === 'super_admin');

    $navItems = [
        'dashboard' => ['url' => '/admin',            'label' => '📊 Dashboard',    'roles' => ['super_admin','admin','viewer']],
        'orders'    => ['url' => '/admin',            'label' => '📦 Orders',        'roles' => ['super_admin','admin','viewer']],
        'products'  => ['url' => '/admin/products',   'label' => '🛋️ Products',      'roles' => ['super_admin','admin']],
        'images'    => ['url' => '/admin/images',     'label' => '🖼️ Images',        'roles' => ['super_admin','admin']],
        'stock'     => ['url' => '/admin/stock',      'label' => '📋 Stock Limits',  'roles' => ['super_admin','admin']],
        'settings'  => ['url' => '/admin/settings',   'label' => '⚙️ Settings',      'roles' => ['super_admin']],
        'users'     => ['url' => '/admin/users',      'label' => '👤 Users',         'roles' => ['super_admin']],
    ];

    echo '<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>' . htmlspecialchars($title) . ' — OmniShop Admin</title>
  <link rel="stylesheet" href="' . SITE_URL . '/static/css/style.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="admin-body">
<div class="admin-wrap">

  <!-- Sidebar -->
  <aside class="admin-sidebar">
    <div class="admin-sidebar-logo">
      <img src="' . SITE_URL . '/static/images/logo_white_background.jpg" alt="OmniSpace"
           style="height:36px;" onerror="this.style.display=\'none\'">
      <div style="font-size:10px;color:#D6F0EF;margin-top:4px;letter-spacing:1px;">ADMIN PANEL</div>
    </div>
    <nav class="admin-nav">';

    foreach ($navItems as $key => $item) {
        if (!in_array($role, $item['roles'])) continue;
        $active = ($activeNav === $key) ? ' active' : '';
        echo "\n      <a href=\"" . SITE_URL . $item['url'] . "\" class=\"admin-nav-item$active\">"
            . $item['label'] . "</a>";
    }

    echo '
    </nav>
    <div class="admin-sidebar-footer">
      <div style="font-size:11px;color:#D6F0EF;margin-bottom:6px;">
        Logged in as: <strong>' . $name . '</strong><br>
        <span style="color:#19AFAC;">' . ucfirst(str_replace('_', ' ', $role)) . '</span>
      </div>
      <a href="' . SITE_URL . '/admin/logout" class="btn btn-outline btn-sm"
         style="color:#fff;border-color:#fff;font-size:11px;">Logout</a>
    </div>
  </aside>

  <!-- Main content area -->
  <main class="admin-main">
    <div class="admin-topbar">
      <h1 class="admin-page-title">' . htmlspecialchars($title) . '</h1>
    </div>
    <div class="admin-content">';
}

function admin_footer(): void
{
    echo '
    </div><!-- /.admin-content -->
  </main><!-- /.admin-main -->
</div><!-- /.admin-wrap -->
</body>
</html>';
}

/**
 * Render a flash/alert message stored in session.
 */
function admin_flash(): void
{
    if (!empty($_SESSION['flash'])) {
        $type = $_SESSION['flash']['type'] ?? 'info';
        $msg  = htmlspecialchars($_SESSION['flash']['message'] ?? '');
        echo "<div class=\"alert alert-$type\" style=\"margin-bottom:16px;\">$msg</div>";
        unset($_SESSION['flash']);
    }
}

/**
 * Set a flash message for next request.
 */
function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

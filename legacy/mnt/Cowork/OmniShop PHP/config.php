<?php
/**
 * OmniShop Configuration File
 * ─────────────────────────────────────────────────────────────────────────────
 * ALEX: Edit the four lines below with your Bluehost MySQL details.
 * Everything else can be left as-is.
 */

// ── Database (Bluehost MySQL) ─────────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');   // e.g. omnispace_omnishop
define('DB_USER', 'your_db_username');     // e.g. omnispace_admin
define('DB_PASS', 'your_db_password');     // the password you set in cPanel

// ── Site URL (no trailing slash) ─────────────────────────────────────────────
// Change this to your actual subdomain once live
define('SITE_URL', 'https://shop.omnispace3d.com');

// ── App Settings ──────────────────────────────────────────────────────────────
define('APP_NAME', 'OmniShop');
define('APP_VERSION', '2.0');
define('VAT_RATE', 16);          // 16% VAT
define('SESSION_LIFETIME', 86400); // Admin session: 1 day in seconds
define('CATALOG_COOKIE_DAYS', 7);  // How long catalog login cookie lasts

// ── File paths ────────────────────────────────────────────────────────────────
define('BASE_PATH', __DIR__);
define('STATIC_PATH', BASE_PATH . '/static');
define('IMAGES_PATH', STATIC_PATH . '/images/products');
define('LIB_PATH', BASE_PATH . '/lib');

// ── Security ──────────────────────────────────────────────────────────────────
// Change this to any long random string — used to sign cookies
define('SECRET_KEY', 'omnispace-omnishop-2026-change-this-in-production');

// ── Timezone ─────────────────────────────────────────────────────────────────
date_default_timezone_set('Africa/Nairobi');

// ── Error handling (set to false on live site) ────────────────────────────────
define('DEBUG_MODE', true);
if (DEBUG_MODE) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

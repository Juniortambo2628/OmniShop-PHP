<?php
/**
 * OmniShop Configuration File
 * ─────────────────────────────────────────────────────────────────────────────
 * ALEX: Edit the four lines below with your Bluehost MySQL details.
 * Everything else can be left as-is.
 */

// ── Database (Local WAMP MySQL) ──────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'omnishop_local');
define('DB_USER', 'root');
define('DB_PASS', '');

// ── Site URL (no trailing slash) ─────────────────────────────────────────────
define('SITE_URL', 'http://localhost/OmniShop-PHP');

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

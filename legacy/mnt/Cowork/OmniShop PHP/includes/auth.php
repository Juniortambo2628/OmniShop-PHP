<?php
/**
 * OmniShop Authentication
 * ─────────────────────────────────────────────────────────────────────────────
 * Handles admin session auth and catalog cookie auth (password gate).
 */

// ── ADMIN AUTH ────────────────────────────────────────────────────────────────

/**
 * Verify credentials and start admin session.
 * Returns true on success, false on failure.
 */
function admin_login(string $email, string $password): bool
{
    $user = verify_admin_user($email, $password);
    if (!$user) return false;

    $_SESSION['admin_user']     = [
        'id'           => $user['id'],
        'username'     => $user['username'],
        'display_name' => $user['display_name'],
        'role'         => $user['role'],
    ];
    $_SESSION['admin_role']     = $user['role'];
    $_SESSION['admin_login_at'] = time();
    return true;
}

function admin_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/**
 * Returns the logged-in user array or null if not logged in / session expired.
 */
function get_admin_user(): ?array
{
    if (empty($_SESSION['admin_user'])) return null;
    if (time() - ($_SESSION['admin_login_at'] ?? 0) > SESSION_LIFETIME) {
        admin_logout();
        return null;
    }
    return $_SESSION['admin_user'];
}

function get_admin_role(): ?string
{
    $user = get_admin_user();
    return $user ? ($user['role'] ?? null) : null;
}

function get_admin_display_name(): string
{
    $user = get_admin_user();
    return $user ? ($user['display_name'] ?? $user['username'] ?? 'Admin') : 'Admin';
}

function is_admin_logged_in(): bool
{
    return get_admin_user() !== null;
}

function is_super_admin(): bool
{
    return get_admin_role() === 'super_admin';
}

function can_edit_products(): bool
{
    return in_array(get_admin_role(), ['super_admin', 'admin']);
}

function require_admin_auth(): void
{
    if (!is_admin_logged_in()) {
        redirect('/admin/login');
    }
}

function require_super_admin(): void
{
    require_admin_auth();
    if (!is_super_admin()) {
        http_response_code(403);
        require BASE_PATH . '/pages/403.php';
        exit;
    }
}

// ── CATALOG AUTH (cookie-based password gate) ─────────────────────────────────

function is_catalog_authenticated(string $eventSlug): bool
{
    $cookieName = 'catalog_auth_' . $eventSlug;
    if (empty($_COOKIE[$cookieName])) return false;
    $expected = hash_hmac('sha256', 'ok:' . $eventSlug, SECRET_KEY);
    return hash_equals($expected, $_COOKIE[$cookieName]);
}

function set_catalog_auth_cookie(string $eventSlug): void
{
    $cookieName = 'catalog_auth_' . $eventSlug;
    $value      = hash_hmac('sha256', 'ok:' . $eventSlug, SECRET_KEY);
    $expires    = time() + (CATALOG_COOKIE_DAYS * 86400);
    setcookie($cookieName, $value, [
        'expires'  => $expires,
        'path'     => '/',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function clear_catalog_auth_cookie(string $eventSlug): void
{
    setcookie('catalog_auth_' . $eventSlug, '', time() - 3600, '/');
}

function require_catalog_auth(string $eventSlug): void
{
    $pwd = get_catalog_password($eventSlug);
    if (!$pwd) return; // No password set — open access
    if (!is_catalog_authenticated($eventSlug)) {
        redirect('/' . $eventSlug . '/login');
    }
}

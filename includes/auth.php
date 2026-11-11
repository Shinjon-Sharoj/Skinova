<?php
/**
 * Authentication & authorisation helpers (session + JWT), verification tokens.
 *
 * Page guards:   require_login();  require_role(ROLE_ADMIN);  require_role([ROLE_ADMIN, ROLE_CONSULTANT]);
 * Queries:       current_user(), current_user_id(), is_logged_in(), has_role('admin')
 */

/** Set by api_init() when a request authenticates through a Bearer JWT. */
$GLOBALS['skinova_api_user'] = null;

function current_user(): ?array
{
    static $cache = [];
    if (!empty($GLOBALS['skinova_api_user'])) {
        return $GLOBALS['skinova_api_user'];
    }
    $id = (int) ($_SESSION['user_id'] ?? 0);
    if ($id <= 0) {
        return null;
    }
    if (!array_key_exists($id, $cache)) {
        $cache[$id] = db_one(
            'SELECT u.id, u.full_name, u.email, u.phone, u.role, u.status, u.email_verified_at, u.phone_verified_at, u.loyalty_points, up.avatar
             FROM users u LEFT JOIN user_profiles up ON up.user_id = u.id WHERE u.id = ?',
            [$id]
        );
        // Account removed/suspended while logged in -> drop the session.
        if (!$cache[$id] || $cache[$id]['status'] === 'suspended') {
            $cache[$id] = null;
            unset($_SESSION['user_id']);
        }
    }
    return $cache[$id];
}

function current_user_id(): ?int
{
    $u = current_user();
    return $u ? (int) $u['id'] : null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

/** @param string|array $roles */
function has_role($roles): bool
{
    $u = current_user();
    return $u !== null && in_array($u['role'], (array) $roles, true);
}

function is_admin(): bool
{
    return has_role(ROLE_ADMIN);
}

function is_consultant(): bool
{
    return has_role(ROLE_CONSULTANT);
}

/** Where each role lands after login (app-relative path). */
function home_url_for_role(string $role): string
{
    switch ($role) {
        case ROLE_ADMIN:      return 'admin/dashboard.php';
        case ROLE_CONSULTANT: return 'consultant/dashboard.php';
        default:              return 'index.php';
    }
}

/** Log the user in (regenerates the session id to prevent fixation). */
function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['_created'] = time();
    unset($_SESSION['_csrf']);
    db_exec('UPDATE users SET last_login_at = NOW() WHERE id = ?', [$user['id']]);
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'] ?? '', $p['secure'], $p['httponly']);
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

/** Redirect guests to the login page (remembering where they wanted to go). */
function require_login(): array
{
    $u = current_user();
    if (!$u) {
        $next = safe_next_path(current_path(), 'index.php');
        flash('warning', 'Please log in to continue.');
        redirect('login.php?next=' . urlencode($next));
    }
    return $u;
}

/** Require a verified (active) account; pending users are sent to verify-account.php. */
function require_verified(): array
{
    $u = require_login();
    if ($u['status'] !== 'active') {
        flash('warning', 'Please verify your account to use this feature.');
        redirect('verify-account.php');
    }
    return $u;
}

/** Require one of the roles; otherwise show a 403 page. */
function require_role($roles): array
{
    $u = require_login();
    if (!in_array($u['role'], (array) $roles, true)) {
        log_activity('access_denied', 'page', null, current_path());
        forbidden();
    }
    return $u;
}

/** Look up a user row (including password hash) by email or phone. */
function find_user_by_login(string $login): ?array
{
    $login = trim($login);
    if (is_valid_email($login)) {
        return db_one('SELECT * FROM users WHERE email = ?', [strtolower($login)]);
    }
    if (is_valid_phone($login)) {
        return db_one('SELECT * FROM users WHERE phone = ?', [normalize_phone($login)]);
    }
    return null;
}

// ---------------------------------------------------------------------------
// Verification / reset tokens (stored hashed)
// ---------------------------------------------------------------------------

/**
 * Create a token for $type ('email' | 'phone' | 'password_reset').
 * email/password_reset -> long random token (for links). phone -> 6 digit OTP.
 * Returns the PLAIN token (only the sha256 hash is stored).
 */
function create_verification_token(int $userId, string $type): string
{
    $plain = $type === 'phone' ? (string) random_int(100000, 999999) : bin2hex(random_bytes(24));
    $ttl = $type === 'password_reset' ? PASSWORD_RESET_TTL : VERIFICATION_TOKEN_TTL;
    db_exec('UPDATE verification_tokens SET used_at = NOW() WHERE user_id = ? AND type = ? AND used_at IS NULL', [$userId, $type]);
    db_exec(
        'INSERT INTO verification_tokens (user_id, type, token_hash, expires_at) VALUES (?,?,?, DATE_ADD(NOW(), INTERVAL ? SECOND))',
        [$userId, $type, hash('sha256', $plain), $ttl]
    );
    return $plain;
}

/**
 * Validate and consume a token. For OTP codes pass $userId (codes are only 6 digits,
 * so attempts are counted). Returns the user id on success or null.
 */
function consume_verification_token(string $type, string $plain, ?int $userId = null): ?int
{
    $hash = hash('sha256', $plain);
    if ($type === 'phone' && $userId) {
        $row = db_one(
            'SELECT * FROM verification_tokens WHERE user_id = ? AND type = ? AND used_at IS NULL AND expires_at > NOW() ORDER BY id DESC LIMIT 1',
            [$userId, $type]
        );
        if (!$row || (int) $row['attempts'] >= 5) return null;
        if (!hash_equals($row['token_hash'], $hash)) {
            db_exec('UPDATE verification_tokens SET attempts = attempts + 1 WHERE id = ?', [$row['id']]);
            return null;
        }
    } else {
        $row = db_one(
            'SELECT * FROM verification_tokens WHERE token_hash = ? AND type = ? AND used_at IS NULL AND expires_at > NOW() LIMIT 1',
            [$hash, $type]
        );
        if (!$row) return null;
    }
    db_exec('UPDATE verification_tokens SET used_at = NOW() WHERE id = ?', [$row['id']]);
    return (int) $row['user_id'];
}

/** Activate a pending user once at least one contact method has been verified. */
function activate_user_if_verified(int $userId): void
{
    db_exec("UPDATE users SET status = 'active' WHERE id = ? AND status = 'pending' AND (email_verified_at IS NOT NULL OR phone_verified_at IS NOT NULL)", [$userId]);
}
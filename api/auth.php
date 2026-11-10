<?php
/**
 * API: Authentication
 * POST action=login  -> {token, user}
 * POST action=logout -> {}
 * GET  action=me     -> {user}
 */
require_once _DIR_ . '/../includes/init.php';

$user = api_init(['methods' => ['GET', 'POST'], 'auth' => false, 'rate' => [30, 60]]);
$in   = api_input();
$action = $in['action'] ?? ($in['action'] ?? '');

// ---- GET me ----------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $in['action'] ?? 'me';
    if ($action === 'me') {
        $u = current_user();
        if (!$u) {
            api_fail('Unauthenticated.', 401);
        }
        api_ok(['user' => ['id' => $u['id'], 'full_name' => $u['full_name'], 'email' => $u['email'],
            'role' => $u['role'], 'status' => $u['status'], 'loyalty_points' => (int) $u['loyalty_points']]]);
    }
    api_fail('Unknown action.', 400);
}

// ---- POST ------------------------------------------------------------------
if ($action === 'login') {
    $login    = trim((string) ($in['login'] ?? ''));
    $password = (string) ($in['password'] ?? '');
    $ip       = client_ip();

    if (!rate_limit('api_login:' . $ip, 10, 600)) {
        api_fail('Too many login attempts. Try again later.', 429);
    }

    if ($login === '' || $password === '') {
        api_fail('Email/phone and password are required.', 422);
    }

    $u = find_user_by_login($login);
    if (!$u || !password_verify($password, $u['password_hash'])) {
        api_fail('Invalid credentials.', 401);
    }
    if ($u['status'] === 'suspended') {
        api_fail('Account suspended.', 403);
    }

    rate_limit_reset('api_login:' . $ip);

    $token = jwt_for_user((int) $u['id'], $u['role']);
    log_activity('api_login', 'user', (int) $u['id']);

    api_ok([
        'token' => $token,
        'user'  => ['id' => $u['id'], 'full_name' => $u['full_name'], 'email' => $u['email'],
                    'role' => $u['role'], 'status' => $u['status']],
    ], 'Signed in.');

} elseif ($action === 'logout') {
    logout_user();
    api_ok([], 'Signed out.');

} else {
    api_fail('Unknown action.', 400);
}
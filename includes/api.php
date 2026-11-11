<?php
/**
 * Helpers for JSON API endpoints (the files in api/).
 *
 * Standard response envelope:
 *   success: {"success":true,  "message":"...", "data":{...}}
 *   failure: {"success":false, "message":"...", "errors":{field:msg}}
 *
 * Authentication: logged-in session (CSRF token required for writes via the
 * X-CSRF-Token header) OR "Authorization: Bearer <JWT>" (no CSRF needed).
 * Obtain a JWT from POST api/auth.php.
 */

/**
 * Initialise an endpoint.
 *
 * Options:
 *   methods  array   allowed HTTP methods            (default GET, POST)
 *   auth     bool    require authentication          (default false)
 *   roles    array   restrict to roles               (default any)
 *   rate     array   [maxRequests, windowSeconds]    (default [120, 60])
 *   csrf     bool    enforce CSRF for session writes (default true)
 *
 * @return array|null the authenticated user row or null
 */
function api_init(array $opts = []): ?array
{
    $o = $opts + ['methods' => ['GET', 'POST'], 'auth' => false, 'roles' => [], 'rate' => [120, 60], 'csrf' => true];
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');

    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if ($method === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
    if (!in_array($method, $o['methods'], true)) {
        header('Allow: ' . implode(', ', $o['methods']));
        api_fail('Method not allowed.', 405);
    }

    // Authenticate: Bearer JWT first, then session.
    $viaJwt = false;
    $token = bearer_token();
    if ($token !== null) {
        $claims = jwt_decode($token);
        if (!$claims) {
            api_fail('Invalid or expired token.', 401);
        }
        $row = db_one('SELECT id, full_name, email, phone, role, status, email_verified_at, phone_verified_at, loyalty_points FROM users WHERE id = ?', [(int) $claims['sub']]);
        if (!$row || $row['status'] === 'suspended') {
            api_fail('Account not available.', 401);
        }
        $GLOBALS['skinova_api_user'] = $row;
        $viaJwt = true;
    }
    $user = current_user();

    // Rate limit per user (or IP for guests).
    [$max, $window] = $o['rate'];
    $key = 'api:' . basename($_SERVER['SCRIPT_NAME'] ?? 'x') . ':' . ($user ? 'u' . $user['id'] : client_ip());
    if (!rate_limit($key, (int) $max, (int) $window)) {
        header('Retry-After: ' . $window);
        api_fail('Too many requests. Please slow down.', 429);
    }

    if ($o['auth'] && !$user) {
        api_fail('Authentication required.', 401);
    }
    if ($o['roles'] && (!$user || !in_array($user['role'], (array) $o['roles'], true))) {
        api_fail('You do not have permission to perform this action.', 403);
    }
    if ($o['csrf'] && !$viaJwt && !in_array($method, ['GET', 'HEAD'], true)) {
        if (!csrf_is_valid()) {
            api_fail('Your session expired. Please refresh the page and try again.', 419);
        }
    }
    return $user;
}

/** Merged request input: JSON body > POST > GET. */
function api_input(): array
{
    static $data = null;
    if ($data !== null) {
        return $data;
    }
    $data = $_GET;
    $ct = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($ct, 'application/json') !== false) {
        $json = json_decode((string) file_get_contents('php://input'), true);
        if (is_array($json)) {
            $data = $json + $data;
        }
    } else {
        $data = $_POST + $data;
    }
    return $data;
}

function api_ok($data = null, string $message = 'OK', int $status = 200): void
{
    json_response(['success' => true, 'message' => $message, 'data' => $data], $status);
}

function api_fail(string $message, int $status = 400, array $errors = []): void
{
    $body = ['success' => false, 'message' => $message];
    if ($errors) $body['errors'] = $errors;
    json_response($body, $status);
}
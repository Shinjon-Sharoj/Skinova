<?php
/**
 * Minimal HS256 JSON Web Token implementation (no external dependencies).
 *
 * The signing secret comes from JWT_SECRET (config/local.php) or, if absent,
 * a random secret generated once and stored in storage/jwt.secret.
 */

function jwt_secret(): string
{
    static $secret = null;
    if ($secret !== null) {
        return $secret;
    }
    if (defined('JWT_SECRET') && JWT_SECRET !== '') {
        return $secret = (string) JWT_SECRET;
    }
    $file = APP_ROOT . '/storage/jwt.secret';
    if (!is_file($file)) {
        @file_put_contents($file, bin2hex(random_bytes(32)), LOCK_EX);
        @chmod($file, 0600);
    }
    $secret = trim((string) @file_get_contents($file));
    if ($secret === '') {
        $secret = hash('sha256', APP_ROOT . php_uname() . DB_NAME);
    }
    return $secret;
}

function base64url_encode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode(string $data): string
{
    return (string) base64_decode(strtr($data, '-_', '+/') . str_repeat('=', (4 - strlen($data) % 4) % 4));
}

/** Create a signed token. $claims are merged with iss/iat/exp. */
function jwt_encode(array $claims, ?int $ttl = null): string
{
    $now = time();
    $payload = $claims + ['iss' => JWT_ISSUER, 'iat' => $now, 'exp' => $now + ($ttl ?? JWT_TTL), 'jti' => bin2hex(random_bytes(8))];
    $header = ['typ' => 'JWT', 'alg' => 'HS256'];
    $segments = [
        base64url_encode(json_encode($header)),
        base64url_encode(json_encode($payload)),
    ];
    $signature = hash_hmac('sha256', implode('.', $segments), jwt_secret(), true);
    $segments[] = base64url_encode($signature);
    return implode('.', $segments);
}

/** Verify signature, issuer and expiry. Returns the payload array or null. */
function jwt_decode(string $token): ?array
{
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return null;
    }
    [$h, $p, $s] = $parts;
    $header = json_decode(base64url_decode($h), true);
    if (!is_array($header) || ($header['alg'] ?? '') !== 'HS256') {
        return null;
    }
    $expected = base64url_encode(hash_hmac('sha256', $h . '.' . $p, jwt_secret(), true));
    if (!hash_equals($expected, $s)) {
        return null;
    }
    $payload = json_decode(base64url_decode($p), true);
    if (!is_array($payload)) {
        return null;
    }
    if (($payload['iss'] ?? '') !== JWT_ISSUER || (int) ($payload['exp'] ?? 0) < time()) {
        return null;
    }
    return $payload;
}

/** Issue an access token for a user row. */
function jwt_for_user(array $user): string
{
    return jwt_encode(['sub' => (int) $user['id'], 'role' => $user['role']]);
}

/** Read "Authorization: Bearer <token>" (handles Apache stripping the header). */
function bearer_token(): ?string
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if ($header === '' && function_exists('apache_request_headers')) {
        $h = apache_request_headers();
        $header = $h['Authorization'] ?? $h['authorization'] ?? '';
    }
    if (preg_match('/^Bearer\s+(.+)$/i', $header, $m)) {
        return trim($m[1]);
    }
    return null;
}
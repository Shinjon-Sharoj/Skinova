<?php
/**
 * Application bootstrap. Every page / action / API endpoint starts with:
 *
 *     require_once _DIR_ . '/includes/init.php';          (root pages)
 *     require_once _DIR_ . '/../includes/init.php';       (admin/, consultant/, api/)
 *     require_once _DIR_ . '/../../includes/init.php';    (actions/<module>/)
 *
 * Loads config, starts a hardened session, registers error handling and
 * loads all shared helper modules.
 */
if (defined('SKINOVA_INIT')) {
    return;
}
define('SKINOVA_INIT', true);

require_once _DIR_ . '/../config/constants.php';
require_once _DIR_ . '/../config/app.php';
require_once _DIR_ . '/../config/database.php';

// --- Error handling ----------------------------------------------------------
error_reporting(E_ALL);
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');
$__logDir = APP_ROOT . '/storage/logs';
if (!is_dir($__logDir)) {
    @mkdir($__logDir, 0775, true);
}
ini_set('error_log', $__logDir . '/php-error.log');
unset($__logDir);

set_exception_handler(function (Throwable $e) {
    error_log('[skinova] Uncaught ' . get_class($e) . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    if (!headers_sent()) {
        http_response_code(500);
    }
    $isApi = strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false;
    if ($isApi) {
        if (!headers_sent()) header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'An unexpected error occurred.'] + (APP_DEBUG ? ['debug' => $e->getMessage()] : []));
    } else {
        echo '<!doctype html><meta charset="utf-8"><title>Something went wrong</title>'
           . '<body style="font-family:sans-serif;padding:3rem;max-width:640px;margin:auto"><h1>Something went wrong</h1>'
           . '<p>We could not complete your request. Please try again in a moment.</p>'
           . (APP_DEBUG ? '<pre style="background:#f6f6f6;padding:1rem;overflow:auto">' . htmlspecialchars($e->getMessage() . "\n" . $e->getTraceAsString()) . '</pre>' : '')
           . '</body>';
    }
    exit;
});

// --- Base path detection -------------------------------------------------------
function app_base_path(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    if (APP_BASE_PATH !== null) {
        return $base = rtrim((string) APP_BASE_PATH, '/');
    }
    $base = '';
    $scriptFile = isset($_SERVER['SCRIPT_FILENAME']) ? realpath($_SERVER['SCRIPT_FILENAME']) : false;
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $root = realpath(APP_ROOT);
    if ($scriptFile && $root && $scriptName !== '') {
        $scriptFile = str_replace('\\', '/', $scriptFile);
        $root = str_replace('\\', '/', $root);
        if (strpos($scriptFile, $root . '/') === 0) {
            $rel = substr($scriptFile, strlen($root) + 1);
            $segments = substr_count($rel, '/') + 1;
            $parts = array_values(array_filter(explode('/', $scriptName), 'strlen'));
            $keep = array_slice($parts, 0, max(0, count($parts) - $segments));
            $base = $keep ? '/' . implode('/', $keep) : '';
        }
    }
    return $base;
}

// --- Secure session ------------------------------------------------------------
function start_secure_session(): void
{
    if (PHP_SAPI === 'cli' || session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();

    $now = time();
    $fingerprint = hash('sha256', ($_SERVER['HTTP_USER_AGENT'] ?? '') . '|skinova');
    if (isset($_SESSION['_last_activity']) && ($now - $_SESSION['_last_activity']) > SESSION_IDLE_TIMEOUT) {
        $_SESSION = [];
        session_regenerate_id(true);
    }
    if (isset($_SESSION['_fp']) && !hash_equals($_SESSION['_fp'], $fingerprint)) {
        $_SESSION = [];
        session_regenerate_id(true);
    }
    $_SESSION['_fp'] = $fingerprint;
    $_SESSION['_last_activity'] = $now;
    if (!isset($_SESSION['_created'])) {
        $_SESSION['_created'] = $now;
    } elseif ($now - $_SESSION['_created'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['_created'] = $now;
    }
}
start_secure_session();

// --- Security headers ----------------------------------------------------------
if (PHP_SAPI !== 'cli' && !headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header_remove('X-Powered-By');
}

// --- Shared modules ------------------------------------------------------------
require_once _DIR_ . '/functions.php';
require_once _DIR_ . '/csrf.php';
require_once _DIR_ . '/validation.php';
require_once _DIR_ . '/jwt.php';
require_once _DIR_ . '/auth.php';
require_once _DIR_ . '/api.php';
require_once _DIR_ . '/upload.php';
require_once _DIR_ . '/notifications.php';
require_once _DIR_ . '/catalog.php';
require_once _DIR_ . '/commerce.php';
<?php
/**
 * Centralised database configuration + PDO helpers (XAMPP defaults).
 *
 * Override any value in config/local.php (copy config/local.example.php) -
 * never commit real credentials.
 */

if (!defined('DB_HOST')) define('DB_HOST', '127.0.0.1');
if (!defined('DB_PORT')) define('DB_PORT', 3306);
if (!defined('DB_NAME')) define('DB_NAME', 'skinova');
if (!defined('DB_USER')) define('DB_USER', 'root');      // XAMPP default
if (!defined('DB_PASS')) define('DB_PASS', '');          // XAMPP default (empty)
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

/**
 * Shared PDO connection (lazy singleton).
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_STRINGIFY_FETCHES  => false,
        ]);
        $pdo->exec("SET time_zone = '" . date('P') . "'");
    } catch (PDOException $e) {
        error_log('[skinova] DB connection failed: ' . $e->getMessage());
        http_response_code(503);
        if (PHP_SAPI !== 'cli') {
            $isApi = strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false;
            if ($isApi) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Service temporarily unavailable.']);
            } else {
                echo '<!doctype html><meta charset="utf-8"><title>Service unavailable</title>'
                   . '<body style="font-family:sans-serif;padding:3rem"><h1>Service temporarily unavailable</h1>'
                   . '<p>The database could not be reached. If you are the site owner, start MySQL in XAMPP and import '
                   . '<code>database/schema.sql</code> via phpMyAdmin.</p></body>';
            }
        }
        exit;
    }
    return $pdo;
}

/** Run a query and return all rows. */
function db_all(string $sql, array $params = []): array
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/** Run a query and return the first row or null. */
function db_one(string $sql, array $params = []): ?array
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

/** Run a query and return the first column of the first row (or $default). */
function db_value(string $sql, array $params = [], $default = null)
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $val = $stmt->fetchColumn();
    return $val === false ? $default : $val;
}

/** Execute INSERT/UPDATE/DELETE; returns affected row count. */
function db_exec(string $sql, array $params = []): int
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}
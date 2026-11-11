<?php
/**
 * General-purpose helpers: escaping, URLs, flash messages, formatting,
 * logging, settings, pagination, JSON helpers, mail/SMS, rate limiting.
 */

// ---------------------------------------------------------------------------
// Output / URLs
// ---------------------------------------------------------------------------

/** HTML-escape a value for safe output. */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Absolute (host-relative) URL for an app path, e.g. url('products.php?page=2'). */
function url(string $path = ''): string
{
    return app_base_path() . '/' . ltrim($path, '/');
}

/** URL for a static asset under /assets with cache-busting, e.g. asset('css/style.css'). */
function asset(string $path): string
{
    $path = ltrim($path, '/');
    $file = APP_ROOT . '/assets/' . $path;
    $v = is_file($file) ? '?v=' . filemtime($file) : '';
    return app_base_path() . '/assets/' . $path . $v;
}

/**
 * URL for an uploaded/media file whose DB path is relative to the project root
 * (e.g. "assets/uploads/profiles/x.jpg"). Falls back to a placeholder SVG
 * ('product', 'category', 'blog', 'avatar', 'banner') when empty or missing.
 */
function media_url(?string $path, string $placeholder = 'product'): string
{
    if ($path) {
        $clean = ltrim(str_replace('\\', '/', $path), '/');
        if (strpos($clean, '..') === false && is_file(APP_ROOT . '/' . $clean)) {
            return app_base_path() . '/' . $clean;
        }
    }
    return app_base_path() . '/assets/images/placeholders/' . $placeholder . '.svg';
}

/** Redirect and stop. $path is an app path (or a full URL). */
function redirect(string $path): void
{
    if (!preg_match('#^https?://#i', $path)) {
        $path = url($path);
    }
    header('Location: ' . $path);
    exit;
}

/** Redirect back to the referring page on this host (or to $fallback). */
function redirect_back(string $fallback = 'index.php'): void
{
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($ref && $host && parse_url($ref, PHP_URL_HOST) === parse_url('http://' . $host, PHP_URL_HOST)) {
        header('Location: ' . $ref);
        exit;
    }
    redirect($fallback);
}

/** Validate a user-supplied "next" path so redirects stay inside the app. */
function safe_next_path(?string $next, string $fallback = 'index.php'): string
{
    $next = (string) $next;
    if ($next === '' || preg_match('#^(https?:)?//#i', $next) || strpos($next, "\n") !== false || strpos($next, "\r") !== false || strpos($next, '\\') !== false) {
        return $fallback;
    }
    $base = app_base_path();
    if ($base !== '' && strpos($next, $base . '/') === 0) {
        $next = substr($next, strlen($base) + 1);
    }
    return ltrim($next, '/') ?: $fallback;
}

function current_path(): string
{
    return $_SERVER['REQUEST_URI'] ?? '';
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

/** Abort with 405 unless the request is a POST (for action handlers). */
function require_post(): void
{
    if (!is_post()) {
        http_response_code(405);
        header('Allow: POST');
        exit('Method not allowed');
    }
}

// ---------------------------------------------------------------------------
// Flash messages + old input
// ---------------------------------------------------------------------------

/** Queue a flash message. $type: success | danger | warning | info */
function flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

/** Return and clear queued flash messages. */
function get_flashes(): array
{
    $f = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $f;
}

/** Render Bootstrap alerts for queued flash messages. */
function render_flashes(): string
{
    $html = '';
    foreach (get_flashes() as $f) {
        $type = in_array($f['type'], ['success', 'danger', 'warning', 'info'], true) ? $f['type'] : 'info';
        $html .= '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">'
               . e($f['message'])
               . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    }
    return $html;
}

/** Remember submitted form values (secrets are never stored) + validation errors. */
function set_old(array $data, array $errors = []): void
{
    unset($data['password'], $data['password_confirm'], $data['current_password'], $data['new_password'], $data['csrf_token'], $data['card_number'], $data['card_cvc']);
    $_SESSION['_old'] = $data;
    $_SESSION['_errors'] = $errors;
}

/** Previously submitted value for a field. */
function old(string $key, $default = '')
{
    return $_SESSION['_old'][$key] ?? $default;
}

/** Validation error message for a field (from the last redirect). */
function field_error(string $key): ?string
{
    return $_SESSION['_errors'][$key] ?? null;
}

/** Cleared automatically by includes/footer.php; call manually if you do not use it. */
function clear_old(): void
{
    unset($_SESSION['_old'], $_SESSION['_errors']);
}

/** Bootstrap is-invalid class helper. */
function invalid_class(string $key): string
{
    return field_error($key) ? ' is-invalid' : '';
}

function invalid_feedback(string $key): string
{
    $err = field_error($key);
    return $err ? '<div class="invalid-feedback d-block">' . e($err) . '</div>' : '';
}

// ---------------------------------------------------------------------------
// Formatting
// ---------------------------------------------------------------------------

function money($amount): string
{
    return CURRENCY_SYMBOL . number_format((float) $amount, 2);
}

function format_date(?string $date, string $format = 'M j, Y'): string
{
    if (!$date) return '-';
    $ts = strtotime($date);
    return $ts ? date($format, $ts) : '-';
}

function format_datetime(?string $date): string
{
    return format_date($date, 'M j, Y g:i A');
}

function time_ago(?string $date): string
{
    if (!$date) return '';
    $diff = time() - strtotime($date);
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' h ago';
    if ($diff < 604800) return floor($diff / 86400) . ' d ago';
    return format_date($date);
}

function truncate(string $text, int $length = 120): string
{
    $text = trim(strip_tags($text));
    return mb_strlen($text) > $length ? mb_substr($text, 0, $length - 1) . '…' : $text;
}

/** "pending_payment" => "Pending Payment" */
function humanize(string $value): string
{
    return ucwords(str_replace('_', ' ', $value));
}

/** Bootstrap badge for a status word. */
function status_badge(string $status): string
{
    $map = [
        'pending' => 'warning', 'requested' => 'warning', 'unpaid' => 'warning', 'draft' => 'secondary', 'open' => 'warning',
        'confirmed' => 'info', 'processing' => 'info', 'approved' => 'info', 'in_progress' => 'info', 'packed' => 'info',
        'shipped' => 'primary', 'out_for_delivery' => 'primary', 'received' => 'primary',
        'delivered' => 'success', 'paid' => 'success', 'completed' => 'success', 'active' => 'success', 'published' => 'success',
        'processed' => 'success', 'refunded' => 'success', 'visible' => 'success', 'waived' => 'success', 'actioned' => 'success',
        'cancelled' => 'danger', 'rejected' => 'danger', 'failed' => 'danger', 'suspended' => 'danger', 'hidden' => 'secondary',
        'partially_refunded' => 'info', 'archived' => 'secondary', 'inactive' => 'secondary', 'dismissed' => 'secondary',
    ];
    $cls = $map[$status] ?? 'secondary';
    return '<span class="badge text-bg-' . $cls . '">' . e(humanize($status)) . '</span>';
}

// ---------------------------------------------------------------------------
// Strings / slugs
// ---------------------------------------------------------------------------

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim((string) $text, '-');
    return $text !== '' ? $text : 'item-' . substr(bin2hex(random_bytes(3)), 0, 6);
}

/** Generate a slug unique within $table.$column (identifiers must be trusted literals). */
function unique_slug(string $table, string $text, ?int $ignoreId = null, string $column = 'slug'): string
{
    $base = slugify($text);
    $slug = $base;
    $i = 2;
    while (true) {
        $sql = "SELECT COUNT(*) FROM {$table} WHERE {$column} = ?" . ($ignoreId ? ' AND id <> ?' : '');
        $params = $ignoreId ? [$slug, $ignoreId] : [$slug];
        if ((int) db_value($sql, $params, 0) === 0) {
            return $slug;
        }
        $slug = $base . '-' . $i++;
    }
}

// ---------------------------------------------------------------------------
// Request helpers
// ---------------------------------------------------------------------------

function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/** Trimmed string from $_POST (or $_GET when $source = 'get'). */
function input(string $key, string $default = '', string $source = 'post'): string
{
    $src = $source === 'get' ? $_GET : $_POST;
    $val = $src[$key] ?? $default;
    return is_string($val) ? trim($val) : $default;
}

function input_int(string $key, int $default = 0, string $source = 'post'): int
{
    $src = $source === 'get' ? $_GET : $_POST;
    return isset($src[$key]) && is_numeric($src[$key]) ? (int) $src[$key] : $default;
}

function input_float(string $key, float $default = 0.0, string $source = 'post'): float
{
    $src = $source === 'get' ? $_GET : $_POST;
    return isset($src[$key]) && is_numeric($src[$key]) ? (float) $src[$key] : $default;
}

function get_str(string $key, string $default = ''): string
{
    return input($key, $default, 'get');
}

function get_int(string $key, int $default = 0): int
{
    return input_int($key, $default, 'get');
}

/** Show a 404 page and stop. */
function not_found(string $message = 'The page you are looking for does not exist.'): void
{
    http_response_code(404);
    $pageTitle = 'Not found';
    include _DIR_ . '/header.php';
    echo '<div class="container py-5 text-center"><h1 class="display-5">404</h1><p class="lead">' . e($message) . '</p>'
       . '<a class="btn btn-primary" href="' . e(url('index.php')) . '">Back to home</a></div>';
    include _DIR_ . '/footer.php';
    exit;
}

/** Show a 403 page and stop. */
function forbidden(string $message = 'You do not have permission to access this page.'): void
{
    http_response_code(403);
    $pageTitle = 'Access denied';
    include _DIR_ . '/header.php';
    echo '<div class="container py-5 text-center"><h1 class="display-5">403</h1><p class="lead">' . e($message) . '</p>'
       . '<a class="btn btn-primary" href="' . e(url('index.php')) . '">Back to home</a></div>';
    include _DIR_ . '/footer.php';
    exit;
}

// ---------------------------------------------------------------------------
// JSON helpers (also used by api/*.php through includes/api.php)
// ---------------------------------------------------------------------------

function json_response($data, int $status = 200): void
{
    if (!headers_sent()) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

// ---------------------------------------------------------------------------
// Logging
// ---------------------------------------------------------------------------

/** Append a line to storage/logs/<file>. */
function app_log(string $level, string $message, array $context = [], string $file = 'app.log'): void
{
    $dir = APP_ROOT . '/storage/logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    $line = sprintf("[%s] %s: %s %s\n", date('Y-m-d H:i:s'), strtoupper($level), $message, $context ? json_encode($context, JSON_UNESCAPED_SLASHES) : '');
    @file_put_contents($dir . '/' . $file, $line, FILE_APPEND | LOCK_EX);
}

/** Write an audit-trail row to activity_logs. Never throws. */
function log_activity(string $action, ?string $entityType = null, ?int $entityId = null, ?string $details = null, ?int $userId = null): void
{
    try {
        $userId = $userId ?? current_user_id();
        db_exec(
            'INSERT INTO activity_logs (user_id, action, entity_type, entity_id, details, ip_address, user_agent) VALUES (?,?,?,?,?,?,?)',
            [$userId, $action, $entityType, $entityId, $details !== null ? mb_substr($details, 0, 1000) : null, client_ip(), mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)]
        );
    } catch (Throwable $e) {
        error_log('[skinova] activity log failed: ' . $e->getMessage());
    }
}

// ---------------------------------------------------------------------------
// Settings (settings table, cached per request)
// ---------------------------------------------------------------------------

function get_setting(string $key, $default = null)
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            foreach (db_all('SELECT setting_key, setting_value FROM settings') as $row) {
                $cache[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Throwable $e) {
            $cache = [];
        }
    }
    return array_key_exists($key, $cache) && $cache[$key] !== null ? $cache[$key] : $default;
}

function set_setting(string $key, string $value): void
{
    db_exec('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)', [$key, $value]);
}

// ---------------------------------------------------------------------------
// Pagination
// ---------------------------------------------------------------------------

/** @return array{page:int,pages:int,per_page:int,total:int,offset:int} */
function paginate(int $total, int $perPage = 12, ?int $page = null): array
{
    $page = $page ?? max(1, get_int('page', 1));
    $pages = max(1, (int) ceil($total / max(1, $perPage)));
    $page = min(max(1, $page), $pages);
    return ['page' => $page, 'pages' => $pages, 'per_page' => $perPage, 'total' => $total, 'offset' => ($page - 1) * $perPage];
}

/** Render Bootstrap pagination preserving the current query string. */
function render_pagination(array $pg, ?array $query = null): string
{
    if ($pg['pages'] <= 1) return '';
    $query = $query ?? $_GET;
    unset($query['page']);
    $link = function (int $p) use ($query) {
        return '?' . http_build_query($query + ['page' => $p]);
    };
    $html = '<nav aria-label="Pagination"><ul class="pagination justify-content-center flex-wrap">';
    $html .= '<li class="page-item' . ($pg['page'] <= 1 ? ' disabled' : '') . '"><a class="page-link" href="' . e($link(max(1, $pg['page'] - 1))) . '">&laquo;</a></li>';
    $start = max(1, $pg['page'] - 2);
    $end = min($pg['pages'], $pg['page'] + 2);
    if ($start > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . e($link(1)) . '">1</a></li>';
        if ($start > 2) $html .= '<li class="page-item disabled"><span class="page-link">…</span></li>';
    }
    for ($i = $start; $i <= $end; $i++) {
        $html .= '<li class="page-item' . ($i === $pg['page'] ? ' active' : '') . '"><a class="page-link" href="' . e($link($i)) . '">' . $i . '</a></li>';
    }
    if ($end < $pg['pages']) {
        if ($end < $pg['pages'] - 1) $html .= '<li class="page-item disabled"><span class="page-link">…</span></li>';
        $html .= '<li class="page-item"><a class="page-link" href="' . e($link($pg['pages'])) . '">' . $pg['pages'] . '</a></li>';
    }
    $html .= '<li class="page-item' . ($pg['page'] >= $pg['pages'] ? ' disabled' : '') . '"><a class="page-link" href="' . e($link(min($pg['pages'], $pg['page'] + 1))) . '">&raquo;</a></li>';
    return $html . '</ul></nav>';
}

// ---------------------------------------------------------------------------
// Rate limiting (DB backed, fixed window)
// ---------------------------------------------------------------------------

/**
 * Returns true if the action is allowed, false if the limit is exceeded.
 * Example: rate_limit('login:' . client_ip(), 10, 600)
 */
function rate_limit(string $key, int $maxHits, int $windowSeconds): bool
{
    $key = mb_substr($key, 0, 190);
    $now = time();
    try {
        return (bool) db_transaction(function () use ($key, $maxHits, $windowSeconds, $now) {
            $row = db_one('SELECT hits, window_start FROM rate_limits WHERE bucket_key = ? FOR UPDATE', [$key]);
            if (!$row || ($now - (int) $row['window_start']) >= $windowSeconds) {
                db_exec('INSERT INTO rate_limits (bucket_key, hits, window_start) VALUES (?,1,?) ON DUPLICATE KEY UPDATE hits = 1, window_start = VALUES(window_start)', [$key, $now]);
                return true;
            }
            if ((int) $row['hits'] >= $maxHits) {
                return false;
            }
            db_exec('UPDATE rate_limits SET hits = hits + 1 WHERE bucket_key = ?', [$key]);
            return true;
        });
    } catch (Throwable $e) {
        error_log('[skinova] rate limit error: ' . $e->getMessage());
        return true; // fail open rather than lock everyone out
    }
}

/** Clear a rate-limit bucket (e.g. after a successful login). */
function rate_limit_reset(string $key): void
{
    try {
        db_exec('DELETE FROM rate_limits WHERE bucket_key = ?', [mb_substr($key, 0, 190)]);
    } catch (Throwable $e) {
    }
}

// ---------------------------------------------------------------------------
// Mail / SMS (log-based by default so XAMPP works without a mail server)
// ---------------------------------------------------------------------------

function send_mail(string $to, string $subject, string $htmlBody): bool
{
    app_log('info', "MAIL to={$to} subject={$subject}\n" . strip_tags(str_replace(['<br>', '</p>'], "\n", $htmlBody)), [], 'mail.log');
    if (MAIL_MODE === 'mail') {
        $headers = "MIME-Version: 1.0\r\nContent-type: text/html; charset=UTF-8\r\nFrom: " . MAIL_FROM_NAME . ' <' . MAIL_FROM . ">\r\n";
        return @mail($to, $subject, $htmlBody, $headers);
    }
    return true;
}

function send_sms(string $phone, string $message): bool
{
    app_log('info', "SMS to={$phone} message={$message}", [], 'mail.log');
    return true; // integrate an SMS gateway here when SMS_MODE != 'log'
}

/** Full absolute URL for emails (scheme + host + base path). */
function absolute_url(string $path = ''): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return ($https ? 'https://' : 'http://') . $host . url($path);
}

/** Render star icons for a 0-5 rating. */
function render_stars(float $rating, bool $withValue = false): string
{
    $html = '<span class="rating-stars text-warning" aria-label="Rated ' . number_format($rating, 1) . ' out of 5">';
    for ($i = 1; $i <= 5; $i++) {
        if ($rating >= $i) $html .= '<i class="bi bi-star-fill"></i>';
        elseif ($rating >= $i - 0.5) $html .= '<i class="bi bi-star-half"></i>';
        else $html .= '<i class="bi bi-star"></i>';
    }
    $html .= '</span>';
    if ($withValue) $html .= ' <small class="text-muted">' . number_format($rating, 1) . '</small>';
    return $html;
}
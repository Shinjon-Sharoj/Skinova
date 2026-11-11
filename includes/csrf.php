<?php
/**
 * CSRF protection.
 *
 * Forms:   <form method="post"> <?= csrf_field() ?> ... </form>
 * Actions: csrf_verify();   // at the top of every POST handler
 * AJAX:    header "X-CSRF-Token" (value from <meta name="csrf-token"> / window.SKINOVA.csrf)
 */

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_meta(): string
{
    return '<meta name="csrf-token" content="' . e(csrf_token()) . '">';
}

/** True if the request carries a valid token (POST field or X-CSRF-Token header). */
function csrf_is_valid(): bool
{
    $sent = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    return is_string($sent) && $sent !== '' && !empty($_SESSION['_csrf']) && hash_equals($_SESSION['_csrf'], $sent);
}

/**
 * Verify the CSRF token; on failure flash an error and redirect back
 * (or return JSON 419 for XHR / API requests).
 */
function csrf_verify(): void
{
    if (csrf_is_valid()) {
        return;
    }
    $wantsJson = (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest')
        || strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false
        || strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false;
    app_log('warning', 'CSRF validation failed', ['ip' => client_ip(), 'uri' => $_SERVER['REQUEST_URI'] ?? '']);
    if ($wantsJson) {
        json_response(['success' => false, 'message' => 'Your session expired. Please refresh the page and try again.'], 419);
    }
    flash('danger', 'Your session expired or the form was invalid. Please try again.');
    redirect_back('index.php');
}
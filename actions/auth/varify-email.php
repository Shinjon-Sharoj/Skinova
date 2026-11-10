<?php
require_once _DIR_ . '/../../includes/init.php';

$token  = get_str('token');
if ($token === '') {
    flash('danger', 'Invalid verification link.');
    redirect('verify-account.php');
}

$userId = consume_verification_token('email', $token);
if (!$userId) {
    flash('danger', 'This verification link is invalid or has expired. Please request a new one.');
    redirect('verify-account.php');
}

db_exec('UPDATE users SET email_verified_at = NOW() WHERE id = ? AND email_verified_at IS NULL', [$userId]);
activate_user_if_verified($userId);
log_activity('email_verified', 'user', $userId);

// Log the user in if they aren't already
if (!is_logged_in()) {
    $user = db_one('SELECT * FROM users WHERE id = ?', [$userId]);
    if ($user) {
        login_user($user);
    }
}

flash('success', 'Your email has been verified. Welcome to ' . APP_NAME . '!');
redirect('index.php');
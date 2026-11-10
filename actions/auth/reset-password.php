<?php
require_once _DIR_ . '/../../includes/init.php';
require_post();
csrf_verify();

$token = input('token');
if ($token === '') {
    flash('danger', 'Missing reset token.');
    redirect('forgot-password.php');
}

$v = new Validator($_POST);
$v->required('password', 'Password')->password('password');
$v->matches('password_confirm', 'password', 'Passwords do not match.');
if ($v->errors()) {
    set_old([], $v->errors());
    redirect('reset-password.php?token=' . urlencode($token));
}

$userId = consume_verification_token('password_reset', $token);
if (!$userId) {
    flash('danger', 'This reset link is invalid or has expired. Please request a new one.');
    redirect('forgot-password.php');
}

$hash = password_hash(input('password'), PASSWORD_BCRYPT);
db_exec('UPDATE users SET password_hash = ?, status = IF(status = ?, ?, status) WHERE id = ?',
    [$hash, 'pending', 'active', $userId]);

log_activity('password_reset', 'user', $userId);

// Invalidate all existing sessions by destroying any active one
// (user will have to log in fresh)
flash('success', 'Your password has been reset. Please sign in with your new password.');
redirect('login.php');
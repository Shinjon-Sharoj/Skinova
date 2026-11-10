<?php
require_once _DIR_ . '/../../includes/init.php';
require_post();
csrf_verify();

$user = require_login();
$otp  = input('otp');
$ip   = client_ip();

if (!rate_limit('otp:' . $ip . ':' . $user['id'], 10, 600)) {
    flash('danger', 'Too many attempts. Please wait before trying again.');
    redirect('verify-account.php');
}

if (!preg_match('/^\d{6}$/', $otp)) {
    flash('danger', 'Please enter a valid 6-digit code.');
    set_old([], ['otp' => 'Enter a 6-digit code.']);
    redirect('verify-account.php');
}

$userId = consume_verification_token('phone', $otp, (int) $user['id']);
if (!$userId) {
    flash('danger', 'Invalid or expired code. Please request a new one.');
    set_old([], ['otp' => 'Invalid or expired code.']);
    redirect('verify-account.php');
}

db_exec('UPDATE users SET phone_verified_at = NOW() WHERE id = ? AND phone_verified_at IS NULL', [$userId]);
activate_user_if_verified($userId);
log_activity('phone_verified', 'user', $userId);

flash('success', 'Phone number verified!');
redirect('index.php');
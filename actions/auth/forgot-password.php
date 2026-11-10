<?php
require_once _DIR_ . '/../../includes/init.php';
require_post();
csrf_verify();

$email = strtolower(input('email'));
$ip    = client_ip();

if (!rate_limit('forgot_pw:' . $ip, 5, 600)) {
    flash('warning', 'Too many requests. Please wait a few minutes.');
    redirect('forgot-password.php');
}

$v = new Validator($_POST);
$v->required('email', 'Email')->email('email');
if ($v->errors()) {
    set_old($_POST, $v->errors());
    redirect('forgot-password.php');
}

// Always show success to prevent email enumeration
$user = db_one('SELECT id, full_name, email FROM users WHERE email = ? AND status != ?', [$email, 'suspended']);
if ($user) {
    $token = create_verification_token((int) $user['id'], 'password_reset');
    $link  = absolute_url('reset-password.php?token=' . urlencode($token));
    send_mail($user['email'], 'Reset your ' . APP_NAME . ' password',
        '<p>Hi ' . htmlspecialchars($user['full_name'], ENT_QUOTES) . ',</p>'
        . '<p>Click below to reset your password. This link expires in 1 hour.</p>'
        . '<p><a href="' . htmlspecialchars($link, ENT_QUOTES) . '" style="background:#6c63ff;color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none;display:inline-block">Reset Password</a></p>'
        . '<p>If you did not request this, you can safely ignore this email.</p>'
    );
    log_activity('password_reset_requested', 'user', (int) $user['id']);
}

flash('success', 'If an account with that email exists, we\'ve sent a reset link. Check your inbox.');
if (SHOW_DEV_VERIFICATION && $user) {
    flash('info', 'Dev mode: check storage/logs/mail.log for the reset link.');
}
redirect('forgot-password.php');
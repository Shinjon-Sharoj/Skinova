<?php
require_once _DIR_ . '/../../includes/init.php';
require_post();
csrf_verify();

if (is_logged_in()) {
    redirect('index.php');
}

$login    = input('login');
$password = input('password');
$next     = safe_next_path(input('next'), 'index.php');
$ip       = client_ip();

if (!rate_limit('login:' . $ip, 10, 600)) {
    flash('danger', 'Too many login attempts. Please wait a few minutes before trying again.');
    set_old(['login' => $login]);
    redirect('login.php?next=' . urlencode($next));
}

$errors = [];
if ($login === '') {
    $errors['login'] = 'Please enter your email or phone.';
}
if ($password === '') {
    $errors['password'] = 'Please enter your password.';
}

if (!$errors) {
    $user = find_user_by_login($login);
    if (!$user || !password_verify($password, $user['password_hash'])) {
        $errors['login'] = 'Email/phone or password is incorrect.';
        log_activity('login_failed', 'user', null, 'login=' . $login);
    } elseif ($user['status'] === 'suspended') {
        $errors['login'] = 'Your account has been suspended. Please contact support.';
    }
}

if ($errors) {
    set_old(['login' => $login], $errors);
    redirect('login.php?next=' . urlencode($next));
}

// Rehash if needed
if (password_needs_rehash($user['password_hash'], PASSWORD_BCRYPT)) {
    $newHash = password_hash($password, PASSWORD_BCRYPT);
    db_exec('UPDATE users SET password_hash = ? WHERE id = ?', [$newHash, $user['id']]);
}

rate_limit_reset('login:' . $ip);
login_user($user);
log_activity('login', 'user', (int) $user['id'], 'role=' . $user['role']);

// Pending users need to verify first
if ($user['status'] === 'pending') {
    flash('warning', 'Please verify your account to access all features.');
    redirect('verify-account.php');
}

redirect(home_url_for_role($user['role']) ?: $next);
<?php
require_once __DIR__ . '/../../includes/init.php';
require_post();
csrf_verify();
$user = require_login();

$ip = client_ip();
if (!rate_limit('change_pw:' . $user['id'], 5, 600)) {
    flash('danger', 'Too many attempts. Please wait.');
    redirect('edit-profile.php');
}

$v = new Validator($_POST);
$v->required('current_password', 'Current password');
$v->required('new_password', 'New password')->password('new_password');
$v->matches('password_confirm', 'new_password', 'Passwords do not match.');
$errors = $v->errors();

if (!$errors) {
    $dbUser = db_one('SELECT password_hash FROM users WHERE id = ?', [$user['id']]);
    if (!password_verify(input('current_password'), $dbUser['password_hash'])) {
        $errors['current_password'] = 'Current password is incorrect.';
    }
}

if ($errors) {
    set_old([], $errors);
    redirect('edit-profile.php');
}

$hash = password_hash(input('new_password'), PASSWORD_BCRYPT);
db_exec('UPDATE users SET password_hash = ? WHERE id = ?', [$hash, $user['id']]);
log_activity('password_changed', 'user', (int) $user['id']);

flash('success', 'Password updated successfully.');
redirect('edit-profile.php');

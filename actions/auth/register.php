<?php
require_once _DIR_ . '/../../includes/init.php';
require_post();
csrf_verify();

if (is_logged_in()) {
    redirect('index.php');
}

$ip = client_ip();
if (!rate_limit('register:' . $ip, 5, 3600)) {
    flash('danger', 'Too many registration attempts. Please try again later.');
    redirect('register.php');
}

$v = new Validator($_POST);
$v->required('full_name', 'Full name')->max('full_name', 120, 'Full name');
$v->required('email', 'Email')->email('email');
$v->max('email', 190, 'Email');
if (!empty($_POST['phone'])) {
    $v->phone('phone');
}
$v->required('password', 'Password')->password('password');
$v->matches('password_confirm', 'password', 'Passwords do not match.');
$v->checked('terms', 'You must accept the terms to continue.');
$errors = $v->errors();

if (!$errors) {
    $email = strtolower(input('email'));
    if (db_value('SELECT id FROM users WHERE email = ?', [$email])) {
        $errors['email'] = 'An account with this email already exists.';
    }
    $phone = '';
    if (!empty($_POST['phone'])) {
        $phone = normalize_phone(input('phone'));
        if ($phone && db_value('SELECT id FROM users WHERE phone = ?', [$phone])) {
            $errors['phone'] = 'This phone number is already registered.';
        }
    }
}

if ($errors) {
    set_old($_POST, $errors);
    redirect('register.php');
}

$hash   = password_hash(input('password'), PASSWORD_BCRYPT);
$userId = db_insert(
    'INSERT INTO users (full_name, email, phone, password_hash, role, status) VALUES (?,?,?,?,?,?)',
    [clean_text(input('full_name')), $email, $phone ?: null, $hash, ROLE_CUSTOMER, 'pending']
);
db_exec('INSERT INTO user_profiles (user_id) VALUES (?)', [$userId]);

// Send email verification
$token = create_verification_token((int) $userId, 'email');
$link  = absolute_url('actions/auth/verify-email.php?token=' . urlencode($token));
send_mail($email, 'Verify your ' . APP_NAME . ' account',
    '<p>Hi ' . htmlspecialchars(input('full_name'), ENT_QUOTES) . ',</p>'
    . '<p>Thanks for joining ' . APP_NAME . '! Click the button below to verify your email address:</p>'
    . '<p><a href="' . htmlspecialchars($link, ENT_QUOTES) . '" style="background:#6c63ff;color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none;display:inline-block">Verify Email</a></p>'
    . '<p>Or paste this link: ' . htmlspecialchars($link, ENT_QUOTES) . '</p>'
    . '<p>This link expires in 24 hours.</p>'
);

if ($phone) {
    $otp = create_verification_token((int) $userId, 'phone');
    send_sms($phone, APP_NAME . ' verification code: ' . $otp . '. Valid for 24 hours.');
}

log_activity('register', 'user', (int) $userId, 'email=' . $email);
login_user(['id' => $userId, 'role' => ROLE_CUSTOMER, 'status' => 'pending', 'password_hash' => '']);

flash('success', 'Account created! Please check your email to verify your account.');
if (SHOW_DEV_VERIFICATION) {
    flash('info', 'Dev mode: verification link is in storage/logs/mail.log');
}
redirect('verify-account.php');
<?php
require_once __DIR__ . '/../../includes/init.php';
require_post();
csrf_verify();
$user = require_login();

$v = new Validator($_POST);
$v->required('full_name', 'Full name')->max('full_name', 120, 'Full name');
if (!empty($_POST['phone'])) {
    $v->phone('phone');
}
if (!empty($_POST['date_of_birth'])) {
    $v->date('date_of_birth');
}
$errors = $v->errors();

// Phone uniqueness check
if (!$errors && !empty($_POST['phone'])) {
    $phone = normalize_phone(input('phone'));
    $existing = db_value('SELECT id FROM users WHERE phone = ? AND id != ?', [$phone, $user['id']]);
    if ($existing) {
        $errors['phone'] = 'This phone number is already in use.';
    }
}

if ($errors) {
    set_old($_POST, $errors);
    redirect('edit-profile.php');
}

$phone = !empty($_POST['phone']) ? normalize_phone(input('phone')) : null;

db_exec('UPDATE users SET full_name = ?, phone = ? WHERE id = ?',
    [clean_text(input('full_name')), $phone, $user['id']]);

$gender = in_array(input('gender'), ['female','male','other','prefer_not_to_say'], true) ? input('gender') : null;
$dob    = input('date_of_birth') ?: null;
$bio    = input('bio') ? mb_substr(clean_text(input('bio')), 0, 500) : null;
$mktOpt = isset($_POST['marketing_opt_in']) ? 1 : 0;

// Handle avatar upload
$avatarPath = null;
if (!empty($_FILES['avatar']['size'])) {
    $result = save_image_upload($_FILES['avatar'], 'profiles');
    if (isset($result['error'])) {
        flash('danger', 'Avatar upload failed: ' . $result['error']);
        redirect('edit-profile.php');
    }
    $avatarPath = $result['path'];
    // Delete old avatar
    $oldAvatar = db_value('SELECT avatar FROM user_profiles WHERE user_id = ?', [$user['id']]);
    if ($oldAvatar) delete_upload($oldAvatar);
}

db_exec(
    'INSERT INTO user_profiles (user_id, date_of_birth, gender, avatar, bio, marketing_opt_in)
     VALUES (?, ?, ?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE date_of_birth = VALUES(date_of_birth), gender = VALUES(gender),
         avatar = IF(VALUES(avatar) IS NOT NULL, VALUES(avatar), avatar),
         bio = VALUES(bio), marketing_opt_in = VALUES(marketing_opt_in)',
    [$user['id'], $dob, $gender, $avatarPath, $bio, $mktOpt]
);

log_activity('profile_updated', 'user', (int) $user['id']);
flash('success', 'Profile updated successfully.');
redirect('profile.php');

<?php
require_once __DIR__ . '/../../includes/init.php';
require_post();
csrf_verify();
$user = require_login();

$editId = get_int('id');

$v = new Validator($_POST);
$v->required('recipient_name', 'Recipient name')->max('recipient_name', 120, 'Recipient name');
$v->required('phone', 'Phone');
$v->required('address_line1', 'Address')->max('address_line1', 190, 'Address');
$v->required('city', 'City');
$v->required('postal_code', 'Postal code');
$v->required('country', 'Country');
$errors = $v->errors();

if ($errors) {
    set_old($_POST, $errors);
    redirect('addresses.php' . ($editId ? '?edit=' . $editId : '?new=1'));
}

$isDefault = isset($_POST['is_default']) ? 1 : 0;

if ($isDefault) {
    db_exec('UPDATE user_addresses SET is_default = 0 WHERE user_id = ?', [$user['id']]);
}

$data = [
    clean_text(input('label') ?: 'Home'),
    clean_text(input('recipient_name')),
    input('phone'),
    clean_text(input('address_line1')),
    input('address_line2') ? clean_text(input('address_line2')) : null,
    clean_text(input('city')),
    input('state') ? clean_text(input('state')) : null,
    input('postal_code'),
    clean_text(input('country') ?: DEFAULT_COUNTRY),
    $isDefault,
];

if ($editId) {
    $existing = db_one('SELECT id FROM user_addresses WHERE id = ? AND user_id = ?', [$editId, $user['id']]);
    if (!$existing) not_found();
    db_exec(
        'UPDATE user_addresses SET label=?, recipient_name=?, phone=?, address_line1=?, address_line2=?, city=?, state=?, postal_code=?, country=?, is_default=? WHERE id=?',
        array_merge($data, [$editId])
    );
    flash('success', 'Address updated.');
} else {
    $count = (int) db_value('SELECT COUNT(*) FROM user_addresses WHERE user_id = ?', [$user['id']], 0);
    if ($count >= 10) {
        flash('warning', 'You can save a maximum of 10 addresses.');
        redirect('addresses.php');
    }
    db_insert(
        'INSERT INTO user_addresses (user_id, label, recipient_name, phone, address_line1, address_line2, city, state, postal_code, country, is_default) VALUES (?,?,?,?,?,?,?,?,?,?,?)',
        array_merge([$user['id']], $data)
    );
    flash('success', 'Address saved.');
}

// Ensure there's always a default
$hasDefault = db_value('SELECT id FROM user_addresses WHERE user_id = ? AND is_default = 1 LIMIT 1', [$user['id']]);
if (!$hasDefault) {
    db_exec('UPDATE user_addresses SET is_default = 1 WHERE user_id = ? ORDER BY id ASC LIMIT 1', [$user['id']]);
}

redirect('addresses.php');

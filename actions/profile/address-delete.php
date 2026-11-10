<?php
require_once __DIR__ . '/../../includes/init.php';
require_post();
csrf_verify();
$user = require_login();

$id = input_int('id');
$addr = db_one('SELECT * FROM user_addresses WHERE id = ? AND user_id = ?', [$id, $user['id']]);
if (!$addr) {
    flash('danger', 'Address not found.');
    redirect('addresses.php');
}

db_exec('DELETE FROM user_addresses WHERE id = ?', [$id]);

// If deleted address was default, set the first remaining as default
if ($addr['is_default']) {
    db_exec('UPDATE user_addresses SET is_default = 1 WHERE user_id = ? ORDER BY id ASC LIMIT 1', [$user['id']]);
}

flash('success', 'Address deleted.');
redirect('addresses.php');

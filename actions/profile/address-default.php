<?php
require_once __DIR__ . '/../../includes/init.php';
require_post();
csrf_verify();
$user = require_login();

$id = input_int('id');
$addr = db_one('SELECT id FROM user_addresses WHERE id = ? AND user_id = ?', [$id, $user['id']]);
if (!$addr) {
    flash('danger', 'Address not found.');
    redirect('addresses.php');
}

db_exec('UPDATE user_addresses SET is_default = 0 WHERE user_id = ?', [$user['id']]);
db_exec('UPDATE user_addresses SET is_default = 1 WHERE id = ?', [$id]);

flash('success', 'Default address updated.');
redirect('addresses.php');

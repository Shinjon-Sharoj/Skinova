<?php
require_once _DIR_ . '/../../includes/init.php';
require_post();
csrf_verify();
$user = require_login();

$id = input_int('id');
if ($id) {
    db_exec('UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id = ? AND user_id = ?', [$id, $user['id']]);
}
redirect_back('notifications.php');
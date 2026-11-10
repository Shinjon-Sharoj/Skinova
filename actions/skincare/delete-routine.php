<?php
require_once __DIR__ . '/../../includes/init.php';
require_post();
csrf_verify();
$user = require_login();

$routineId = input_int('routine_id');
$routine   = db_one('SELECT id FROM skincare_routines WHERE id = ? AND user_id = ?', [$routineId, $user['id']]);
if (!$routine) {
    flash('danger', 'Routine not found.');
    redirect('skincare-routine.php');
}

db_exec('DELETE FROM skincare_routines WHERE id = ?', [$routineId]);
flash('success', 'Routine deleted.');
redirect('skincare-routine.php');

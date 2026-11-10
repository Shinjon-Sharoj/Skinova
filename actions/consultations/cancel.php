<?php
require_once _DIR_ . '/../../includes/init.php';
require_once _DIR_ . '/../../includes/consultation.php';
require_post();
csrf_verify();
$user = require_login();

$id           = input_int('consultation_id');
$consultation = get_consultation($id, (int) $user['id']);

if (!$consultation) not_found();
if (!in_array($consultation['status'], ['requested', 'confirmed'], true)) {
    flash('danger', 'This consultation cannot be cancelled at this stage.');
    redirect('consultation-details.php?id=' . $id);
}

db_exec("UPDATE consultations SET status = 'cancelled', updated_at = NOW() WHERE id = ?", [$id]);

// Notify consultant
notify((int) db_value('SELECT user_id FROM consultants WHERE id = ?', [$consultation['consultant_id']]),
    'consultation', 'Consultation cancelled',
    $user['full_name'] . ' cancelled the consultation: ' . $consultation['subject'],
    'consultant/consultation-details.php?id=' . $id
);

log_activity('consultation_cancelled', 'consultation', $id, null, (int) $user['id']);
flash('success', 'Consultation cancelled.');
redirect('consultations.php');
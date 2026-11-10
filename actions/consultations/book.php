<?php
require_once _DIR_ . '/../../includes/init.php';
require_once _DIR_ . '/../../includes/consultation.php';
require_post();
csrf_verify();
$user = require_login();

$consultantId = input_int('consultant_id');
$subject      = mb_substr(clean_text(input('subject')), 0, 190);
$details      = input('concern_details') ? mb_substr(clean_text(input('concern_details')), 0, 2000) : null;
$preferredAt  = input('preferred_at') ?: null;
$shareProfile = isset($_POST['share_skin_profile']) ? 1 : 0;

$v = new Validator($_POST);
$v->required('consultant_id', 'Consultant');
$v->required('subject', 'Subject')->max('subject', 190, 'Subject');
$errors = $v->errors();

if (!$errors) {
    $cons = db_one("SELECT * FROM consultants WHERE id = ? AND status = 'active' AND is_available = 1", [$consultantId]);
    if (!$cons) $errors['consultant_id'] = 'Selected consultant is not available.';
}

if ($errors) {
    set_old($_POST, $errors);
    redirect('consultations.php');
}

// Prevent duplicate open consultation with same consultant
$existing = db_value(
    "SELECT id FROM consultations WHERE user_id = ? AND consultant_id = ? AND status IN ('requested','confirmed','in_progress')",
    [$user['id'], $consultantId]
);
if ($existing) {
    flash('info', 'You already have an open consultation with this consultant.');
    redirect('consultation-details.php?id=' . $existing);
}

if (!rate_limit('book_consult:' . $user['id'], 5, 3600)) {
    flash('warning', 'Too many booking attempts. Please wait.');
    redirect('consultations.php');
}

$consultationId = db_insert(
    "INSERT INTO consultations (user_id, consultant_id, subject, concern_details, preferred_at, status, fee, payment_status, share_skin_profile)
     VALUES (?,?,?,?,?,?,?,?,?)",
    [$user['id'], $consultantId, $subject, $details,
     $preferredAt ? date('Y-m-d H:i:s', strtotime($preferredAt)) : null,
     'requested', (float) $cons['consultation_fee'],
     $cons['consultation_fee'] > 0 ? 'unpaid' : 'waived',
     $shareProfile]
);

// Notify consultant
notify((int) $cons['user_id'], 'consultation',
    'New consultation request',
    $user['full_name'] . ' has requested a consultation: ' . $subject,
    'consultant/consultation-details.php?id=' . $consultationId
);

log_activity('consultation_booked', 'consultation', $consultationId, 'consultant_id=' . $consultantId, (int) $user['id']);
flash('success', 'Consultation request sent! The consultant will confirm shortly.');
redirect('consultation-details.php?id=' . $consultationId);
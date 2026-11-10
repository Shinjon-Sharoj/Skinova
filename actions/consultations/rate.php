<?php
require_once _DIR_ . '/../../includes/init.php';
require_once _DIR_ . '/../../includes/consultation.php';
require_post();
csrf_verify();
$user = require_login();

$id     = input_int('consultation_id');
$rating = input_int('user_rating');
$fb     = input('user_feedback') ? mb_substr(clean_text(input('user_feedback')), 0, 500) : null;

$consultation = get_consultation($id, (int) $user['id']);
if (!$consultation || $consultation['status'] !== 'completed') not_found();
if ($consultation['user_rating']) {
    flash('info', 'You have already rated this consultation.');
    redirect('consultation-details.php?id=' . $id);
}

if ($rating < 1 || $rating > 5) {
    flash('danger', 'Please select a rating between 1 and 5.');
    redirect('consultation-details.php?id=' . $id);
}

db_exec('UPDATE consultations SET user_rating = ?, user_feedback = ?, updated_at = NOW() WHERE id = ?', [$rating, $fb, $id]);
refresh_consultant_rating((int) $consultation['consultant_id']);

log_activity('consultation_rated', 'consultation', $id, 'rating=' . $rating, (int) $user['id']);
flash('success', 'Thank you for your feedback!');
redirect('consultation-details.php?id=' . $id);
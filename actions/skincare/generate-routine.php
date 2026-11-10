<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/ai.php';
require_post();
csrf_verify();
$user = require_login();

$profile = get_skin_profile((int) $user['id']);
if (!$profile['profile'] || !$profile['profile']['consent_given']) {
    flash('warning', 'Please complete and save your skin profile first.');
    redirect('skincare-profile.php');
}

if (!rate_limit('gen_routine:' . $user['id'], 3, 3600)) {
    flash('warning', 'You\'ve generated several routines recently. Please wait before generating another.');
    redirect('skincare-routine.php');
}

$routineId = generate_routine((int) $user['id']);
if (!$routineId) {
    flash('danger', 'Could not generate a routine. Please try again.');
    redirect('skincare-routine.php');
}

flash('success', 'Your personalised routine has been generated!');
redirect('skincare-routine.php?id=' . $routineId);

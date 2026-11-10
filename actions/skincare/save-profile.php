<?php
require_once __DIR__ . '/../../includes/init.php';
require_post();
csrf_verify();
$user = require_login();

if (!isset($_POST['consent'])) {
    flash('danger', 'You must give consent to save your skin profile.');
    redirect('skincare-profile.php');
}

$skinType  = in_array(input('skin_type'), SKIN_TYPES, true) ? input('skin_type') : null;
$age       = input_int('age') ?: null;
$budgetMin = input_float('budget_min') ?: null;
$budgetMax = input_float('budget_max') ?: null;
$complexity = in_array(input('routine_complexity'), ['minimal','standard','extended'], true) ? input('routine_complexity') : 'standard';
$allergies  = input('allergies') ? mb_substr(clean_text(input('allergies')), 0, 500) : null;
$prefs      = input('preferences') ? mb_substr(clean_text(input('preferences')), 0, 500) : null;

db_exec(
    'INSERT INTO skin_profiles (user_id, skin_type, age, budget_min, budget_max, preferences, allergies, routine_complexity, consent_given)
     VALUES (?,?,?,?,?,?,?,?,1)
     ON DUPLICATE KEY UPDATE skin_type=VALUES(skin_type), age=VALUES(age), budget_min=VALUES(budget_min),
         budget_max=VALUES(budget_max), preferences=VALUES(preferences), allergies=VALUES(allergies),
         routine_complexity=VALUES(routine_complexity), consent_given=1',
    [$user['id'], $skinType, $age, $budgetMin, $budgetMax, $prefs, $allergies, $complexity]
);

// Also update user_profiles consent flag
db_exec('UPDATE user_profiles SET skin_data_consent = 1, skin_data_consent_at = NOW() WHERE user_id = ?', [$user['id']]);

// Skin concerns
$concerns = array_map('intval', (array) ($_POST['concerns'] ?? []));
db_exec('DELETE FROM user_skin_concerns WHERE user_id = ?', [$user['id']]);
foreach ($concerns as $cid) {
    if ($cid > 0) {
        db_exec('INSERT IGNORE INTO user_skin_concerns (user_id, skin_concern_id, severity) VALUES (?,?,?)', [$user['id'], $cid, 'moderate']);
    }
}

// Ingredient sensitivities
$sens = array_map('intval', (array) ($_POST['sensitivities'] ?? []));
db_exec('DELETE FROM user_ingredient_sensitivities WHERE user_id = ?', [$user['id']]);
foreach ($sens as $iid) {
    if ($iid > 0) {
        db_exec('INSERT IGNORE INTO user_ingredient_sensitivities (user_id, ingredient_id) VALUES (?,?)', [$user['id'], $iid]);
    }
}

log_activity('skin_profile_saved', 'user', (int) $user['id']);
flash('success', 'Skin profile saved!');
redirect('skincare-profile.php');

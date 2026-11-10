<?php
/**
 * API: Skincare Routines
 * POST action=generate -> {routine_id}
 * GET  action=list     -> {routines}
 * GET  action=detail   ?id= -> {routine, steps}
 */
require_once _DIR_ . '/../includes/init.php';
require_once _DIR_ . '/../includes/ai.php';

$user = api_init(['methods' => ['GET', 'POST'], 'auth' => true, 'rate' => [10, 3600]]);
$in   = api_input();
$uid  = (int) $user['id'];
$action = $in['action'] ?? 'list';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'list') {
        $routines = db_all('SELECT * FROM skincare_routines WHERE user_id = ? ORDER BY created_at DESC LIMIT 20', [$uid]);
        api_ok(['routines' => $routines]);
    }
    if ($action === 'detail') {
        $id      = (int) ($in['id'] ?? 0);
        $routine = db_one('SELECT * FROM skincare_routines WHERE id = ? AND user_id = ?', [$id, $uid]);
        if (!$routine) api_fail('Not found.', 404);
        $steps   = db_all('SELECT * FROM routine_steps WHERE routine_id = ? ORDER BY period, step_order', [$id]);
        api_ok(['routine' => $routine, 'steps' => $steps]);
    }
    api_fail('Unknown action.', 400);
}

// POST generate
$profile = get_skin_profile($uid);
if (!$profile['profile'] || !$profile['profile']['consent_given']) {
    api_fail('Skin profile not set up or consent not given.', 422);
}
$routineId = generate_routine($uid);
if (!$routineId) api_fail('Could not generate routine.', 500);
api_ok(['routine_id' => $routineId], 'Routine generated!');
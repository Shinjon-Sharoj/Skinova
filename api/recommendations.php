<?php
/**
 * API: Recommendations
 * GET  action=list  -> {products, source, provider}
 * POST action=click {recommendation_id} -> {}
 */
require_once _DIR_ . '/../includes/init.php';
require_once _DIR_ . '/../includes/ai.php';

$user = api_init(['methods' => ['GET', 'POST'], 'auth' => true, 'rate' => [30, 60]]);
$in   = api_input();
$uid  = (int) $user['id'];
$action = $in['action'] ?? 'list';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $recType = $in['type'] ?? 'personalized';
    $limit   = min(12, (int) ($in['limit'] ?? 8));
    $recs    = generate_recommendations($uid, $recType, $limit);
    api_ok($recs);
}

if ($action === 'click') {
    $recId = (int) ($in['recommendation_id'] ?? 0);
    db_exec('UPDATE ai_recommendations SET clicked = 1 WHERE id = ? AND user_id = ?', [$recId, $uid]);
    api_ok([]);
}

api_fail('Unknown action.', 400);
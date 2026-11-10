<?php
/**
 * API: Skin Analysis
 * GET  action=list -> {analyses}
 * GET  action=detail ?id= -> {analysis}
 * POST action=delete {id} -> {}
 */
require_once _DIR_ . '/../includes/init.php';

$user = api_init(['methods' => ['GET', 'POST'], 'auth' => true, 'rate' => [30, 60]]);
$in   = api_input();
$uid  = (int) $user['id'];
$action = $in['action'] ?? 'list';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'list') {
        $analyses = db_all(
            'SELECT id, method, provider, summary, created_at FROM skin_analyses WHERE user_id = ? ORDER BY created_at DESC LIMIT 20',
            [$uid]
        );
        api_ok(['analyses' => $analyses]);
    }
    if ($action === 'detail') {
        $id  = (int) ($in['id'] ?? 0);
        $row = db_one('SELECT * FROM skin_analyses WHERE id = ? AND user_id = ?', [$id, $uid]);
        if (!$row) api_fail('Not found.', 404);
        // Don't return raw image path
        unset($row['image_path']);
        api_ok(['analysis' => $row]);
    }
    api_fail('Unknown action.', 400);
}

if ($action === 'delete') {
    $id  = (int) ($in['id'] ?? 0);
    $row = db_one('SELECT * FROM skin_analyses WHERE id = ? AND user_id = ?', [$id, $uid]);
    if (!$row) api_fail('Not found.', 404);
    delete_upload($row['image_path']);
    db_exec('DELETE FROM skin_analyses WHERE id = ?', [$id]);
    api_ok([], 'Analysis deleted.');
}

api_fail('Unknown action.', 400);
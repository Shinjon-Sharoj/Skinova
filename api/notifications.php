<?php
/**
 * API: Notifications
 * GET  action=count       -> {unread}
 * GET  action=list        -> {notifications:[...]}
 * POST action=mark_read   -> {}  body: {id}
 * POST action=mark_all    -> {}
 */
require_once _DIR_ . '/../includes/init.php';

$user = api_init(['methods' => ['GET', 'POST'], 'auth' => true, 'rate' => [120, 60]]);
$in     = api_input();
$action = $in['action'] ?? 'count';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'count') {
        api_ok(['unread' => unread_notification_count((int) $user['id'])]);
    }
    if ($action === 'list') {
        $notifs = db_all(
            'SELECT id, type, title, message, link, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 20',
            [$user['id']]
        );
        api_ok(['notifications' => $notifs, 'unread' => unread_notification_count((int) $user['id'])]);
    }
    api_fail('Unknown action.', 400);
}

// POST
if ($action === 'mark_read') {
    $id = (int) ($in['id'] ?? 0);
    if ($id) {
        db_exec('UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id = ? AND user_id = ?', [$id, $user['id']]);
    }
    api_ok(['unread' => unread_notification_count((int) $user['id'])]);
}
if ($action === 'mark_all') {
    mark_all_notifications_read((int) $user['id']);
    api_ok(['unread' => 0]);
}
api_fail('Unknown action.', 400);
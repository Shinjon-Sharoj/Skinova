<?php
/**
 * API: Consultation Messages (polling)
 * GET  action=poll  ?consultation_id=&since_id= -> {messages, unread}
 * POST action=read  {consultation_id}           -> {}
 */
require_once _DIR_ . '/../includes/init.php';
require_once _DIR_ . '/../includes/consultation.php';

$user = api_init(['methods' => ['GET', 'POST'], 'auth' => true, 'rate' => [120, 60]]);
$in   = api_input();
$uid  = (int) $user['id'];
$action = $in['action'] ?? 'poll';

$consultationId = (int) ($in['consultation_id'] ?? 0);

// Verify access
$cn = db_one(
    "SELECT cn.*, c.user_id AS consultant_user_id FROM consultations cn JOIN consultants c ON c.id=cn.consultant_id WHERE cn.id=?",
    [$consultationId]
);
if (!$cn) api_fail('Not found.', 404);
if ($cn['user_id'] != $uid && $cn['consultant_user_id'] != $uid) api_fail('Access denied.', 403);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sinceId  = (int) ($in['since_id'] ?? 0);
    $messages = get_consultation_messages($consultationId, $sinceId);
    // Format attachment URLs
    foreach ($messages as &$m) {
        if ($m['attachment_path']) {
            $m['attachment_url'] = private_file_url('message', $m['id']);
        }
    }
    unset($m);
    api_ok(['messages' => $messages, 'unread' => unread_message_count_for_user($uid)]);
}

// POST mark read
mark_messages_read($consultationId, $uid);
api_ok(['unread' => unread_message_count_for_user($uid)]);
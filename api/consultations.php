<?php
/**
 * API: Consultations
 * GET  action=list   -> {consultations}
 * GET  action=detail ?id= -> {consultation}
 * POST action=book   {consultant_id, subject, concern_details?, preferred_at?, share_skin_profile?}
 * POST action=cancel {consultation_id}
 */
require_once _DIR_ . '/../includes/init.php';
require_once _DIR_ . '/../includes/consultation.php';

$user = api_init(['methods' => ['GET', 'POST'], 'auth' => true, 'rate' => [30, 60]]);
$in   = api_input();
$uid  = (int) $user['id'];
$action = $in['action'] ?? 'list';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'list') {
        $rows = db_all(
            "SELECT cn.id, cn.subject, cn.status, cn.payment_status, cn.fee, cn.created_at, u.full_name AS consultant_name
             FROM consultations cn JOIN consultants c ON c.id=cn.consultant_id JOIN users u ON u.id=c.user_id
             WHERE cn.user_id=? ORDER BY cn.created_at DESC LIMIT 50",
            [$uid]
        );
        api_ok(['consultations' => $rows]);
    }
    if ($action === 'detail') {
        $cn = get_consultation((int) ($in['id'] ?? 0), $uid);
        if (!$cn) api_fail('Not found.', 404);
        $msgs = get_consultation_messages((int) $in['id']);
        api_ok(['consultation' => $cn, 'messages' => $msgs]);
    }
    api_fail('Unknown action.', 400);
}

if ($action === 'book') {
    $consId = (int) ($in['consultant_id'] ?? 0);
    $subj   = mb_substr(trim((string) ($in['subject'] ?? '')), 0, 190);
    if (!$consId || $subj === '') api_fail('consultant_id and subject are required.', 422);
    $cons = db_one("SELECT * FROM consultants WHERE id=? AND status='active' AND is_available=1", [$consId]);
    if (!$cons) api_fail('Consultant not available.', 422);
    $id = db_insert(
        "INSERT INTO consultations (user_id,consultant_id,subject,concern_details,status,fee,payment_status,share_skin_profile) VALUES (?,?,?,?,?,?,?,?)",
        [$uid, $consId, $subj, mb_substr((string)($in['concern_details']??''),0,2000) ?: null,
         'requested', (float)$cons['consultation_fee'], $cons['consultation_fee']>0?'unpaid':'waived',
         !empty($in['share_skin_profile'])?1:0]
    );
    api_ok(['consultation_id' => $id], 'Consultation requested.');
}

if ($action === 'cancel') {
    $cn = get_consultation((int) ($in['consultation_id'] ?? 0), $uid);
    if (!$cn) api_fail('Not found.', 404);
    if (!in_array($cn['status'], ['requested','confirmed'], true)) api_fail('Cannot cancel now.', 422);
    db_exec("UPDATE consultations SET status='cancelled', updated_at=NOW() WHERE id=?", [$cn['id']]);
    api_ok([], 'Cancelled.');
}

api_fail('Unknown action.', 400);
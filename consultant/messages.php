<?php
require_once _DIR_ . '/../includes/init.php';
require_once _DIR_ . '/../includes/consultation.php';
$authUser = require_role(ROLE_CONSULTANT);

$pageTitle = 'Messages';
$panel     = 'consultant';

$consultant = get_consultant_by_user_id((int) $authUser['id']);
if (!$consultant) redirect('consultant/dashboard.php');

$convs = db_all(
    "SELECT cn.id, cn.subject, cn.status, cn.updated_at,
            u.full_name AS customer_name, up.avatar AS customer_avatar,
            (SELECT COUNT(*) FROM consultation_messages m WHERE m.consultation_id=cn.id AND m.sender_id != ? AND m.is_read=0) AS unread_count,
            (SELECT m2.message FROM consultation_messages m2 WHERE m2.consultation_id=cn.id ORDER BY m2.id DESC LIMIT 1) AS last_message,
            (SELECT m3.created_at FROM consultation_messages m3 WHERE m3.consultation_id=cn.id ORDER BY m3.id DESC LIMIT 1) AS last_message_at
     FROM consultations cn
     JOIN users u ON u.id=cn.user_id
     LEFT JOIN user_profiles up ON up.user_id=cn.user_id
     WHERE cn.consultant_id=?
     ORDER BY COALESCE(last_message_at, cn.created_at) DESC",
    [$authUser['id'], $consultant['id']]
);

include _DIR_ . '/../admin/includes/header.php';
admin_page_header('Messages');
?>
<?php if ($convs): ?>
<div class="card shadow-sm">
    <div class="list-group list-group-flush">
        <?php foreach ($convs as $conv): ?>
        <a href="<?= e(url('consultant/consultation-details.php?id=' . $conv['id'])) ?>"
           class="list-group-item list-group-item-action py-3 <?= $conv['unread_count'] > 0 ? 'fw-semibold' : '' ?>">
            <div class="d-flex gap-3 align-items-center">
                <div class="position-relative flex-shrink-0">
                    <img src="<?= e(media_url($conv['customer_avatar'] ?? null, 'avatar')) ?>" class="rounded-circle" width="44" height="44" style="object-fit:cover" alt="">
                    <?php if ($conv['unread_count'] > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:.55rem"><?= $conv['unread_count'] ?></span>
                    <?php endif; ?>
                </div>
                <div class="flex-grow-1 overflow-hidden">
                    <div class="d-flex justify-content-between">
                        <span class="small"><?= e($conv['customer_name']) ?></span>
                        <span class="text-muted small fw-normal"><?= $conv['last_message_at'] ? time_ago($conv['last_message_at']) : '' ?></span>
                    </div>
                    <div class="small text-truncate"><?= e(truncate($conv['subject'], 60)) ?></div>
                    <?php if ($conv['last_message']): ?><div class="text-muted small fw-normal text-truncate"><?= e(truncate($conv['last_message'], 60)) ?></div><?php endif; ?>
                </div>
                <?= status_badge($conv['status']) ?>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</div>
<?php else: ?>
<div class="text-center py-5 text-muted">
    <i class="bi bi-chat-dots" style="font-size:3rem"></i>
    <p class="mt-2">No messages yet.</p>
</div>
<?php endif; ?>
<?php include _DIR_ . '/../admin/includes/footer.php'; ?>
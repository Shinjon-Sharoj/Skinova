<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/consultation.php';
require_once __DIR__ . '/includes/auth-check.php';

$pageTitle = 'Messages';
$extraCss  = ['css/consultation.css'];

// All consultations with at least one message, or active ones
$convs = db_all(
    "SELECT cn.id, cn.subject, cn.status, cn.updated_at,
            u.full_name AS consultant_name, up.avatar AS consultant_avatar,
            (SELECT COUNT(*) FROM consultation_messages m WHERE m.consultation_id=cn.id AND m.sender_id != ? AND m.is_read=0) AS unread_count,
            (SELECT m2.message FROM consultation_messages m2 WHERE m2.consultation_id=cn.id ORDER BY m2.id DESC LIMIT 1) AS last_message,
            (SELECT m3.created_at FROM consultation_messages m3 WHERE m3.consultation_id=cn.id ORDER BY m3.id DESC LIMIT 1) AS last_message_at
     FROM consultations cn
     JOIN consultants c ON c.id=cn.consultant_id
     JOIN users u ON u.id=c.user_id
     LEFT JOIN user_profiles up ON up.user_id=c.user_id
     WHERE cn.user_id=?
     ORDER BY COALESCE(last_message_at, cn.created_at) DESC",
    [$authUser['id'], $authUser['id']]
);

include __DIR__ . '/includes/header.php';
?>
<div class="container py-4">
    <div class="row">
        <div class="col-lg-3 mb-4"><?php include __DIR__ . '/includes/sidebar.php'; ?></div>
        <div class="col-lg-9">
            <h2 class="h4 mb-4">Messages</h2>

            <?php if ($convs): ?>
            <div class="list-group shadow-sm">
                <?php foreach ($convs as $conv): ?>
                <a href="<?= e(url('consultation-details.php?id=' . $conv['id'])) ?>"
                   class="list-group-item list-group-item-action py-3 <?= $conv['unread_count'] > 0 ? 'fw-semibold' : '' ?>">
                    <div class="d-flex gap-3 align-items-center">
                        <div class="position-relative flex-shrink-0">
                            <img src="<?= e(media_url($conv['consultant_avatar'] ?? null, 'avatar')) ?>"
                                 class="rounded-circle" width="44" height="44" style="object-fit:cover" alt="">
                            <?php if ($conv['unread_count'] > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:.55rem">
                                <?= $conv['unread_count'] ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        <div class="flex-grow-1 overflow-hidden">
                            <div class="d-flex justify-content-between">
                                <span class="small"><?= e($conv['consultant_name']) ?></span>
                                <span class="text-muted small fw-normal"><?= $conv['last_message_at'] ? time_ago($conv['last_message_at']) : '' ?></span>
                            </div>
                            <div class="small text-truncate <?= $conv['unread_count'] > 0 ? '' : 'text-muted fw-normal' ?>">
                                <?= e(truncate($conv['subject'], 60)) ?>
                            </div>
                            <?php if ($conv['last_message']): ?>
                            <div class="text-muted small fw-normal text-truncate"><?= e(truncate($conv['last_message'], 60)) ?></div>
                            <?php endif; ?>
                        </div>
                        <?= status_badge($conv['status']) ?>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-chat-dots" style="font-size:3rem"></i>
                <p class="mt-2">No messages yet.</p>
                <a href="<?= e(url('consultations.php')) ?>" class="btn btn-primary">Start a consultation</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

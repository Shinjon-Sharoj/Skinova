<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/auth-check.php';

// Mark one notification read and redirect to its link
$openId = get_int('open');
if ($openId) {
    $notif = db_one('SELECT * FROM notifications WHERE id = ? AND user_id = ?', [$openId, $authUser['id']]);
    if ($notif) {
        mark_notification_read($openId);
        if ($notif['link']) {
            redirect($notif['link']);
        }
    }
}

// Mark all read action
if (is_post() && csrf_is_valid() && input('action') === 'mark_all_read') {
    mark_all_notifications_read((int) $authUser['id']);
    flash('success', 'All notifications marked as read.');
    redirect('notifications.php');
}

$perPage  = 20;
$page     = get_int('page', 1);
$total    = (int) db_value('SELECT COUNT(*) FROM notifications WHERE user_id = ?', [$authUser['id']], 0);
$pg       = paginate($total, $perPage, $page);
$notifs   = db_all('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?',
    [$authUser['id'], $pg['per_page'], $pg['offset']]);
$unread   = unread_notification_count((int) $authUser['id']);

$pageTitle = 'Notifications';

include __DIR__ . '/includes/header.php';
?>
<div class="container py-4">
    <div class="row">
        <div class="col-lg-3 mb-4"><?php include __DIR__ . '/includes/sidebar.php'; ?></div>
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="h4 mb-0">
                    Notifications <?php if ($unread > 0): ?>
                    <span class="badge text-bg-danger"><?= $unread ?></span>
                    <?php endif; ?>
                </h2>
                <?php if ($unread > 0): ?>
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="mark_all_read">
                    <button type="submit" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-check-all"></i> Mark all as read
                    </button>
                </form>
                <?php endif; ?>
            </div>

            <?php if ($notifs): ?>
            <div class="list-group shadow-sm">
                <?php foreach ($notifs as $n): ?>
                <a href="<?= e(url('notifications.php?open=' . $n['id'])) ?>"
                   class="list-group-item list-group-item-action d-flex gap-3 py-3 <?= !$n['is_read'] ? 'fw-semibold' : '' ?>">
                    <span class="<?= !$n['is_read'] ? 'text-primary' : 'text-muted' ?> flex-shrink-0 mt-1">
                        <i class="bi <?= notification_icon($n['type']) ?> fs-5"></i>
                    </span>
                    <div class="flex-grow-1 overflow-hidden">
                        <div class="d-flex justify-content-between align-items-start">
                            <span class="text-truncate"><?= e($n['title']) ?></span>
                            <small class="text-muted ms-2 flex-shrink-0"><?= time_ago($n['created_at']) ?></small>
                        </div>
                        <p class="mb-0 small text-muted fw-normal text-truncate"><?= e($n['message']) ?></p>
                    </div>
                    <?php if (!$n['is_read']): ?>
                    <span class="flex-shrink-0 align-self-center"><span class="badge rounded-pill text-bg-primary">&bull;</span></span>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>
            <div class="mt-4"><?= render_pagination($pg) ?></div>
            <?php else: ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-bell" style="font-size:3rem"></i>
                <p class="mt-2">No notifications yet.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

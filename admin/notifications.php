<?php
require_once __DIR__ . '/../admin/includes/auth-check.php';
$pageTitle = 'Broadcast Notifications';
$panel     = 'admin';

if (is_post() && csrf_is_valid()) {
    $type    = input('type');
    $title   = mb_substr(clean_text(input('title')), 0, 150);
    $message = mb_substr(clean_text(input('message')), 0, 500);
    $link    = input('link') ? mb_substr(input('link'), 0, 255) : null;
    $target  = input('target');  // 'all' | 'customers'

    if ($title && $message) {
        if ($target === 'all') {
            $cnt = notify_all_customers($type, $title, $message, $link);
        } else {
            $cnt = notify_all_customers($type, $title, $message, $link);
        }
        log_activity('admin_broadcast_notification', null, null, 'title=' . $title, (int)$authUser['id']);
        flash('success', 'Notification sent to customers.');
    } else {
        flash('danger', 'Title and message are required.');
    }
    redirect('admin/notifications.php');
}

// Recent notifications sent via admin
$recent = db_all(
    "SELECT n.*, u.full_name FROM notifications n JOIN users u ON u.id=n.user_id ORDER BY n.created_at DESC LIMIT 30"
);

include __DIR__ . '/../admin/includes/header.php';
admin_page_header('Broadcast Notifications');
?>
<div class="row g-4">
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header fw-semibold">Send Notification to All Customers</div>
            <div class="card-body">
                <form method="post" novalidate class="needs-validation">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-select form-select-sm">
                            <option value="system">System</option>
                            <option value="discount">Discount / Promo</option>
                            <option value="new_product">New product</option>
                            <option value="order">Order</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Title <span class="text-danger">*</span></label>
                        <input name="title" class="form-control form-control-sm" required maxlength="150" placeholder="Notification title…">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Message <span class="text-danger">*</span></label>
                        <textarea name="message" class="form-control form-control-sm" rows="3" required maxlength="500" placeholder="Notification body…"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Link <span class="text-muted">(optional app-relative path)</span></label>
                        <input name="link" class="form-control form-control-sm" maxlength="255" placeholder="e.g. products.php?sort=newest">
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-megaphone"></i> Send to all customers
                    </button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header fw-semibold">Recent Notifications</div>
            <div class="list-group list-group-flush" style="max-height:400px;overflow-y:auto">
                <?php if ($recent): foreach ($recent as $n): ?>
                <div class="list-group-item py-2 small">
                    <div class="d-flex justify-content-between">
                        <span class="fw-semibold"><?= e($n['title']) ?></span>
                        <span class="text-muted"><?= time_ago($n['created_at']) ?></span>
                    </div>
                    <div class="text-muted"><?= e(truncate($n['message'],80)) ?></div>
                    <div class="text-muted">→ <?= e($n['full_name']) ?></div>
                </div>
                <?php endforeach; else: ?>
                <div class="list-group-item text-muted small text-center py-3">No notifications sent yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../admin/includes/footer.php'; ?>

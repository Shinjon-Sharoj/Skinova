<?php
require_once _DIR_ . '/../includes/init.php';
require_once _DIR_ . '/../includes/consultation.php';
$authUser = require_role(ROLE_CONSULTANT);

$pageTitle = 'Dashboard';
$panel     = 'consultant';

$consultant = get_consultant_by_user_id((int) $authUser['id']);
if (!$consultant) {
    flash('warning', 'Your consultant profile is not set up yet. Please contact the administrator.');
    redirect('index.php');
}

// Stats
$total      = (int) db_value('SELECT COUNT(*) FROM consultations WHERE consultant_id = ?', [$consultant['id']], 0);
$pending    = (int) db_value("SELECT COUNT(*) FROM consultations WHERE consultant_id = ? AND status = 'requested'", [$consultant['id']], 0);
$active     = (int) db_value("SELECT COUNT(*) FROM consultations WHERE consultant_id = ? AND status IN ('confirmed','in_progress')", [$consultant['id']], 0);
$completed  = (int) db_value("SELECT COUNT(*) FROM consultations WHERE consultant_id = ? AND status = 'completed'", [$consultant['id']], 0);
$unreadMsgs = unread_message_count_for_user((int) $authUser['id']);

// Recent consultations
$recent = db_all(
    "SELECT cn.*, u.full_name AS customer_name, up.avatar AS customer_avatar
     FROM consultations cn
     JOIN users u ON u.id = cn.user_id
     LEFT JOIN user_profiles up ON up.user_id = cn.user_id
     WHERE cn.consultant_id = ? ORDER BY cn.updated_at DESC LIMIT 8",
    [$consultant['id']]
);

include _DIR_ . '/../admin/includes/header.php';
?>
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3"><?= admin_stat_card('Total consultations', number_format($total), 'bi-person-heart', 'primary') ?></div>
    <div class="col-6 col-lg-3"><?= admin_stat_card('Pending requests', number_format($pending), 'bi-hourglass-split', 'warning') ?></div>
    <div class="col-6 col-lg-3"><?= admin_stat_card('Active', number_format($active), 'bi-chat-dots', 'success') ?></div>
    <div class="col-6 col-lg-3"><?= admin_stat_card('Unread messages', number_format($unreadMsgs), 'bi-envelope', 'danger') ?></div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Recent Consultations</span>
                <a href="<?= e(url('consultant/consultations.php')) ?>" class="btn btn-outline-primary btn-sm">View all</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light"><tr><th>Customer</th><th>Subject</th><th>Status</th><th>Date</th><th></th></tr></thead>
                    <tbody>
                        <?php if ($recent): foreach ($recent as $cn): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="<?= e(media_url($cn['customer_avatar'] ?? null, 'avatar')) ?>" class="rounded-circle" width="32" height="32" style="object-fit:cover" alt="">
                                    <span class="small"><?= e($cn['customer_name']) ?></span>
                                </div>
                            </td>
                            <td class="small"><?= e(truncate($cn['subject'], 40)) ?></td>
                            <td><?= status_badge($cn['status']) ?></td>
                            <td class="small text-muted"><?= time_ago($cn['created_at']) ?></td>
                            <td><a href="<?= e(url('consultant/consultation-details.php?id=' . $cn['id'])) ?>" class="btn btn-sm btn-outline-primary">Open</a></td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="5" class="text-center text-muted py-3">No consultations yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow-sm mb-3">
            <div class="card-body text-center">
                <img src="<?= e(media_url($consultant['avatar'] ?? null, 'avatar')) ?>" class="rounded-circle mb-2" width="72" height="72" style="object-fit:cover" alt="">
                <div class="fw-semibold"><?= e(($consultant['title'] ? $consultant['title'] . ' ' : '') . $consultant['full_name']) ?></div>
                <div class="text-muted small"><?= e($consultant['specialization']) ?></div>
                <?php if ($consultant['rating_count'] > 0): ?>
                <div class="mt-1"><?= render_stars((float)$consultant['rating_avg'], true) ?></div>
                <?php endif; ?>
                <div class="mt-2">
                    <span class="badge <?= $consultant['is_available'] ? 'text-bg-success' : 'text-bg-secondary' ?>">
                        <?= $consultant['is_available'] ? 'Available' : 'Unavailable' ?>
                    </span>
                </div>
                <a href="<?= e(url('consultant/profile.php')) ?>" class="btn btn-outline-primary btn-sm mt-2">Edit profile</a>
            </div>
        </div>
        <?php if ($pending > 0): ?>
        <div class="alert alert-warning small">
            <i class="bi bi-bell"></i> You have <strong><?= $pending ?></strong> pending consultation request<?= $pending > 1 ? 's' : '' ?>.
            <a href="<?= e(url('consultant/consultations.php?status=requested')) ?>" class="alert-link ms-1">Review →</a>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php include _DIR_ . '/../admin/includes/footer.php'; ?>
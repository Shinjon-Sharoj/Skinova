<?php
require_once _DIR_ . '/../includes/init.php';
require_once _DIR_ . '/../includes/consultation.php';
$authUser = require_role(ROLE_CONSULTANT);

$pageTitle = 'Consultations';
$panel     = 'consultant';

$consultant = get_consultant_by_user_id((int) $authUser['id']);
if (!$consultant) redirect('consultant/dashboard.php');

$status  = get_str('status');
$perPage = 15;
$page    = get_int('page', 1);

$where  = ['cn.consultant_id = ?'];
$params = [$consultant['id']];
if ($status && in_array($status, CONSULTATION_STATUSES, true)) {
    $where[]  = 'cn.status = ?';
    $params[] = $status;
}
$whereSql = 'WHERE ' . implode(' AND ', $where);
$total    = (int) db_value("SELECT COUNT(*) FROM consultations cn $whereSql", $params, 0);
$pg       = paginate($total, $perPage, $page);
$rows     = db_all(
    "SELECT cn.*, u.full_name AS customer_name, up.avatar AS customer_avatar
     FROM consultations cn
     JOIN users u ON u.id = cn.user_id
     LEFT JOIN user_profiles up ON up.user_id = cn.user_id
     $whereSql ORDER BY cn.updated_at DESC LIMIT ? OFFSET ?",
    array_merge($params, [$pg['per_page'], $pg['offset']])
);

include _DIR_ . '/../admin/includes/header.php';
admin_page_header('Consultations', '', $total . ' total');
?>
<!-- Status filters -->
<div class="d-flex flex-wrap gap-1 mb-3">
    <a href="?" class="btn btn-sm <?= !$status ? 'btn-primary' : 'btn-outline-secondary' ?>">All</a>
    <?php foreach (CONSULTATION_STATUSES as $s): ?>
    <a href="?status=<?= $s ?>" class="btn btn-sm <?= $status === $s ? 'btn-primary' : 'btn-outline-secondary' ?>"><?= humanize($s) ?></a>
    <?php endforeach; ?>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>Customer</th><th>Subject</th><th>Status</th><th>Fee</th><th>Requested</th><th></th></tr>
            </thead>
            <tbody>
                <?php if ($rows): foreach ($rows as $cn): ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <img src="<?= e(media_url($cn['customer_avatar'] ?? null, 'avatar')) ?>" class="rounded-circle" width="32" height="32" style="object-fit:cover" alt="">
                            <span class="small"><?= e($cn['customer_name']) ?></span>
                        </div>
                    </td>
                    <td class="small"><?= e(truncate($cn['subject'], 50)) ?></td>
                    <td><?= status_badge($cn['status']) ?></td>
                    <td class="small"><?= e(money($cn['fee'])) ?> <?= status_badge($cn['payment_status']) ?></td>
                    <td class="small text-muted"><?= time_ago($cn['created_at']) ?></td>
                    <td>
                        <a href="<?= e(url('consultant/consultation-details.php?id=' . $cn['id'])) ?>" class="btn btn-sm btn-outline-primary">Open</a>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <?php admin_empty_row(6, 'No consultations found.'); ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3"><?= render_pagination($pg) ?></div>
<?php include _DIR_ . '/../admin/includes/footer.php'; ?>
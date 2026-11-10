<?php
require_once __DIR__ . '/../admin/includes/auth-check.php';
require_once __DIR__ . '/../includes/consultation.php';
$pageTitle = 'Consultations';
$panel     = 'admin';

$status  = get_str('status');
$perPage = ADMIN_PER_PAGE;
$page    = get_int('page', 1);

$where = []; $params = [];
if ($status && in_array($status, CONSULTATION_STATUSES, true)) { $where[]='cn.status=?'; $params[]=$status; }
$whereSql = $where ? 'WHERE '.implode(' AND ',$where) : '';

$total = (int) db_value("SELECT COUNT(*) FROM consultations cn $whereSql", $params, 0);
$pg    = paginate($total, $perPage, $page);
$rows  = db_all(
    "SELECT cn.*, u.full_name AS customer_name, cu.full_name AS consultant_name
     FROM consultations cn JOIN users u ON u.id=cn.user_id JOIN consultants c ON c.id=cn.consultant_id JOIN users cu ON cu.id=c.user_id
     $whereSql ORDER BY cn.created_at DESC LIMIT ? OFFSET ?",
    array_merge($params, [$pg['per_page'], $pg['offset']])
);

include __DIR__ . '/../admin/includes/header.php';
admin_page_header('Consultations', '', $total . ' total');
?>
<div class="d-flex flex-wrap gap-1 mb-3">
    <a href="?" class="btn btn-sm <?= !$status?'btn-primary':'btn-outline-secondary' ?>">All</a>
    <?php foreach (CONSULTATION_STATUSES as $s): ?>
    <a href="?status=<?= $s ?>" class="btn btn-sm <?= $status===$s?'btn-primary':'btn-outline-secondary' ?>"><?= humanize($s) ?></a>
    <?php endforeach; ?>
</div>
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Customer</th><th>Consultant</th><th>Subject</th><th>Status</th><th>Fee</th><th>Date</th><th></th></tr></thead>
            <tbody>
                <?php if ($rows): foreach ($rows as $cn): ?>
                <tr>
                    <td class="small"><?= e($cn['customer_name']) ?></td>
                    <td class="small"><?= e($cn['consultant_name']) ?></td>
                    <td class="small"><?= e(truncate($cn['subject'],40)) ?></td>
                    <td><?= status_badge($cn['status']) ?></td>
                    <td class="small"><?= e(money($cn['fee'])) ?> <?= status_badge($cn['payment_status']) ?></td>
                    <td class="small text-muted"><?= time_ago($cn['created_at']) ?></td>
                    <td><a href="<?= e(url('admin/user-details.php?id='.$cn['user_id'])) ?>" class="btn btn-sm btn-outline-secondary" title="View customer"><i class="bi bi-person"></i></a></td>
                </tr>
                <?php endforeach; else: admin_empty_row(7,'No consultations found.'); endif; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3"><?= render_pagination($pg) ?></div>
<?php include __DIR__ . '/../admin/includes/footer.php'; ?>

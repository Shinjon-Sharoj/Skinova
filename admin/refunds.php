<?php
require_once __DIR__ . '/../admin/includes/auth-check.php';
$pageTitle = 'Returns & Refunds';
$panel     = 'admin';

$status  = get_str('status');
$perPage = ADMIN_PER_PAGE;
$page    = get_int('page', 1);

$where = []; $params = [];
if ($status && in_array($status, RETURN_STATUSES, true)) { $where[]='r.status=?'; $params[]=$status; }
$whereSql = $where ? 'WHERE '.implode(' AND ',$where) : '';

$total   = (int) db_value("SELECT COUNT(*) FROM returns r $whereSql", $params, 0);
$pg      = paginate($total, $perPage, $page);
$returns = db_all(
    "SELECT r.*, o.order_number, u.full_name AS customer_name
     FROM returns r JOIN orders o ON o.id=r.order_id JOIN users u ON u.id=r.user_id
     $whereSql ORDER BY r.created_at DESC LIMIT ? OFFSET ?",
    array_merge($params, [$pg['per_page'], $pg['offset']])
);

// Handle status update
if (is_post() && csrf_is_valid()) {
    $retId  = input_int('return_id');
    $newSt  = input('status');
    $note   = mb_substr(clean_text(input('admin_note')),0,500);
    if (in_array($newSt, RETURN_STATUSES, true)) {
        db_exec('UPDATE returns SET status=?,admin_note=? WHERE id=?', [$newSt,$note?:null,$retId]);
        // If approved → create refund
        if ($newSt === 'refunded') {
            $ret = db_one('SELECT * FROM returns WHERE id=?', [$retId]);
            if ($ret) {
                $order = get_order((int)$ret['order_id']);
                if ($order && $order['payment_status']==='paid') {
                    create_refund((int)$ret['order_id'], null, $ret['user_id'], (float)$order['total'], 'Return approved', $retId);
                }
            }
        }
        log_activity('admin_return_updated','return',$retId,'status='.$newSt,(int)$authUser['id']);
        flash('success','Return updated.');
    }
    redirect('admin/refunds.php' . ($status?'?status='.$status:''));
}

include __DIR__ . '/../admin/includes/header.php';
admin_page_header('Returns & Refunds', '', $total . ' total');
?>
<div class="d-flex flex-wrap gap-1 mb-3">
    <a href="?" class="btn btn-sm <?= !$status?'btn-primary':'btn-outline-secondary' ?>">All</a>
    <?php foreach (RETURN_STATUSES as $s): ?>
    <a href="?status=<?= $s ?>" class="btn btn-sm <?= $status===$s?'btn-primary':'btn-outline-secondary' ?>"><?= humanize($s) ?></a>
    <?php endforeach; ?>
</div>
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Order</th><th>Customer</th><th>Reason</th><th>Status</th><th>Requested</th><th></th></tr></thead>
            <tbody>
                <?php if ($returns): foreach ($returns as $r): ?>
                <tr>
                    <td><a href="<?= e(url('admin/order-details.php?id='.$r['order_id'])) ?>" class="small fw-semibold"><?= e($r['order_number']) ?></a></td>
                    <td class="small"><?= e($r['customer_name']) ?></td>
                    <td class="small"><?= e(truncate($r['reason'],40)) ?></td>
                    <td><?= status_badge($r['status']) ?></td>
                    <td class="small text-muted"><?= time_ago($r['created_at']) ?></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#returnModal"
                            onclick="document.getElementById('rtId').value=<?= $r['id'] ?>;document.getElementById('rtSt').value='<?= $r['status'] ?>';document.getElementById('rtNote').value=<?= json_encode($r['admin_note']??'') ?>">
                            Review
                        </button>
                    </td>
                </tr>
                <?php endforeach; else: admin_empty_row(6,'No returns found.'); endif; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3"><?= render_pagination($pg) ?></div>

<div class="modal fade" id="returnModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="return_id" id="rtId">
            <div class="modal-header"><h5 class="modal-title">Update Return</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">New status</label>
                    <select name="status" id="rtSt" class="form-select form-select-sm">
                        <?php foreach (RETURN_STATUSES as $s): ?>
                        <option value="<?= $s ?>"><?= humanize($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Admin note</label>
                    <textarea name="admin_note" id="rtNote" class="form-control form-control-sm" rows="3" maxlength="500"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm">Save</button>
            </div>
        </form>
    </div>
</div>
<?php include __DIR__ . '/../admin/includes/footer.php'; ?>

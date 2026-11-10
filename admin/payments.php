<?php
require_once __DIR__ . '/../admin/includes/auth-check.php';
$pageTitle = 'Payments';
$panel     = 'admin';

$status  = get_str('status');
$q       = get_str('q');
$perPage = ADMIN_PER_PAGE;
$page    = get_int('page', 1);

$where = []; $params = [];
if ($status && in_array($status, PAYMENT_STATUSES, true)) { $where[] = 'p.status=?'; $params[] = $status; }
if ($q) { $like='%'.addcslashes($q,'%_\\').'%'; $where[]='(o.order_number LIKE ? OR u.full_name LIKE ? OR p.transaction_id LIKE ?)'; array_push($params,$like,$like,$like); }
$whereSql = $where ? 'WHERE '.implode(' AND ',$where) : '';

$total = (int) db_value("SELECT COUNT(*) FROM payments p JOIN orders o ON o.id=p.order_id JOIN users u ON u.id=p.user_id $whereSql", $params, 0);
$pg    = paginate($total, $perPage, $page);
$payments = db_all(
    "SELECT p.*, o.order_number, u.full_name AS customer_name
     FROM payments p JOIN orders o ON o.id=p.order_id JOIN users u ON u.id=p.user_id
     $whereSql ORDER BY p.created_at DESC LIMIT ? OFFSET ?",
    array_merge($params, [$pg['per_page'], $pg['offset']])
);

include __DIR__ . '/../admin/includes/header.php';
admin_page_header('Payments', '', $total . ' total');
?>
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-sm-4"><input type="search" name="q" class="form-control form-control-sm" placeholder="Order # / customer / txn ID…" value="<?= e($q) ?>"></div>
            <div class="col-sm-auto">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All statuses</option>
                    <?php foreach (PAYMENT_STATUSES as $s): ?>
                    <option value="<?= $s ?>" <?= $status===$s?'selected':'' ?>><?= humanize($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-auto"><button class="btn btn-primary btn-sm">Filter</button></div>
        </form>
    </div>
</div>
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Order</th><th>Customer</th><th>Amount</th><th>Method</th><th>Provider</th><th>Txn ID</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
                <?php if ($payments): foreach ($payments as $p): ?>
                <tr>
                    <td><a href="<?= e(url('admin/order-details.php?id='.$p['order_id'])) ?>" class="small fw-semibold"><?= e($p['order_number']) ?></a></td>
                    <td class="small"><?= e($p['customer_name']) ?></td>
                    <td class="small fw-semibold text-primary"><?= e(money($p['amount'])) ?></td>
                    <td class="small"><?= humanize($p['method']) ?></td>
                    <td class="small text-muted"><?= e($p['provider']) ?></td>
                    <td class="small text-muted"><?= e(truncate($p['transaction_id']??'—',20)) ?></td>
                    <td><?= status_badge($p['status']) ?></td>
                    <td class="small text-muted"><?= format_datetime($p['created_at']) ?></td>
                </tr>
                <?php endforeach; else: admin_empty_row(8,'No payments found.'); endif; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3"><?= render_pagination($pg) ?></div>
<?php include __DIR__ . '/../admin/includes/footer.php'; ?>

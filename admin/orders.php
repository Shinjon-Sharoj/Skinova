<?php
require_once __DIR__ . '/../admin/includes/auth-check.php';
$pageTitle = 'Orders';
$panel     = 'admin';

$status  = get_str('status');
$q       = get_str('q');
$from    = get_str('from');
$to      = get_str('to');
$perPage = ADMIN_PER_PAGE;
$page    = get_int('page', 1);

$where = []; $params = [];
if ($status && in_array($status, ORDER_STATUSES, true)) { $where[] = 'o.status = ?'; $params[] = $status; }
if ($q) { $like = '%'.addcslashes($q,'%_\\').'%'; $where[] = '(o.order_number LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)'; array_push($params,$like,$like,$like); }
if ($from) { $where[] = 'o.created_at >= ?'; $params[] = $from . ' 00:00:00'; }
if ($to)   { $where[] = 'o.created_at <= ?'; $params[] = $to   . ' 23:59:59'; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total = (int) db_value("SELECT COUNT(*) FROM orders o JOIN users u ON u.id=o.user_id $whereSql", $params, 0);
$pg    = paginate($total, $perPage, $page);
$orders = db_all(
    "SELECT o.id, o.order_number, o.total, o.status, o.payment_status, o.payment_method, o.created_at, u.full_name AS customer_name, u.email AS customer_email
     FROM orders o JOIN users u ON u.id=o.user_id $whereSql ORDER BY o.created_at DESC LIMIT ? OFFSET ?",
    array_merge($params, [$pg['per_page'], $pg['offset']])
);

include __DIR__ . '/../admin/includes/header.php';
admin_page_header('Orders', '', $total . ' total');
?>
<!-- Filters -->
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-sm-3"><input type="search" name="q" class="form-control form-control-sm" placeholder="Order # / customer…" value="<?= e($q) ?>"></div>
            <div class="col-sm-auto">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All statuses</option>
                    <?php foreach (ORDER_STATUSES as $s): ?>
                    <option value="<?= $s ?>" <?= $status===$s?'selected':'' ?>><?= humanize($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-auto"><input type="date" name="from" class="form-control form-control-sm" value="<?= e($from) ?>" placeholder="From"></div>
            <div class="col-sm-auto"><input type="date" name="to" class="form-control form-control-sm" value="<?= e($to) ?>" placeholder="To"></div>
            <div class="col-sm-auto"><button class="btn btn-primary btn-sm">Filter</button> <a href="?" class="btn btn-outline-secondary btn-sm">Reset</a></div>
        </form>
    </div>
</div>

<!-- Status tab pills -->
<div class="d-flex flex-wrap gap-1 mb-3">
    <a href="?" class="btn btn-sm <?= !$status?'btn-primary':'btn-outline-secondary' ?>">All</a>
    <?php foreach (ORDER_STATUSES as $s): ?>
    <a href="?status=<?= $s ?>" class="btn btn-sm <?= $status===$s?'btn-primary':'btn-outline-secondary' ?>"><?= humanize($s) ?></a>
    <?php endforeach; ?>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>Order #</th><th>Customer</th><th>Total</th><th>Status</th><th>Payment</th><th>Method</th><th>Date</th><th></th></tr>
            </thead>
            <tbody>
                <?php if ($orders): foreach ($orders as $o): ?>
                <tr>
                    <td class="fw-semibold small"><?= e($o['order_number']) ?></td>
                    <td>
                        <div class="small"><?= e($o['customer_name']) ?></div>
                        <div class="text-muted" style="font-size:.7rem"><?= e($o['customer_email']) ?></div>
                    </td>
                    <td class="small fw-semibold text-primary"><?= e(money($o['total'])) ?></td>
                    <td><?= status_badge($o['status']) ?></td>
                    <td><?= status_badge($o['payment_status']) ?></td>
                    <td class="small text-muted"><?= humanize($o['payment_method']) ?></td>
                    <td class="small text-muted"><?= format_datetime($o['created_at']) ?></td>
                    <td><a href="<?= e(url('admin/order-details.php?id='.$o['id'])) ?>" class="btn btn-sm btn-outline-primary">View</a></td>
                </tr>
                <?php endforeach; else: admin_empty_row(8,'No orders found.'); endif; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3"><?= render_pagination($pg) ?></div>
<?php include __DIR__ . '/../admin/includes/footer.php'; ?>

<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/auth-check.php';

$pageTitle = 'My Orders';
$status    = get_str('status');
$page      = get_int('page', 1);
$perPage   = 10;

$where  = ['o.user_id = ?'];
$params = [$authUser['id']];
if ($status && in_array($status, ORDER_STATUSES, true)) {
    $where[]  = 'o.status = ?';
    $params[] = $status;
}
$whereSql = 'WHERE ' . implode(' AND ', $where);

$total   = (int) db_value("SELECT COUNT(*) FROM orders o $whereSql", $params, 0);
$pg      = paginate($total, $perPage, $page);
$orders  = db_all(
    "SELECT o.*, s.tracking_number, s.status AS shipment_status
     FROM orders o LEFT JOIN shipments s ON s.order_id = o.id
     $whereSql ORDER BY o.created_at DESC LIMIT ? OFFSET ?",
    array_merge($params, [$pg['per_page'], $pg['offset']])
);

include __DIR__ . '/includes/header.php';
?>
<div class="container py-4">
    <div class="row">
        <div class="col-lg-3 mb-4"><?php include __DIR__ . '/includes/sidebar.php'; ?></div>
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="h4 mb-0">My Orders</h2>
            </div>

            <!-- Status filter tabs -->
            <div class="d-flex flex-wrap gap-1 mb-3">
                <a href="<?= e(url('orders.php')) ?>" class="btn btn-sm <?= !$status ? 'btn-primary' : 'btn-outline-secondary' ?>">All</a>
                <?php foreach (ORDER_STATUSES as $s): ?>
                <a href="<?= e(url('orders.php?status=' . $s)) ?>" class="btn btn-sm <?= $status === $s ? 'btn-primary' : 'btn-outline-secondary' ?>">
                    <?= humanize($s) ?>
                </a>
                <?php endforeach; ?>
            </div>

            <?php if ($orders): ?>
            <div class="list-group shadow-sm">
                <?php foreach ($orders as $o): ?>
                <a href="<?= e(url('order-details.php?id=' . $o['id'])) ?>"
                   class="list-group-item list-group-item-action py-3">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                        <div>
                            <div class="fw-semibold"><?= e($o['order_number']) ?></div>
                            <div class="text-muted small"><?= format_datetime($o['created_at']) ?></div>
                            <?php if ($o['tracking_number']): ?>
                            <div class="text-muted small"><i class="bi bi-truck"></i> <?= e($o['tracking_number']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold text-primary"><?= e(money($o['total'])) ?></div>
                            <div class="d-flex gap-1 justify-content-end mt-1">
                                <?= status_badge($o['status']) ?>
                                <?= status_badge($o['payment_status']) ?>
                            </div>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <div class="mt-4"><?= render_pagination($pg) ?></div>
            <?php else: ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-box-seam" style="font-size:3rem"></i>
                <p class="mt-2">No orders <?= $status ? 'with status "' . humanize($status) . '"' : 'yet' ?>.</p>
                <a href="<?= e(url('products.php')) ?>" class="btn btn-primary">Start shopping</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

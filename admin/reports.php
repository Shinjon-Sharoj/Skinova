<?php
require_once __DIR__ . '/../admin/includes/auth-check.php';
$pageTitle = 'Reports';
$panel     = 'admin';

$from = get_str('from') ?: date('Y-m-01');
$to   = get_str('to')   ?: date('Y-m-d');

$params = [$from . ' 00:00:00', $to . ' 23:59:59'];

$summary = db_one(
    "SELECT COUNT(*) AS total_orders,
            COALESCE(SUM(CASE WHEN status!='cancelled' THEN 1 ELSE 0 END),0) AS completed_orders,
            COALESCE(SUM(CASE WHEN status!='cancelled' AND payment_status='paid' THEN total ELSE 0 END),0) AS revenue,
            COALESCE(SUM(CASE WHEN status='cancelled' THEN 1 ELSE 0 END),0) AS cancelled_orders,
            COALESCE(AVG(CASE WHEN status!='cancelled' AND payment_status='paid' THEN total END),0) AS avg_order_value
     FROM orders WHERE created_at BETWEEN ? AND ?",
    $params
);

$topProducts = db_all(
    "SELECT p.name, SUM(oi.quantity) AS units_sold, SUM(oi.line_total) AS revenue
     FROM order_items oi JOIN products p ON p.id=oi.product_id JOIN orders o ON o.id=oi.order_id
     WHERE o.status!='cancelled' AND o.created_at BETWEEN ? AND ?
     GROUP BY oi.product_id ORDER BY revenue DESC LIMIT 10",
    $params
);

$topCategories = db_all(
    "SELECT c.name, SUM(oi.quantity) AS units, SUM(oi.line_total) AS revenue
     FROM order_items oi JOIN products p ON p.id=oi.product_id JOIN categories c ON c.id=p.category_id JOIN orders o ON o.id=oi.order_id
     WHERE o.status!='cancelled' AND o.created_at BETWEEN ? AND ?
     GROUP BY c.id ORDER BY revenue DESC",
    $params
);

$newCustomers = (int) db_value("SELECT COUNT(*) FROM users WHERE role='customer' AND created_at BETWEEN ? AND ?", $params, 0);
$returningCust = (int) db_value("SELECT COUNT(DISTINCT user_id) FROM orders WHERE status!='cancelled' AND created_at BETWEEN ? AND ?", $params, 0);

include __DIR__ . '/../admin/includes/header.php';
admin_page_header('Reports');
?>
<!-- Date range picker -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-sm-4">
                <label class="form-label small">From</label>
                <input type="date" name="from" class="form-control form-control-sm" value="<?= e($from) ?>">
            </div>
            <div class="col-sm-4">
                <label class="form-label small">To</label>
                <input type="date" name="to" class="form-control form-control-sm" value="<?= e($to) ?>">
            </div>
            <div class="col-sm-auto">
                <button type="submit" class="btn btn-primary btn-sm">Apply</button>
                <a href="?" class="btn btn-outline-secondary btn-sm ms-1">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Summary cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3"><?= admin_stat_card('Revenue', money($summary['revenue']), 'bi-cash-stack', 'success') ?></div>
    <div class="col-6 col-lg-3"><?= admin_stat_card('Orders', number_format($summary['completed_orders']), 'bi-bag-check', 'primary') ?></div>
    <div class="col-6 col-lg-3"><?= admin_stat_card('Avg Order Value', money($summary['avg_order_value']), 'bi-graph-up', 'info') ?></div>
    <div class="col-6 col-lg-3"><?= admin_stat_card('New Customers', number_format($newCustomers), 'bi-person-plus', 'warning') ?></div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header fw-semibold">Top Products by Revenue</div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light"><tr><th>#</th><th>Product</th><th>Units</th><th>Revenue</th></tr></thead>
                    <tbody>
                        <?php if ($topProducts): foreach ($topProducts as $i => $p): ?>
                        <tr>
                            <td class="text-muted small"><?= $i+1 ?></td>
                            <td class="small"><?= e(truncate($p['name'],40)) ?></td>
                            <td class="small"><?= number_format($p['units_sold']) ?></td>
                            <td class="small fw-semibold text-primary"><?= e(money($p['revenue'])) ?></td>
                        </tr>
                        <?php endforeach; else: admin_empty_row(4,'No sales data for this period.'); endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card shadow-sm mb-3">
            <div class="card-header fw-semibold">Revenue by Category</div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light"><tr><th>Category</th><th>Revenue</th></tr></thead>
                    <tbody>
                        <?php if ($topCategories): foreach ($topCategories as $c): ?>
                        <tr>
                            <td class="small"><?= e($c['name']) ?></td>
                            <td class="small fw-semibold"><?= e(money($c['revenue'])) ?></td>
                        </tr>
                        <?php endforeach; else: admin_empty_row(2,'No data.'); endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card shadow-sm">
            <div class="card-body text-muted small">
                <div><strong>Cancelled orders:</strong> <?= number_format($summary['cancelled_orders']) ?></div>
                <div><strong>Customers who ordered:</strong> <?= number_format($returningCust) ?></div>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../admin/includes/footer.php'; ?>

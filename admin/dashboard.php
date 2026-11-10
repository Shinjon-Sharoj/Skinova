<?php
require_once __DIR__ . '/../admin/includes/auth-check.php';

$pageTitle = 'Dashboard';
$panel     = 'admin';

// Key stats
$totalRevenue   = (float) db_value("SELECT COALESCE(SUM(total),0) FROM orders WHERE status != 'cancelled' AND payment_status = 'paid'", [], 0);
$totalOrders    = (int)   db_value("SELECT COUNT(*) FROM orders WHERE status != 'cancelled'", [], 0);
$totalCustomers = (int)   db_value("SELECT COUNT(*) FROM users WHERE role = 'customer' AND status = 'active'", [], 0);
$totalProducts  = (int)   db_value("SELECT COUNT(*) FROM products WHERE is_active = 1", [], 0);
$pendingOrders  = (int)   db_value("SELECT COUNT(*) FROM orders WHERE status = 'pending'", [], 0);
$pendingReviews = (int)   db_value("SELECT COUNT(*) FROM reviews WHERE status = 'pending'", [], 0);
$openReturns    = (int)   db_value("SELECT COUNT(*) FROM returns WHERE status = 'requested'", [], 0);
$pendingConsult = (int)   db_value("SELECT COUNT(*) FROM consultations WHERE status = 'requested'", [], 0);
$lowStock       = (int)   db_value("SELECT COUNT(*) FROM inventory i JOIN products p ON p.id=i.product_id WHERE i.quantity <= i.low_stock_threshold AND p.is_active=1", [], 0);

// Revenue last 7 days (daily)
$revenueByDay = db_all(
    "SELECT DATE(created_at) AS day, SUM(total) AS revenue, COUNT(*) AS orders
     FROM orders WHERE status != 'cancelled' AND payment_status='paid' AND created_at >= DATE_SUB(NOW(),INTERVAL 7 DAY)
     GROUP BY DATE(created_at) ORDER BY day"
);

// Recent orders
$recentOrders = db_all(
    "SELECT o.id, o.order_number, o.total, o.status, o.payment_status, o.created_at, u.full_name AS customer_name
     FROM orders o JOIN users u ON u.id=o.user_id ORDER BY o.created_at DESC LIMIT 8"
);

// Top products
$topProducts = db_all(
    "SELECT p.id, p.name, p.slug, p.sold_count, p.rating_avg,
            ROUND(p.price*(1-p.discount_percent/100),2) AS final_price
     FROM products p WHERE p.is_active=1 ORDER BY p.sold_count DESC LIMIT 5"
);

include __DIR__ . '/../admin/includes/header.php';
?>

<!-- Alert banners -->
<?php if ($pendingOrders || $lowStock || $pendingReviews || $openReturns || $pendingConsult): ?>
<div class="row g-2 mb-4">
    <?php if ($pendingOrders): ?><div class="col-auto"><a href="<?= e(url('admin/orders.php?status=pending')) ?>" class="btn btn-sm btn-warning"><i class="bi bi-clock"></i> <?= $pendingOrders ?> pending order<?= $pendingOrders>1?'s':'' ?></a></div><?php endif; ?>
    <?php if ($lowStock): ?><div class="col-auto"><a href="<?= e(url('admin/inventory.php?filter=low')) ?>" class="btn btn-sm btn-danger"><i class="bi bi-boxes"></i> <?= $lowStock ?> low stock item<?= $lowStock>1?'s':'' ?></a></div><?php endif; ?>
    <?php if ($pendingReviews): ?><div class="col-auto"><a href="<?= e(url('admin/reviews.php?status=pending')) ?>" class="btn btn-sm btn-info"><i class="bi bi-star"></i> <?= $pendingReviews ?> review<?= $pendingReviews>1?'s':'' ?> to approve</a></div><?php endif; ?>
    <?php if ($openReturns): ?><div class="col-auto"><a href="<?= e(url('admin/refunds.php?status=requested')) ?>" class="btn btn-sm btn-secondary"><i class="bi bi-arrow-return-left"></i> <?= $openReturns ?> return request<?= $openReturns>1?'s':'' ?></a></div><?php endif; ?>
    <?php if ($pendingConsult): ?><div class="col-auto"><a href="<?= e(url('admin/consultations.php?status=requested')) ?>" class="btn btn-sm btn-primary"><i class="bi bi-person-heart"></i> <?= $pendingConsult ?> consultation request<?= $pendingConsult>1?'s':'' ?></a></div><?php endif; ?>
</div>
<?php endif; ?>

<!-- Stat cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3"><?= admin_stat_card('Total Revenue', money($totalRevenue), 'bi-currency-dollar', 'success') ?></div>
    <div class="col-6 col-lg-3"><?= admin_stat_card('Total Orders', number_format($totalOrders), 'bi-bag-check', 'primary') ?></div>
    <div class="col-6 col-lg-3"><?= admin_stat_card('Active Customers', number_format($totalCustomers), 'bi-people', 'info') ?></div>
    <div class="col-6 col-lg-3"><?= admin_stat_card('Active Products', number_format($totalProducts), 'bi-box', 'warning') ?></div>
</div>

<!-- Charts row -->
<?php if ($revenueByDay): ?>
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card shadow-sm h-100">
            <div class="card-header fw-semibold">Revenue — Last 7 Days</div>
            <div class="card-body">
                <canvas id="revenueChart" height="90"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header fw-semibold">Top 5 Products by Sales</div>
            <div class="list-group list-group-flush">
                <?php foreach ($topProducts as $i => $tp): ?>
                <a href="<?= e(url('admin/product-form.php?id=' . $tp['id'])) ?>" class="list-group-item list-group-item-action py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="small">
                            <span class="badge text-bg-secondary me-1"><?= $i+1 ?></span>
                            <?= e(truncate($tp['name'], 35)) ?>
                        </div>
                        <span class="badge text-bg-light border small"><?= number_format($tp['sold_count']) ?> sold</span>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Recent orders -->
<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-semibold">Recent Orders</span>
        <a href="<?= e(url('admin/orders.php')) ?>" class="btn btn-outline-primary btn-sm">View all</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Order #</th><th>Customer</th><th>Total</th><th>Status</th><th>Payment</th><th>Date</th><th></th></tr></thead>
            <tbody>
                <?php if ($recentOrders): foreach ($recentOrders as $o): ?>
                <tr>
                    <td class="fw-semibold small"><?= e($o['order_number']) ?></td>
                    <td class="small"><?= e($o['customer_name']) ?></td>
                    <td class="small fw-semibold text-primary"><?= e(money($o['total'])) ?></td>
                    <td><?= status_badge($o['status']) ?></td>
                    <td><?= status_badge($o['payment_status']) ?></td>
                    <td class="small text-muted"><?= time_ago($o['created_at']) ?></td>
                    <td><a href="<?= e(url('admin/order-details.php?id=' . $o['id'])) ?>" class="btn btn-sm btn-outline-primary">View</a></td>
                </tr>
                <?php endforeach; else: admin_empty_row(7); endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($revenueByDay): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
const labels = <?= json_encode(array_column($revenueByDay, 'day')) ?>;
const data   = <?= json_encode(array_map(fn($r) => round((float)$r['revenue'], 2), $revenueByDay)) ?>;
new Chart(document.getElementById('revenueChart'), {
    type: 'bar',
    data: { labels, datasets: [{ label: 'Revenue', data, backgroundColor: '#6c63ff66', borderColor: '#6c63ff', borderWidth: 2 }] },
    options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});
</script>
<?php endif; ?>
<?php include __DIR__ . '/../admin/includes/footer.php'; ?>

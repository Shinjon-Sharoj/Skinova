<?php
require_once __DIR__ . '/../admin/includes/auth-check.php';
$pageTitle = 'Analytics';
$panel     = 'admin';

$days = get_int('days', 30);
if (!in_array($days, [7, 30, 90], true)) $days = 30;

// Revenue & orders over time
$revenueData = db_all(
    "SELECT DATE(created_at) AS day, COALESCE(SUM(total),0) AS revenue, COUNT(*) AS orders
     FROM orders WHERE status!='cancelled' AND payment_status='paid' AND created_at >= DATE_SUB(NOW(),INTERVAL ? DAY)
     GROUP BY DATE(created_at) ORDER BY day",
    [$days]
);

// Orders by status
$byStatus = db_all("SELECT status, COUNT(*) AS cnt FROM orders GROUP BY status ORDER BY cnt DESC");

// Revenue by category
$byCat = db_all(
    "SELECT c.name, SUM(oi.line_total) AS revenue
     FROM order_items oi
     JOIN products p ON p.id=oi.product_id
     JOIN categories c ON c.id=p.category_id
     JOIN orders o ON o.id=oi.order_id
     WHERE o.status!='cancelled' AND o.payment_status='paid' AND o.created_at >= DATE_SUB(NOW(),INTERVAL ? DAY)
     GROUP BY c.id ORDER BY revenue DESC LIMIT 8",
    [$days]
);

// New users over time
$newUsers = db_all(
    "SELECT DATE(created_at) AS day, COUNT(*) AS users
     FROM users WHERE role='customer' AND created_at >= DATE_SUB(NOW(),INTERVAL ? DAY)
     GROUP BY DATE(created_at) ORDER BY day",
    [$days]
);

// Top search terms
$searches = db_all("SELECT term, SUM(results_count) AS hits FROM search_logs GROUP BY term ORDER BY hits DESC LIMIT 10");

include __DIR__ . '/../admin/includes/header.php';
admin_page_header('Analytics', '
    <a href="?days=7"  class="btn btn-sm ' . ($days===7  ? 'btn-primary' : 'btn-outline-secondary') . '">7d</a>
    <a href="?days=30" class="btn btn-sm ' . ($days===30 ? 'btn-primary' : 'btn-outline-secondary') . '">30d</a>
    <a href="?days=90" class="btn btn-sm ' . ($days===90 ? 'btn-primary' : 'btn-outline-secondary') . '">90d</a>
');
?>

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header fw-semibold">Revenue &amp; Orders (<?= $days ?> days)</div>
            <div class="card-body"><canvas id="revChart" height="100"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header fw-semibold">Orders by Status</div>
            <div class="card-body"><canvas id="statusChart" height="200"></canvas></div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header fw-semibold">Revenue by Category (<?= $days ?> days)</div>
            <div class="card-body"><canvas id="catChart" height="160"></canvas></div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header fw-semibold">New Customers (<?= $days ?> days)</div>
            <div class="card-body"><canvas id="usersChart" height="160"></canvas></div>
        </div>
    </div>
</div>

<?php if ($searches): ?>
<div class="card shadow-sm">
    <div class="card-header fw-semibold">Top Search Terms</div>
    <div class="table-responsive">
        <table class="table table-sm mb-0 align-middle">
            <thead class="table-light"><tr><th>#</th><th>Term</th><th>Searches</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($searches as $i => $s): ?>
                <tr>
                    <td class="text-muted small"><?= $i+1 ?></td>
                    <td><?= e($s['term']) ?></td>
                    <td><?= number_format($s['hits']) ?></td>
                    <td><a href="<?= e(url('products.php?q=' . urlencode($s['term']))) ?>" class="btn btn-xs btn-outline-secondary btn-sm" target="_blank">Search</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
const revLabels = <?= json_encode(array_column($revenueData, 'day')) ?>;
new Chart('revChart', { type:'line', data:{ labels:revLabels,
    datasets:[
        {label:'Revenue',data:<?= json_encode(array_map(fn($r)=>round((float)$r['revenue'],2),$revenueData)) ?>,yAxisID:'y',borderColor:'#6c63ff',tension:.3,fill:true,backgroundColor:'#6c63ff22'},
        {label:'Orders',data:<?= json_encode(array_column($revenueData,'orders')) ?>,yAxisID:'y1',borderColor:'#48cae4',tension:.3}
    ]}, options:{responsive:true,scales:{y:{beginAtZero:true},y1:{position:'right',beginAtZero:true,grid:{display:false}}}}
});
new Chart('statusChart', { type:'doughnut', data:{
    labels:<?= json_encode(array_column($byStatus,'status')) ?>,
    datasets:[{data:<?= json_encode(array_column($byStatus,'cnt')) ?>,backgroundColor:['#ffc107','#0dcaf0','#6c63ff','#198754','#0d6efd','#dc3545']}]
},options:{responsive:true}});
new Chart('catChart',{type:'bar',data:{labels:<?= json_encode(array_column($byCat,'name')) ?>,datasets:[{label:'Revenue',data:<?= json_encode(array_map(fn($r)=>round((float)$r['revenue'],2),$byCat)) ?>,backgroundColor:'#48cae4aa'}]},options:{responsive:true,plugins:{legend:{display:false}}}});
new Chart('usersChart',{type:'line',data:{labels:<?= json_encode(array_column($newUsers,'day')) ?>,datasets:[{label:'New customers',data:<?= json_encode(array_column($newUsers,'users')) ?>,borderColor:'#198754',fill:true,backgroundColor:'#19875422',tension:.3}]},options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true}}}});
</script>
<?php include __DIR__ . '/../admin/includes/footer.php'; ?>

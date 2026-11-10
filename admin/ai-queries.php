<?php
require_once __DIR__ . '/../admin/includes/auth-check.php';
$pageTitle = 'AI Query Logs';
$panel     = 'admin';

$module  = get_str('module');
$perPage = ADMIN_PER_PAGE;
$page    = get_int('page', 1);

$modules = ['chatbot','assistant','recommendation','routine','ingredient','skin_analysis','comparison'];
$where = []; $params = [];
if ($module && in_array($module, $modules, true)) { $where[]='module=?'; $params[]=$module; }
$whereSql = $where ? 'WHERE '.implode(' AND ',$where) : '';

$total = (int) db_value("SELECT COUNT(*) FROM ai_query_logs $whereSql", $params, 0);
$pg    = paginate($total, $perPage, $page);
$logs  = db_all(
    "SELECT l.*, u.full_name AS user_name FROM ai_query_logs l LEFT JOIN users u ON u.id=l.user_id
     $whereSql ORDER BY l.created_at DESC LIMIT ? OFFSET ?",
    array_merge($params,[$pg['per_page'],$pg['offset']])
);

// Stats
$stats = db_all("SELECT module, COUNT(*) AS total, SUM(escalated_to_human) AS escalated, AVG(response_ms) AS avg_ms FROM ai_query_logs GROUP BY module ORDER BY total DESC");

include __DIR__ . '/../admin/includes/header.php';
admin_page_header('AI Query Logs', '', $total . ' total');
?>
<!-- Stats cards -->
<div class="row g-3 mb-4">
    <?php foreach ($stats as $s): ?>
    <div class="col-6 col-lg-3">
        <div class="card shadow-sm h-100">
            <div class="card-body py-2 px-3">
                <div class="small fw-semibold"><?= ucfirst($s['module']) ?></div>
                <div class="h5 mb-0"><?= number_format($s['total']) ?></div>
                <div class="text-muted" style="font-size:.7rem"><?= number_format($s['escalated']) ?> escalated · <?= number_format((float)$s['avg_ms']) ?>ms avg</div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Filter -->
<div class="d-flex flex-wrap gap-1 mb-3">
    <a href="?" class="btn btn-sm <?= !$module?'btn-primary':'btn-outline-secondary' ?>">All</a>
    <?php foreach ($modules as $m): ?>
    <a href="?module=<?= $m ?>" class="btn btn-sm <?= $module===$m?'btn-primary':'btn-outline-secondary' ?>"><?= ucfirst($m) ?></a>
    <?php endforeach; ?>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Module</th><th>User</th><th>Query</th><th>Source</th><th>Status</th><th>Escalated</th><th>ms</th><th>Date</th></tr></thead>
            <tbody>
                <?php if ($logs): foreach ($logs as $l): ?>
                <tr>
                    <td><span class="badge text-bg-light border"><?= e($l['module']) ?></span></td>
                    <td class="small"><?= $l['user_name']?e($l['user_name']):'<span class="text-muted">Guest</span>' ?></td>
                    <td class="small text-muted"><?= e(truncate($l['query_text']??'',50)) ?></td>
                    <td><?= status_badge($l['response_source']) ?></td>
                    <td><?= status_badge($l['status']) ?></td>
                    <td><?= $l['escalated_to_human']?'<i class="bi bi-person-exclamation text-warning"></i>':'' ?></td>
                    <td class="small text-muted"><?= $l['response_ms']?number_format($l['response_ms']).'ms':'—' ?></td>
                    <td class="small text-muted"><?= time_ago($l['created_at']) ?></td>
                </tr>
                <?php endforeach; else: admin_empty_row(8,'No logs yet.'); endif; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3"><?= render_pagination($pg) ?></div>
<?php include __DIR__ . '/../admin/includes/footer.php'; ?>

<?php
require_once __DIR__ . '/../admin/includes/auth-check.php';
$pageTitle = 'AI Recommendations';
$panel     = 'admin';

$perPage = ADMIN_PER_PAGE;
$page    = get_int('page', 1);

$total = (int) db_value("SELECT COUNT(*) FROM ai_recommendations", [], 0);
$pg    = paginate($total, $perPage, $page);
$rows  = db_all(
    "SELECT ar.*, u.full_name AS user_name, p.name AS product_name
     FROM ai_recommendations ar
     JOIN users u ON u.id=ar.user_id
     JOIN products p ON p.id=ar.product_id
     ORDER BY ar.created_at DESC LIMIT ? OFFSET ?",
    [$pg['per_page'], $pg['offset']]
);

// Stats
$sourceStats = db_all("SELECT source, COUNT(*) AS cnt, SUM(clicked) AS clicks FROM ai_recommendations GROUP BY source");
$typeStats   = db_all("SELECT rec_type, COUNT(*) AS cnt FROM ai_recommendations GROUP BY rec_type ORDER BY cnt DESC");

// Handle hide
if (is_post() && csrf_is_valid() && input('action') === 'hide') {
    db_exec("UPDATE ai_recommendations SET status='hidden' WHERE id=?", [input_int('rec_id')]);
    flash('info','Recommendation hidden.');
    redirect('admin/ai-recommendations.php');
}

include __DIR__ . '/../admin/includes/header.php';
admin_page_header('AI Recommendations', '', $total . ' total');
?>
<div class="row g-3 mb-4">
    <?php foreach ($sourceStats as $s): ?>
    <div class="col-6 col-lg-3"><?= admin_stat_card(ucfirst($s['source']), number_format($s['cnt']), 'bi-magic', 'primary', number_format($s['clicks']) . ' clicked') ?></div>
    <?php endforeach; ?>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>User</th><th>Product</th><th>Type</th><th>Score</th><th>Source</th><th>Status</th><th>Clicked</th><th>Date</th><th></th></tr></thead>
            <tbody>
                <?php if ($rows): foreach ($rows as $r): ?>
                <tr>
                    <td class="small"><?= e($r['user_name']) ?></td>
                    <td class="small"><?= e(truncate($r['product_name'],35)) ?></td>
                    <td><span class="badge text-bg-light border"><?= humanize($r['rec_type']) ?></span></td>
                    <td class="small"><?= number_format((float)$r['score'],1) ?></td>
                    <td><?= status_badge($r['source']) ?></td>
                    <td><?= status_badge($r['status']) ?></td>
                    <td><?= $r['clicked']?'<i class="bi bi-cursor-fill text-success"></i>':'' ?></td>
                    <td class="small text-muted"><?= time_ago($r['created_at']) ?></td>
                    <td>
                        <?php if ($r['status']==='active'): ?>
                        <form method="post" class="d-inline">
                            <?= csrf_field() ?><input type="hidden" name="action" value="hide"><input type="hidden" name="rec_id" value="<?= $r['id'] ?>">
                            <button class="btn btn-xs btn-outline-secondary btn-sm" title="Hide"><i class="bi bi-eye-slash"></i></button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; else: admin_empty_row(9,'No recommendations yet.'); endif; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3"><?= render_pagination($pg) ?></div>
<?php include __DIR__ . '/../admin/includes/footer.php'; ?>

<?php
require_once __DIR__ . '/../admin/includes/auth-check.php';
$pageTitle = 'Reviews';
$panel     = 'admin';

$status  = get_str('status', 'pending');
$perPage = ADMIN_PER_PAGE;
$page    = get_int('page', 1);

$where = ['r.status=?']; $params = [$status ?: 'pending'];
$whereSql = 'WHERE '.implode(' AND ',$where);

$total   = (int) db_value("SELECT COUNT(*) FROM reviews r $whereSql", $params, 0);
$pg      = paginate($total, $perPage, $page);
$reviews = db_all(
    "SELECT r.*, p.name AS product_name, p.slug AS product_slug, u.full_name AS customer_name
     FROM reviews r JOIN products p ON p.id=r.product_id JOIN users u ON u.id=r.user_id
     $whereSql ORDER BY r.created_at DESC LIMIT ? OFFSET ?",
    array_merge($params, [$pg['per_page'], $pg['offset']])
);

// Handle approve/reject
if (is_post() && csrf_is_valid()) {
    $reviewId = input_int('review_id');
    $act      = input('action');
    if (in_array($act, ['approved','rejected'], true)) {
        db_exec('UPDATE reviews SET status=? WHERE id=?', [$act, $reviewId]);
        refresh_product_rating((int) db_value('SELECT product_id FROM reviews WHERE id=?', [$reviewId], 0));
        log_activity('admin_review_'.$act,'review',$reviewId,null,(int)$authUser['id']);
        flash('success','Review '.$act.'.');
    }
    redirect('admin/reviews.php?status='.$status);
}

include __DIR__ . '/../admin/includes/header.php';
admin_page_header('Reviews', '', $total . ' total');
?>
<div class="d-flex flex-wrap gap-1 mb-3">
    <?php foreach (['pending','approved','rejected'] as $s): ?>
    <a href="?status=<?= $s ?>" class="btn btn-sm <?= $status===$s?'btn-primary':'btn-outline-secondary' ?>"><?= ucfirst($s) ?></a>
    <?php endforeach; ?>
</div>
<div class="card shadow-sm">
    <div class="list-group list-group-flush">
        <?php if ($reviews): foreach ($reviews as $r): ?>
        <div class="list-group-item py-3">
            <div class="d-flex justify-content-between flex-wrap gap-2">
                <div>
                    <div class="small fw-semibold"><?= e($r['customer_name']) ?> — <a href="<?= e(url('product-details.php?slug='.urlencode($r['product_slug']))) ?>" target="_blank"><?= e(truncate($r['product_name'],40)) ?></a></div>
                    <div><?= render_stars((float)$r['rating']) ?> <?php if ($r['title']): ?><span class="small fw-semibold ms-1"><?= e($r['title']) ?></span><?php endif; ?></div>
                    <p class="small text-muted mb-0 mt-1"><?= e(truncate($r['body']??'',200)) ?></p>
                    <div class="text-muted" style="font-size:.7rem"><?= format_datetime($r['created_at']) ?> <?= $r['is_verified_purchase']?'<span class="badge text-bg-success ms-1">Verified</span>':'' ?></div>
                </div>
                <?php if ($status==='pending'): ?>
                <div class="d-flex gap-2 align-self-center">
                    <form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="review_id" value="<?= $r['id'] ?>"><input type="hidden" name="action" value="approved"><button class="btn btn-success btn-sm">Approve</button></form>
                    <form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="review_id" value="<?= $r['id'] ?>"><input type="hidden" name="action" value="rejected"><button class="btn btn-outline-danger btn-sm">Reject</button></form>
                </div>
                <?php else: ?><?= status_badge($r['status']) ?><?php endif; ?>
            </div>
        </div>
        <?php endforeach; else: ?>
        <div class="list-group-item text-center text-muted py-4">No <?= e($status) ?> reviews.</div>
        <?php endif; ?>
    </div>
</div>
<div class="mt-3"><?= render_pagination($pg) ?></div>
<?php include __DIR__ . '/../admin/includes/footer.php'; ?>

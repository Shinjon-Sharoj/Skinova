<?php
require_once __DIR__ . '/../admin/includes/auth-check.php';
$pageTitle = 'Blog Posts';
$panel     = 'admin';

$status  = get_str('status');
$perPage = ADMIN_PER_PAGE;
$page    = get_int('page', 1);

$where = []; $params = [];
if ($status && in_array($status, ['draft','published','archived'], true)) { $where[]='b.status=?'; $params[]=$status; }
$whereSql = $where ? 'WHERE '.implode(' AND ',$where) : '';

$total = (int) db_value("SELECT COUNT(*) FROM blogs b $whereSql", $params, 0);
$pg    = paginate($total, $perPage, $page);
$blogs = db_all(
    "SELECT b.id, b.title, b.slug, b.status, b.is_tip, b.view_count, b.like_count, b.comment_count, b.published_at, b.created_at,
            u.full_name AS author_name, bc.name AS category_name
     FROM blogs b JOIN users u ON u.id=b.author_id LEFT JOIN blog_categories bc ON bc.id=b.category_id
     $whereSql ORDER BY b.created_at DESC LIMIT ? OFFSET ?",
    array_merge($params, [$pg['per_page'], $pg['offset']])
);

if (is_post() && csrf_is_valid() && input('action') === 'delete') {
    $bid = input_int('blog_id');
    db_exec('UPDATE blogs SET status=? WHERE id=?', ['archived', $bid]);
    flash('info','Blog post archived.');
    redirect('admin/blogs.php');
}

include __DIR__ . '/../admin/includes/header.php';
admin_page_header('Blog Posts',
    '<a href="' . e(url('admin/blog-form.php')) . '" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> New post</a>',
    $total . ' total'
);
?>
<div class="d-flex flex-wrap gap-1 mb-3">
    <a href="?" class="btn btn-sm <?= !$status?'btn-primary':'btn-outline-secondary' ?>">All</a>
    <?php foreach (['draft','published','archived'] as $s): ?>
    <a href="?status=<?= $s ?>" class="btn btn-sm <?= $status===$s?'btn-primary':'btn-outline-secondary' ?>"><?= ucfirst($s) ?></a>
    <?php endforeach; ?>
</div>
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Title</th><th>Author</th><th>Category</th><th>Status</th><th>Views</th><th>Published</th><th></th></tr></thead>
            <tbody>
                <?php if ($blogs): foreach ($blogs as $b): ?>
                <tr>
                    <td>
                        <div class="small fw-semibold"><?= e(truncate($b['title'],50)) ?></div>
                        <?php if ($b['is_tip']): ?><span class="badge text-bg-info" style="font-size:.6rem">Tip</span><?php endif; ?>
                    </td>
                    <td class="small"><?= e($b['author_name']) ?></td>
                    <td class="small text-muted"><?= e($b['category_name'] ?? '—') ?></td>
                    <td><?= status_badge($b['status']) ?></td>
                    <td class="small"><?= number_format($b['view_count']) ?></td>
                    <td class="small text-muted"><?= $b['published_at'] ? format_date($b['published_at']) : '—' ?></td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="<?= e(url('admin/blog-form.php?id='.$b['id'])) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <?php if ($b['status']==='published'): ?>
                            <a href="<?= e(url('blog-details.php?slug='.urlencode($b['slug']))) ?>" class="btn btn-sm btn-outline-info" target="_blank"><i class="bi bi-eye"></i></a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; else: admin_empty_row(7,'No blog posts found.'); endif; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3"><?= render_pagination($pg) ?></div>
<?php include __DIR__ . '/../admin/includes/footer.php'; ?>

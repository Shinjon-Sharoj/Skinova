<?php
require_once _DIR_ . '/includes/init.php';

$pageTitle = 'Blog';
$catSlug   = get_str('category');
$q         = get_str('q');
$perPage   = 9;
$page      = get_int('page', 1);

// Build query
$where  = ["b.status = 'published'"];
$params = [];

$blogCats = db_all('SELECT * FROM blog_categories ORDER BY name');

$activeCat = null;
if ($catSlug) {
    $activeCat = db_one('SELECT * FROM blog_categories WHERE slug = ?', [$catSlug]);
    if ($activeCat) {
        $where[]  = 'b.category_id = ?';
        $params[] = $activeCat['id'];
    }
}

if ($q !== '') {
    $where[]  = 'MATCH(b.title, b.excerpt, b.content) AGAINST(? IN BOOLEAN MODE)';
    $params[] = $q . '*';
}

$whereSql = ' WHERE ' . implode(' AND ', $where);
$total    = (int) db_value('SELECT COUNT(*) FROM blogs b' . $whereSql, $params, 0);
$pg       = paginate($total, $perPage, $page);
$blogs    = db_all(
    'SELECT b.*, u.full_name AS author_name, bc.name AS category_name, bc.slug AS category_slug
     FROM blogs b
     LEFT JOIN users u ON u.id = b.author_id
     LEFT JOIN blog_categories bc ON bc.id = b.category_id'
    . $whereSql . ' ORDER BY b.published_at DESC LIMIT ? OFFSET ?',
    array_merge($params, [$pg['per_page'], $pg['offset']])
);

// Tips sidebar
$tips = db_all("SELECT id, title, slug, excerpt, published_at FROM blogs WHERE status = 'published' AND is_tip = 1 ORDER BY published_at DESC LIMIT 5");

include _DIR_ . '/includes/header.php';
?>
<div class="container py-4">
    <!-- Page title -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0"><?= $activeCat ? e($activeCat['name']) : 'Skincare Blog' ?></h1>
            <?php if ($q): ?><p class="text-muted mb-0">Search results for: <strong><?= e($q) ?></strong></p><?php endif; ?>
        </div>
        <form class="d-flex gap-2" action="" method="get">
            <input type="search" name="q" class="form-control form-control-sm" placeholder="Search blog…" value="<?= e($q) ?>">
            <button class="btn btn-outline-primary btn-sm">Go</button>
        </form>
    </div>

    <!-- Category pills -->
    <?php if ($blogCats): ?>
    <div class="d-flex flex-wrap gap-2 mb-4">
        <a href="<?= e(url('blog.php')) ?>" class="btn btn-sm <?= !$catSlug ? 'btn-primary' : 'btn-outline-secondary' ?>">All</a>
        <?php foreach ($blogCats as $bc): ?>
        <a href="<?= e(url('blog.php?category=' . urlencode($bc['slug']))) ?>" class="btn btn-sm <?= $catSlug === $bc['slug'] ? 'btn-primary' : 'btn-outline-secondary' ?>">
            <?= e($bc['name']) ?>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="row">
        <!-- Articles -->
        <div class="col-lg-8">
            <?php if ($blogs): ?>
            <div class="row g-4">
                <?php foreach ($blogs as $post): ?>
                <div class="col-md-6">
                    <article class="card h-100 shadow-sm">
                        <a href="<?= e(url('blog-details.php?slug=' . urlencode($post['slug']))) ?>">
                            <img src="<?= e(media_url($post['featured_image'] ?? null, 'blog')) ?>"
                                 class="card-img-top" alt="<?= e($post['title']) ?>"
                                 style="height:180px;object-fit:cover">
                        </a>
                        <div class="card-body d-flex flex-column">
                            <?php if ($post['category_name']): ?>
                            <a href="<?= e(url('blog.php?category=' . urlencode($post['category_slug']))) ?>"
                               class="badge text-bg-light border text-decoration-none mb-2 align-self-start">
                                <?= e($post['category_name']) ?>
                            </a>
                            <?php endif; ?>
                            <h5 class="card-title">
                                <a href="<?= e(url('blog-details.php?slug=' . urlencode($post['slug']))) ?>" class="text-decoration-none text-dark">
                                    <?= e($post['title']) ?>
                                </a>
                            </h5>
                            <p class="card-text text-muted small flex-grow-1"><?= e(truncate($post['excerpt'] ?? $post['content'], 120)) ?></p>
                            <div class="d-flex justify-content-between align-items-center mt-auto">
                                <small class="text-muted"><?= e($post['author_name']) ?> &bull; <?= format_date($post['published_at']) ?></small>
                                <small class="text-muted"><i class="bi bi-eye"></i> <?= number_format($post['view_count']) ?></small>
                            </div>
                        </div>
                    </article>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="mt-4"><?= render_pagination($pg) ?></div>
            <?php else: ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-file-earmark-text" style="font-size:3rem"></i>
                <p class="mt-2">No articles found.</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4 mt-4 mt-lg-0">
            <?php if ($tips): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header"><i class="bi bi-lightbulb"></i> Quick Skincare Tips</div>
                <div class="list-group list-group-flush">
                    <?php foreach ($tips as $tip): ?>
                    <a href="<?= e(url('blog-details.php?slug=' . urlencode($tip['slug']))) ?>" class="list-group-item list-group-item-action py-2">
                        <div class="small fw-semibold"><?= e($tip['title']) ?></div>
                        <div class="text-muted" style="font-size:.75rem"><?= format_date($tip['published_at']) ?></div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- CTA -->
            <div class="card shadow-sm bg-light">
                <div class="card-body text-center">
                    <i class="bi bi-person-heart text-primary fs-2"></i>
                    <h6 class="mt-2">Need personalised advice?</h6>
                    <p class="small text-muted">Book a one-on-one consultation with a certified skincare professional.</p>
                    <a href="<?= e(url('consultations.php')) ?>" class="btn btn-primary btn-sm">Book a consultation</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include _DIR_ . '/includes/footer.php'; ?>
// Cpmment korlam
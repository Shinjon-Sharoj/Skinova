<?php
require_once __DIR__ . '/includes/init.php';

$q       = get_str('q');
$pageTitle = $q ? 'Search: ' . $q : 'Search';
$extraCss  = ['css/products.css'];

$result = $q ? get_products(['q' => $q, 'sort' => get_str('sort', 'popular')], get_int('page', 1)) : ['items' => [], 'total' => 0, 'page' => paginate(0)];
$items  = $result['items'];
$pg     = $result['page'];

if ($q) log_search($q, $pg['total']);

// Suggest popular searches if no results
$popularSearches = [];
if ($q && !$items) {
    $popularSearches = db_all(
        'SELECT term, SUM(results_count) AS hits FROM search_logs WHERE results_count > 0 GROUP BY term ORDER BY hits DESC LIMIT 8'
    );
}

include __DIR__ . '/includes/header.php';
?>
<div class="container py-4">
    <!-- Search bar -->
    <div class="row justify-content-center mb-4">
        <div class="col-md-8">
            <form method="get" action="">
                <div class="input-group input-group-lg">
                    <input type="search" name="q" class="form-control" placeholder="Search for products, brands, ingredients…"
                           value="<?= e($q) ?>" autofocus autocomplete="off" maxlength="100">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Search</button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($q): ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <p class="mb-0 text-muted">
            <?= $pg['total'] > 0
                ? number_format($pg['total']) . ' result' . ($pg['total'] !== 1 ? 's' : '') . ' for <strong>' . e($q) . '</strong>'
                : 'No results for <strong>' . e($q) . '</strong>' ?>
        </p>
        <?php if ($items): ?>
        <form method="get" class="d-flex gap-2">
            <input type="hidden" name="q" value="<?= e($q) ?>">
            <select name="sort" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                <option value="popular" <?= get_str('sort', 'popular') === 'popular' ? 'selected' : '' ?>>Most popular</option>
                <option value="newest"  <?= get_str('sort') === 'newest'  ? 'selected' : '' ?>>Newest</option>
                <option value="price_asc" <?= get_str('sort') === 'price_asc' ? 'selected' : '' ?>>Price ↑</option>
                <option value="price_desc" <?= get_str('sort') === 'price_desc' ? 'selected' : '' ?>>Price ↓</option>
                <option value="rating" <?= get_str('sort') === 'rating' ? 'selected' : '' ?>>Top rated</option>
            </select>
        </form>
        <?php endif; ?>
    </div>

    <?php if ($items): ?>
    <?php render_product_grid($items, ''); ?>
    <div class="mt-4"><?= render_pagination($pg) ?></div>
    <?php else: ?>
    <div class="text-center py-4">
        <p class="text-muted">Try different keywords, or browse our categories.</p>
        <?php if ($popularSearches): ?>
        <p class="text-muted small">Popular searches:</p>
        <div class="d-flex flex-wrap gap-2 justify-content-center">
            <?php foreach ($popularSearches as $ps): ?>
            <a href="?q=<?= urlencode($ps['term']) ?>" class="badge text-bg-light border text-decoration-none fs-6"><?= e($ps['term']) ?></a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <div class="mt-4">
            <a href="<?= e(url('products.php')) ?>" class="btn btn-primary">Browse all products</a>
        </div>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <!-- Empty search state: show popular & categories -->
    <div class="row justify-content-center">
        <div class="col-md-8 text-center">
            <i class="bi bi-search text-muted" style="font-size:4rem"></i>
            <p class="text-muted mt-2">Enter keywords to search for products, brands or ingredients.</p>
        </div>
    </div>
    <?php $cats = get_categories(); if ($cats): ?>
    <h5 class="mt-5 mb-3">Browse by category</h5>
    <div class="row g-3">
        <?php foreach ($cats as $cat): if ($cat['parent_id']) continue; ?>
        <div class="col-6 col-sm-4 col-md-3 col-lg-2">
            <a href="<?= e(url('products.php?category=' . urlencode($cat['slug']))) ?>" class="card text-center text-decoration-none shadow-sm border-0 h-100">
                <div class="card-body py-3">
                    <img src="<?= e(media_url($cat['image'] ?? null, 'category')) ?>" class="rounded-circle mb-2" width="52" height="52" style="object-fit:cover" alt="">
                    <div class="small fw-semibold text-dark"><?= e($cat['name']) ?></div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

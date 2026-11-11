<?php
require_once _DIR_ . '/includes/init.php';

$pageTitle  = 'Categories';
$extraCss   = ['css/products.css'];
$categories = get_categories();

// Group: top-level + their children
$topLevel = [];
$children = [];
foreach ($categories as $c) {
    if ($c['parent_id']) {
        $children[$c['parent_id']][] = $c;
    } else {
        $topLevel[] = $c;
    }
}

include _DIR_ . '/includes/header.php';
?>
<div class="container py-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= e(url('index.php')) ?>">Home</a></li>
            <li class="breadcrumb-item active">Categories</li>
        </ol>
    </nav>

    <h1 class="h3 mb-4">All Categories</h1>

    <?php if ($topLevel): ?>
    <div class="row g-4">
        <?php foreach ($topLevel as $cat): ?>
        <div class="col-sm-6 col-lg-4">
            <div class="card h-100 shadow-sm category-card">
                <a href="<?= e(url('products.php?category=' . urlencode($cat['slug']))) ?>">
                    <img src="<?= e(media_url($cat['image'] ?? null, 'category')) ?>"
                         alt="<?= e($cat['name']) ?>" class="card-img-top"
                         style="height:160px;object-fit:cover">
                </a>
                <div class="card-body">
                    <h2 class="h5 card-title">
                        <a href="<?= e(url('products.php?category=' . urlencode($cat['slug']))) ?>" class="text-decoration-none text-dark">
                            <?= e($cat['name']) ?>
                        </a>
                    </h2>
                    <?php if ($cat['description']): ?>
                    <p class="card-text text-muted small"><?= e(truncate($cat['description'], 100)) ?></p>
                    <?php endif; ?>
                    <span class="badge text-bg-light border"><?= (int) ($cat['product_count'] ?? 0) ?> products</span>

                    <!-- Sub-categories -->
                    <?php if (!empty($children[$cat['id']])): ?>
                    <div class="mt-2 d-flex flex-wrap gap-1">
                        <?php foreach ($children[$cat['id']] as $sub): ?>
                        <a href="<?= e(url('products.php?category=' . urlencode($sub['slug']))) ?>"
                           class="badge text-bg-secondary text-decoration-none">
                            <?= e($sub['name']) ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer bg-transparent">
                    <a href="<?= e(url('products.php?category=' . urlencode($cat['slug']))) ?>" class="btn btn-outline-primary btn-sm w-100">
                        View products
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="text-center py-5 text-muted">
        <i class="bi bi-grid" style="font-size:3rem"></i>
        <p class="mt-2">No categories available.</p>
    </div>
    <?php endif; ?>
</div>
<?php include _DIR_ . '/includes/footer.php'; ?>
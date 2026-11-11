<?php
require_once __DIR__ . '/includes/init.php';

$pageTitle        = APP_NAME . ' - ' . APP_TAGLINE;
$metaDescription  = APP_TAGLINE;
$extraCss         = ['css/products.css'];

// Data
$featured    = get_featured_products(8);
$bestSellers = get_best_sellers(8);
$newArrivals = get_new_arrivals(8);
$categories  = get_categories();
$recentlyViewed = is_logged_in() ? get_recently_viewed((int) current_user_id(), 6) : [];

// Active promotions for hero banners
$promos = db_all(
    "SELECT * FROM promotions WHERE is_active = 1 AND banner_image IS NOT NULL
     AND (starts_at IS NULL OR starts_at <= NOW()) AND (ends_at IS NULL OR ends_at >= NOW())
     ORDER BY priority DESC LIMIT 3"
);

// Latest published blog posts
$latestPosts = db_all(
    "SELECT b.id, b.title, b.slug, b.featured_image, b.excerpt, b.published_at, u.full_name AS author_name
     FROM blogs b JOIN users u ON u.id = b.author_id
     WHERE b.status = 'published' ORDER BY b.published_at DESC LIMIT 3"
);

include __DIR__ . '/includes/header.php';
?>

<!-- ===== HERO ===== -->
<?php if ($promos): ?>
<div id="heroCarousel" class="carousel slide hero-carousel" data-bs-ride="carousel">
    <div class="carousel-indicators">
        <?php foreach ($promos as $i => $promo): ?>
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="<?= $i ?>"
                <?= $i === 0 ? 'class="active" aria-current="true"' : '' ?>
                aria-label="Slide <?= $i + 1 ?>"></button>
        <?php endforeach; ?>
    </div>
    <div class="carousel-inner">
        <?php foreach ($promos as $i => $promo): ?>
        <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
            <div class="hero-banner d-flex align-items-center"
                 style="background-image:url('<?= e(media_url($promo['banner_image'], 'banner')) ?>');min-height:400px;background-size:cover;background-position:center;">
                <div class="container text-white py-5">
                    <h2 class="display-5 fw-bold"><?= e($promo['title']) ?></h2>
                    <?php if ($promo['description']): ?>
                    <p class="lead"><?= e($promo['description']) ?></p>
                    <?php endif; ?>
                    <a href="<?= e(url('products.php')) ?>" class="btn btn-light btn-lg">Shop now</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Previous</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Next</span>
    </button>
</div>
<?php else: ?>
<!-- Default hero when no promo banners -->
<section class="hero-default text-white py-5"
         style="background:linear-gradient(135deg,#6c63ff 0%,#48cae4 100%);min-height:380px">
    <div class="container py-4 text-center">
        <h1 class="display-5 fw-bold mb-3">Smart Skincare for Every Skin Type</h1>
        <p class="lead mb-4">Personalised product recommendations, AI-powered routines and one-to-one expert consultations — all in one place.</p>
        <div class="d-flex flex-wrap gap-2 justify-content-center">
            <a href="<?= e(url('products.php')) ?>" class="btn btn-light btn-lg px-4">Shop now</a>
            <a href="<?= e(url('skincare-profile.php')) ?>" class="btn btn-outline-light btn-lg px-4">Get recommendations</a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ===== VALUE PROPS ===== -->
<section class="py-3 bg-light border-top border-bottom">
    <div class="container">
        <div class="row g-2 text-center justify-content-center">
            <div class="col-6 col-md-3">
                <div class="d-flex align-items-center gap-2 justify-content-center">
                    <i class="bi bi-truck text-primary fs-4"></i>
                    <div class="text-start">
                        <div class="fw-semibold small">Free shipping</div>
                        <div class="text-muted" style="font-size:.75rem">Orders over <?= e(money(get_setting('free_shipping_threshold', 60))) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="d-flex align-items-center gap-2 justify-content-center">
                    <i class="bi bi-shield-check text-success fs-4"></i>
                    <div class="text-start">
                        <div class="fw-semibold small">Verified products</div>
                        <div class="text-muted" style="font-size:.75rem">Dermatologist reviewed</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="d-flex align-items-center gap-2 justify-content-center">
                    <i class="bi bi-person-heart text-danger fs-4"></i>
                    <div class="text-start">
                        <div class="fw-semibold small">Expert consultations</div>
                        <div class="text-muted" style="font-size:.75rem">Real skincare professionals</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="d-flex align-items-center gap-2 justify-content-center">
                    <i class="bi bi-arrow-counterclockwise text-info fs-4"></i>
                    <div class="text-start">
                        <div class="fw-semibold small">Easy returns</div>
                        <div class="text-muted" style="font-size:.75rem"><?= (int) get_setting('return_window_days', 30) ?>-day returns</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== CATEGORIES ===== -->
<?php if ($categories): ?>
<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h4 mb-0">Shop by Category</h2>
            <a href="<?= e(url('categories.php')) ?>" class="btn btn-outline-primary btn-sm">View all</a>
        </div>
        <div class="row g-3">
            <?php foreach (array_slice($categories, 0, 6) as $cat): ?>
            <div class="col-6 col-sm-4 col-md-2">
                <a href="<?= e(url('products.php?category=' . urlencode($cat['slug']))) ?>" class="text-decoration-none">
                    <div class="card text-center shadow-sm h-100 category-card border-0">
                        <div class="card-body">
                            <img src="<?= e(media_url($cat['image'] ?? null, 'category')) ?>" alt="<?= e($cat['name']) ?>"
                                 class="mb-2 rounded-circle" width="72" height="72" style="object-fit:cover">
                            <div class="small fw-semibold text-dark"><?= e($cat['name']) ?></div>
                            <div class="text-muted" style="font-size:.7rem"><?= number_format((int)($cat['product_count'] ?? 0)) ?> products</div>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ===== FEATURED PRODUCTS ===== -->
<?php if ($featured): ?>
<section class="py-5 bg-light">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h4 mb-0">Featured Products</h2>
            <a href="<?= e(url('products.php?sort=popular')) ?>" class="btn btn-outline-primary btn-sm">View all</a>
        </div>
        <?php render_product_grid($featured, '', 'col-6 col-md-4 col-lg-3'); ?>
    </div>
</section>
<?php endif; ?>

<!-- ===== AI / SKIN CARE CTA ===== -->
<section class="py-5 bg-gradient-purple text-white"
         style="background:linear-gradient(135deg,#6c63ff,#a78bfa)">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-md-8">
                <h2 class="h3 fw-bold">Not sure which products are right for you?</h2>
                <p class="mb-0 opacity-90">Complete your skin profile and get AI-powered recommendations tailored to your unique skin. Or book a consultation with a real skincare expert.</p>
            </div>
            <div class="col-md-4 text-md-end d-flex flex-wrap gap-2 justify-content-md-end">
                <a href="<?= e(url('skincare-profile.php')) ?>" class="btn btn-light">Get recommendations</a>
                <a href="<?= e(url('consultations.php')) ?>" class="btn btn-outline-light">Talk to an expert</a>
            </div>
        </div>
    </div>
</section>

<!-- ===== BEST SELLERS ===== -->
<?php if ($bestSellers): ?>
<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h4 mb-0">Best Sellers</h2>
            <a href="<?= e(url('products.php?sort=popular')) ?>" class="btn btn-outline-primary btn-sm">View all</a>
        </div>
        <?php render_product_grid($bestSellers, '', 'col-6 col-md-4 col-lg-3'); ?>
    </div>
</section>
<?php endif; ?>

<!-- ===== NEW ARRIVALS ===== -->
<?php if ($newArrivals): ?>
<section class="py-5 bg-light">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h4 mb-0">New Arrivals</h2>
            <a href="<?= e(url('products.php?sort=newest')) ?>" class="btn btn-outline-primary btn-sm">View all</a>
        </div>
        <?php render_product_grid($newArrivals, '', 'col-6 col-md-4 col-lg-3'); ?>
    </div>
</section>
<?php endif; ?>

<!-- ===== RECENTLY VIEWED ===== -->
<?php if ($recentlyViewed): ?>
<section class="py-5">
    <div class="container">
        <h2 class="h4 mb-4">Recently Viewed</h2>
        <?php render_product_grid($recentlyViewed, '', 'col-6 col-md-4 col-lg-2'); ?>
    </div>
</section>
<?php endif; ?>

<!-- ===== BLOG POSTS ===== -->
<?php if ($latestPosts): ?>
<section class="py-5 bg-light">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h4 mb-0">From the Blog</h2>
            <a href="<?= e(url('blog.php')) ?>" class="btn btn-outline-primary btn-sm">All articles</a>
        </div>
        <div class="row g-4">
            <?php foreach ($latestPosts as $post): ?>
            <div class="col-md-4">
                <article class="card h-100 shadow-sm">
                    <a href="<?= e(url('blog-details.php?slug=' . urlencode($post['slug']))) ?>">
                        <img src="<?= e(media_url($post['featured_image'] ?? null, 'blog')) ?>"
                             class="card-img-top" alt="<?= e($post['title']) ?>"
                             style="height:180px;object-fit:cover">
                    </a>
                    <div class="card-body">
                        <h3 class="h6 card-title">
                            <a href="<?= e(url('blog-details.php?slug=' . urlencode($post['slug']))) ?>" class="text-decoration-none text-dark">
                                <?= e($post['title']) ?>
                            </a>
                        </h3>
                        <?php if ($post['excerpt']): ?>
                        <p class="card-text text-muted small"><?= e(truncate($post['excerpt'], 100)) ?></p>
                        <?php endif; ?>
                        <small class="text-muted"><?= e($post['author_name']) ?> &bull; <?= format_date($post['published_at']) ?></small>
                    </div>
                </article>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>

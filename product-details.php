<?php
require_once __DIR__ . '/includes/init.php';

$slug = get_str('slug');
if ($slug === '') not_found();

$product = get_product_by_slug($slug);
if (!$product) not_found();

$details = get_product_details((int) $product['id']);
track_product_view((int) $product['id']);

// Reviews
$reviewsPerPage = 5;
$reviewPage = get_int('rpage', 1);
$reviewTotal = (int) db_value("SELECT COUNT(*) FROM reviews WHERE product_id = ? AND status = 'approved'", [$product['id']], 0);
$rpg = paginate($reviewTotal, $reviewsPerPage, $reviewPage);
$reviews = db_all(
    "SELECT r.*, u.full_name, up.avatar FROM reviews r
     JOIN users u ON u.id = r.user_id
     LEFT JOIN user_profiles up ON up.user_id = r.user_id
     WHERE r.product_id = ? AND r.status = 'approved'
     ORDER BY r.is_verified_purchase DESC, r.helpful_count DESC, r.created_at DESC
     LIMIT ? OFFSET ?",
    [$product['id'], $rpg['per_page'], $rpg['offset']]
);

// Rating distribution
$ratingDist = [];
for ($i = 5; $i >= 1; $i--) {
    $ratingDist[$i] = (int) db_value("SELECT COUNT(*) FROM reviews WHERE product_id = ? AND status = 'approved' AND rating = ?", [$product['id'], $i], 0);
}

// User review state
$userReview = null;
$canReview  = false;
if (is_logged_in()) {
    $userReview = db_one('SELECT * FROM reviews WHERE product_id = ? AND user_id = ?', [$product['id'], current_user_id()]);
    if (!$userReview) {
        $canReview = (bool) db_value(
            "SELECT 1 FROM order_items oi JOIN orders o ON o.id = oi.order_id WHERE oi.product_id = ? AND o.user_id = ? AND o.status = 'delivered' LIMIT 1",
            [$product['id'], current_user_id()]
        );
    }
}

// Related products
$related = get_related_products($product, 4);

// Recently viewed
$recentlyViewed = is_logged_in() ? get_recently_viewed((int) current_user_id(), 4, (int) $product['id']) : [];

// In wishlist?
$inWishlist = is_logged_in() ? isset(wishlist_product_ids()[(int) $product['id']]) : false;

$pageTitle       = $product['name'];
$metaDescription = $product['short_description'] ?? truncate($product['description'] ?? '', 160);
$extraCss        = ['css/products.css'];

include __DIR__ . '/includes/header.php';
?>
<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= e(url('index.php')) ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= e(url('products.php')) ?>">Products</a></li>
            <?php if ($product['category_name']): ?>
            <li class="breadcrumb-item">
                <a href="<?= e(url('products.php?category=' . urlencode($product['category_slug']))) ?>"><?= e($product['category_name']) ?></a>
            </li>
            <?php endif; ?>
            <li class="breadcrumb-item active" aria-current="page"><?= e(truncate($product['name'], 40)) ?></li>
        </ol>
    </nav>

    <!-- ===== Product info ===== -->
    <div class="row g-4 mb-5">
        <!-- Images -->
        <div class="col-md-5">
            <?php if ($details['images']): ?>
            <div id="productCarousel" class="carousel slide border rounded" data-bs-ride="false">
                <div class="carousel-inner">
                    <?php foreach ($details['images'] as $i => $img): ?>
                    <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
                        <img src="<?= e(media_url($img['image_path'], 'product')) ?>"
                             class="d-block w-100" alt="<?= e($img['alt_text'] ?? $product['name']) ?>"
                             style="object-fit:contain;height:380px;background:#f8f8f8">
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($details['images']) > 1): ?>
                <button class="carousel-control-prev" type="button" data-bs-target="#productCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" style="filter:invert(1) brightness(0.5)"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#productCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon" style="filter:invert(1) brightness(0.5)"></span>
                    <span class="visually-hidden">Next</span>
                </button>
                <!-- Thumbnails -->
                <div class="d-flex gap-2 flex-wrap mt-2 justify-content-center p-2">
                    <?php foreach ($details['images'] as $i => $img): ?>
                    <img src="<?= e(media_url($img['image_path'], 'product')) ?>" alt=""
                         class="border rounded cursor-pointer thumbnail-img <?= $i === 0 ? 'border-primary' : '' ?>"
                         width="60" height="60" style="object-fit:cover;cursor:pointer"
                         data-bs-target="#productCarousel" data-bs-slide-to="<?= $i ?>">
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <img src="<?= e(media_url(null, 'product')) ?>" class="img-fluid rounded border" alt="<?= e($product['name']) ?>">
            <?php endif; ?>
        </div>

        <!-- Details -->
        <div class="col-md-7">
            <?php if ($product['brand_name']): ?>
            <a href="<?= e(url('products.php?brand=' . urlencode($product['brand_slug']))) ?>" class="text-muted small text-uppercase text-decoration-none">
                <?= e($product['brand_name']) ?>
            </a>
            <?php endif; ?>
            <h1 class="h3 mt-1 mb-1"><?= e($product['name']) ?></h1>

            <!-- Rating -->
            <div class="d-flex align-items-center gap-2 mb-2">
                <?= render_stars((float) $product['rating_avg'], true) ?>
                <a href="#reviews" class="small text-muted">(<?= number_format($product['rating_count']) ?> reviews)</a>
                <?php if ($product['sold_count'] > 0): ?>
                <span class="text-muted small">&bull; <?= number_format($product['sold_count']) ?> sold</span>
                <?php endif; ?>
            </div>

            <!-- Price -->
            <div class="mb-3">
                <span class="fs-4 fw-bold text-primary"><?= e(money($product['final_price'])) ?></span>
                <?php if ((float) $product['discount_percent'] > 0): ?>
                <span class="text-muted text-decoration-line-through ms-2"><?= e(money($product['price'])) ?></span>
                <span class="badge bg-danger ms-1">-<?= rtrim(rtrim(number_format((float) $product['discount_percent'], 2),'0'),'.') ?>%</span>
                <?php endif; ?>
            </div>

            <!-- Stock -->
            <?php $stock = (int) $product['stock']; ?>
            <div class="mb-3">
                <?php if ($stock <= 0): ?>
                <span class="badge bg-secondary fs-6">Out of stock</span>
                <?php elseif ($stock <= (int) get_setting('low_stock_alert_threshold', 5)): ?>
                <span class="badge bg-warning text-dark fs-6">Only <?= $stock ?> left!</span>
                <?php else: ?>
                <span class="badge bg-success fs-6">In stock</span>
                <?php endif; ?>
            </div>

            <?php if ($product['short_description']): ?>
            <p class="text-muted"><?= e($product['short_description']) ?></p>
            <?php endif; ?>

            <!-- Skin types & concerns badges -->
            <?php if ($details['skin_types']): ?>
            <div class="mb-2">
                <?php foreach ($details['skin_types'] as $st): ?>
                <a href="<?= e(url('products.php?skin_type=' . $st)) ?>" class="badge text-bg-info text-decoration-none me-1"><?= e(ucfirst($st)) ?></a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Add to cart -->
            <div class="d-flex gap-2 align-items-center mb-4 mt-3">
                <div class="input-group" style="width:130px">
                    <button class="btn btn-outline-secondary btn-sm" type="button" id="qtyMinus">-</button>
                    <input type="number" id="qtyInput" value="1" min="1" max="<?= $stock ?>" class="form-control text-center" style="width:50px">
                    <button class="btn btn-outline-secondary btn-sm" type="button" id="qtyPlus">+</button>
                </div>
                <button class="btn btn-primary flex-grow-1" data-action="add-to-cart"
                        data-product-id="<?= $product['id'] ?>"
                        data-qty-input="#qtyInput"
                        <?= $stock <= 0 ? 'disabled' : '' ?>>
                    <i class="bi bi-cart-plus me-1"></i> <?= $stock > 0 ? 'Add to cart' : 'Out of stock' ?>
                </button>
                <button class="btn btn-outline-danger wishlist-btn <?= $inWishlist ? 'active' : '' ?>"
                        data-action="toggle-wishlist" data-product-id="<?= $product['id'] ?>"
                        aria-label="Wishlist" aria-pressed="<?= $inWishlist ? 'true' : 'false' ?>">
                    <i class="bi <?= $inWishlist ? 'bi-heart-fill' : 'bi-heart' ?>"></i>
                </button>
            </div>

            <!-- SKU / Size -->
            <div class="text-muted small d-flex flex-wrap gap-3">
                <span>SKU: <?= e($product['sku']) ?></span>
                <?php if ($product['size_label']): ?><span>Size: <?= e($product['size_label']) ?></span><?php endif; ?>
                <?php if ($product['routine_step'] && $product['routine_step'] !== 'other'): ?>
                <span>Step: <?= e(ucfirst(str_replace('_',' ',$product['routine_step']))) ?></span>
                <?php endif; ?>
            </div>

            <!-- Compare / AI -->
            <div class="mt-3 d-flex gap-2">
                <a href="<?= e(url('product-comparison.php?ids=' . $product['id'])) ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-columns-gap"></i> Compare
                </a>
                <a href="<?= e(url('ingredient-analyzer.php')) ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-eyedropper"></i> Analyze ingredients
                </a>
            </div>
        </div>
    </div>

    <!-- ===== Tabs: Description / Ingredients / How to use ===== -->
    <ul class="nav nav-tabs mb-4" id="productTabs" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-desc" type="button">Description</button></li>
        <?php if ($details['ingredients']): ?><li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-ingr" type="button">Ingredients</button></li><?php endif; ?>
        <?php if ($product['usage_instructions']): ?><li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-how" type="button">How to use</button></li><?php endif; ?>
        <?php if ($product['benefits']): ?><li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-ben" type="button">Benefits</button></li><?php endif; ?>
    </ul>
    <div class="tab-content mb-5" id="productTabsContent">
        <div class="tab-pane fade show active" id="tab-desc">
            <?php if ($product['description']): ?>
            <div class="product-description"><?= sanitize_html($product['description']) ?></div>
            <?php else: ?>
            <p class="text-muted">No description available.</p>
            <?php endif; ?>
            <?php if ($product['precautions']): ?>
            <div class="alert alert-warning mt-3 small"><i class="bi bi-exclamation-triangle"></i> <strong>Precautions:</strong> <?= e($product['precautions']) ?></div>
            <?php endif; ?>
        </div>
        <?php if ($details['ingredients']): ?>
        <div class="tab-pane fade" id="tab-ingr">
            <p class="small text-muted">Ingredients are listed in order of concentration (highest first).</p>
            <div class="row g-2">
                <?php foreach ($details['ingredients'] as $ing): ?>
                <div class="col-md-6">
                    <a href="<?= e(url('ingredient-analyzer.php?ingredient=' . urlencode($ing['slug']))) ?>"
                       class="d-flex align-items-center gap-2 p-2 border rounded text-decoration-none text-dark hover-bg">
                        <span class="badge <?= $ing['risk_level'] === 'high' ? 'text-bg-danger' : ($ing['risk_level'] === 'moderate' ? 'text-bg-warning' : 'text-bg-success') ?>">
                            <?= e(ucfirst($ing['risk_level'])) ?>
                        </span>
                        <div>
                            <div class="small fw-semibold"><?= e($ing['name']) ?></div>
                            <?php if ($ing['ingredient_type']): ?><div class="text-muted" style="font-size:.7rem"><?= e($ing['ingredient_type']) ?></div><?php endif; ?>
                        </div>
                        <?php if ($ing['is_common_allergen']): ?><i class="bi bi-exclamation-triangle text-warning ms-auto" title="Common allergen"></i><?php endif; ?>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php if ($product['usage_instructions']): ?>
        <div class="tab-pane fade" id="tab-how">
            <div><?= nl2br(e($product['usage_instructions'])) ?></div>
        </div>
        <?php endif; ?>
        <?php if ($product['benefits']): ?>
        <div class="tab-pane fade" id="tab-ben">
            <ul class="list-group list-group-flush">
                <?php foreach (explode("\n", trim($product['benefits'])) as $b): if (trim($b) === '') continue; ?>
                <li class="list-group-item d-flex gap-2 align-items-start border-0 px-0">
                    <i class="bi bi-check-circle-fill text-success mt-1"></i> <?= e(trim($b)) ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>

    <!-- ===== Reviews ===== -->
    <div id="reviews" class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h4 mb-0">Customer Reviews</h2>
            <?php if ($canReview): ?>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#reviewModal">
                <i class="bi bi-pencil"></i> Write a review
            </button>
            <?php endif; ?>
        </div>

        <!-- Rating summary -->
        <?php if ($product['rating_count'] > 0): ?>
        <div class="row g-4 mb-4 align-items-center">
            <div class="col-auto text-center">
                <div class="display-4 fw-bold text-primary"><?= number_format((float) $product['rating_avg'], 1) ?></div>
                <?= render_stars((float) $product['rating_avg']) ?>
                <div class="small text-muted mt-1"><?= number_format($product['rating_count']) ?> reviews</div>
            </div>
            <div class="col">
                <?php for ($i = 5; $i >= 1; $i--): $cnt = $ratingDist[$i] ?? 0; $pct = $product['rating_count'] > 0 ? round($cnt / $product['rating_count'] * 100) : 0; ?>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="small text-muted" style="width:40px"><?= $i ?> <i class="bi bi-star-fill text-warning"></i></span>
                    <div class="progress flex-grow-1" style="height:10px">
                        <div class="progress-bar bg-warning" style="width:<?= $pct ?>%"></div>
                    </div>
                    <span class="small text-muted" style="width:30px"><?= $cnt ?></span>
                </div>
                <?php endfor; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Review list -->
        <?php if ($reviews): ?>
        <div class="list-group list-group-flush">
            <?php foreach ($reviews as $r): ?>
            <div class="list-group-item px-0 py-3">
                <div class="d-flex gap-3">
                    <img src="<?= e(media_url($r['avatar'] ?? null, 'avatar')) ?>" class="rounded-circle flex-shrink-0" width="44" height="44" style="object-fit:cover" alt="">
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <span class="fw-semibold small"><?= e($r['full_name']) ?></span>
                                <?php if ($r['is_verified_purchase']): ?>
                                <span class="badge text-bg-success ms-1 small">Verified purchase</span>
                                <?php endif; ?>
                            </div>
                            <small class="text-muted"><?= time_ago($r['created_at']) ?></small>
                        </div>
                        <?= render_stars($r['rating']) ?>
                        <?php if ($r['title']): ?><div class="fw-semibold mt-1"><?= e($r['title']) ?></div><?php endif; ?>
                        <p class="mb-1 small"><?= nl2br(e($r['body'])) ?></p>
                        <!-- Helpful -->
                        <?php if (is_logged_in()): ?>
                        <form method="post" action="<?= e(url('actions/reviews/helpful.php')) ?>" class="d-inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="review_id" value="<?= $r['id'] ?>">
                            <button type="submit" class="btn btn-link btn-sm p-0 text-muted">
                                <i class="bi bi-hand-thumbs-up"></i> Helpful (<?= $r['helpful_count'] ?>)
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="mt-3"><?= render_pagination($rpg, array_merge($_GET, [])) ?></div>
        <?php else: ?>
        <p class="text-muted">No reviews yet.<?= $canReview ? ' Be the first to review this product!' : '' ?></p>
        <?php endif; ?>
    </div>

    <!-- ===== Related products ===== -->
    <?php if ($related): ?>
    <section class="mb-5">
        <h2 class="h4 mb-3">You may also like</h2>
        <?php render_product_grid($related, '', 'col-6 col-md-3'); ?>
    </section>
    <?php endif; ?>

    <!-- ===== Recently viewed ===== -->
    <?php if ($recentlyViewed): ?>
    <section class="mb-5">
        <h2 class="h4 mb-3">Recently Viewed</h2>
        <?php render_product_grid($recentlyViewed, '', 'col-6 col-md-3'); ?>
    </section>
    <?php endif; ?>
</div>

<!-- Review Modal -->
<?php if ($canReview): ?>
<div class="modal fade" id="reviewModal" tabindex="-1" aria-labelledby="reviewModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="<?= e(url('actions/reviews/submit.php')) ?>" enctype="multipart/form-data" novalidate class="needs-validation">
                <?= csrf_field() ?>
                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                <div class="modal-header">
                    <h5 class="modal-title" id="reviewModalLabel">Write a review for <?= e($product['name']) ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Rating <span class="text-danger">*</span></label>
                        <div class="d-flex gap-2 fs-3" id="starRating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <label class="cursor-pointer text-warning" for="star<?= $i ?>">
                                <input type="radio" name="rating" id="star<?= $i ?>" value="<?= $i ?>" class="visually-hidden" required>
                                <i class="bi bi-star" data-star="<?= $i ?>"></i>
                            </label>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="rev_title" class="form-label">Review title</label>
                        <input type="text" id="rev_title" name="title" class="form-control" maxlength="150" placeholder="Summary of your experience">
                    </div>
                    <div class="mb-3">
                        <label for="rev_body" class="form-label">Your review <span class="text-danger">*</span></label>
                        <textarea id="rev_body" name="body" class="form-control" rows="4" required placeholder="Tell others what you think…"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="rev_images" class="form-label">Photos <span class="text-muted">(optional, up to 3)</span></label>
                        <input type="file" id="rev_images" name="images[]" class="form-control" accept="image/*" multiple>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit review</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
// Simple star rating UI
document.querySelectorAll('#starRating label').forEach(label => {
    const inp = label.querySelector('input');
    const val = parseInt(inp.value);
    label.addEventListener('mouseenter', () => highlightStars(val));
    label.addEventListener('mouseleave', () => {
        const checked = document.querySelector('#starRating input:checked');
        highlightStars(checked ? parseInt(checked.value) : 0);
    });
    inp.addEventListener('change', () => highlightStars(val));
});
function highlightStars(n) {
    document.querySelectorAll('#starRating i').forEach((star, i) => {
        star.className = i < n ? 'bi bi-star-fill' : 'bi bi-star';
    });
}
</script>
<?php endif; ?>

<script>
// Qty +/- buttons
const qtyInput = document.getElementById('qtyInput');
const maxQty = parseInt(qtyInput?.max) || 99;
document.getElementById('qtyMinus')?.addEventListener('click', () => {
    if (qtyInput && parseInt(qtyInput.value) > 1) qtyInput.value = parseInt(qtyInput.value) - 1;
});
document.getElementById('qtyPlus')?.addEventListener('click', () => {
    if (qtyInput && parseInt(qtyInput.value) < maxQty) qtyInput.value = parseInt(qtyInput.value) + 1;
});
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>

<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/auth-check.php';

$pageTitle = 'My Wishlist';
$extraCss  = ['css/products.css'];

$items = db_all(
    'SELECT wi.id, wi.product_id, wi.price_when_added, wi.added_at,
            p.name, p.slug, p.price, p.discount_percent, p.is_active, p.size_label,
            ROUND(p.price*(1-p.discount_percent/100),2) AS final_price,
            COALESCE(i.quantity,0) AS stock,
            (SELECT pi.image_path FROM product_images pi WHERE pi.product_id=p.id ORDER BY pi.is_primary DESC, pi.id LIMIT 1) AS image_path
     FROM wishlist_items wi
     JOIN wishlists w ON w.id = wi.wishlist_id
     JOIN products p ON p.id = wi.product_id
     LEFT JOIN inventory i ON i.product_id = p.id
     WHERE w.user_id = ?
     ORDER BY wi.added_at DESC',
    [$authUser['id']]
);

include __DIR__ . '/includes/header.php';
?>
<div class="container py-4">
    <div class="row">
        <div class="col-lg-3 mb-4"><?php include __DIR__ . '/includes/sidebar.php'; ?></div>
        <div class="col-lg-9">
            <h2 class="h4 mb-4">My Wishlist
                <?php if ($items): ?>
                <span class="text-muted fs-6 fw-normal">(<?= count($items) ?> item<?= count($items) !== 1 ? 's' : '' ?>)</span>
                <?php endif; ?>
            </h2>

            <?php if ($items): ?>
            <div class="row g-3">
                <?php foreach ($items as $item): ?>
                <div class="col-sm-6 col-lg-4">
                    <div class="card h-100 shadow-sm">
                        <div class="position-relative">
                            <a href="<?= e(url('product-details.php?slug=' . urlencode($item['slug']))) ?>">
                                <img src="<?= e(product_image_url($item['image_path'] ?? null)) ?>"
                                     class="card-img-top p-2" alt="<?= e($item['name']) ?>"
                                     style="height:180px;object-fit:contain;background:#f8f9fa">
                            </a>
                            <?php if (!$item['is_active'] || $item['stock'] <= 0): ?>
                            <span class="badge bg-secondary position-absolute top-0 start-0 m-2">Out of stock</span>
                            <?php endif; ?>
                            <!-- Price drop badge -->
                            <?php if ((float) $item['final_price'] < (float) $item['price_when_added']): ?>
                            <span class="badge bg-danger position-absolute top-0 end-0 m-2">Price drop!</span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <a href="<?= e(url('product-details.php?slug=' . urlencode($item['slug']))) ?>"
                               class="card-title text-dark text-decoration-none fw-semibold small d-block mb-1">
                                <?= e($item['name']) ?>
                            </a>
                            <?php if ($item['size_label']): ?>
                            <div class="text-muted small mb-1"><?= e($item['size_label']) ?></div>
                            <?php endif; ?>
                            <div class="mb-2">
                                <span class="fw-bold text-primary"><?= e(money($item['final_price'])) ?></span>
                                <?php if ((float) $item['discount_percent'] > 0): ?>
                                <small class="text-muted text-decoration-line-through ms-1"><?= e(money($item['price'])) ?></small>
                                <?php endif; ?>
                            </div>
                            <div class="text-muted small mb-2">Added <?= time_ago($item['added_at']) ?></div>
                        </div>
                        <div class="card-footer bg-transparent d-flex gap-2">
                            <button class="btn btn-primary btn-sm flex-grow-1"
                                    data-action="add-to-cart" data-product-id="<?= $item['product_id'] ?>"
                                    <?= ($item['stock'] <= 0 || !$item['is_active']) ? 'disabled' : '' ?>>
                                <i class="bi bi-cart-plus"></i> Add to cart
                            </button>
                            <button class="btn btn-outline-danger btn-sm"
                                    data-action="toggle-wishlist" data-product-id="<?= $item['product_id'] ?>"
                                    aria-label="Remove from wishlist">
                                <i class="bi bi-heart-fill"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-heart" style="font-size:3rem"></i>
                <p class="mt-2">Your wishlist is empty.</p>
                <a href="<?= e(url('products.php')) ?>" class="btn btn-primary">Discover products</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

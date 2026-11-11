<?php
require_once _DIR_ . '/includes/init.php';
require_once _DIR_ . '/includes/auth-check.php';

$pageTitle = 'Shopping Cart';
$extraCss  = ['css/products.css'];
$extraJs   = ['js/cart.js'];

$items   = cart_items((int) $authUser['id']);
$pricing = $items ? calculate_pricing((int) $authUser['id']) : null;

include _DIR_ . '/includes/header.php';
?>
<div class="container py-4">
    <h1 class="h3 mb-4">
        Shopping Cart
        <?php if ($items): ?>
        <span class="text-muted fs-6 fw-normal">(<?= count($items) ?> item<?= count($items) !== 1 ? 's' : '' ?>)</span>
        <?php endif; ?>
    </h1>

    <?php if ($items): ?>
    <div class="row g-4">
        <!-- Cart items -->
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <?php foreach ($items as $item): ?>
                    <div class="cart-item d-flex gap-3 p-3 border-bottom" id="cart-item-<?= $item['product_id'] ?>">
                        <a href="<?= e(url('product-details.php?slug=' . urlencode($item['slug']))) ?>">
                            <img src="<?= e(product_image_url($item['image_path'] ?? null)) ?>"
                                 alt="<?= e($item['name']) ?>" class="rounded"
                                 width="90" height="90" style="object-fit:contain;background:#f8f9fa">
                        </a>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <a href="<?= e(url('product-details.php?slug=' . urlencode($item['slug']))) ?>"
                                       class="fw-semibold text-dark text-decoration-none"><?= e($item['name']) ?></a>
                                    <?php if ($item['size_label']): ?>
                                    <div class="text-muted small"><?= e($item['size_label']) ?></div>
                                    <?php endif; ?>
                                    <div class="d-flex align-items-center gap-2 mt-1">
                                        <span class="fw-bold text-primary"><?= e(money($item['unit_price'])) ?></span>
                                        <?php if ((float) $item['discount_percent'] > 0): ?>
                                        <span class="text-muted text-decoration-line-through small"><?= e(money($item['original_price'])) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <!-- Remove -->
                                <button class="btn btn-link text-danger p-0 ms-2 cart-remove-btn"
                                        data-product-id="<?= $item['product_id'] ?>"
                                        aria-label="Remove <?= e($item['name']) ?> from cart">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                            <!-- Qty + stock warning -->
                            <div class="d-flex align-items-center gap-3 mt-2 flex-wrap">
                                <div class="input-group" style="width:120px">
                                    <button class="btn btn-outline-secondary btn-sm cart-qty-btn" type="button"
                                            data-product-id="<?= $item['product_id'] ?>" data-delta="-1">-</button>
                                    <input type="number" class="form-control form-control-sm text-center cart-qty-input"
                                           value="<?= (int) $item['quantity'] ?>" min="1" max="<?= (int) $item['stock'] ?>"
                                           data-product-id="<?= $item['product_id'] ?>" style="width:45px">
                                    <button class="btn btn-outline-secondary btn-sm cart-qty-btn" type="button"
                                            data-product-id="<?= $item['product_id'] ?>" data-delta="1">+</button>
                                </div>
                                <?php if ($item['stock'] <= 0): ?>
                                <span class="badge bg-danger small">Out of stock</span>
                                <?php elseif ($item['stock'] < $item['quantity']): ?>
                                <span class="badge bg-warning text-dark small">Only <?= $item['stock'] ?> left</span>
                                <?php endif; ?>
                                <span class="text-muted small">
                                    Subtotal: <strong><?= e(money($item['unit_price'] * $item['quantity'])) ?></strong>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="card-footer bg-transparent d-flex justify-content-between">
                    <a href="<?= e(url('products.php')) ?>" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left"></i> Continue shopping
                    </a>
                    <form method="post" action="<?= e(url('actions/cart/clear.php')) ?>"
                          onsubmit="return confirm('Clear your entire cart?')">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-trash"></i> Clear cart
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Summary -->
        <div class="col-lg-4">
            <div class="card shadow-sm" id="cartSummary">
                <div class="card-header fw-semibold">Order summary</div>
                <div class="card-body">
                    <!-- Coupon -->
                    <form id="couponForm" class="d-flex gap-2 mb-3">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="text" id="couponInput" name="code" class="form-control form-control-sm"
                               placeholder="Coupon code"
                               value="<?= e($pricing['coupon_code'] ?? '') ?>">
                        <button type="submit" class="btn btn-outline-primary btn-sm">Apply</button>
                        <?php if ($pricing['coupon_code'] ?? ''): ?>
                        <button type="button" id="removeCoupon" class="btn btn-outline-danger btn-sm">✕</button>
                        <?php endif; ?>
                    </form>

                    <!-- Loyalty -->
                    <?php if ((int) $authUser['loyalty_points'] > 0): ?>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">
                            Loyalty points <span class="text-muted fw-normal">(<?= number_format((int) $authUser['loyalty_points']) ?> available)</span>
                        </label>
                        <div class="d-flex gap-2">
                            <input type="number" id="loyaltyInput" class="form-control form-control-sm"
                                   min="0" max="<?= $pricing['loyalty_max_usable'] ?? 0 ?>"
                                   value="<?= $pricing['loyalty_points_used'] ?? 0 ?>"
                                   placeholder="0">
                            <button type="button" id="applyLoyalty" class="btn btn-outline-success btn-sm">Use</button>
                        </div>
                        <div class="form-text">Max usable: <?= number_format($pricing['loyalty_max_usable'] ?? 0) ?> pts</div>
                    </div>
                    <?php endif; ?>

                    <!-- Pricing breakdown -->
                    <table class="table table-sm mb-3">
                        <tbody>
                            <tr><td>Subtotal</td><td class="text-end"><?= e(money($pricing['subtotal'] ?? 0)) ?></td></tr>
                            <?php if (($pricing['promotion_discount'] ?? 0) > 0): ?>
                            <tr class="text-success"><td>Promotions</td><td class="text-end">-<?= e(money($pricing['promotion_discount'])) ?></td></tr>
                            <?php endif; ?>
                            <?php if (($pricing['coupon_discount'] ?? 0) > 0): ?>
                            <tr class="text-success"><td>Coupon (<?= e($pricing['coupon_code']) ?>)</td><td class="text-end">-<?= e(money($pricing['coupon_discount'])) ?></td></tr>
                            <?php endif; ?>
                            <?php if (($pricing['loyalty_discount'] ?? 0) > 0): ?>
                            <tr class="text-success"><td>Loyalty points</td><td class="text-end">-<?= e(money($pricing['loyalty_discount'])) ?></td></tr>
                            <?php endif; ?>
                            <?php if (($pricing['shipping_fee'] ?? 0) > 0): ?>
                            <tr><td>Shipping</td><td class="text-end"><?= e(money($pricing['shipping_fee'])) ?></td></tr>
                            <?php else: ?>
                            <tr class="text-success"><td>Shipping</td><td class="text-end">Free</td></tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-active fw-bold">
                                <td>Total</td>
                                <td class="text-end text-primary fs-5"><?= e(money($pricing['total'] ?? 0)) ?></td>
                            </tr>
                        </tfoot>
                    </table>

                    <?php if (($pricing['points_to_earn'] ?? 0) > 0): ?>
                    <div class="alert alert-info py-2 small">
                        <i class="bi bi-gem"></i> You'll earn <strong><?= number_format($pricing['points_to_earn']) ?></strong> loyalty points with this order.
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($pricing['issues'])): ?>
                    <div class="alert alert-warning py-2 small">
                        <?php foreach ($pricing['issues'] as $issue): ?>
                        <div><i class="bi bi-exclamation-triangle"></i> <?= e($issue) ?></div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <a href="<?= e(url('checkout.php')) ?>"
                       class="btn btn-primary w-100 <?= ($pricing['can_checkout'] ?? false) ? '' : 'disabled' ?>">
                        <i class="bi bi-lock"></i> Proceed to checkout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php else: ?>
    <div class="text-center py-5">
        <i class="bi bi-bag text-muted" style="font-size:4rem"></i>
        <h4 class="mt-3">Your cart is empty</h4>
        <p class="text-muted">Add some products to get started.</p>
        <a href="<?= e(url('products.php')) ?>" class="btn btn-primary">Browse products</a>
    </div>
    <?php endif; ?>
</div>
<?php include _DIR_ . '/includes/footer.php'; ?>
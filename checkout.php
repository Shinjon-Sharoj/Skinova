<?php
require_once _DIR_ . '/includes/init.php';
require_once _DIR_ . '/includes/auth-check.php';

$pageTitle        = 'Checkout';
$extraCss         = ['css/products.css'];
$extraJs          = ['js/checkout.js'];
$hideFloatingChat = true;

$pricing = calculate_pricing((int) $authUser['id']);
if (!$pricing['can_checkout']) {
    flash('warning', 'Your cart is empty or has issues. Please review your cart.');
    redirect('cart.php');
}

$addresses = db_all('SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, id', [$authUser['id']]);
$defaultAddr = null;
foreach ($addresses as $addr) {
    if ($addr['is_default']) { $defaultAddr = $addr; break; }
}
if (!$defaultAddr && $addresses) $defaultAddr = $addresses[0];

include _DIR_ . '/includes/header.php';
?>
<div class="container py-4" style="max-width:960px">
    <h1 class="h3 mb-4">Checkout</h1>

    <!-- Checkout steps indicator -->
    <div class="d-flex align-items-center gap-2 mb-4 text-muted small">
        <span class="badge text-bg-secondary">1. Cart</span>
        <i class="bi bi-chevron-right"></i>
        <span class="badge text-bg-primary">2. Checkout</span>
        <i class="bi bi-chevron-right"></i>
        <span class="badge text-bg-light border">3. Payment</span>
        <i class="bi bi-chevron-right"></i>
        <span class="badge text-bg-light border">4. Confirmation</span>
    </div>

    <form method="post" action="<?= e(url('actions/checkout/place-order.php')) ?>" id="checkoutForm" novalidate class="needs-validation">
        <?= csrf_field() ?>
        <div class="row g-4">
            <!-- Left: Shipping + Payment -->
            <div class="col-lg-7">

                <!-- Shipping address -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span class="fw-semibold">Shipping address</span>
                        <a href="<?= e(url('addresses.php?new=1')) ?>" class="btn btn-link btn-sm p-0" target="_blank">
                            + Add new address
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if ($addresses): ?>
                        <?php foreach ($addresses as $addr): ?>
                        <div class="form-check mb-2 p-0">
                            <label class="d-flex gap-3 p-3 border rounded cursor-pointer w-100 <?= $addr['is_default'] ? 'border-primary bg-light' : '' ?>">
                                <input type="radio" class="form-check-input mt-1" name="address_id"
                                       value="<?= $addr['id'] ?>" required
                                       <?= ($defaultAddr && $addr['id'] == $defaultAddr['id']) ? 'checked' : '' ?>>
                                <div>
                                    <div class="fw-semibold small"><?= e($addr['label']) ?> — <?= e($addr['recipient_name']) ?></div>
                                    <div class="text-muted small">
                                        <?= e($addr['address_line1']) ?><?= $addr['address_line2'] ? ', ' . e($addr['address_line2']) : '' ?>,
                                        <?= e($addr['city']) ?><?= $addr['state'] ? ', ' . e($addr['state']) : '' ?>
                                        <?= e($addr['postal_code']) ?>, <?= e($addr['country']) ?>
                                    </div>
                                    <div class="text-muted small"><i class="bi bi-telephone"></i> <?= e($addr['phone']) ?></div>
                                </div>
                            </label>
                        </div>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <div class="alert alert-warning mb-0">
                            You have no saved addresses.
                            <a href="<?= e(url('addresses.php?new=1')) ?>">Add an address</a> to continue.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Payment method -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header fw-semibold">Payment method</div>
                    <div class="card-body">
                        <div class="form-check mb-3 p-0">
                            <label class="d-flex gap-3 p-3 border rounded cursor-pointer w-100 border-primary bg-light">
                                <input type="radio" class="form-check-input mt-1" name="payment_method" value="cod" checked>
                                <div>
                                    <div class="fw-semibold small"><i class="bi bi-cash-coin text-success me-1"></i> Cash on delivery</div>
                                    <div class="text-muted small">Pay when your order arrives.</div>
                                </div>
                            </label>
                        </div>
                        <div class="form-check p-0">
                            <label class="d-flex gap-3 p-3 border rounded cursor-pointer w-100">
                                <input type="radio" class="form-check-input mt-1" name="payment_method" value="online">
                                <div>
                                    <div class="fw-semibold small"><i class="bi bi-credit-card text-primary me-1"></i> Online payment</div>
                                    <div class="text-muted small">Pay securely by card. (<?= ONLINE_PAYMENT_PROVIDER === 'stripe' ? 'Stripe' : 'Sandbox/test mode' ?>)</div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Order notes -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header fw-semibold">Order notes <span class="text-muted fw-normal">(optional)</span></div>
                    <div class="card-body">
                        <textarea name="notes" class="form-control" rows="2" maxlength="500"
                                  placeholder="Special delivery instructions or notes for the seller…"><?= e(old('notes')) ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Right: Summary -->
            <div class="col-lg-5">
                <div class="card shadow-sm sticky-top" style="top:80px">
                    <div class="card-header fw-semibold">Order summary</div>
                    <div class="card-body p-0">
                        <!-- Items list -->
                        <ul class="list-group list-group-flush">
                            <?php foreach ($pricing['items'] as $it): ?>
                            <li class="list-group-item d-flex gap-2 py-2">
                                <img src="<?= e(product_image_url($it['image_path'] ?? null)) ?>" width="44" height="44"
                                     style="object-fit:contain;background:#f8f9fa;border-radius:4px" alt="">
                                <div class="flex-grow-1 small">
                                    <div class="fw-semibold text-truncate" style="max-width:160px"><?= e($it['name']) ?></div>
                                    <div class="text-muted">Qty: <?= $it['quantity'] ?> × <?= e(money($it['unit_price'])) ?></div>
                                </div>
                                <div class="small fw-semibold"><?= e(money($it['line_total'])) ?></div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <!-- Pricing breakdown -->
                        <div class="p-3 border-top">
                            <table class="table table-sm mb-0">
                                <tbody>
                                    <tr><td class="text-muted">Subtotal</td><td class="text-end"><?= e(money($pricing['subtotal'])) ?></td></tr>
                                    <?php if ($pricing['promotion_discount'] > 0): ?>
                                    <tr class="text-success"><td>Promotions</td><td class="text-end">-<?= e(money($pricing['promotion_discount'])) ?></td></tr>
                                    <?php endif; ?>
                                    <?php if ($pricing['coupon_discount'] > 0): ?>
                                    <tr class="text-success"><td>Coupon</td><td class="text-end">-<?= e(money($pricing['coupon_discount'])) ?></td></tr>
                                    <?php endif; ?>
                                    <?php if ($pricing['loyalty_discount'] > 0): ?>
                                    <tr class="text-success"><td>Loyalty</td><td class="text-end">-<?= e(money($pricing['loyalty_discount'])) ?></td></tr>
                                    <?php endif; ?>
                                    <tr>
                                        <td class="text-muted">Shipping</td>
                                        <td class="text-end <?= $pricing['shipping_fee'] > 0 ? '' : 'text-success' ?>">
                                            <?= $pricing['shipping_fee'] > 0 ? e(money($pricing['shipping_fee'])) : 'Free' ?>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="table-active fw-bold">
                                        <td>Total</td>
                                        <td class="text-end text-primary fs-5"><?= e(money($pricing['total'])) ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                            <?php if ($pricing['points_to_earn'] > 0): ?>
                            <div class="alert alert-info py-2 small mt-2 mb-0">
                                <i class="bi bi-gem"></i> Earn <?= number_format($pricing['points_to_earn']) ?> loyalty points
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 py-2 fs-5"
                                <?= $addresses ? '' : 'disabled' ?>>
                            <i class="bi bi-lock"></i> Place order
                        </button>
                        <p class="text-muted small text-center mt-2 mb-0">
                            <i class="bi bi-shield-check"></i> Secure checkout
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
<?php include _DIR_ . '/includes/footer.php'; ?>
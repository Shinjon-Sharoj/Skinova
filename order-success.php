<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/auth-check.php';

$orderId = get_int('id');
$order   = $orderId ? get_order($orderId, (int) $authUser['id']) : null;
if (!$order) not_found();

$orderItems = get_order_items($orderId);
$pageTitle  = 'Order Confirmed';
$hideFloatingChat = true;

include __DIR__ . '/includes/header.php';
?>
<div class="container py-5 text-center" style="max-width:640px">
    <div class="mb-4">
        <div class="rounded-circle bg-success d-inline-flex align-items-center justify-content-center mb-3"
             style="width:80px;height:80px">
            <i class="bi bi-check-lg text-white" style="font-size:2.5rem"></i>
        </div>
        <h1 class="h3 fw-bold">Order placed successfully!</h1>
        <p class="text-muted">Thank you for your order. We'll get it ready soon.</p>
    </div>

    <!-- Order summary card -->
    <div class="card shadow-sm text-start mb-4">
        <div class="card-body">
            <div class="row g-2 text-muted small mb-3">
                <div class="col-6">Order number<br><strong class="text-dark fs-6"><?= e($order['order_number']) ?></strong></div>
                <div class="col-6">Order date<br><strong class="text-dark"><?= format_datetime($order['created_at']) ?></strong></div>
                <div class="col-6">Payment<br><strong class="text-dark"><?= humanize($order['payment_method']) ?></strong> <?= status_badge($order['payment_status']) ?></div>
                <div class="col-6">Total<br><strong class="text-primary fs-5"><?= e(money($order['total'])) ?></strong></div>
            </div>

            <?php if ($order['loyalty_points_earned'] > 0): ?>
            <div class="alert alert-success py-2 small">
                <i class="bi bi-gem"></i> You earned <strong><?= number_format($order['loyalty_points_earned']) ?></strong> loyalty points with this order!
            </div>
            <?php endif; ?>

            <!-- Items -->
            <ul class="list-group list-group-flush">
                <?php foreach ($orderItems as $it): ?>
                <li class="list-group-item px-0 d-flex gap-2 align-items-center py-2">
                    <img src="<?= e(product_image_url($it['image_path'] ?? null)) ?>" width="44" height="44"
                         style="object-fit:contain;background:#f8f9fa;border-radius:4px" alt="">
                    <div class="flex-grow-1 small">
                        <div class="fw-semibold"><?= e($it['product_name']) ?></div>
                        <div class="text-muted">Qty: <?= $it['quantity'] ?> × <?= e(money($it['unit_price'])) ?></div>
                    </div>
                    <div class="small fw-semibold"><?= e(money($it['line_total'])) ?></div>
                </li>
                <?php endforeach; ?>
            </ul>

            <!-- Shipping address -->
            <div class="mt-3 small text-muted border-top pt-3">
                <strong class="text-dark">Shipping to:</strong><br>
                <?= e($order['shipping_name']) ?>, <?= e($order['shipping_address']) ?>,
                <?= e($order['shipping_city']) ?><?= $order['shipping_state'] ? ', ' . e($order['shipping_state']) : '' ?>
                <?= e($order['shipping_postal_code']) ?>, <?= e($order['shipping_country']) ?>
            </div>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 justify-content-center">
        <a href="<?= e(url('order-details.php?id=' . $orderId)) ?>" class="btn btn-primary">
            <i class="bi bi-box-seam"></i> Track my order
        </a>
        <a href="<?= e(url('products.php')) ?>" class="btn btn-outline-secondary">
            Continue shopping
        </a>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

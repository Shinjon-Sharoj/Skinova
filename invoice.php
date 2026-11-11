<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/auth-check.php';

$orderId = get_int('id');
$order   = $orderId ? get_order($orderId, (int) $authUser['id']) : null;
if (!$order) not_found();

$items   = get_order_items($orderId);
$hideNavbar      = true;
$hideFooter      = true;
$hideFloatingChat = true;
$pageTitle       = 'Invoice ' . $order['order_number'];

include __DIR__ . '/includes/header.php';
?>
<div class="container py-4" id="invoice" style="max-width:680px">
    <!-- Invoice header -->
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h1 class="h4 fw-bold mb-0"><?= e(APP_NAME) ?></h1>
            <div class="text-muted small"><?= e(SUPPORT_EMAIL) ?> &bull; <?= e(SUPPORT_PHONE) ?></div>
        </div>
        <div class="text-end">
            <div class="h5 fw-bold text-primary">INVOICE</div>
            <div class="small"># <?= e($order['order_number']) ?></div>
            <div class="small text-muted"><?= format_date($order['created_at']) ?></div>
        </div>
    </div>

    <hr>

    <!-- Bill to / Ship to -->
    <div class="row mb-4">
        <div class="col-6">
            <div class="fw-semibold small mb-1">Bill to</div>
            <div class="small text-muted">
                <?= e($order['customer_name']) ?><br>
                <?= e($order['customer_email']) ?>
            </div>
        </div>
        <div class="col-6">
            <div class="fw-semibold small mb-1">Ship to</div>
            <div class="small text-muted">
                <?= e($order['shipping_name']) ?><br>
                <?= e($order['shipping_address']) ?><br>
                <?= e($order['shipping_city']) ?><?= $order['shipping_state'] ? ', ' . e($order['shipping_state']) : '' ?>
                <?= e($order['shipping_postal_code']) ?><br>
                <?= e($order['shipping_country']) ?>
            </div>
        </div>
    </div>

    <!-- Items table -->
    <table class="table table-bordered table-sm mb-4">
        <thead class="table-light">
            <tr>
                <th>Item</th>
                <th class="text-end">Unit price</th>
                <th class="text-end">Qty</th>
                <th class="text-end">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $it): ?>
            <tr>
                <td>
                    <?= e($it['product_name']) ?>
                    <?php if ($it['sku']): ?><br><small class="text-muted">SKU: <?= e($it['sku']) ?></small><?php endif; ?>
                </td>
                <td class="text-end"><?= e(money($it['unit_price'])) ?></td>
                <td class="text-end"><?= $it['quantity'] ?></td>
                <td class="text-end"><?= e(money($it['line_total'])) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr><td colspan="3" class="text-end text-muted">Subtotal</td><td class="text-end"><?= e(money($order['subtotal'])) ?></td></tr>
            <?php if ($order['promotion_discount'] > 0): ?>
            <tr class="text-success"><td colspan="3" class="text-end">Promotions</td><td class="text-end">-<?= e(money($order['promotion_discount'])) ?></td></tr>
            <?php endif; ?>
            <?php if ($order['coupon_discount'] > 0): ?>
            <tr class="text-success"><td colspan="3" class="text-end">Coupon (<?= e($order['coupon_code']) ?>)</td><td class="text-end">-<?= e(money($order['coupon_discount'])) ?></td></tr>
            <?php endif; ?>
            <?php if ($order['loyalty_discount'] > 0): ?>
            <tr class="text-success"><td colspan="3" class="text-end">Loyalty points</td><td class="text-end">-<?= e(money($order['loyalty_discount'])) ?></td></tr>
            <?php endif; ?>
            <?php if ($order['shipping_fee'] > 0): ?>
            <tr><td colspan="3" class="text-end">Shipping</td><td class="text-end"><?= e(money($order['shipping_fee'])) ?></td></tr>
            <?php endif; ?>
            <tr class="table-active fw-bold">
                <td colspan="3" class="text-end">Total</td>
                <td class="text-end text-primary"><?= e(money($order['total'])) ?></td>
            </tr>
        </tfoot>
    </table>

    <!-- Payment status -->
    <div class="d-flex justify-content-between text-muted small mb-4">
        <div>Payment: <?= humanize($order['payment_method']) ?> — <?= status_badge($order['payment_status']) ?></div>
        <div>Order status: <?= status_badge($order['status']) ?></div>
    </div>

    <div class="text-muted small text-center border-top pt-3">
        <?= e(AI_DISCLAIMER) ?><br>
        Thank you for shopping with <?= e(APP_NAME) ?>!
    </div>
</div>

<div class="container text-center mt-3 pb-4 d-print-none">
    <button onclick="window.print()" class="btn btn-primary"><i class="bi bi-printer"></i> Print invoice</button>
    <a href="<?= e(url('order-details.php?id=' . $orderId)) ?>" class="btn btn-outline-secondary ms-2">Back to order</a>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

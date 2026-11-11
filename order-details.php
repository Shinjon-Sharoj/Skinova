<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/auth-check.php';

$orderId    = get_int('id');
$order      = $orderId ? get_order($orderId, (int) $authUser['id']) : null;
if (!$order) not_found();

$items      = get_order_items($orderId);
$history    = get_order_history($orderId);
$shipment   = db_one('SELECT * FROM shipments WHERE order_id = ?', [$orderId]);
$canCancel  = customer_can_cancel($order);
$pageTitle  = 'Order ' . $order['order_number'];

include __DIR__ . '/includes/header.php';
?>
<div class="container py-4">
    <div class="row">
        <div class="col-lg-3 mb-4"><?php include __DIR__ . '/includes/sidebar.php'; ?></div>
        <div class="col-lg-9">
            <!-- Header -->
            <div class="d-flex flex-wrap justify-content-between align-items-start mb-4 gap-2">
                <div>
                    <h2 class="h4 mb-0">Order <?= e($order['order_number']) ?></h2>
                    <div class="text-muted small"><?= format_datetime($order['created_at']) ?></div>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <?= status_badge($order['status']) ?>
                    <?= status_badge($order['payment_status']) ?>
                    <a href="<?= e(url('invoice.php?id=' . $orderId)) ?>" class="btn btn-outline-secondary btn-sm" target="_blank">
                        <i class="bi bi-file-text"></i> Invoice
                    </a>
                    <?php if ($order['status'] === 'shipped' || $order['status'] === 'delivered'): ?>
                    <a href="<?= e(url('track-order.php?id=' . $orderId)) ?>" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-truck"></i> Track
                    </a>
                    <?php endif; ?>
                    <?php if ($order['payment_method'] === 'online' && $order['payment_status'] === 'pending' && $order['status'] === 'pending'): ?>
                    <a href="<?= e(url('payment.php?order=' . $orderId)) ?>" class="btn btn-primary btn-sm">
                        <i class="bi bi-credit-card"></i> Pay now
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="row g-4">
                <!-- Items -->
                <div class="col-lg-8">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header">Items</div>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($items as $it): ?>
                            <li class="list-group-item d-flex gap-3 py-3">
                                <?php if ($it['slug']): ?>
                                <a href="<?= e(url('product-details.php?slug=' . urlencode($it['slug']))) ?>">
                                    <img src="<?= e(product_image_url($it['image_path'] ?? null)) ?>" width="56" height="56"
                                         style="object-fit:contain;background:#f8f9fa;border-radius:4px" alt="">
                                </a>
                                <?php else: ?>
                                <img src="<?= e(media_url(null, 'product')) ?>" width="56" height="56" style="border-radius:4px" alt="">
                                <?php endif; ?>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold small">
                                        <?= $it['slug'] ? '<a href="' . e(url('product-details.php?slug=' . urlencode($it['slug']))) . '" class="text-dark text-decoration-none">' . e($it['product_name']) . '</a>' : e($it['product_name']) ?>
                                    </div>
                                    <?php if ($it['sku']): ?><div class="text-muted small">SKU: <?= e($it['sku']) ?></div><?php endif; ?>
                                    <div class="text-muted small">Qty: <?= $it['quantity'] ?> × <?= e(money($it['unit_price'])) ?></div>
                                </div>
                                <div class="fw-semibold small"><?= e(money($it['line_total'])) ?></div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <!-- Status history -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header">Order timeline</div>
                        <div class="card-body p-3">
                            <ul class="list-unstyled timeline mb-0">
                                <?php foreach (array_reverse($history) as $h): ?>
                                <li class="d-flex gap-3 mb-3">
                                    <div class="timeline-dot bg-primary rounded-circle flex-shrink-0 mt-1" style="width:12px;height:12px"></div>
                                    <div>
                                        <div class="fw-semibold small"><?= status_badge($h['status']) ?></div>
                                        <?php if ($h['note']): ?><div class="text-muted small"><?= e($h['note']) ?></div><?php endif; ?>
                                        <div class="text-muted" style="font-size:.7rem"><?= format_datetime($h['created_at']) ?></div>
                                    </div>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>

                    <!-- Return / cancel buttons -->
                    <div class="d-flex gap-2 flex-wrap">
                        <?php if ($canCancel): ?>
                        <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#cancelModal">
                            <i class="bi bi-x-circle"></i> Cancel order
                        </button>
                        <?php endif; ?>
                        <?php if ($order['status'] === 'delivered'): ?>
                        <a href="<?= e(url('returns.php?order=' . $orderId)) ?>" class="btn btn-outline-warning btn-sm">
                            <i class="bi bi-arrow-return-left"></i> Request return
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Sidebar: payment + shipping -->
                <div class="col-lg-4">
                    <!-- Pricing -->
                    <div class="card shadow-sm mb-3">
                        <div class="card-header">Payment summary</div>
                        <div class="card-body">
                            <table class="table table-sm mb-0">
                                <tbody>
                                    <tr><td class="text-muted">Subtotal</td><td class="text-end"><?= e(money($order['subtotal'])) ?></td></tr>
                                    <?php if ($order['promotion_discount'] > 0): ?>
                                    <tr class="text-success"><td>Promotions</td><td class="text-end">-<?= e(money($order['promotion_discount'])) ?></td></tr>
                                    <?php endif; ?>
                                    <?php if ($order['coupon_discount'] > 0): ?>
                                    <tr class="text-success"><td>Coupon</td><td class="text-end">-<?= e(money($order['coupon_discount'])) ?></td></tr>
                                    <?php endif; ?>
                                    <?php if ($order['loyalty_discount'] > 0): ?>
                                    <tr class="text-success"><td>Loyalty</td><td class="text-end">-<?= e(money($order['loyalty_discount'])) ?></td></tr>
                                    <?php endif; ?>
                                    <tr><td class="text-muted">Shipping</td><td class="text-end"><?= $order['shipping_fee'] > 0 ? e(money($order['shipping_fee'])) : 'Free' ?></td></tr>
                                </tbody>
                                <tfoot>
                                    <tr class="table-active fw-bold">
                                        <td>Total</td>
                                        <td class="text-end text-primary"><?= e(money($order['total'])) ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                            <div class="mt-2 small text-muted">
                                Payment: <?= humanize($order['payment_method']) ?> — <?= status_badge($order['payment_status']) ?>
                            </div>
                        </div>
                    </div>

                    <!-- Shipping info -->
                    <div class="card shadow-sm mb-3">
                        <div class="card-header">Shipping address</div>
                        <div class="card-body small text-muted">
                            <?= e($order['shipping_name']) ?><br>
                            <?= e($order['shipping_address']) ?><br>
                            <?= e($order['shipping_city']) ?><?= $order['shipping_state'] ? ', ' . e($order['shipping_state']) : '' ?>
                            <?= e($order['shipping_postal_code']) ?><br>
                            <?= e($order['shipping_country']) ?> &bull; <?= e($order['shipping_phone']) ?>
                        </div>
                    </div>

                    <!-- Shipment tracking -->
                    <?php if ($shipment && $shipment['tracking_number']): ?>
                    <div class="card shadow-sm">
                        <div class="card-header">Tracking</div>
                        <div class="card-body small">
                            <div><?= e($shipment['carrier'] ?? 'Carrier') ?>: <strong><?= e($shipment['tracking_number']) ?></strong></div>
                            <?php if ($shipment['estimated_delivery']): ?>
                            <div class="text-muted">Est. delivery: <?= format_date($shipment['estimated_delivery']) ?></div>
                            <?php endif; ?>
                            <?= status_badge($shipment['status']) ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Cancel Modal -->
<?php if ($canCancel): ?>
<div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="<?= e(url('actions/orders/cancel.php')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="order_id" value="<?= $orderId ?>">
                <div class="modal-header">
                    <h5 class="modal-title" id="cancelModalLabel">Cancel Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to cancel order <strong><?= e($order['order_number']) ?></strong>?</p>
                    <div class="mb-3">
                        <label for="cancel_reason" class="form-label">Reason <span class="text-muted">(optional)</span></label>
                        <textarea id="cancel_reason" name="cancel_reason" class="form-control" rows="2" maxlength="300"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Keep order</button>
                    <button type="submit" class="btn btn-danger">Yes, cancel order</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>

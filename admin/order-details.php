<?php
require_once __DIR__ . '/../admin/includes/auth-check.php';
$pageTitle = 'Order Details';
$panel     = 'admin';

$id    = get_int('id');
$order = $id ? get_order($id) : null;
if (!$order) not_found();

$items    = get_order_items($id);
$history  = get_order_history($id);
$shipment = db_one('SELECT * FROM shipments WHERE order_id=?', [$id]);
$refunds  = db_all('SELECT * FROM refunds WHERE order_id=? ORDER BY created_at DESC', [$id]);

// Handle status update
if (is_post() && csrf_is_valid()) {
    $act = input('action');

    if ($act === 'update_status') {
        $newStatus = input('status');
        $note      = mb_substr(clean_text(input('note')), 0, 300);
        $shipData  = [];
        if ($newStatus === 'shipped') {
            $shipData = ['carrier' => input('carrier'), 'tracking_number' => input('tracking_number'), 'estimated_delivery' => input('estimated_delivery') ?: null];
        }
        $res = update_order_status($id, $newStatus, $note ?: null, (int)$authUser['id'], $shipData);
        flash($res['ok'] ? 'success' : 'danger', $res['ok'] ? 'Order status updated.' : ($res['error'] ?? 'Failed.'));
    } elseif ($act === 'mark_paid') {
        $txn = input('transaction_id') ?: ('MANUAL_' . strtoupper(bin2hex(random_bytes(4))));
        $res = mark_order_paid($id, $txn, 'manual');
        flash($res['ok'] ? 'success' : 'danger', $res['ok'] ? 'Order marked as paid.' : 'Failed.');
    }
    log_activity('admin_order_' . $act, 'order', $id, null, (int)$authUser['id']);
    redirect('admin/order-details.php?id=' . $id);
}

$allowedTransitions = ORDER_TRANSITIONS[$order['status']] ?? [];

include __DIR__ . '/../admin/includes/header.php';
admin_page_header('Order ' . $order['order_number'],
    '<a href="' . e(url('admin/orders.php')) . '" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Orders</a>
     <a href="' . e(url('invoice.php?id=' . $id)) . '" class="btn btn-outline-info btn-sm" target="_blank"><i class="bi bi-file-text"></i> Invoice</a>'
);
?>
<div class="row g-4">
    <div class="col-lg-8">
        <!-- Items -->
        <div class="card shadow-sm mb-4">
            <div class="card-header fw-semibold">Items</div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light"><tr><th>Product</th><th>Unit price</th><th>Qty</th><th>Total</th></tr></thead>
                    <tbody>
                        <?php foreach ($items as $it): ?>
                        <tr>
                            <td class="small">
                                <div class="fw-semibold"><?= e($it['product_name']) ?></div>
                                <?php if ($it['sku']): ?><div class="text-muted">SKU: <?= e($it['sku']) ?></div><?php endif; ?>
                            </td>
                            <td class="small"><?= e(money($it['unit_price'])) ?></td>
                            <td class="small"><?= $it['quantity'] ?></td>
                            <td class="small fw-semibold"><?= e(money($it['line_total'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- History -->
        <div class="card shadow-sm mb-4">
            <div class="card-header fw-semibold">Timeline</div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <?php foreach (array_reverse($history) as $h): ?>
                    <li class="d-flex gap-3 mb-3">
                        <div class="flex-shrink-0 mt-1"><span class="badge rounded-pill text-bg-primary">&bull;</span></div>
                        <div>
                            <?= status_badge($h['status']) ?>
                            <?php if ($h['note']): ?><div class="small text-muted mt-1"><?= e($h['note']) ?></div><?php endif; ?>
                            <div class="text-muted" style="font-size:.7rem"><?= format_datetime($h['created_at']) ?></div>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <!-- Refunds -->
        <?php if ($refunds): ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header fw-semibold">Refunds</div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light"><tr><th>Amount</th><th>Reason</th><th>Status</th><th>Date</th></tr></thead>
                    <tbody>
                        <?php foreach ($refunds as $r): ?>
                        <tr>
                            <td class="small"><?= e(money($r['amount'])) ?></td>
                            <td class="small text-muted"><?= e(truncate($r['reason']??'',50)) ?></td>
                            <td><?= status_badge($r['status']) ?></td>
                            <td class="small text-muted"><?= format_date($r['created_at']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-lg-4">
        <!-- Pricing -->
        <div class="card shadow-sm mb-3">
            <div class="card-header fw-semibold">Pricing</div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tbody>
                        <tr><td class="text-muted small">Subtotal</td><td class="text-end small"><?= e(money($order['subtotal'])) ?></td></tr>
                        <?php if ($order['promotion_discount']>0): ?><tr class="text-success"><td class="small">Promotions</td><td class="text-end small">-<?= e(money($order['promotion_discount'])) ?></td></tr><?php endif; ?>
                        <?php if ($order['coupon_discount']>0): ?><tr class="text-success"><td class="small">Coupon</td><td class="text-end small">-<?= e(money($order['coupon_discount'])) ?></td></tr><?php endif; ?>
                        <?php if ($order['loyalty_discount']>0): ?><tr class="text-success"><td class="small">Loyalty</td><td class="text-end small">-<?= e(money($order['loyalty_discount'])) ?></td></tr><?php endif; ?>
                        <tr><td class="small text-muted">Shipping</td><td class="text-end small"><?= $order['shipping_fee']>0?e(money($order['shipping_fee'])):'Free' ?></td></tr>
                    </tbody>
                    <tfoot><tr class="table-active fw-bold"><td>Total</td><td class="text-end text-primary"><?= e(money($order['total'])) ?></td></tr></tfoot>
                </table>
                <div class="small text-muted mt-2">Payment: <?= humanize($order['payment_method']) ?> — <?= status_badge($order['payment_status']) ?></div>
            </div>
        </div>

        <!-- Customer & shipping -->
        <div class="card shadow-sm mb-3">
            <div class="card-header fw-semibold">Customer</div>
            <div class="card-body small">
                <div class="fw-semibold"><?= e($order['customer_name']) ?></div>
                <div class="text-muted"><?= e($order['customer_email']) ?></div>
                <div class="mt-2 fw-semibold">Ship to:</div>
                <div class="text-muted"><?= e($order['shipping_name']) ?>, <?= e($order['shipping_address']) ?>, <?= e($order['shipping_city']) ?> <?= e($order['shipping_postal_code']) ?>, <?= e($order['shipping_country']) ?></div>
                <a href="<?= e(url('admin/user-details.php?id=' . $order['user_id'])) ?>" class="btn btn-outline-secondary btn-sm mt-2">View customer</a>
            </div>
        </div>

        <!-- Actions -->
        <div class="card shadow-sm mb-3">
            <div class="card-header fw-semibold">Update Status</div>
            <div class="card-body">
                <?php if ($allowedTransitions): ?>
                <form method="post">
                    <?= csrf_field() ?><input type="hidden" name="action" value="update_status">
                    <div class="mb-2">
                        <select name="status" class="form-select form-select-sm" id="newStatusSel">
                            <?php foreach ($allowedTransitions as $t): ?>
                            <option value="<?= $t ?>"><?= humanize($t) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- Shipped fields -->
                    <div id="shippedFields" class="d-none mb-2">
                        <input name="carrier" class="form-control form-control-sm mb-1" placeholder="Carrier (e.g. FedEx)">
                        <input name="tracking_number" class="form-control form-control-sm mb-1" placeholder="Tracking number">
                        <input type="date" name="estimated_delivery" class="form-control form-control-sm">
                    </div>
                    <div class="mb-2"><input name="note" class="form-control form-control-sm" placeholder="Note (optional)" maxlength="300"></div>
                    <button class="btn btn-primary btn-sm w-100">Update</button>
                </form>
                <script>
                document.getElementById('newStatusSel')?.addEventListener('change',function(){
                    document.getElementById('shippedFields').classList.toggle('d-none',this.value!=='shipped');
                });
                </script>
                <?php else: ?>
                <p class="small text-muted mb-0">No transitions available for status: <?= status_badge($order['status']) ?></p>
                <?php endif; ?>

                <?php if ($order['payment_status']==='pending' && $order['status']!=='cancelled'): ?>
                <hr>
                <form method="post">
                    <?= csrf_field() ?><input type="hidden" name="action" value="mark_paid">
                    <input name="transaction_id" class="form-control form-control-sm mb-2" placeholder="Transaction ID (optional)">
                    <button class="btn btn-success btn-sm w-100">Mark as paid</button>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Shipment -->
        <?php if ($shipment && ($shipment['tracking_number'] || $shipment['carrier'])): ?>
        <div class="card shadow-sm">
            <div class="card-header fw-semibold">Shipment</div>
            <div class="card-body small">
                <?php if ($shipment['carrier']): ?><div>Carrier: <?= e($shipment['carrier']) ?></div><?php endif; ?>
                <?php if ($shipment['tracking_number']): ?><div>Tracking: <strong><?= e($shipment['tracking_number']) ?></strong></div><?php endif; ?>
                <div><?= status_badge($shipment['status']) ?></div>
                <?php if ($shipment['estimated_delivery']): ?><div class="text-muted">Est: <?= format_date($shipment['estimated_delivery']) ?></div><?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/../admin/includes/footer.php'; ?>

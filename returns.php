<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/auth-check.php';

$pageTitle = 'Returns & Refunds';
$orderId   = get_int('order');

// My returns list
$myReturns = db_all(
    'SELECT r.*, o.order_number FROM returns r JOIN orders o ON o.id = r.order_id
     WHERE r.user_id = ? ORDER BY r.created_at DESC',
    [$authUser['id']]
);

// Order for new return request
$order      = null;
$orderItems = [];
if ($orderId) {
    $order = get_order($orderId, (int) $authUser['id']);
    if (!$order || $order['status'] !== 'delivered') {
        flash('warning', 'Returns are only available for delivered orders.');
        redirect('returns.php');
    }
    $returnWindow = (int) get_setting('return_window_days', 30);
    $deliveredAt  = strtotime($order['delivered_at'] ?? $order['created_at']);
    if ($deliveredAt && (time() - $deliveredAt) > $returnWindow * 86400) {
        flash('warning', "The {$returnWindow}-day return window for this order has passed.");
        redirect('returns.php');
    }
    $orderItems = get_order_items($orderId);
}

include __DIR__ . '/includes/header.php';
?>
<div class="container py-4">
    <div class="row">
        <div class="col-lg-3 mb-4"><?php include __DIR__ . '/includes/sidebar.php'; ?></div>
        <div class="col-lg-9">
            <h2 class="h4 mb-4">Returns &amp; Refunds</h2>

            <?php if ($order && $orderItems): ?>
            <!-- Return request form -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">Request return for order <?= e($order['order_number']) ?></div>
                <div class="card-body">
                    <form method="post" action="<?= e(url('actions/orders/return-request.php')) ?>" novalidate class="needs-validation">
                        <?= csrf_field() ?>
                        <input type="hidden" name="order_id" value="<?= $orderId ?>">

                        <div class="mb-3">
                            <label class="form-label">Select item to return</label>
                            <?php foreach ($orderItems as $it): ?>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="order_item_id"
                                       id="item_<?= $it['id'] ?>" value="<?= $it['id'] ?>" required>
                                <label class="form-check-label" for="item_<?= $it['id'] ?>">
                                    <?= e($it['product_name']) ?> × <?= $it['quantity'] ?> — <?= e(money($it['line_total'])) ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="order_item_id" id="item_whole" value="">
                                <label class="form-check-label" for="item_whole">Entire order</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="quantity" class="form-label">Quantity</label>
                            <input type="number" id="quantity" name="quantity" class="form-control" min="1" value="1" required style="width:120px">
                        </div>

                        <div class="mb-3">
                            <label for="reason" class="form-label">Reason <span class="text-danger">*</span></label>
                            <select id="reason" name="reason" class="form-select<?= invalid_class('reason') ?>" required>
                                <option value="">Select a reason…</option>
                                <option>Received wrong item</option>
                                <option>Item damaged or defective</option>
                                <option>Item not as described</option>
                                <option>Changed my mind</option>
                                <option>Allergic reaction</option>
                                <option>Other</option>
                            </select>
                            <?= invalid_feedback('reason') ?>
                        </div>

                        <div class="mb-3">
                            <label for="details" class="form-label">Additional details <span class="text-muted">(optional)</span></label>
                            <textarea id="details" name="details" class="form-control" rows="3" maxlength="1000"></textarea>
                        </div>

                        <button type="submit" class="btn btn-warning">Submit return request</button>
                        <a href="<?= e(url('order-details.php?id=' . $orderId)) ?>" class="btn btn-outline-secondary ms-2">Cancel</a>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- My returns history -->
            <?php if ($myReturns): ?>
            <div class="card shadow-sm">
                <div class="card-header">My return requests</div>
                <div class="list-group list-group-flush">
                    <?php foreach ($myReturns as $ret): ?>
                    <div class="list-group-item py-3">
                        <div class="d-flex justify-content-between flex-wrap gap-2">
                            <div>
                                <div class="fw-semibold">Order <?= e($ret['order_number']) ?></div>
                                <div class="text-muted small"><?= e($ret['reason']) ?></div>
                                <?php if ($ret['admin_note']): ?>
                                <div class="text-info small mt-1"><i class="bi bi-info-circle"></i> <?= e($ret['admin_note']) ?></div>
                                <?php endif; ?>
                                <div class="text-muted small"><?= format_datetime($ret['created_at']) ?></div>
                            </div>
                            <?= status_badge($ret['status']) ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php elseif (!$order): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-arrow-return-left" style="font-size:3rem"></i>
                <p class="mt-2">No return requests yet.</p>
                <p class="small">To request a return, go to your <a href="<?= e(url('orders.php')) ?>">orders</a> and select a delivered order.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

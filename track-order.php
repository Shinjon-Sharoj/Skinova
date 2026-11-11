<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/auth-check.php';

$orderId  = get_int('id');
$order    = $orderId ? get_order($orderId, (int) $authUser['id']) : null;
if (!$order) not_found();

$shipment  = db_one('SELECT * FROM shipments WHERE order_id = ?', [$orderId]);
$history   = get_order_history($orderId);
$pageTitle = 'Track Order ' . $order['order_number'];

$steps = [
    'pending'    => ['icon' => 'bi-bag-check',     'label' => 'Order placed'],
    'confirmed'  => ['icon' => 'bi-clipboard-check','label' => 'Confirmed'],
    'processing' => ['icon' => 'bi-box',            'label' => 'Packing'],
    'shipped'    => ['icon' => 'bi-truck',          'label' => 'Shipped'],
    'delivered'  => ['icon' => 'bi-house-check',    'label' => 'Delivered'],
];
$statusOrder = ['pending','confirmed','processing','shipped','delivered'];
$currentIdx  = array_search($order['status'], $statusOrder);

include __DIR__ . '/includes/header.php';
?>
<div class="container py-4" style="max-width:720px">
    <div class="row">
        <div class="col-lg-3 d-none d-lg-block"><?php // no sidebar for track page ?></div>
        <div class="col">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="h4 mb-0">Track Order</h2>
                <a href="<?= e(url('order-details.php?id=' . $orderId)) ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Order details
                </a>
            </div>

            <!-- Order info -->
            <div class="card shadow-sm mb-4">
                <div class="card-body d-flex flex-wrap gap-4 justify-content-between">
                    <div>
                        <div class="text-muted small">Order number</div>
                        <div class="fw-semibold"><?= e($order['order_number']) ?></div>
                    </div>
                    <div>
                        <div class="text-muted small">Status</div>
                        <?= status_badge($order['status']) ?>
                    </div>
                    <?php if ($shipment && $shipment['tracking_number']): ?>
                    <div>
                        <div class="text-muted small">Tracking #</div>
                        <div class="fw-semibold"><?= e($shipment['tracking_number']) ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if ($shipment && $shipment['estimated_delivery']): ?>
                    <div>
                        <div class="text-muted small">Est. delivery</div>
                        <div class="fw-semibold"><?= format_date($shipment['estimated_delivery']) ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($order['status'] !== 'cancelled'): ?>
            <!-- Progress steps -->
            <div class="card shadow-sm mb-4">
                <div class="card-body py-4">
                    <div class="d-flex justify-content-between align-items-start position-relative">
                        <!-- Progress bar line -->
                        <div class="position-absolute top-0 start-0 w-100" style="height:4px;background:#dee2e6;top:20px;z-index:0">
                            <div class="h-100 bg-primary" style="width:<?= $currentIdx >= 0 ? (min(100, ($currentIdx / (count($steps) - 1)) * 100)) : 0 ?>%"></div>
                        </div>
                        <?php foreach ($statusOrder as $idx => $step): ?>
                        <?php if ($step === 'cancelled') continue; ?>
                        <?php
                            $done   = $currentIdx !== false && $idx <= $currentIdx;
                            $active = $currentIdx !== false && $idx === $currentIdx;
                            $info   = $steps[$step];
                        ?>
                        <div class="text-center flex-grow-1 position-relative" style="z-index:1">
                            <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-2
                                <?= $done ? 'bg-primary text-white' : 'bg-light border' ?>"
                                 style="width:40px;height:40px">
                                <i class="bi <?= $info['icon'] ?>"></i>
                            </div>
                            <div class="small <?= $active ? 'fw-bold text-primary' : ($done ? 'text-muted' : 'text-muted') ?>">
                                <?= e($info['label']) ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="alert alert-danger">
                <i class="bi bi-x-circle"></i> This order was cancelled.
                <?php if ($order['cancel_reason']): ?><br><small><?= e($order['cancel_reason']) ?></small><?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Timeline -->
            <div class="card shadow-sm">
                <div class="card-header">Status history</div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <?php foreach (array_reverse($history) as $h): ?>
                        <li class="d-flex gap-3 mb-3 pb-3 border-bottom last-no-border">
                            <i class="bi bi-circle-fill text-primary mt-1 flex-shrink-0" style="font-size:.5rem"></i>
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
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

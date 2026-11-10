<?php
require_once _DIR_ . '/../../includes/init.php';
require_post();
csrf_verify();
$user = require_login();

$orderId     = input_int('order_id');
$orderItemId = input_int('order_item_id') ?: null;
$quantity    = max(1, input_int('quantity', 1));
$reason      = mb_substr(clean_text(input('reason')), 0, 120);
$details     = input('details') ? mb_substr(clean_text(input('details')), 0, 1000) : null;

if (!$reason) {
    set_old($_POST, ['reason' => 'Please select a reason.']);
    redirect('returns.php?order=' . $orderId);
}

$order = get_order($orderId, (int) $user['id']);
if (!$order || $order['status'] !== 'delivered') {
    flash('danger', 'Invalid order.');
    redirect('returns.php');
}

$returnWindow = (int) get_setting('return_window_days', 30);
$deliveredAt  = strtotime($order['delivered_at'] ?? $order['created_at']);
if ($deliveredAt && (time() - $deliveredAt) > $returnWindow * 86400) {
    flash('danger', "The {$returnWindow}-day return window for this order has passed.");
    redirect('returns.php');
}

// Prevent duplicate return for same item
$existingReturn = db_value(
    'SELECT id FROM returns WHERE order_id = ? AND user_id = ? AND ' .
    ($orderItemId ? 'order_item_id = ?' : 'order_item_id IS NULL') . " AND status NOT IN ('rejected')",
    $orderItemId ? [$orderId, $user['id'], $orderItemId] : [$orderId, $user['id']]
);
if ($existingReturn) {
    flash('info', 'A return request already exists for this item.');
    redirect('returns.php');
}

db_insert(
    "INSERT INTO returns (order_id, order_item_id, user_id, quantity, reason, details, status) VALUES (?,?,?,?,?,?,'requested')",
    [$orderId, $orderItemId, $user['id'], $quantity, $reason, $details]
);

log_activity('return_requested', 'order', $orderId, 'reason=' . $reason, (int) $user['id']);
notify((int) $user['id'], 'order', 'Return request submitted', 'Your return request for order ' . $order['order_number'] . ' has been received.', 'returns.php');

flash('success', 'Return request submitted. We\'ll review it within 2–3 business days.');
redirect('returns.php');
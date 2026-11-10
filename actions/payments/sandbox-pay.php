<?php
require_once _DIR_ . '/../../includes/init.php';
require_post();
csrf_verify();
$user = require_login();

$orderId = input_int('order_id');
$order   = get_order($orderId, (int) $user['id']);

if (!$order) not_found();
if ($order['payment_method'] !== 'online') {
    flash('danger', 'Invalid payment method for this order.');
    redirect('order-details.php?id=' . $orderId);
}
if ($order['payment_status'] === 'paid') {
    redirect('order-success.php?id=' . $orderId);
}

// Simulate successful payment with fake transaction ID
$txnId = 'SANDBOX_' . strtoupper(bin2hex(random_bytes(8)));
$result = mark_order_paid($orderId, $txnId, 'sandbox');

if ($result['ok']) {
    log_activity('payment_completed', 'order', $orderId, 'provider=sandbox txn=' . $txnId, (int) $user['id']);
    redirect('order-success.php?id=' . $orderId);
}

flash('danger', 'Payment failed. Please try again.');
redirect('payment.php?order=' . $orderId);
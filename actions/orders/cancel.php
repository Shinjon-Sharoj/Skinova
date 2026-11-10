<?php
require_once _DIR_ . '/../../includes/init.php';
require_post();
csrf_verify();
$user = require_login();

$orderId = input_int('order_id');
$reason  = mb_substr(clean_text(input('cancel_reason')), 0, 300);
$order   = get_order($orderId, (int) $user['id']);

if (!$order) not_found();
if (!customer_can_cancel($order)) {
    flash('danger', 'This order cannot be cancelled at this stage.');
    redirect('order-details.php?id=' . $orderId);
}

$result = update_order_status($orderId, 'cancelled', $reason ?: 'Cancelled by customer.', (int) $user['id']);
if ($result['ok']) {
    flash('success', 'Order cancelled successfully.');
} else {
    flash('danger', $result['error'] ?? 'Could not cancel order.');
}
redirect('order-details.php?id=' . $orderId);
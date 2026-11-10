<?php
require_once _DIR_ . '/../../includes/init.php';
require_post();
csrf_verify();
$user = require_login();

$addressId     = input_int('address_id');
$paymentMethod = input('payment_method');
$notes         = input('notes');

if (!$addressId) {
    flash('danger', 'Please select a shipping address.');
    redirect('checkout.php');
}

$result = place_order((int) $user['id'], $addressId, $paymentMethod, $notes ?: null);

if (!$result['ok']) {
    flash('danger', $result['error'] ?? 'Could not place your order. Please try again.');
    redirect('checkout.php');
}

// COD → success page directly
if ($result['payment_method'] === 'cod') {
    redirect('order-success.php?id=' . $result['order_id']);
}

// Online → payment page
redirect('payment.php?order=' . $result['order_id']);
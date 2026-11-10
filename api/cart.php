<?php
/**
 * API: Cart
 * POST action=add          {product_id, quantity=1}  -> {cart_count, message}
 * POST action=remove        {product_id}              -> {cart_count}
 * POST action=set_quantity  {product_id, quantity}    -> {cart_count}
 * POST action=set_coupon    {code}                    -> {pricing}
 * POST action=set_loyalty   {points}                  -> {pricing}
 * POST action=clear                                   -> {}
 * GET  action=summary                                 -> {pricing}
 */
require_once _DIR_ . '/../includes/init.php';

$user = api_init(['methods' => ['GET', 'POST'], 'auth' => true, 'rate' => [120, 60]]);
$in   = api_input();
$uid  = (int) $user['id'];
$action = $in['action'] ?? 'summary';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'summary') {
        api_ok(['pricing' => calculate_pricing($uid), 'count' => cart_count($uid)]);
    }
    api_fail('Unknown action.', 400);
}

// POST
switch ($action) {
    case 'add':
        $pid = (int) ($in['product_id'] ?? 0);
        $qty = max(1, (int) ($in['quantity'] ?? 1));
        $res = cart_add($uid, $pid, $qty);
        api_ok(['cart_count' => cart_count($uid), 'ok' => $res['ok']], $res['message']);

    case 'remove':
        cart_remove($uid, (int) ($in['product_id'] ?? 0));
        api_ok(['cart_count' => cart_count($uid)]);

    case 'set_quantity':
        $pid = (int) ($in['product_id'] ?? 0);
        $qty = (int) ($in['quantity'] ?? 0);
        $res = cart_set_quantity($uid, $pid, $qty);
        api_ok(['cart_count' => cart_count($uid), 'ok' => $res['ok']], $res['message']);

    case 'set_coupon':
        $code = trim((string) ($in['code'] ?? ''));
        if ($code === '') {
            cart_set_coupon($uid, null);
            api_ok(['pricing' => calculate_pricing($uid)], 'Coupon removed.');
        }
        // Validate first
        $items   = cart_items($uid);
        $pricing = calculate_pricing($uid);
        $afterPromo = max(0.0, ($pricing['subtotal'] ?? 0) - ($pricing['promotion_discount'] ?? 0));
        $val = validate_coupon(strtoupper($code), $uid, $afterPromo);
        if (!$val['valid']) {
            api_fail($val['message'], 422);
        }
        cart_set_coupon($uid, strtoupper($code));
        api_ok(['pricing' => calculate_pricing($uid)], 'Coupon applied!');

    case 'set_loyalty':
        $points = max(0, (int) ($in['points'] ?? 0));
        cart_set_loyalty_points($uid, $points);
        api_ok(['pricing' => calculate_pricing($uid)]);

    case 'clear':
        cart_clear($uid);
        api_ok(['cart_count' => 0]);

    default:
        api_fail('Unknown action.', 400);
}
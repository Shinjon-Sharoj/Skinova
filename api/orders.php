<?php
/**
 * API: Orders
 * GET  action=list   ?status=  -> {orders}
 * GET  action=detail ?id=      -> {order, items, history}
 * POST action=cancel {order_id, reason} -> {}
 */
require_once _DIR_ . '/../includes/init.php';

$user = api_init(['methods' => ['GET', 'POST'], 'auth' => true, 'rate' => [60, 60]]);
$in   = api_input();
$uid  = (int) $user['id'];
$action = $in['action'] ?? 'list';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'list') {
        $status = $in['status'] ?? '';
        $where  = ['o.user_id = ?'];
        $params = [$uid];
        if ($status && in_array($status, ORDER_STATUSES, true)) {
            $where[]  = 'o.status = ?';
            $params[] = $status;
        }
        $orders = db_all(
            'SELECT o.id, o.order_number, o.status, o.payment_status, o.total, o.created_at
             FROM orders o WHERE ' . implode(' AND ', $where) . ' ORDER BY o.created_at DESC LIMIT 50',
            $params
        );
        api_ok(['orders' => $orders]);
    }

    if ($action === 'detail') {
        $id    = (int) ($in['id'] ?? 0);
        $order = get_order($id, $uid);
        if (!$order) api_fail('Order not found.', 404);
        api_ok([
            'order'   => $order,
            'items'   => get_order_items($id),
            'history' => get_order_history($id),
        ]);
    }
    api_fail('Unknown action.', 400);
}

// POST
if ($action === 'cancel') {
    $id     = (int) ($in['order_id'] ?? 0);
    $order  = get_order($id, $uid);
    if (!$order) api_fail('Order not found.', 404);
    if (!customer_can_cancel($order)) api_fail('This order cannot be cancelled now.', 422);

    $reason = mb_substr(trim((string) ($in['reason'] ?? '')), 0, 300) ?: 'Cancelled by customer.';
    $result = update_order_status($id, 'cancelled', $reason, $uid);
    if (!$result['ok']) api_fail($result['error'] ?? 'Failed to cancel.', 422);
    api_ok([], 'Order cancelled.');
}
api_fail('Unknown action.', 400);
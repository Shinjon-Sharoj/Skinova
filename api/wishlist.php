<?php
/**
 * API: Wishlist
 * POST action=toggle {product_id} -> {in_wishlist, wishlist_count}
 * POST action=move_to_cart {product_id} -> {cart_count}
 * GET  action=list   -> {items:[...]}
 */
require_once _DIR_ . '/../includes/init.php';

$user = api_init(['methods' => ['GET', 'POST'], 'auth' => true, 'rate' => [120, 60]]);
$in   = api_input();
$uid  = (int) $user['id'];
$action = $in['action'] ?? 'list';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $items = db_all(
        'SELECT wi.product_id, wi.price_when_added, wi.added_at, p.name, p.slug,
                ROUND(p.price*(1-p.discount_percent/100),2) AS final_price,
                (SELECT pi.image_path FROM product_images pi WHERE pi.product_id=p.id ORDER BY pi.is_primary DESC LIMIT 1) AS image_path
         FROM wishlist_items wi
         JOIN wishlists w ON w.id = wi.wishlist_id
         JOIN products p ON p.id = wi.product_id
         WHERE w.user_id = ? ORDER BY wi.added_at DESC',
        [$uid]
    );
    api_ok(['items' => $items, 'wishlist_count' => count($items)]);
}

// POST
$pid = (int) ($in['product_id'] ?? 0);

if ($action === 'toggle') {
    if (!$pid) api_fail('product_id required.', 422);
    $product = db_one('SELECT id FROM products WHERE id = ? AND is_active = 1', [$pid]);
    if (!$product) api_fail('Product not found.', 404);

    // Ensure wishlist exists
    $wid = db_value('SELECT id FROM wishlists WHERE user_id = ?', [$uid]);
    if (!$wid) {
        db_exec('INSERT IGNORE INTO wishlists (user_id) VALUES (?)', [$uid]);
        $wid = db_value('SELECT id FROM wishlists WHERE user_id = ?', [$uid]);
    }

    $existing = db_value('SELECT 1 FROM wishlist_items WHERE wishlist_id = ? AND product_id = ?', [$wid, $pid]);
    if ($existing) {
        db_exec('DELETE FROM wishlist_items WHERE wishlist_id = ? AND product_id = ?', [$wid, $pid]);
        $inWishlist = false;
    } else {
        $price = (float) db_value('SELECT ROUND(price*(1-discount_percent/100),2) FROM products WHERE id = ?', [$pid], 0);
        db_exec('INSERT IGNORE INTO wishlist_items (wishlist_id, product_id, price_when_added) VALUES (?,?,?)', [$wid, $pid, $price]);
        $inWishlist = true;
    }

    $count = (int) db_value('SELECT COUNT(*) FROM wishlist_items wi JOIN wishlists w ON w.id=wi.wishlist_id WHERE w.user_id=?', [$uid], 0);
    api_ok(['in_wishlist' => $inWishlist, 'wishlist_count' => $count], $inWishlist ? 'Added to wishlist.' : 'Removed from wishlist.');
}

if ($action === 'move_to_cart') {
    if (!$pid) api_fail('product_id required.', 422);
    $res = wishlist_move_to_cart($uid, $pid);
    api_ok(['cart_count' => cart_count($uid), 'ok' => $res['ok']], $res['message']);
}

api_fail('Unknown action.', 400);
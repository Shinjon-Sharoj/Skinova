<?php
/**
 * Commerce core: cart, server-side pricing (promotions, coupons, loyalty, shipping),
 * transactional order placement, status changes, cancellation, payment state,
 * refunds and loyalty points.
 *
 * Browsers NEVER supply prices or totals. Everything is recomputed here from the
 * database on every cart view and again - inside a transaction with row locks - when an
 * order is placed.
 */

/** Exception whose message is safe to show to the customer. */
class CommerceException extends RuntimeException
{
}

// ---------------------------------------------------------------------------
// Cart
// ---------------------------------------------------------------------------

function cart_id_for_user(int $userId, bool $create = true): ?int
{
    $id = db_value('SELECT id FROM carts WHERE user_id = ?', [$userId]);
    if ($id) return (int) $id;
    if (!$create) return null;
    db_exec('INSERT IGNORE INTO carts (user_id) VALUES (?)', [$userId]);
    return (int) db_value('SELECT id FROM carts WHERE user_id = ?', [$userId]);
}

/** Cart lines with live product data. unit_price is the discounted product price. */
function cart_items(int $userId): array
{
    $cartId = cart_id_for_user($userId, false);
    if (!$cartId) return [];
    return db_all(
        "SELECT ci.id AS cart_item_id, ci.quantity, p.id AS product_id, p.name, p.slug, p.sku, p.price AS original_price,
                p.discount_percent, p.category_id, p.is_active, p.size_label,
                ROUND(p.price * (1 - p.discount_percent / 100), 2) AS unit_price,
                COALESCE(i.quantity, 0) AS stock,
                (SELECT pi.image_path FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.is_primary DESC, pi.sort_order, pi.id LIMIT 1) AS image_path
         FROM cart_items ci
         JOIN products p ON p.id = ci.product_id
         LEFT JOIN inventory i ON i.product_id = p.id
         WHERE ci.cart_id = ?
         ORDER BY ci.id",
        [$cartId]
    );
}

function cart_count(int $userId): int
{
    return (int) db_value(
        'SELECT COALESCE(SUM(ci.quantity),0) FROM cart_items ci JOIN carts c ON c.id = ci.cart_id WHERE c.user_id = ?',
        [$userId],
        0
    );
}

/** @return array{ok:bool,message:string} */
function cart_add(int $userId, int $productId, int $qty = 1): array
{
    $qty = max(1, min(99, $qty));
    $p = db_one('SELECT p.id, p.name, p.is_active, COALESCE(i.quantity,0) AS stock FROM products p LEFT JOIN inventory i ON i.product_id = p.id WHERE p.id = ?', [$productId]);
    if (!$p || !(int) $p['is_active']) {
        return ['ok' => false, 'message' => 'This product is not available.'];
    }
    $cartId = cart_id_for_user($userId);
    $existing = (int) db_value('SELECT quantity FROM cart_items WHERE cart_id = ? AND product_id = ?', [$cartId, $productId], 0);
    $newQty = $existing + $qty;
    if ((int) $p['stock'] <= 0) {
        return ['ok' => false, 'message' => $p['name'] . ' is out of stock.'];
    }
    if ($newQty > (int) $p['stock']) {
        $newQty = (int) $p['stock'];
        $msg = 'Only ' . $p['stock'] . ' unit(s) of ' . $p['name'] . ' available - quantity adjusted.';
    } else {
        $msg = $p['name'] . ' added to your cart.';
    }
    db_exec(
        'INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?,?,?) ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)',
        [$cartId, $productId, $newQty]
    );
    return ['ok' => true, 'message' => $msg];
}

/** Set an absolute quantity (0 removes the line). */
function cart_set_quantity(int $userId, int $productId, int $qty): array
{
    $cartId = cart_id_for_user($userId, false);
    if (!$cartId) return ['ok' => false, 'message' => 'Your cart is empty.'];
    if ($qty <= 0) {
        db_exec('DELETE FROM cart_items WHERE cart_id = ? AND product_id = ?', [$cartId, $productId]);
        return ['ok' => true, 'message' => 'Item removed.'];
    }
    $stock = (int) db_value('SELECT COALESCE(quantity,0) FROM inventory WHERE product_id = ?', [$productId], 0);
    $msg = 'Cart updated.';
    if ($qty > $stock) {
        $qty = $stock;
        $msg = 'Only ' . $stock . ' unit(s) available - quantity adjusted.';
        if ($qty <= 0) {
            db_exec('DELETE FROM cart_items WHERE cart_id = ? AND product_id = ?', [$cartId, $productId]);
            return ['ok' => false, 'message' => 'This product is out of stock and was removed.'];
        }
    }
    $qty = min(99, $qty);
    db_exec('UPDATE cart_items SET quantity = ? WHERE cart_id = ? AND product_id = ?', [$qty, $cartId, $productId]);
    return ['ok' => true, 'message' => $msg];
}

function cart_remove(int $userId, int $productId): void
{
    cart_set_quantity($userId, $productId, 0);
}

function cart_clear(int $userId): void
{
    $cartId = cart_id_for_user($userId, false);
    if ($cartId) {
        db_exec('DELETE FROM cart_items WHERE cart_id = ?', [$cartId]);
        db_exec('UPDATE carts SET coupon_code = NULL, use_loyalty_points = 0 WHERE id = ?', [$cartId]);
    }
}

function cart_get_meta(int $userId): array
{
    $row = db_one('SELECT coupon_code, use_loyalty_points FROM carts WHERE user_id = ?', [$userId]);
    return ['coupon_code' => $row['coupon_code'] ?? null, 'loyalty_points' => (int) ($row['use_loyalty_points'] ?? 0)];
}

function cart_set_coupon(int $userId, ?string $code): void
{
    cart_id_for_user($userId);
    db_exec('UPDATE carts SET coupon_code = ? WHERE user_id = ?', [$code !== null ? strtoupper(trim($code)) : null, $userId]);
}

function cart_set_loyalty_points(int $userId, int $points): void
{
    cart_id_for_user($userId);
    db_exec('UPDATE carts SET use_loyalty_points = ? WHERE user_id = ?', [max(0, $points), $userId]);
}

/** Move a wishlist product into the cart and remove it from the wishlist. */
function wishlist_move_to_cart(int $userId, int $productId): array
{
    $res = cart_add($userId, $productId, 1);
    if ($res['ok']) {
        db_exec(
            'DELETE wi FROM wishlist_items wi JOIN wishlists w ON w.id = wi.wishlist_id WHERE w.user_id = ? AND wi.product_id = ?',
            [$userId, $productId]
        );
    }
    return $res;
}

// ---------------------------------------------------------------------------
// Pricing: promotions, coupons, shipping, loyalty
// ---------------------------------------------------------------------------

function loyalty_point_value(): float
{
    return (float) get_setting('loyalty_point_value', 0.01);
}

function active_promotions(): array
{
    $promos = db_all(
        "SELECT * FROM promotions WHERE is_active = 1 AND (starts_at IS NULL OR starts_at <= NOW()) AND (ends_at IS NULL OR ends_at >= NOW()) ORDER BY priority DESC, id"
    );
    if (!$promos) return [];
    $ids = array_column($promos, 'id');
    $map = [];
    foreach (db_all('SELECT promotion_id, product_id FROM promotion_products WHERE promotion_id IN (' . db_placeholders($ids) . ')', $ids) as $r) {
        $map[(int) $r['promotion_id']][] = (int) $r['product_id'];
    }
    foreach ($promos as &$p) {
        $p['product_ids'] = $map[(int) $p['id']] ?? [];
    }
    unset($p);
    return $promos;
}

function user_has_prior_order(int $userId): bool
{
    return (int) db_value("SELECT COUNT(*) FROM orders WHERE user_id = ? AND status <> 'cancelled'", [$userId], 0) > 0;
}

/**
 * Apply active promotions to cart lines.
 *  - product_discount / flash_sale / seasonal / bogo: each line gets the single best discount
 *  - bundle: all listed products must be in the cart; discount per complete set
 *  - first_order: applies once for customers without a previous (non-cancelled) order
 * @return array{total:float,applied:array<int,array{promotion_id:int,title:string,type:string,amount:float}>}
 */
function compute_promotion_discounts(array $items, int $userId): array
{
    $applied = [];
    $add = function (array $promo, float $amount) use (&$applied) {
        if ($amount <= 0) return;
        $pid = (int) $promo['id'];
        if (!isset($applied[$pid])) {
            $applied[$pid] = ['promotion_id' => $pid, 'title' => $promo['title'], 'type' => $promo['promo_type'], 'amount' => 0.0];
        }
        $applied[$pid]['amount'] = round($applied[$pid]['amount'] + $amount, 2);
    };
    $promos = active_promotions();
    if (!$promos || !$items) return ['total' => 0.0, 'applied' => []];

    $lineTypes = ['product_discount', 'flash_sale', 'seasonal', 'bogo'];
    $lineDiscountTotal = 0.0;

    foreach ($items as $it) {
        $unit = (float) $it['unit_price'];
        $qty = (int) $it['quantity'];
        $lineTotal = $unit * $qty;
        $best = 0.0;
        $bestPromo = null;
        foreach ($promos as $promo) {
            if (!in_array($promo['promo_type'], $lineTypes, true)) continue;
            $matches = in_array((int) $it['product_id'], $promo['product_ids'], true)
                || ($promo['category_id'] && (int) $promo['category_id'] === (int) $it['category_id'])
                || (!$promo['product_ids'] && !$promo['category_id']);
            if (!$matches) continue;
            if ($promo['promo_type'] === 'bogo') {
                $d = floor($qty / 2) * $unit;
            } elseif ($promo['discount_type'] === 'percentage') {
                $d = $lineTotal * min(100, (float) $promo['discount_value']) / 100;
            } else {
                $d = min($lineTotal, (float) $promo['discount_value'] * $qty);
            }
            if ($d > $best) {
                $best = $d;
                $bestPromo = $promo;
            }
        }
        if ($bestPromo) {
            $add($bestPromo, round($best, 2));
            $lineDiscountTotal += round($best, 2);
        }
    }

    $byProduct = [];
    foreach ($items as $it) $byProduct[(int) $it['product_id']] = $it;
    $bundleTotal = 0.0;
    foreach ($promos as $promo) {
        if ($promo['promo_type'] !== 'bundle' || !$promo['product_ids']) continue;
        $sets = null;
        $base = 0.0;
        foreach ($promo['product_ids'] as $pid) {
            if (!isset($byProduct[$pid])) { $sets = 0; break; }
            $q = (int) $byProduct[$pid]['quantity'];
            $sets = $sets === null ? $q : min($sets, $q);
        }
        if (!$sets) continue;
        foreach ($promo['product_ids'] as $pid) $base += (float) $byProduct[$pid]['unit_price'] * $sets;
        $d = $promo['discount_type'] === 'percentage'
            ? $base * min(100, (float) $promo['discount_value']) / 100
            : min($base, (float) $promo['discount_value'] * $sets);
        $d = round($d, 2);
        $add($promo, $d);
        $bundleTotal += $d;
    }

    $subtotal = 0.0;
    foreach ($items as $it) $subtotal += (float) $it['unit_price'] * (int) $it['quantity'];
    $remaining = max(0.0, $subtotal - $lineDiscountTotal - $bundleTotal);
    $firstOrderTotal = 0.0;
    if ($remaining > 0 && !user_has_prior_order($userId)) {
        foreach ($promos as $promo) {
            if ($promo['promo_type'] !== 'first_order') continue;
            $d = $promo['discount_type'] === 'percentage'
                ? $remaining * min(100, (float) $promo['discount_value']) / 100
                : min($remaining, (float) $promo['discount_value']);
            $d = round($d, 2);
            $add($promo, $d);
            $firstOrderTotal += $d;
            break; // one first-order offer only
        }
    }

    $total = min($subtotal, round($lineDiscountTotal + $bundleTotal + $firstOrderTotal, 2));
    return ['total' => round($total, 2), 'applied' => array_values($applied)];
}

/**
 * Validate a coupon against the user and order amount.
 * @return array{valid:bool,message:string,coupon:?array,discount:float}
 */
function validate_coupon(string $code, int $userId, float $amount, bool $lock = false): array
{
    $code = strtoupper(trim($code));
    $bad = fn(string $m) => ['valid' => false, 'message' => $m, 'coupon' => null, 'discount' => 0.0];
    if ($code === '') return $bad('Enter a coupon code.');
    $c = db_one('SELECT * FROM coupons WHERE code = ?' . ($lock ? ' FOR UPDATE' : ''), [$code]);
    if (!$c || !(int) $c['is_active']) return $bad('This coupon code is not valid.');
    $now = time();
    if ($c['starts_at'] && strtotime($c['starts_at']) > $now) return $bad('This coupon is not active yet.');
    if ($c['expires_at'] && strtotime($c['expires_at']) < $now) return $bad('This coupon has expired.');
    if ($c['usage_limit'] !== null && (int) $c['used_count'] >= (int) $c['usage_limit']) return $bad('This coupon has reached its usage limit.');
    $userUses = (int) db_value('SELECT COUNT(*) FROM coupon_usages WHERE coupon_id = ? AND user_id = ?', [$c['id'], $userId], 0);
    if ($userUses >= (int) $c['per_user_limit']) return $bad('You have already used this coupon.');
    if ((int) $c['first_order_only'] && user_has_prior_order($userId)) return $bad('This coupon is only valid on your first order.');
    if ($amount < (float) $c['min_order_amount']) return $bad('Spend at least ' . money($c['min_order_amount']) . ' to use this coupon.');

    $discount = $c['discount_type'] === 'percentage'
        ? $amount * min(100, (float) $c['discount_value']) / 100
        : (float) $c['discount_value'];
    if ($c['max_discount_amount'] !== null) {
        $discount = min($discount, (float) $c['max_discount_amount']);
    }
    $discount = round(min($discount, $amount), 2);
    return ['valid' => true, 'message' => 'Coupon applied.', 'coupon' => $c, 'discount' => $discount];
}

function calculate_shipping(float $amountAfterDiscounts, int $itemCount): float
{
    if ($itemCount <= 0) return 0.0;
    $threshold = (float) get_setting('free_shipping_threshold', 60);
    if ($threshold > 0 && $amountAfterDiscounts >= $threshold) return 0.0;
    return round((float) get_setting('shipping_flat_fee', 5), 2);
}

/**
 * Authoritative price calculation for the user's cart.
 *
 * @return array{
 *   items:array, item_count:int, subtotal:float, promotion_discount:float, promotions:array,
 *   coupon_code:?string, coupon_discount:float, coupon_message:?string, coupon_error:?string,
 *   loyalty_points_used:int, loyalty_discount:float, loyalty_available:int, loyalty_max_usable:int,
 *   shipping_fee:float, free_shipping_threshold:float, total:float, points_to_earn:int,
 *   issues:array<int,string>, can_checkout:bool
 * }
 */
function calculate_pricing(int $userId, array $override = []): array
{
    $items = cart_items($userId);
    $meta = cart_get_meta($userId);
    $couponCode = array_key_exists('coupon_code', $override) ? $override['coupon_code'] : $meta['coupon_code'];
    $requestedPoints = array_key_exists('loyalty_points', $override) ? (int) $override['loyalty_points'] : $meta['loyalty_points'];

    $issues = [];
    $subtotal = 0.0;
    $count = 0;
    foreach ($items as &$it) {
        $it['quantity'] = (int) $it['quantity'];
        $it['unit_price'] = (float) $it['unit_price'];
        $it['line_total'] = round($it['unit_price'] * $it['quantity'], 2);
        $it['stock'] = (int) $it['stock'];
        $it['issue'] = null;
        if (!(int) $it['is_active']) {
            $it['issue'] = 'No longer available';
        } elseif ($it['stock'] <= 0) {
            $it['issue'] = 'Out of stock';
        } elseif ($it['quantity'] > $it['stock']) {
            $it['issue'] = 'Only ' . $it['stock'] . ' left in stock';
        }
        if ($it['issue']) $issues[] = $it['name'] . ': ' . $it['issue'];
        $subtotal += $it['line_total'];
        $count += $it['quantity'];
    }
    unset($it);
    $subtotal = round($subtotal, 2);

    $promo = compute_promotion_discounts($items, $userId);
    $afterPromo = max(0.0, round($subtotal - $promo['total'], 2));

    $couponDiscount = 0.0;
    $couponMessage = null;
    $couponError = null;
    $appliedCode = null;
    if ($couponCode) {
        $res = validate_coupon($couponCode, $userId, $afterPromo);
        if ($res['valid']) {
            $couponDiscount = $res['discount'];
            $couponMessage = $res['message'];
            $appliedCode = $res['coupon']['code'];
        } else {
            $couponError = $res['message'];
        }
    }
    $afterCoupon = max(0.0, round($afterPromo - $couponDiscount, 2));

    $shipping = calculate_shipping($afterCoupon, $count);

    // Loyalty redemption (capped by balance and a % of the payable merchandise amount)
    $balance = (int) db_value('SELECT loyalty_points FROM users WHERE id = ?', [$userId], 0);
    $pointValue = max(0.0001, loyalty_point_value());
    $maxPercent = (float) get_setting('loyalty_max_redeem_percent', 50);
    $maxByAmount = (int) floor(($afterCoupon * $maxPercent / 100) / $pointValue);
    $maxUsable = max(0, min($balance, $maxByAmount));
    $pointsUsed = max(0, min($requestedPoints, $maxUsable));
    $loyaltyDiscount = min(round($pointsUsed * $pointValue, 2), $afterCoupon);

    $merchandisePaid = max(0.0, round($afterCoupon - $loyaltyDiscount, 2));
    $total = round($merchandisePaid + $shipping, 2);
    $earnRate = (float) get_setting('loyalty_earn_rate', 1);

    return [
        'items' => $items,
        'item_count' => $count,
        'subtotal' => $subtotal,
        'promotion_discount' => $promo['total'],
        'promotions' => $promo['applied'],
        'coupon_code' => $appliedCode,
        'coupon_discount' => $couponDiscount,
        'coupon_message' => $couponMessage,
        'coupon_error' => $couponError,
        'loyalty_points_used' => $pointsUsed,
        'loyalty_discount' => $loyaltyDiscount,
        'loyalty_available' => $balance,
        'loyalty_max_usable' => $maxUsable,
        'shipping_fee' => $shipping,
        'free_shipping_threshold' => (float) get_setting('free_shipping_threshold', 60),
        'total' => $total,
        'points_to_earn' => (int) floor($merchandisePaid * $earnRate),
        'issues' => $issues,
        'can_checkout' => $count > 0 && !$issues,
    ];
}

// ---------------------------------------------------------------------------
// Loyalty
// ---------------------------------------------------------------------------

/** Adjust a user's points (positive = earn, negative = redeem) and log the transaction. */
function award_loyalty(int $userId, int $points, string $type, string $description, ?int $orderId = null): void
{
    if ($points === 0) return;
    db_exec('UPDATE users SET loyalty_points = GREATEST(0, loyalty_points + ?) WHERE id = ?', [$points, $userId]);
    $balance = (int) db_value('SELECT loyalty_points FROM users WHERE id = ?', [$userId], 0);
    db_insert(
        'INSERT INTO loyalty_transactions (user_id, order_id, points, type, description, balance_after) VALUES (?,?,?,?,?,?)',
        [$userId, $orderId, $points, $type, mb_substr($description, 0, 255), $balance]
    );
}

// ---------------------------------------------------------------------------
// Orders
// ---------------------------------------------------------------------------

function generate_order_number(): string
{
    do {
        $n = 'SK' . date('ymd') . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
    } while (db_value('SELECT id FROM orders WHERE order_number = ?', [$n]));
    return $n;
}

function add_order_history(int $orderId, string $status, ?string $note = null, ?int $actorId = null): void
{
    db_insert('INSERT INTO order_status_history (order_id, status, note, changed_by) VALUES (?,?,?,?)', [$orderId, $status, $note, $actorId]);
}

/** Online payment provider currently configured: 'stripe' or 'sandbox'. */
function online_payment_provider(): string
{
    return ONLINE_PAYMENT_PROVIDER === 'stripe' && STRIPE_SECRET_KEY !== '' ? 'stripe' : 'sandbox';
}

/** Cancel online orders that were never paid within $minutes and give the stock back. */
function cancel_stale_unpaid_orders(int $minutes = 60): int
{
    $stale = db_all(
        "SELECT id FROM orders WHERE status = 'pending' AND payment_method = 'online' AND payment_status IN ('pending','failed') AND created_at < DATE_SUB(NOW(), INTERVAL ? MINUTE) LIMIT 20",
        [$minutes]
    );
    $n = 0;
    foreach ($stale as $o) {
        $r = update_order_status((int) $o['id'], 'cancelled', 'Automatically cancelled: online payment not completed in time.', null);
        if ($r['ok']) $n++;
    }
    return $n;
}

/**
 * Place an order from the user's cart. Everything happens in one DB transaction with
 * row locks on inventory so concurrent customers cannot oversell.
 *
 * @return array{ok:bool,error?:string,order_id?:int,order_number?:string,total?:float,payment_method?:string}
 */
function place_order(int $userId, int $addressId, string $paymentMethod, ?string $notes = null): array
{
    if (!in_array($paymentMethod, ['cod', 'online'], true)) {
        return ['ok' => false, 'error' => 'Please choose a valid payment method.'];
    }
    $notes = $notes !== null ? mb_substr(trim($notes), 0, 500) : null;

    try {
        cancel_stale_unpaid_orders();
        return db_transaction(function () use ($userId, $addressId, $paymentMethod, $notes) {
            $address = db_one('SELECT * FROM user_addresses WHERE id = ? AND user_id = ?', [$addressId, $userId]);
            if (!$address) throw new CommerceException('Please select a valid shipping address.');

            $items = cart_items($userId);
            if (!$items) throw new CommerceException('Your cart is empty.');

            // Lock inventory rows in a stable order (prevents deadlocks and overselling).
            $productIds = array_map(fn($i) => (int) $i['product_id'], $items);
            sort($productIds);
            db_all('SELECT product_id, quantity FROM inventory WHERE product_id IN (' . db_placeholders($productIds) . ') ORDER BY product_id FOR UPDATE', $productIds);
            db_one('SELECT id FROM users WHERE id = ? FOR UPDATE', [$userId]);

            $pricing = calculate_pricing($userId);
            if (!$pricing['can_checkout']) {
                throw new CommerceException('Some items in your cart are unavailable: ' . implode('; ', $pricing['issues']));
            }
            if ($pricing['coupon_error']) {
                throw new CommerceException('Coupon problem: ' . $pricing['coupon_error']);
            }
            // Re-validate the coupon with a row lock to enforce usage limits under concurrency.
            $coupon = null;
            if ($pricing['coupon_code']) {
                $afterPromo = max(0.0, $pricing['subtotal'] - $pricing['promotion_discount']);
                $res = validate_coupon($pricing['coupon_code'], $userId, $afterPromo, true);
                if (!$res['valid']) throw new CommerceException($res['message']);
                $coupon = $res['coupon'];
            }

            $orderNumber = generate_order_number();
            $fullAddress = trim($address['address_line1'] . ($address['address_line2'] ? ', ' . $address['address_line2'] : ''));
            $orderId = db_insert(
                'INSERT INTO orders (order_number, user_id, status, payment_method, payment_status, shipping_name, shipping_phone, shipping_address,
                    shipping_city, shipping_state, shipping_postal_code, shipping_country, subtotal, promotion_discount, coupon_code, coupon_discount,
                    loyalty_points_used, loyalty_discount, shipping_fee, total, loyalty_points_earned, notes)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                [
                    $orderNumber, $userId, 'pending', $paymentMethod, 'pending', $address['recipient_name'], $address['phone'], $fullAddress,
                    $address['city'], $address['state'], $address['postal_code'], $address['country'],
                    $pricing['subtotal'], $pricing['promotion_discount'], $pricing['coupon_code'], $pricing['coupon_discount'],
                    $pricing['loyalty_points_used'], $pricing['loyalty_discount'], $pricing['shipping_fee'], $pricing['total'], $pricing['points_to_earn'], $notes,
                ]
            );

            foreach ($pricing['items'] as $it) {
                db_insert(
                    'INSERT INTO order_items (order_id, product_id, product_name, sku, original_price, unit_price, quantity, line_total) VALUES (?,?,?,?,?,?,?,?)',
                    [$orderId, $it['product_id'], $it['name'], $it['sku'], $it['original_price'], $it['unit_price'], $it['quantity'], $it['line_total']]
                );
                $updated = db_exec('UPDATE inventory SET quantity = quantity - ? WHERE product_id = ? AND quantity >= ?', [$it['quantity'], $it['product_id'], $it['quantity']]);
                if ($updated !== 1) {
                    throw new CommerceException($it['name'] . ' just went out of stock. Please review your cart.');
                }
                db_exec('UPDATE products SET sold_count = sold_count + ? WHERE id = ?', [$it['quantity'], $it['product_id']]);
            }

            if ($coupon) {
                db_insert('INSERT INTO coupon_usages (coupon_id, user_id, order_id, discount_amount) VALUES (?,?,?,?)', [$coupon['id'], $userId, $orderId, $pricing['coupon_discount']]);
                db_exec('UPDATE coupons SET used_count = used_count + 1 WHERE id = ?', [$coupon['id']]);
            }
            if ($pricing['loyalty_points_used'] > 0) {
                award_loyalty($userId, -$pricing['loyalty_points_used'], 'redeemed', 'Redeemed on order ' . $orderNumber, $orderId);
            }

            $provider = $paymentMethod === 'cod' ? 'cod' : online_payment_provider();
            db_insert(
                'INSERT INTO payments (order_id, user_id, method, provider, amount, currency, status) VALUES (?,?,?,?,?,?,?)',
                [$orderId, $userId, $paymentMethod, $provider, $pricing['total'], CURRENCY_CODE, 'pending']
            );
            db_insert('INSERT INTO shipments (order_id, status) VALUES (?, ?)', [$orderId, 'pending']);
            add_order_history($orderId, 'pending', 'Order placed.', $userId);
            cart_clear($userId);

            notify($userId, 'order', 'Order ' . $orderNumber . ' placed', 'Thank you! We received your order of ' . money($pricing['total']) . '.', 'order-details.php?id=' . $orderId);
            log_activity('order_placed', 'order', $orderId, $orderNumber . ' total=' . $pricing['total'] . ' method=' . $paymentMethod, $userId);

            return ['ok' => true, 'order_id' => $orderId, 'order_number' => $orderNumber, 'total' => $pricing['total'], 'payment_method' => $paymentMethod];
        });
    } catch (CommerceException $e) {
        return ['ok' => false, 'error' => $e->getMessage()];
    } catch (Throwable $e) {
        app_log('error', 'place_order failed: ' . $e->getMessage());
        return ['ok' => false, 'error' => 'We could not place your order. Please try again.'];
    }
}

/** Order row; pass $userId to enforce ownership (customers) or null for staff. */
function get_order(int $orderId, ?int $userId = null): ?array
{
    $sql = 'SELECT o.*, u.full_name AS customer_name, u.email AS customer_email FROM orders o JOIN users u ON u.id = o.user_id WHERE o.id = ?';
    $params = [$orderId];
    if ($userId !== null) {
        $sql .= ' AND o.user_id = ?';
        $params[] = $userId;
    }
    return db_one($sql, $params);
}

function get_order_items(int $orderId): array
{
    return db_all(
        'SELECT oi.*, (SELECT pi.image_path FROM product_images pi WHERE pi.product_id = oi.product_id ORDER BY pi.is_primary DESC, pi.sort_order LIMIT 1) AS image_path,
                (SELECT p.slug FROM products p WHERE p.id = oi.product_id) AS slug
         FROM order_items oi WHERE oi.order_id = ? ORDER BY oi.id',
        [$orderId]
    );
}

function get_order_history(int $orderId): array
{
    return db_all('SELECT * FROM order_status_history WHERE order_id = ? ORDER BY id', [$orderId]);
}

/** Customers may cancel before the order is processed. */
function customer_can_cancel(array $order): bool
{
    return in_array($order['status'], ['pending', 'confirmed'], true);
}

/** Put stock back for every item of an order (used on cancellation). */
function restock_order(int $orderId): void
{
    foreach (db_all('SELECT product_id, quantity FROM order_items WHERE order_id = ? AND product_id IS NOT NULL ORDER BY product_id', [$orderId]) as $it) {
        db_exec('INSERT INTO inventory (product_id, quantity) VALUES (?, ?) ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)', [$it['product_id'], $it['quantity']]);
        db_exec('UPDATE products SET sold_count = GREATEST(0, sold_count - ?) WHERE id = ?', [$it['quantity'], $it['product_id']]);
    }
}

/**
 * Move an order to a new status. Enforces ORDER_TRANSITIONS, writes history, updates the
 * shipment, restocks/refunds on cancellation, awards loyalty and COD payment on delivery,
 * and notifies the customer. Safe to call for both customers (cancel) and staff.
 *
 * $shipment keys (optional, for 'shipped'): carrier, tracking_number, estimated_delivery (Y-m-d)
 *
 * @return array{ok:bool,error?:string}
 */
function update_order_status(int $orderId, string $newStatus, ?string $note = null, ?int $actorId = null, array $shipment = []): array
{
    if (!in_array($newStatus, ORDER_STATUSES, true)) {
        return ['ok' => false, 'error' => 'Invalid status.'];
    }
    try {
        return db_transaction(function () use ($orderId, $newStatus, $note, $actorId, $shipment) {
            $order = db_one('SELECT * FROM orders WHERE id = ? FOR UPDATE', [$orderId]);
            if (!$order) throw new CommerceException('Order not found.');
            $current = $order['status'];
            if ($current === $newStatus) return ['ok' => true];
            if (!in_array($newStatus, ORDER_TRANSITIONS[$current] ?? [], true)) {
                throw new CommerceException('An order that is ' . $current . ' cannot be changed to ' . $newStatus . '.');
            }
            $uid = (int) $order['user_id'];
            $num = $order['order_number'];
            $link = 'order-details.php?id=' . $orderId;

            if ($newStatus === 'cancelled') {
                restock_order($orderId);
                if ((int) $order['loyalty_points_used'] > 0) {
                    award_loyalty($uid, (int) $order['loyalty_points_used'], 'reversed', 'Points returned: order ' . $num . ' cancelled', $orderId);
                }
                if ($order['coupon_code']) {
                    $deleted = db_exec('DELETE cu FROM coupon_usages cu JOIN coupons c ON c.id = cu.coupon_id WHERE cu.order_id = ? AND c.code = ?', [$orderId, $order['coupon_code']]);
                    if ($deleted) db_exec('UPDATE coupons SET used_count = GREATEST(0, used_count - 1) WHERE code = ?', [$order['coupon_code']]);
                }
                db_exec('UPDATE orders SET status = ?, cancel_reason = ? WHERE id = ?', ['cancelled', $note ? mb_substr($note, 0, 300) : null, $orderId]);
                if ($order['payment_status'] === 'paid') {
                    $payment = db_one("SELECT id FROM payments WHERE order_id = ? AND status = 'paid' ORDER BY id DESC LIMIT 1", [$orderId]);
                    db_insert(
                        'INSERT INTO refunds (order_id, payment_id, user_id, amount, reason, status) VALUES (?,?,?,?,?,?)',
                        [$orderId, $payment['id'] ?? null, $uid, $order['total'], 'Order cancelled', 'pending']
                    );
                } else {
                    db_exec("UPDATE payments SET status = 'failed', failure_reason = 'Order cancelled' WHERE order_id = ? AND status = 'pending'", [$orderId]);
                    db_exec("UPDATE orders SET payment_status = 'failed' WHERE id = ? AND payment_status = 'pending'", [$orderId]);
                }
                notify($uid, 'order', 'Order ' . $num . ' cancelled', 'Your order has been cancelled.' . ($order['payment_status'] === 'paid' ? ' A refund will be processed.' : ''), $link);
            } else {
                db_exec('UPDATE orders SET status = ? WHERE id = ?', [$newStatus, $orderId]);
            }

            if ($newStatus === 'shipped') {
                db_exec(
                    "UPDATE shipments SET status = 'shipped', carrier = ?, tracking_number = ?, estimated_delivery = ?, shipped_at = NOW() WHERE order_id = ?",
                    [$shipment['carrier'] ?? null, $shipment['tracking_number'] ?? null, $shipment['estimated_delivery'] ?? null, $orderId]
                );
                notify($uid, 'shipping', 'Order ' . $num . ' shipped', 'Your order is on its way.' . (!empty($shipment['tracking_number']) ? ' Tracking: ' . $shipment['tracking_number'] : ''), 'track-order.php?id=' . $orderId);
            } elseif ($newStatus === 'processing') {
                db_exec("UPDATE shipments SET status = 'packed' WHERE order_id = ?", [$orderId]);
            } elseif ($newStatus === 'delivered') {
                db_exec("UPDATE shipments SET status = 'delivered', delivered_at = NOW() WHERE order_id = ?", [$orderId]);
                db_exec('UPDATE orders SET delivered_at = NOW() WHERE id = ?', [$orderId]);
                if ($order['payment_method'] === 'cod' && $order['payment_status'] !== 'paid') {
                    db_exec("UPDATE payments SET status = 'paid', paid_at = NOW() WHERE order_id = ? AND status = 'pending'", [$orderId]);
                    db_exec("UPDATE orders SET payment_status = 'paid' WHERE id = ?", [$orderId]);
                }
                if ((int) $order['loyalty_points_earned'] > 0) {
                    award_loyalty($uid, (int) $order['loyalty_points_earned'], 'earned', 'Earned on order ' . $num, $orderId);
                }
                notify($uid, 'delivery', 'Order ' . $num . ' delivered', 'Your order was delivered. We would love your review!', $link);
            } elseif ($newStatus === 'confirmed') {
                notify($uid, 'order', 'Order ' . $num . ' confirmed', 'Your order has been confirmed and will be prepared soon.', $link);
            }

            add_order_history($orderId, $newStatus, $note, $actorId);
            log_activity('order_status_' . $newStatus, 'order', $orderId, $num . ($note ? ': ' . $note : ''), $actorId);
            return ['ok' => true];
        });
    } catch (CommerceException $e) {
        return ['ok' => false, 'error' => $e->getMessage()];
    } catch (Throwable $e) {
        app_log('error', 'update_order_status failed: ' . $e->getMessage());
        return ['ok' => false, 'error' => 'Could not update the order.'];
    }
}

/** Customer-initiated cancellation. */
function cancel_order(int $orderId, int $userId, string $reason = 'Cancelled by customer'): array
{
    $order = get_order($orderId, $userId);
    if (!$order) return ['ok' => false, 'error' => 'Order not found.'];
    if (!customer_can_cancel($order)) return ['ok' => false, 'error' => 'This order can no longer be cancelled.'];
    return update_order_status($orderId, 'cancelled', $reason, $userId);
}

// ---------------------------------------------------------------------------
// Payments
// ---------------------------------------------------------------------------

/** Record a successful online payment (idempotent). Auto-confirms a pending order. */
function mark_order_paid(int $orderId, string $provider, string $transactionId, ?string $cardBrand = null, ?string $cardLast4 = null): array
{
    try {
        $already = db_transaction(function () use ($orderId, $provider, $transactionId, $cardBrand, $cardLast4) {
            $order = db_one('SELECT * FROM orders WHERE id = ? FOR UPDATE', [$orderId]);
            if (!$order) throw new CommerceException('Order not found.');
            if ($order['status'] === 'cancelled') throw new CommerceException('This order was cancelled.');
            if ($order['payment_status'] === 'paid') return true;
            $payment = db_one("SELECT id FROM payments WHERE order_id = ? AND status IN ('pending','failed') ORDER BY id DESC LIMIT 1", [$orderId]);
            if ($payment) {
                db_exec(
                    "UPDATE payments SET status = 'paid', provider = ?, transaction_id = ?, card_brand = ?, card_last4 = ?, failure_reason = NULL, paid_at = NOW() WHERE id = ?",
                    [$provider, $transactionId, $cardBrand, $cardLast4, $payment['id']]
                );
            } else {
                db_insert(
                    "INSERT INTO payments (order_id, user_id, method, provider, transaction_id, amount, currency, status, card_brand, card_last4, paid_at) VALUES (?,?,?,?,?,?,?,?,?,?,NOW())",
                    [$orderId, $order['user_id'], 'online', $provider, $transactionId, $order['total'], CURRENCY_CODE, 'paid', $cardBrand, $cardLast4]
                );
            }
            db_exec("UPDATE orders SET payment_status = 'paid' WHERE id = ?", [$orderId]);
            add_order_history($orderId, $order['status'], 'Payment received (' . $provider . ').', null);
            notify((int) $order['user_id'], 'order', 'Payment received', 'We received your payment for order ' . $order['order_number'] . '.', 'order-details.php?id=' . $orderId);
            log_activity('payment_paid', 'order', $orderId, $provider . ' ' . $transactionId, (int) $order['user_id']);
            return false;
        });
        if (!$already) {
            $order = db_one('SELECT status FROM orders WHERE id = ?', [$orderId]);
            if ($order && $order['status'] === 'pending') {
                update_order_status($orderId, 'confirmed', 'Auto-confirmed after successful payment.', null);
            }
        }
        return ['ok' => true];
    } catch (CommerceException $e) {
        return ['ok' => false, 'error' => $e->getMessage()];
    } catch (Throwable $e) {
        app_log('error', 'mark_order_paid failed: ' . $e->getMessage());
        return ['ok' => false, 'error' => 'Could not record the payment.'];
    }
}

function mark_payment_failed(int $orderId, string $reason): void
{
    db_exec("UPDATE payments SET status = 'failed', failure_reason = ? WHERE order_id = ? AND status = 'pending'", [mb_substr($reason, 0, 250), $orderId]);
    db_exec("UPDATE orders SET payment_status = 'failed' WHERE id = ? AND payment_status = 'pending'", [$orderId]);
    log_activity('payment_failed', 'order', $orderId, $reason);
}

// ---------------------------------------------------------------------------
// Refunds
// ---------------------------------------------------------------------------

/** Create a pending refund (e.g. for an approved return). Amount is capped at what remains refundable. */
function create_refund(int $orderId, float $amount, string $reason, ?int $returnId = null, ?int $actorId = null): array
{
    $order = db_one('SELECT * FROM orders WHERE id = ?', [$orderId]);
    if (!$order) return ['ok' => false, 'error' => 'Order not found.'];
    $refunded = (float) db_value("SELECT COALESCE(SUM(amount),0) FROM refunds WHERE order_id = ? AND status IN ('pending','processed')", [$orderId], 0);
    $remaining = round((float) $order['total'] - $refunded, 2);
    $amount = round($amount, 2);
    if ($amount <= 0 || $amount > $remaining) {
        return ['ok' => false, 'error' => 'Refund amount must be between 0.01 and ' . money($remaining) . '.'];
    }
    $payment = db_one("SELECT id FROM payments WHERE order_id = ? AND status IN ('paid','partially_refunded') ORDER BY id DESC LIMIT 1", [$orderId]);
    $id = db_insert(
        'INSERT INTO refunds (order_id, return_id, payment_id, user_id, amount, reason, status) VALUES (?,?,?,?,?,?,?)',
        [$orderId, $returnId, $payment['id'] ?? null, $order['user_id'], $amount, mb_substr($reason, 0, 300), 'pending']
    );
    log_activity('refund_created', 'refund', $id, 'order ' . $order['order_number'] . ' amount ' . $amount, $actorId);
    return ['ok' => true, 'refund_id' => $id];
}

/** Mark a refund processed and update payment / order / return status. */
function process_refund(int $refundId, int $actorId): array
{
    try {
        return db_transaction(function () use ($refundId, $actorId) {
            $r = db_one('SELECT * FROM refunds WHERE id = ? FOR UPDATE', [$refundId]);
            if (!$r) throw new CommerceException('Refund not found.');
            if ($r['status'] !== 'pending') throw new CommerceException('This refund was already ' . $r['status'] . '.');
            $order = db_one('SELECT * FROM orders WHERE id = ? FOR UPDATE', [$r['order_id']]);
            db_exec("UPDATE refunds SET status = 'processed', processed_by = ?, processed_at = NOW() WHERE id = ?", [$actorId, $refundId]);
            $totalRefunded = (float) db_value("SELECT COALESCE(SUM(amount),0) FROM refunds WHERE order_id = ? AND status = 'processed'", [$r['order_id']], 0);
            $full = $totalRefunded + 0.001 >= (float) $order['total'];
            $paymentStatus = in_array($order['payment_status'], ['paid', 'partially_refunded'], true)
                ? ($full ? 'refunded' : 'partially_refunded') : $order['payment_status'];
            db_exec('UPDATE orders SET payment_status = ? WHERE id = ?', [$paymentStatus, $r['order_id']]);
            if ($r['payment_id']) {
                db_exec('UPDATE payments SET status = ? WHERE id = ?', [$full ? 'refunded' : 'partially_refunded', $r['payment_id']]);
            }
            if ($r['return_id']) {
                db_exec("UPDATE returns SET status = 'refunded' WHERE id = ?", [$r['return_id']]);
            }
            notify((int) $r['user_id'], 'order', 'Refund processed', 'A refund of ' . money($r['amount']) . ' for order ' . $order['order_number'] . ' has been processed.', 'order-details.php?id=' . $r['order_id']);
            log_activity('refund_processed', 'refund', $refundId, 'amount ' . $r['amount'], $actorId);
            return ['ok' => true];
        });
    } catch (CommerceException $e) {
        return ['ok' => false, 'error' => $e->getMessage()];
    } catch (Throwable $e) {
        app_log('error', 'process_refund failed: ' . $e->getMessage());
        return ['ok' => false, 'error' => 'Could not process the refund.'];
    }
}

/** Order id of a delivered order containing the product (verified purchase), or null. */
function user_has_purchased(int $userId, int $productId): ?int
{
    $id = db_value(
        "SELECT o.id FROM orders o JOIN order_items oi ON oi.order_id = o.id WHERE o.user_id = ? AND oi.product_id = ? AND o.status = 'delivered' ORDER BY o.id DESC LIMIT 1",
        [$userId, $productId]
    );
    return $id ? (int) $id : null;
}
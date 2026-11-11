<?php
/**
 * In-app notification helpers. Notifications are rows in notifications
 * addressed to a single user; links are app-relative paths.
 */

const NOTIFICATION_TYPES = ['order', 'shipping', 'delivery', 'discount', 'new_product', 'wishlist_discount', 'consultation', 'message', 'system'];

/** Create one notification. Never throws (a failed notification must not break the main action). */
function notify(int $userId, string $type, string $title, string $message, ?string $link = null): ?int
{
    if (!in_array($type, NOTIFICATION_TYPES, true)) {
        $type = 'system';
    }
    try {
        return db_insert(
            'INSERT INTO notifications (user_id, type, title, message, link) VALUES (?,?,?,?,?)',
            [$userId, $type, mb_substr($title, 0, 150), mb_substr($message, 0, 500), $link]
        );
    } catch (Throwable $e) {
        error_log('[skinova] notify failed: ' . $e->getMessage());
        return null;
    }
}

/** Send the same notification to many user ids; returns the number created. */
function notify_many(array $userIds, string $type, string $title, string $message, ?string $link = null): int
{
    $n = 0;
    foreach (array_unique(array_map('intval', $userIds)) as $uid) {
        if ($uid > 0 && notify($uid, $type, $title, $message, $link)) $n++;
    }
    return $n;
}

/** Notify every active user with the given role (default customers). */
function notify_all_customers(string $type, string $title, string $message, ?string $link = null, string $role = ROLE_CUSTOMER): int
{
    $ids = db_all("SELECT id FROM users WHERE role = ? AND status = 'active'", [$role]);
    return notify_many(array_column($ids, 'id'), $type, $title, $message, $link);
}

function unread_notification_count(int $userId): int
{
    return (int) db_value('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0', [$userId], 0);
}

function recent_notifications(int $userId, int $limit = 6): array
{
    return db_all('SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT ' . (int) $limit, [$userId]);
}

function mark_notification_read(int $userId, int $notificationId): void
{
    db_exec('UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id = ? AND user_id = ?', [$notificationId, $userId]);
}

function mark_all_notifications_read(int $userId): void
{
    db_exec('UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = ? AND is_read = 0', [$userId]);
}

/** Bootstrap Icons class for a notification type. */
function notification_icon(string $type): string
{
    $map = [
        'order' => 'bi-bag-check', 'shipping' => 'bi-truck', 'delivery' => 'bi-box-seam', 'discount' => 'bi-tag',
        'new_product' => 'bi-stars', 'wishlist_discount' => 'bi-heart', 'consultation' => 'bi-person-heart',
        'message' => 'bi-chat-dots', 'system' => 'bi-bell',
    ];
    return $map[$type] ?? 'bi-bell';
}

/**
 * Tell users who wishlisted a product that its price dropped.
 * Call after changing a product's price or discount.
 */
function notify_wishlist_price_drop(int $productId, float $oldFinalPrice, float $newFinalPrice): int
{
    if ($newFinalPrice >= $oldFinalPrice) return 0;
    $p = db_one('SELECT name, slug FROM products WHERE id = ?', [$productId]);
    if (!$p) return 0;
    $users = db_all(
        'SELECT w.user_id FROM wishlist_items wi JOIN wishlists w ON w.id = wi.wishlist_id WHERE wi.product_id = ?',
        [$productId]
    );
    return notify_many(
        array_column($users, 'user_id'),
        'wishlist_discount',
        'Price drop on your wishlist',
        $p['name'] . ' is now ' . money($newFinalPrice) . ' (was ' . money($oldFinalPrice) . ').',
        'product-details.php?slug=' . $p['slug']
    );
}

/** Announce a newly published product to active customers. */
function notify_new_product(int $productId): int
{
    $p = db_one('SELECT name, slug FROM products WHERE id = ? AND is_active = 1', [$productId]);
    if (!$p) return 0;
    return notify_all_customers('new_product', 'New arrival: ' . $p['name'], 'Check out our newest skincare addition.', 'product-details.php?slug=' . $p['slug']);
}
<?php
require_once __DIR__ . '/../../includes/init.php';
require_post();
csrf_verify();
$user = require_login();

$reviewId = input_int('review_id');
$review   = db_one("SELECT r.id, p.slug FROM reviews r JOIN products p ON p.id = r.product_id WHERE r.id = ? AND r.status = 'approved'", [$reviewId]);
if (!$review) {
    redirect_back('products.php');
}

// Toggle helpful
$existing = db_value('SELECT 1 FROM review_helpful WHERE review_id = ? AND user_id = ?', [$reviewId, $user['id']]);
if ($existing) {
    db_exec('DELETE FROM review_helpful WHERE review_id = ? AND user_id = ?', [$reviewId, $user['id']]);
    db_exec('UPDATE reviews SET helpful_count = GREATEST(0, helpful_count - 1) WHERE id = ?', [$reviewId]);
} else {
    db_exec('INSERT IGNORE INTO review_helpful (review_id, user_id) VALUES (?,?)', [$reviewId, $user['id']]);
    db_exec('UPDATE reviews SET helpful_count = helpful_count + 1 WHERE id = ?', [$reviewId]);
}

redirect_back('product-details.php?slug=' . urlencode($review['slug']));

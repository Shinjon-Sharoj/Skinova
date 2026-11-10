<?php
require_once __DIR__ . '/../../includes/init.php';
require_post();
csrf_verify();
$user = require_login();

$reviewId = input_int('review_id');
$reason   = mb_substr(clean_text(input('reason')), 0, 300);

if ($reason === '') {
    flash('danger', 'Please provide a reason for reporting.');
    redirect_back('products.php');
}

$review = db_one("SELECT r.id, p.slug FROM reviews r JOIN products p ON p.id = r.product_id WHERE r.id = ? AND r.status = 'approved'", [$reviewId]);
if (!$review) redirect_back('products.php');

// One report per user per review
$existing = db_value('SELECT id FROM review_reports WHERE review_id = ? AND user_id = ?', [$reviewId, $user['id']]);
if ($existing) {
    flash('info', 'You have already reported this review.');
    redirect('product-details.php?slug=' . urlencode($review['slug']));
}

db_insert("INSERT INTO review_reports (review_id, user_id, reason, status) VALUES (?,?,?,'open')", [$reviewId, $user['id'], $reason]);
db_exec('UPDATE reviews SET report_count = report_count + 1 WHERE id = ?', [$reviewId]);

flash('success', 'Review reported. Thank you.');
redirect('product-details.php?slug=' . urlencode($review['slug']));

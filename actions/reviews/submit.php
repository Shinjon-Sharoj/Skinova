<?php
require_once __DIR__ . '/../../includes/init.php';
require_post();
csrf_verify();
$user = require_login();

$productId = input_int('product_id');
$product   = db_one('SELECT id, slug, name FROM products WHERE id = ? AND is_active = 1', [$productId]);
if (!$product) not_found();

// Check purchase
$canReview = (bool) db_value(
    "SELECT 1 FROM order_items oi JOIN orders o ON o.id = oi.order_id WHERE oi.product_id = ? AND o.user_id = ? AND o.status = 'delivered' LIMIT 1",
    [$productId, $user['id']]
);
if (!$canReview) {
    flash('danger', 'You can only review products you have purchased and received.');
    redirect('product-details.php?slug=' . urlencode($product['slug']));
}

// Already reviewed?
if (db_value('SELECT id FROM reviews WHERE product_id = ? AND user_id = ?', [$productId, $user['id']])) {
    flash('info', 'You have already reviewed this product.');
    redirect('product-details.php?slug=' . urlencode($product['slug']));
}

// Rate limit
if (!rate_limit('review:' . $user['id'], 5, 3600)) {
    flash('danger', 'Too many reviews submitted. Please try again later.');
    redirect('product-details.php?slug=' . urlencode($product['slug']));
}

$v = new Validator($_POST);
$v->required('rating', 'Rating')->integer('rating')->min_val('rating', 1)->max_val('rating', 5);
$v->required('body', 'Review');
$errors = $v->errors();

if ($errors) {
    flash('danger', implode(' ', $errors));
    redirect('product-details.php?slug=' . urlencode($product['slug']));
}

$rating = input_int('rating');
$title  = input('title') ? mb_substr(clean_text(input('title')), 0, 150) : null;
$body   = mb_substr(clean_text(input('body')), 0, 5000);

// Check verified purchase
$orderId = db_value(
    "SELECT o.id FROM order_items oi JOIN orders o ON o.id = oi.order_id WHERE oi.product_id = ? AND o.user_id = ? AND o.status = 'delivered' ORDER BY o.id DESC LIMIT 1",
    [$productId, $user['id']]
);

$reviewId = db_insert(
    'INSERT INTO reviews (product_id, user_id, order_id, rating, title, body, is_verified_purchase, status) VALUES (?,?,?,?,?,?,?,?)',
    [$productId, $user['id'], $orderId ?: null, $rating, $title, $body, $orderId ? 1 : 0, 'pending']
);

// Images
if (!empty($_FILES['images']['name'][0])) {
    $files = normalize_files_array($_FILES['images']);
    $count = 0;
    foreach ($files as $file) {
        if ($count >= 3) break;
        $result = save_image_upload($file, 'reviews');
        if (!isset($result['error'])) {
            db_insert('INSERT INTO review_images (review_id, image_path) VALUES (?,?)', [$reviewId, $result['path']]);
            $count++;
        }
    }
}

log_activity('review_submitted', 'product', $productId, 'rating=' . $rating, (int) $user['id']);
flash('success', 'Thank you for your review! It will appear once approved.');
redirect('product-details.php?slug=' . urlencode($product['slug']));

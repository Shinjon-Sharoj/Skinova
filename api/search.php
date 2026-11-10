<?php
/**
 * API: Search (autocomplete)
 * GET ?q=  -> {products:[...], suggestions:[...]}
 */
require_once _DIR_ . '/../includes/init.php';

$user = api_init(['methods' => ['GET'], 'auth' => false, 'rate' => [120, 60]]);
$in   = api_input();
$q    = trim((string) ($in['q'] ?? ''));

if (strlen($q) < 2) {
    api_ok(['products' => [], 'suggestions' => []]);
}

// Quick product name/brand match
$like    = '%' . addcslashes($q, '%_\\') . '%';
$products = db_all(
    "SELECT p.id, p.name, p.slug, p.price, p.discount_percent,
            ROUND(p.price * (1 - p.discount_percent/100), 2) AS final_price,
            b.name AS brand_name,
            (SELECT pi.image_path FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.is_primary DESC, pi.id LIMIT 1) AS image_path
     FROM products p
     LEFT JOIN brands b ON b.id = p.brand_id
     WHERE p.is_active = 1 AND (p.name LIKE ? OR b.name LIKE ?)
     ORDER BY p.sold_count DESC LIMIT 6",
    [$like, $like]
);

// Format for autocomplete
$formatted = array_map(fn($p) => [
    'id'         => $p['id'],
    'name'       => $p['name'],
    'slug'       => $p['slug'],
    'price'      => money($p['final_price']),
    'brand'      => $p['brand_name'],
    'image_url'  => media_url($p['image_path'], 'product'),
    'url'        => url('product-details.php?slug=' . urlencode($p['slug'])),
], $products);

// Top search suggestions from logs
$suggestions = db_all(
    "SELECT term FROM search_logs WHERE term LIKE ? AND results_count > 0 GROUP BY term ORDER BY COUNT(*) DESC LIMIT 5",
    [$like]
);

api_ok([
    'products'    => $formatted,
    'suggestions' => array_column($suggestions, 'term'),
]);
<?php
/**
 * API: Products
 * GET action=list        ?q=&category=&brand=&sort=&page=  -> {items,total,page}
 * GET action=detail      ?id= or ?slug=                    -> {product,images,ingredients,...}
 * GET action=search      ?q=                               -> {items}
 * POST action=track_view {product_id}                      -> {}
 */
require_once _DIR_ . '/../includes/init.php';

$user = api_init(['methods' => ['GET', 'POST'], 'auth' => false, 'rate' => [120, 60]]);
$in   = api_input();
$action = $in['action'] ?? 'list';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'list') {
        $f = [
            'q'         => (string) ($in['q'] ?? ''),
            'category'  => $in['category'] ?? '',
            'brand'     => $in['brand'] ?? '',
            'skin_type' => $in['skin_type'] ?? '',
            'concern'   => $in['concern'] ?? '',
            'min_price' => $in['min_price'] ?? '',
            'max_price' => $in['max_price'] ?? '',
            'sort'      => $in['sort'] ?? 'popular',
            'in_stock'  => !empty($in['in_stock']),
            'on_sale'   => !empty($in['on_sale']),
            'featured'  => !empty($in['featured']),
        ];
        $page   = (int) ($in['page'] ?? 1);
        $result = get_products($f, $page, (int) ($in['per_page'] ?? PRODUCTS_PER_PAGE));
        if ($f['q']) log_search($f['q'], $result['page']['total']);
        api_ok($result);
    }

    if ($action === 'detail') {
        $product = null;
        if (!empty($in['slug'])) {
            $product = get_product_by_slug((string) $in['slug']);
        } elseif (!empty($in['id'])) {
            $product = get_product((int) $in['id']);
        }
        if (!$product) api_fail('Product not found.', 404);
        $details = get_product_details((int) $product['id']);
        api_ok(['product' => $details]);
    }

    if ($action === 'related') {
        $p = get_product((int) ($in['id'] ?? 0));
        if (!$p) api_fail('Product not found.', 404);
        api_ok(['products' => get_related_products($p, (int) ($in['limit'] ?? 4))]);
    }

    api_fail('Unknown action.', 400);
}

// POST
if ($action === 'track_view') {
    $id = (int) ($in['product_id'] ?? 0);
    if ($id) track_product_view($id);
    api_ok([]);
}
api_fail('Unknown action.', 400);
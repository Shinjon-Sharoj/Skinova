<?php
/**
 * API: Ingredient Analysis
 * GET  action=detail  ?slug= -> {ingredient, products}
 * GET  action=list           -> {ingredients}
 * POST action=toggle_sensitivity {ingredient_id} -> {sensitive}
 */
require_once _DIR_ . '/../includes/init.php';

$user = api_init(['methods' => ['GET', 'POST'], 'auth' => false, 'rate' => [120, 60]]);
$in   = api_input();
$action = $in['action'] ?? 'list';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'list') {
        api_ok(['ingredients' => get_all_ingredients()]);
    }
    if ($action === 'detail') {
        $slug = (string) ($in['slug'] ?? '');
        $ing  = $slug ? db_one('SELECT * FROM ingredients WHERE slug = ?', [$slug]) : null;
        if (!$ing) api_fail('Ingredient not found.', 404);
        $products = db_all(
            'SELECT p.id, p.name, p.slug FROM product_ingredients pi JOIN products p ON p.id=pi.product_id WHERE pi.ingredient_id=? AND p.is_active=1 LIMIT 10',
            [$ing['id']]
        );
        api_ok(['ingredient' => $ing, 'products' => $products]);
    }
    api_fail('Unknown action.', 400);
}

// POST — requires auth
$u = current_user();
if (!$u) api_fail('Unauthenticated.', 401);

if ($action === 'toggle_sensitivity') {
    $id  = (int) ($in['ingredient_id'] ?? 0);
    $ing = db_one('SELECT id FROM ingredients WHERE id = ?', [$id]);
    if (!$ing) api_fail('Ingredient not found.', 404);

    $exists = db_value('SELECT 1 FROM user_ingredient_sensitivities WHERE user_id=? AND ingredient_id=?', [$u['id'], $id]);
    if ($exists) {
        db_exec('DELETE FROM user_ingredient_sensitivities WHERE user_id=? AND ingredient_id=?', [$u['id'], $id]);
    } else {
        db_exec('INSERT IGNORE INTO user_ingredient_sensitivities (user_id, ingredient_id) VALUES (?,?)', [$u['id'], $id]);
    }
    api_ok(['sensitive' => !$exists]);
}

api_fail('Unknown action.', 400);
<?php
require_once __DIR__ . '/../../includes/init.php';
require_post();
csrf_verify();
$user = require_login();

$ingredientId = input_int('ingredient_id');
$ing = db_one('SELECT id, slug FROM ingredients WHERE id = ?', [$ingredientId]);
if (!$ing) {
    flash('danger', 'Ingredient not found.');
    redirect('ingredient-analyzer.php');
}

$exists = db_value('SELECT 1 FROM user_ingredient_sensitivities WHERE user_id = ? AND ingredient_id = ?', [$user['id'], $ingredientId]);
if ($exists) {
    db_exec('DELETE FROM user_ingredient_sensitivities WHERE user_id = ? AND ingredient_id = ?', [$user['id'], $ingredientId]);
    flash('success', 'Removed from your sensitivities.');
} else {
    db_exec('INSERT IGNORE INTO user_ingredient_sensitivities (user_id, ingredient_id) VALUES (?,?)', [$user['id'], $ingredientId]);
    flash('info', 'Added to your sensitivities. Products containing this ingredient will be flagged in recommendations.');
}

redirect('ingredient-analyzer.php?ingredient=' . urlencode($ing['slug']));

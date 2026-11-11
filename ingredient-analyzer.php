<?php
require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Ingredient Analyzer';
$extraCss  = ['css/skincare.css'];

$ingredientSlug = get_str('ingredient');
$ingredient     = null;
$products       = [];
$userSensitive  = false;

if ($ingredientSlug) {
    $ingredient = db_one('SELECT * FROM ingredients WHERE slug = ?', [$ingredientSlug]);
    if ($ingredient) {
        // Products containing this ingredient
        $prodRows = db_all(
            'SELECT p.id, p.name, p.slug, ROUND(p.price*(1-p.discount_percent/100),2) AS final_price,
                    (SELECT pi.image_path FROM product_images pi WHERE pi.product_id=p.id ORDER BY pi.is_primary DESC LIMIT 1) AS image_path
             FROM product_ingredients pii
             JOIN products p ON p.id = pii.product_id
             WHERE pii.ingredient_id = ? AND p.is_active = 1 ORDER BY p.sold_count DESC LIMIT 8',
            [$ingredient['id']]
        );
        $products = $prodRows;

        // Check if logged-in user marked as sensitive
        if (is_logged_in()) {
            $userSensitive = (bool) db_value(
                'SELECT 1 FROM user_ingredient_sensitivities WHERE user_id = ? AND ingredient_id = ?',
                [current_user_id(), $ingredient['id']]
            );
        }
    }
}

// All ingredients for search
$allIngredients = db_all('SELECT id, name, slug, ingredient_type, risk_level, is_common_allergen FROM ingredients ORDER BY name');

include __DIR__ . '/includes/header.php';
?>
<div class="container py-4">
    <h1 class="h3 mb-2">Ingredient Analyzer</h1>
    <p class="text-muted mb-4">Look up ingredients in your skincare products to understand what they do and any considerations.</p>

    <div class="alert alert-warning small py-2 mb-4">
        <i class="bi bi-exclamation-triangle"></i> <?= e(AI_DISCLAIMER) ?>
    </div>

    <!-- Search -->
    <div class="row justify-content-center mb-5">
        <div class="col-md-6">
            <form method="get" action="">
                <div class="input-group">
                    <input type="text" name="ingredient" class="form-control"
                           placeholder="Search ingredient (e.g. niacinamide, retinol)…"
                           value="<?= e($ingredientSlug) ?>" list="ingredientList" autocomplete="off">
                    <datalist id="ingredientList">
                        <?php foreach ($allIngredients as $ing): ?>
                        <option value="<?= e($ing['slug']) ?>"><?= e($ing['name']) ?></option>
                        <?php endforeach; ?>
                    </datalist>
                    <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Analyze</button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($ingredient): ?>
    <!-- Ingredient detail -->
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h2 class="h4 mb-0"><?= e($ingredient['name']) ?></h2>
                            <?php if ($ingredient['inci_name']): ?>
                            <div class="text-muted small fst-italic"><?= e($ingredient['inci_name']) ?></div>
                            <?php endif; ?>
                        </div>
                        <span class="badge <?= $ingredient['risk_level'] === 'high' ? 'text-bg-danger' : ($ingredient['risk_level'] === 'moderate' ? 'text-bg-warning' : 'text-bg-success') ?> fs-6">
                            <?= ucfirst($ingredient['risk_level']) ?> risk
                        </span>
                    </div>

                    <?php if ($ingredient['ingredient_type']): ?>
                    <div class="mb-2"><span class="badge text-bg-info"><?= e($ingredient['ingredient_type']) ?></span></div>
                    <?php endif; ?>

                    <?php if ($ingredient['is_common_allergen']): ?>
                    <div class="alert alert-warning py-2 small">
                        <i class="bi bi-exclamation-triangle-fill"></i> This is a commonly reported allergen. Patch test before use.
                    </div>
                    <?php endif; ?>

                    <?php if ($userSensitive): ?>
                    <div class="alert alert-danger py-2 small">
                        <i class="bi bi-person-exclamation"></i> You have marked this ingredient as a sensitivity in your skin profile.
                    </div>
                    <?php endif; ?>

                    <?php if ($ingredient['purpose']): ?>
                    <h6 class="mt-3">Purpose</h6>
                    <p class="text-muted"><?= e($ingredient['purpose']) ?></p>
                    <?php endif; ?>

                    <?php if ($ingredient['description']): ?>
                    <h6>About this ingredient</h6>
                    <p class="text-muted"><?= nl2br(e($ingredient['description'])) ?></p>
                    <?php endif; ?>

                    <?php if ($ingredient['considerations']): ?>
                    <h6>Considerations</h6>
                    <div class="alert alert-light border small"><?= nl2br(e($ingredient['considerations'])) ?></div>
                    <?php endif; ?>

                    <?php if (is_logged_in()): ?>
                    <div class="mt-3">
                        <form method="post" action="<?= e(url('actions/skincare/toggle-sensitivity.php')) ?>" class="d-inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="ingredient_id" value="<?= $ingredient['id'] ?>">
                            <button type="submit" class="btn btn-sm <?= $userSensitive ? 'btn-danger' : 'btn-outline-warning' ?>">
                                <i class="bi bi-person-exclamation"></i>
                                <?= $userSensitive ? 'Remove from my sensitivities' : 'Mark as my sensitivity' ?>
                            </button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Products with this ingredient -->
        <?php if ($products): ?>
        <div class="col-lg-5">
            <h5 class="mb-3">Products containing this ingredient</h5>
            <div class="list-group shadow-sm">
                <?php foreach ($products as $p): ?>
                <a href="<?= e(url('product-details.php?slug=' . urlencode($p['slug']))) ?>"
                   class="list-group-item list-group-item-action d-flex gap-3 py-2">
                    <img src="<?= e(product_image_url($p['image_path'] ?? null)) ?>" width="44" height="44"
                         style="object-fit:contain;background:#f8f9fa;border-radius:4px" alt="">
                    <div>
                        <div class="small fw-semibold"><?= e($p['name']) ?></div>
                        <div class="text-primary small"><?= e(money($p['final_price'])) ?></div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php elseif ($ingredientSlug): ?>
    <div class="alert alert-warning">Ingredient not found. Try a different search term.</div>
    <?php else: ?>
    <!-- Browse all -->
    <h5 class="mb-3">Browse all ingredients</h5>
    <div class="row g-2">
        <?php foreach ($allIngredients as $ing): ?>
        <div class="col-6 col-md-4 col-lg-3">
            <a href="?ingredient=<?= urlencode($ing['slug']) ?>"
               class="d-flex align-items-center gap-2 p-2 border rounded text-decoration-none text-dark hover-bg small">
                <span class="badge <?= $ing['risk_level'] === 'high' ? 'text-bg-danger' : ($ing['risk_level'] === 'moderate' ? 'text-bg-warning' : 'text-bg-success') ?>">
                    <?= ucfirst(substr($ing['risk_level'], 0, 3)) ?>
                </span>
                <span class="text-truncate"><?= e($ing['name']) ?></span>
                <?php if ($ing['is_common_allergen']): ?><i class="bi bi-exclamation-triangle text-warning ms-auto" title="Common allergen"></i><?php endif; ?>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/ai.php';
require_once __DIR__ . '/includes/auth-check.php';

$pageTitle = 'My Recommendations';
$extraCss  = ['css/products.css', 'css/skincare.css'];

$profile = get_skin_profile((int) $authUser['id']);
$sp      = $profile['profile'];

// Generate or load recommendations
$generate = isset($_GET['refresh']) || !$sp;
$recs = ['products' => [], 'source' => 'rule_based', 'provider' => null];

if ($sp && $sp['consent_given']) {
    // Load existing active recommendations (newest batch)
    $batchKey = db_value(
        "SELECT batch_key FROM ai_recommendations WHERE user_id = ? AND status = 'active' ORDER BY created_at DESC LIMIT 1",
        [$authUser['id']]
    );
    if ($batchKey && !isset($_GET['refresh'])) {
        $recRows  = db_all(
            "SELECT ar.*, p.name, p.slug FROM ai_recommendations ar
             JOIN products p ON p.id = ar.product_id
             WHERE ar.user_id = ? AND ar.batch_key = ? AND ar.status = 'active' ORDER BY ar.score DESC",
            [$authUser['id'], $batchKey]
        );
        if ($recRows) {
            $ids     = array_column($recRows, 'product_id');
            $prods   = get_products_by_ids($ids);
            $source  = $recRows[0]['source'] ?? 'rule_based';
            $recs    = ['products' => $prods, 'source' => $source, 'provider' => $recRows[0]['provider'] ?? null];
        }
    }
    if (!$recs['products'] || isset($_GET['refresh'])) {
        $recs = generate_recommendations((int) $authUser['id']);
    }
} elseif (!$sp) {
    $recs['products'] = get_best_sellers(8);
}

// Budget recommendations
$budgetRecs = [];
if ($sp && $sp['budget_max']) {
    $budgetResult = get_products([
        'sort'      => 'rating',
        'in_stock'  => true,
        'max_price' => (float) $sp['budget_max'],
        'skin_type' => $sp['skin_type'] ?? null,
    ], 1, 4);
    $budgetRecs = $budgetResult['items'];
}

include __DIR__ . '/includes/header.php';
?>
<div class="container py-4">
    <div class="row">
        <div class="col-lg-3 mb-4"><?php include __DIR__ . '/includes/sidebar.php'; ?></div>
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="h4 mb-0">My Recommendations</h2>
                    <?php if ($recs['source']): ?>
                    <span class="badge text-bg-light border small">
                        <i class="bi bi-info-circle"></i>
                        Source: <?= $recs['source'] === 'ai' ? 'AI (' . e($recs['provider'] ?? 'AI') . ')' : humanize($recs['source']) ?>
                    </span>
                    <?php endif; ?>
                </div>
                <?php if ($sp): ?>
                <a href="?refresh=1" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-arrow-clockwise"></i> Refresh
                </a>
                <?php endif; ?>
            </div>

            <?php if (!$sp || !$sp['consent_given']): ?>
            <div class="alert alert-info">
                <i class="bi bi-droplet-half"></i>
                <strong>Set up your skin profile</strong> for personalised recommendations.
                <a href="<?= e(url('skincare-profile.php')) ?>" class="alert-link ms-1">Complete your profile →</a>
            </div>
            <p class="text-muted small">Showing popular products below. These are not personalised.</p>
            <?php endif; ?>

            <div class="alert alert-warning small py-2">
                <i class="bi bi-exclamation-triangle"></i> <?= e(AI_DISCLAIMER) ?>
            </div>

            <?php if ($recs['products']): ?>
            <h5 class="mb-3">
                <?= $sp ? 'Recommended for your ' . ucfirst($sp['skin_type'] ?? '') . ' skin' : 'Popular Products' ?>
            </h5>
            <?php render_product_grid($recs['products'], 'No recommendations yet.'); ?>
            <?php endif; ?>

            <?php if ($budgetRecs): ?>
            <h5 class="mt-5 mb-3">Within your budget (up to <?= e(money($sp['budget_max'])) ?>)</h5>
            <?php render_product_grid($budgetRecs, '', 'col-6 col-md-3'); ?>
            <?php endif; ?>

            <?php if ($sp): ?>
            <div class="card shadow-sm mt-5 bg-light border-0">
                <div class="card-body d-flex flex-wrap gap-3 align-items-center justify-content-between">
                    <div>
                        <h6 class="mb-1">Want expert advice?</h6>
                        <p class="mb-0 small text-muted">Our certified skincare professionals can provide a personalised consultation.</p>
                    </div>
                    <a href="<?= e(url('consultations.php')) ?>" class="btn btn-primary btn-sm">
                        <i class="bi bi-person-heart"></i> Book a consultation
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

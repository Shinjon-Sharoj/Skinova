<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/ai.php';
require_once __DIR__ . '/includes/auth-check.php';

$pageTitle = 'Skincare Routine';
$extraCss  = ['css/skincare.css', 'css/products.css'];

$routineId = get_int('id');
$routine   = null;
$steps     = [];

if ($routineId) {
    $routine = db_one('SELECT * FROM skincare_routines WHERE id = ? AND user_id = ?', [$routineId, $authUser['id']]);
    if (!$routine) not_found();
    $steps = db_all(
        'SELECT rs.*, p.name AS product_name, p.slug, p.price, p.discount_percent,
                ROUND(p.price*(1-p.discount_percent/100),2) AS final_price,
                COALESCE(i.quantity,0) AS stock,
                (SELECT pi2.image_path FROM product_images pi2 WHERE pi2.product_id=p.id ORDER BY pi2.is_primary DESC LIMIT 1) AS image_path
         FROM routine_steps rs
         LEFT JOIN products p ON p.id = rs.product_id
         LEFT JOIN inventory i ON i.product_id = p.id
         WHERE rs.routine_id = ? ORDER BY rs.period, rs.step_order',
        [$routineId]
    );
} else {
    // List all routines
    $routines = db_all(
        'SELECT * FROM skincare_routines WHERE user_id = ? ORDER BY created_at DESC LIMIT 20',
        [$authUser['id']]
    );
}

// Group steps by period
$byPeriod = [];
foreach ($steps as $step) {
    $byPeriod[$step['period']][] = $step;
}

include __DIR__ . '/includes/header.php';
?>
<div class="container py-4">
    <div class="row">
        <div class="col-lg-3 mb-4"><?php include __DIR__ . '/includes/sidebar.php'; ?></div>
        <div class="col-lg-9">
            <?php if ($routine): ?>
            <!-- Single routine view -->
            <div class="d-flex flex-wrap justify-content-between align-items-start mb-4 gap-2">
                <div>
                    <h2 class="h4 mb-0"><?= e($routine['title']) ?></h2>
                    <div class="text-muted small">
                        <?= ucfirst($routine['skin_type'] ?? '') ?> skin
                        <?php if ($routine['concerns_summary']): ?> · <?= e($routine['concerns_summary']) ?><?php endif; ?>
                        · Generated <?= time_ago($routine['created_at']) ?>
                        · <span class="badge text-bg-light border">
                            <?= $routine['generation_source'] === 'ai' ? 'AI (' . e($routine['ai_provider'] ?? '') . ')' : 'Rule-based' ?>
                          </span>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= e(url('skincare-routine.php')) ?>" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left"></i> All routines
                    </a>
                    <form method="post" action="<?= e(url('actions/skincare/delete-routine.php')) ?>"
                          onsubmit="return confirm('Delete this routine?')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="routine_id" value="<?= $routineId ?>">
                        <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                    </form>
                </div>
            </div>

            <div class="alert alert-warning small py-2">
                <i class="bi bi-exclamation-triangle"></i> <?= e(AI_DISCLAIMER) ?>
            </div>

            <?php if ($routine['precautions']): ?>
            <div class="alert alert-info small">
                <i class="bi bi-info-circle"></i> <?= nl2br(e($routine['precautions'])) ?>
            </div>
            <?php endif; ?>

            <?php foreach (['morning' => '☀️ Morning Routine', 'evening' => '🌙 Evening Routine', 'weekly' => '📅 Weekly'] as $period => $label): ?>
            <?php if (empty($byPeriod[$period])) continue; ?>
            <h4 class="mt-4 mb-3"><?= $label ?></h4>
            <div class="list-group shadow-sm mb-4">
                <?php foreach ($byPeriod[$period] as $idx => $step): ?>
                <div class="list-group-item py-3">
                    <div class="d-flex gap-3 align-items-start">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center flex-shrink-0 fw-bold"
                             style="width:32px;height:32px;font-size:.9rem"><?= $step['step_order'] ?></div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold"><?= e($step['step_name']) ?></div>
                            <?php if ($step['product_id']): ?>
                            <div class="d-flex gap-2 mt-2 align-items-center">
                                <img src="<?= e(product_image_url($step['image_path'] ?? null)) ?>" width="48" height="48"
                                     style="object-fit:contain;background:#f8f9fa;border-radius:4px" alt="">
                                <div>
                                    <a href="<?= e(url('product-details.php?slug=' . urlencode($step['slug']))) ?>"
                                       class="small fw-semibold text-decoration-none"><?= e($step['product_name']) ?></a>
                                    <div class="text-primary small"><?= e(money($step['final_price'])) ?></div>
                                </div>
                                <button class="btn btn-primary btn-sm ms-auto" data-action="add-to-cart"
                                        data-product-id="<?= $step['product_id'] ?>"
                                        <?= $step['stock'] <= 0 ? 'disabled' : '' ?>>
                                    <i class="bi bi-cart-plus"></i>
                                </button>
                            </div>
                            <?php else: ?>
                            <p class="small text-muted mb-0">No specific product assigned — choose one that suits your skin type.</p>
                            <?php endif; ?>
                            <?php if ($step['instructions']): ?>
                            <p class="small text-muted mt-1 mb-0"><i class="bi bi-arrow-right-short"></i> <?= e(truncate($step['instructions'], 150)) ?></p>
                            <?php endif; ?>
                            <?php if ($step['reason']): ?>
                            <p class="small text-muted mb-0 fst-italic"><i class="bi bi-lightbulb"></i> <?= e(truncate($step['reason'], 100)) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>

            <?php if ($routine['total_cost'] > 0): ?>
            <div class="text-muted small">Estimated total: <strong class="text-primary"><?= e(money($routine['total_cost'])) ?></strong></div>
            <?php endif; ?>

            <?php else: ?>
            <!-- Routines list -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="h4 mb-0">My Skincare Routines</h2>
                <form method="post" action="<?= e(url('actions/skincare/generate-routine.php')) ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-magic"></i> Generate new routine
                    </button>
                </form>
            </div>

            <?php if (!empty($routines)): ?>
            <div class="list-group shadow-sm">
                <?php foreach ($routines as $r): ?>
                <a href="<?= e(url('skincare-routine.php?id=' . $r['id'])) ?>"
                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                    <div>
                        <div class="fw-semibold"><?= e($r['title']) ?></div>
                        <div class="text-muted small"><?= time_ago($r['created_at']) ?> · <?= e(money($r['total_cost'])) ?></div>
                    </div>
                    <span class="badge text-bg-light border">
                        <?= $r['generation_source'] === 'ai' ? 'AI' : 'Rule-based' ?>
                    </span>
                </a>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-list-check" style="font-size:3rem"></i>
                <p class="mt-2">No routines yet.</p>
                <?php $sp = get_skin_profile((int) $authUser['id'])['profile']; ?>
                <?php if (!$sp || !$sp['consent_given']): ?>
                <p class="small">First <a href="<?= e(url('skincare-profile.php')) ?>">set up your skin profile</a>, then generate your routine.</p>
                <?php else: ?>
                <form method="post" action="<?= e(url('actions/skincare/generate-routine.php')) ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-primary">Generate my routine</button>
                </form>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

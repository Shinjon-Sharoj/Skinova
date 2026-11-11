<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/ai.php';
require_once __DIR__ . '/includes/auth-check.php';

$pageTitle = 'Skin Image Analysis';
$extraCss  = ['css/skincare.css'];

$myAnalyses = db_all(
    'SELECT * FROM skin_analyses WHERE user_id = ? ORDER BY created_at DESC LIMIT 10',
    [$authUser['id']]
);

// View a specific analysis
$viewId   = get_int('view');
$analysis = null;
if ($viewId) {
    $analysis = db_one('SELECT * FROM skin_analyses WHERE id = ? AND user_id = ?', [$viewId, $authUser['id']]);
    if (!$analysis) not_found();
}

include __DIR__ . '/includes/header.php';
?>
<div class="container py-4" style="max-width:900px">
    <div class="row">
        <div class="col-lg-3 mb-4"><?php include __DIR__ . '/includes/sidebar.php'; ?></div>
        <div class="col-lg-9">
            <h2 class="h4 mb-2">Skin Image Analysis</h2>

            <div class="alert alert-danger small">
                <i class="bi bi-shield-exclamation"></i>
                <strong>Important:</strong> This tool uses
                <?= AI_PROVIDER !== 'none' ? 'AI vision and ' : '' ?>basic image metrics.
                It is <strong>NOT a medical diagnosis</strong> and cannot replace a dermatologist.
                <?= e(AI_DISCLAIMER) ?>
            </div>

            <?php if ($analysis): ?>
            <!-- View single analysis -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Analysis from <?= format_datetime($analysis['created_at']) ?></h5>
                <a href="<?= e(url('skin-analysis.php')) ?>" class="btn btn-outline-secondary btn-sm">Back</a>
            </div>

            <div class="row g-4">
                <div class="col-md-5">
                    <img src="<?= e(private_file_url('skin', $analysis['id'])) ?>"
                         class="img-fluid rounded shadow-sm" alt="Skin analysis image">
                </div>
                <div class="col-md-7">
                    <?php $results = json_decode($analysis['results'] ?? '{}', true); ?>

                    <div class="mb-3">
                        <span class="badge text-bg-light border">
                            Method: <?= e(humanize($analysis['method'])) ?>
                            <?php if ($analysis['provider']): ?>(<?= e($analysis['provider']) ?>)<?php endif; ?>
                        </span>
                    </div>

                    <?php if ($analysis['summary']): ?>
                    <div class="card shadow-sm mb-3">
                        <div class="card-body small"><?= nl2br(e($analysis['summary'])) ?></div>
                    </div>
                    <?php endif; ?>

                    <?php if ($results): ?>
                    <h6>Detected characteristics</h6>
                    <?php foreach (['oiliness' => 'Oiliness', 'redness' => 'Redness', 'uneven_tone' => 'Uneven tone', 'acne_like_spots' => 'Acne-like spots', 'dark_spots' => 'Dark spots'] as $key => $label): ?>
                    <?php if (isset($results[$key])): ?>
                    <div class="mb-2">
                        <div class="d-flex justify-content-between small">
                            <span><?= $label ?></span>
                            <span><?= (int) $results[$key] ?>%</span>
                        </div>
                        <div class="progress" style="height:8px">
                            <div class="progress-bar <?= $results[$key] > 70 ? 'bg-danger' : ($results[$key] > 40 ? 'bg-warning' : 'bg-success') ?>"
                                 style="width:<?= min(100, (int)$results[$key]) ?>%"></div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <?php else: ?>
            <!-- Upload form -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">Upload a skin photo for analysis</div>
                <div class="card-body">
                    <p class="text-muted small">
                        Take a clear, well-lit photo of the skin area you want to analyse (face recommended).
                        Your image is stored privately and only accessible to you.
                    </p>
                    <form method="post" action="<?= e(url('actions/ai/skin-analysis.php')) ?>"
                          enctype="multipart/form-data" novalidate>
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label for="skin_image" class="form-label">Skin photo <span class="text-danger">*</span></label>
                            <input type="file" id="skin_image" name="skin_image" class="form-control"
                                   accept="image/jpeg,image/png,image/webp" required
                                   data-preview="skinPreview">
                            <div class="form-text">JPEG, PNG or WebP, max 5 MB. Well-lit, clear photo works best.</div>
                        </div>
                        <img id="skinPreview" src="" class="img-fluid rounded mb-3 d-none" style="max-height:200px" alt="">
                        <div class="mb-3 form-check">
                            <input class="form-check-input" type="checkbox" id="consent" name="consent" value="1" required>
                            <label class="form-check-label small" for="consent">
                                I consent to <?= e(APP_NAME) ?> storing and processing this image to provide analysis results.
                                I understand this is not a medical diagnosis.
                            </label>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-camera"></i> Analyse skin
                        </button>
                    </form>
                </div>
            </div>

            <!-- Past analyses -->
            <?php if ($myAnalyses): ?>
            <h5 class="mb-3">My past analyses</h5>
            <div class="row g-3">
                <?php foreach ($myAnalyses as $a): ?>
                <div class="col-sm-6 col-md-4">
                    <a href="?view=<?= $a['id'] ?>" class="text-decoration-none">
                        <div class="card shadow-sm h-100">
                            <img src="<?= e(private_file_url('skin', $a['id'])) ?>"
                                 class="card-img-top" alt="" style="height:120px;object-fit:cover">
                            <div class="card-body py-2 small">
                                <div class="text-muted"><?= format_datetime($a['created_at']) ?></div>
                                <span class="badge text-bg-light border"><?= humanize($a['method']) ?></span>
                            </div>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>

            <div class="card bg-light border-0 mt-4">
                <div class="card-body d-flex gap-3 align-items-center">
                    <i class="bi bi-person-heart text-primary fs-3"></i>
                    <div>
                        <div class="fw-semibold small">Want a professional opinion?</div>
                        <div class="text-muted small">Book a consultation with a certified skincare professional for expert analysis.</div>
                        <a href="<?= e(url('consultations.php')) ?>" class="btn btn-primary btn-sm mt-2">Book consultation</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

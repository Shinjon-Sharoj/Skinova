<?php
require_once _DIR_ . '/includes/init.php';

$pageTitle       = 'About Us';
$metaDescription = 'Learn about ' . APP_NAME . ' — our mission, values, and the team behind your personalised skincare journey.';

// Pull some live stats
$productCount     = (int) db_value("SELECT COUNT(*) FROM products WHERE is_active = 1", [], 0);
$consultantCount  = (int) db_value("SELECT COUNT(*) FROM consultants WHERE status = 'active'", [], 0);
$customerCount    = (int) db_value("SELECT COUNT(*) FROM users WHERE role = 'customer' AND status = 'active'", [], 0);
$reviewCount      = (int) db_value("SELECT COUNT(*) FROM reviews WHERE status = 'approved'", [], 0);

include _DIR_ . '/includes/header.php';
?>
<div class="container py-5" style="max-width:960px">
    <!-- Hero -->
    <div class="text-center mb-5">
        <h1 class="display-5 fw-bold mb-3">About <?= e(APP_NAME) ?></h1>
        <p class="lead text-muted">Smart skincare, expert guidance, and community — all in one place.</p>
    </div>

    <!-- Mission -->
    <div class="row align-items-center g-5 mb-5">
        <div class="col-md-6">
            <h2 class="h3 fw-bold mb-3">Our Mission</h2>
            <p class="text-muted">We believe everyone deserves personalised skincare advice — not just those who can afford an expensive consultation. <?= e(APP_NAME) ?> combines AI-powered tools with a network of certified skincare professionals to put the right knowledge in your hands.</p>
            <p class="text-muted">From product recommendations to live one-on-one consultations, we're here every step of your skincare journey.</p>
        </div>
        <div class="col-md-6">
            <div class="row g-3 text-center">
                <div class="col-6">
                    <div class="card shadow-sm border-0 bg-primary text-white">
                        <div class="card-body py-3">
                            <div class="display-6 fw-bold"><?= number_format($productCount) ?>+</div>
                            <div class="small">Curated products</div>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="card shadow-sm border-0 bg-success text-white">
                        <div class="card-body py-3">
                            <div class="display-6 fw-bold"><?= number_format($consultantCount) ?>+</div>
                            <div class="small">Expert consultants</div>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="card shadow-sm border-0 bg-info text-white">
                        <div class="card-body py-3">
                            <div class="display-6 fw-bold"><?= number_format($customerCount) ?>+</div>
                            <div class="small">Happy customers</div>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="card shadow-sm border-0 bg-warning text-dark">
                        <div class="card-body py-3">
                            <div class="display-6 fw-bold"><?= number_format($reviewCount) ?>+</div>
                            <div class="small">Verified reviews</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Values -->
    <div class="text-center mb-4">
        <h2 class="h3 fw-bold">What We Stand For</h2>
    </div>
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body text-center">
                    <i class="bi bi-transparency text-primary fs-1 mb-3"></i>
                    <h5>Transparency</h5>
                    <p class="text-muted small">We label every recommendation source — AI, rule-based, or expert human opinion — so you always know where advice comes from.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body text-center">
                    <i class="bi bi-person-heart text-danger fs-1 mb-3"></i>
                    <h5>Human-first</h5>
                    <p class="text-muted small">AI is a tool, not a replacement. Every sensitive concern is escalated to a real human professional, and consultations are always available.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body text-center">
                    <i class="bi bi-shield-check text-success fs-1 mb-3"></i>
                    <h5>Privacy by design</h5>
                    <p class="text-muted small">Your skin data is yours. You control what you share, with whom, and you can delete it at any time.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Consultants -->
    <?php
    $consultants = db_all(
        "SELECT c.*, u.full_name, u.email, up.avatar FROM consultants c
         JOIN users u ON u.id = c.user_id
         LEFT JOIN user_profiles up ON up.user_id = c.user_id
         WHERE c.status = 'active' AND c.is_available = 1 ORDER BY c.rating_avg DESC LIMIT 3"
    );
    if ($consultants):
    ?>
    <div class="text-center mb-4">
        <h2 class="h3 fw-bold">Meet Our Experts</h2>
        <p class="text-muted">Certified skincare professionals available for 1-on-1 consultations.</p>
    </div>
    <div class="row g-4 mb-5">
        <?php foreach ($consultants as $cons): ?>
        <div class="col-md-4">
            <div class="card text-center shadow-sm border-0 h-100">
                <div class="card-body">
                    <img src="<?= e(media_url($cons['avatar'] ?? null, 'avatar')) ?>"
                         alt="<?= e($cons['full_name']) ?>" class="rounded-circle mb-3"
                         width="80" height="80" style="object-fit:cover">
                    <h5 class="mb-0"><?= e(($cons['title'] ? $cons['title'] . ' ' : '') . $cons['full_name']) ?></h5>
                    <p class="text-primary small mb-1"><?= e($cons['specialization']) ?></p>
                    <?= render_stars((float) $cons['rating_avg'], true) ?>
                    <p class="text-muted small mt-2"><?= e(truncate($cons['bio'] ?? '', 100)) ?></p>
                </div>
                <div class="card-footer bg-transparent">
                    <a href="<?= e(url('consultations.php?book=' . $cons['user_id'])) ?>" class="btn btn-primary btn-sm w-100">Book consultation</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- CTA -->
    <div class="card text-center shadow bg-primary text-white border-0 py-4">
        <div class="card-body">
            <h3 class="fw-bold mb-2">Ready to start your personalised skincare journey?</h3>
            <p class="mb-3 opacity-90">Complete your skin profile and get recommendations tailored just for you.</p>
            <div class="d-flex flex-wrap gap-2 justify-content-center">
                <a href="<?= e(url('register.php')) ?>" class="btn btn-light">Create free account</a>
                <a href="<?= e(url('consultations.php')) ?>" class="btn btn-outline-light">Talk to an expert</a>
            </div>
        </div>
    </div>
</div>
<?php include _DIR_ . '/includes/footer.php'; ?>
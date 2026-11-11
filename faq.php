<?php
require_once _DIR_ . '/includes/init.php';

$pageTitle = 'FAQ';
$faqs = db_all(
    "SELECT * FROM ai_knowledge_base WHERE is_faq = 1 AND is_active = 1 ORDER BY category, id",
);

// Group by category
$grouped = [];
foreach ($faqs as $faq) {
    $grouped[$faq['category']][] = $faq;
}

include _DIR_ . '/includes/header.php';
?>
<div class="container py-4" style="max-width:860px">
    <h1 class="h3 mb-2">Frequently Asked Questions</h1>
    <p class="text-muted mb-4">Find answers to the most common questions about our products, orders, and skincare.</p>

    <?php if ($grouped): ?>
    <?php foreach ($grouped as $category => $items): ?>
    <h5 class="text-primary mt-4 mb-2"><?= e(ucfirst(str_replace('_', ' ', $category))) ?></h5>
    <div class="accordion mb-3" id="faq_<?= e($category) ?>">
        <?php foreach ($items as $i => $faq): ?>
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                        data-bs-target="#faq_item_<?= $faq['id'] ?>" aria-expanded="false"
                        aria-controls="faq_item_<?= $faq['id'] ?>">
                    <?= e($faq['question']) ?>
                </button>
            </h2>
            <div id="faq_item_<?= $faq['id'] ?>" class="accordion-collapse collapse"
                 data-bs-parent="#faq_<?= e($category) ?>">
                <div class="accordion-body">
                    <?= nl2br(e($faq['answer'])) ?>
                    <?php if ($faq['is_sensitive']): ?>
                    <div class="alert alert-warning mt-2 small mb-0">
                        <i class="bi bi-exclamation-triangle"></i> <?= e(AI_DISCLAIMER) ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
    <?php else: ?>
    <div class="text-center py-5 text-muted">
        <i class="bi bi-question-circle" style="font-size:3rem"></i>
        <p class="mt-2">No FAQ entries yet. <a href="<?= e(url('contact.php')) ?>">Contact us</a> if you have a question.</p>
    </div>
    <?php endif; ?>

    <!-- CTA -->
    <div class="card mt-5 bg-light shadow-sm">
        <div class="card-body text-center py-4">
            <h5>Still have questions?</h5>
            <p class="text-muted">Our skincare professionals are here to help.</p>
            <a href="<?= e(url('consultations.php')) ?>" class="btn btn-primary me-2">Book a consultation</a>
            <a href="<?= e(url('contact.php')) ?>" class="btn btn-outline-secondary">Contact us</a>
        </div>
    </div>
</div>
<?php include _DIR_ . '/includes/footer.php'; ?>
<?php
require_once _DIR_ . '/includes/init.php';
require_once _DIR_ . '/includes/consultation.php';
require_once _DIR_ . '/includes/auth-check.php';

$id           = get_int('id');
$consultation = $id ? get_consultation($id, (int) $authUser['id']) : null;
if (!$consultation) not_found();

$messages     = get_consultation_messages($id);
mark_messages_read($id, (int) $authUser['id']);

$canSend      = in_array($consultation['status'], ['confirmed', 'in_progress'], true);
$canCancel    = in_array($consultation['status'], ['requested', 'confirmed'], true);
$canRate      = $consultation['status'] === 'completed' && !$consultation['user_rating'];

// Skin profile sharing
$sharedProfile = null;
if ($consultation['share_skin_profile']) {
    $sharedProfile = get_skin_profile((int) $authUser['id']);
}

$pageTitle = 'Consultation: ' . truncate($consultation['subject'], 40);
$extraCss  = ['css/consultation.css'];
$extraJs   = ['js/consultation.js'];

include _DIR_ . '/includes/header.php';
?>
<div class="container py-4">
    <div class="row">
        <div class="col-lg-3 mb-4"><?php include _DIR_ . '/includes/sidebar.php'; ?></div>
        <div class="col-lg-9">

            <!-- Header -->
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-4">
                <div>
                    <a href="<?= e(url('consultations.php')) ?>" class="text-muted small"><i class="bi bi-arrow-left"></i> Back</a>
                    <h2 class="h4 mb-0 mt-1"><?= e($consultation['subject']) ?></h2>
                    <div class="text-muted small">
                        with <?= e($consultation['consultant_name']) ?> · <?= format_datetime($consultation['created_at']) ?>
                    </div>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <?= status_badge($consultation['status']) ?>
                    <?= status_badge($consultation['payment_status']) ?>
                </div>
            </div>

            <div class="row g-4">
                <!-- Chat -->
                <div class="col-lg-8">
                    <div class="card shadow-sm d-flex flex-column" style="height:520px">
                        <div class="card-header small text-muted">
                            Messages
                            <?php if (!$canSend): ?>
                            <span class="float-end badge text-bg-warning"><?= humanize($consultation['status']) ?> — messaging <?= in_array($consultation['status'], ['completed','cancelled','rejected']) ? 'closed' : 'not yet open' ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Messages list -->
                        <div class="overflow-auto flex-grow-1 p-3" id="chatMessages"
                             data-consultation-id="<?= $id ?>"
                             data-last-id="<?= $messages ? end($messages)['id'] : 0 ?>">
                            <?php if (!$messages): ?>
                            <div class="text-center text-muted py-4 small">No messages yet.</div>
                            <?php endif; ?>
                            <?php foreach ($messages as $msg): ?>
                            <?php $isMine = $msg['sender_id'] == $authUser['id']; ?>
                            <div class="d-flex gap-2 mb-3 <?= $isMine ? 'flex-row-reverse' : '' ?> chat-msg" id="msg-<?= $msg['id'] ?>">
                                <img src="<?= e(media_url($msg['sender_avatar'] ?? null, 'avatar')) ?>"
                                     class="rounded-circle flex-shrink-0 align-self-end"
                                     width="32" height="32" style="object-fit:cover" alt="">
                                <div class="message-bubble <?= $isMine ? 'bg-primary text-white' : 'bg-light border' ?> rounded-3 px-3 py-2"
                                     style="max-width:75%">
                                    <div class="small"><?= nl2br(e($msg['message'])) ?></div>
                                    <?php if ($msg['attachment_path']): ?>
                                    <a href="<?= e(private_file_url('message', $msg['id'])) ?>"
                                       class="d-block mt-1 small <?= $isMine ? 'text-white-50' : 'text-muted' ?>">
                                        <i class="bi bi-paperclip"></i> <?= e($msg['attachment_name'] ?? 'Attachment') ?>
                                    </a>
                                    <?php endif; ?>
                                    <div style="font-size:.65rem;opacity:.6;margin-top:2px;text-align:<?= $isMine ? 'right' : 'left' ?>">
                                        <?= time_ago($msg['created_at']) ?>
                                        <?php if ($isMine): ?><?= $msg['is_read'] ? ' ✓✓' : ' ✓' ?><?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <div id="typingEnd"></div>
                        </div>

                        <!-- Input -->
                        <?php if ($canSend): ?>
                        <div class="card-footer p-2">
                            <form id="msgForm" enctype="multipart/form-data">
                                <div class="input-group">
                                    <label class="btn btn-outline-secondary btn-sm" for="attachFile" title="Attach file">
                                        <i class="bi bi-paperclip"></i>
                                        <input type="file" id="attachFile" name="attachment" class="d-none"
                                               accept=".pdf,.jpg,.jpeg,.png,.webp">
                                    </label>
                                    <input type="text" id="msgInput" class="form-control form-control-sm"
                                           placeholder="Type a message…" maxlength="5000" autocomplete="off">
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="bi bi-send"></i>
                                    </button>
                                </div>
                                <div id="attachName" class="text-muted small mt-1 d-none"></div>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Side info -->
                <div class="col-lg-4">
                    <!-- Consultant card -->
                    <div class="card shadow-sm mb-3">
                        <div class="card-body text-center">
                            <img src="<?= e(media_url($consultation['consultant_avatar'] ?? null, 'avatar')) ?>"
                                 class="rounded-circle mb-2" width="64" height="64" style="object-fit:cover" alt="">
                            <div class="fw-semibold"><?= e($consultation['consultant_name']) ?></div>
                            <div class="text-muted small"><?= e($consultation['specialization']) ?></div>
                            <div class="text-primary small fw-semibold"><?= e(money($consultation['fee'])) ?></div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="card shadow-sm mb-3">
                        <div class="card-body">
                            <?php if ($canCancel): ?>
                            <form method="post" action="<?= e(url('actions/consultations/cancel.php')) ?>"
                                  onsubmit="return confirm('Cancel this consultation?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="consultation_id" value="<?= $id ?>">
                                <button type="submit" class="btn btn-outline-danger btn-sm w-100 mb-2">
                                    <i class="bi bi-x-circle"></i> Cancel consultation
                                </button>
                            </form>
                            <?php endif; ?>

                            <?php if ($canRate): ?>
                            <button class="btn btn-outline-success btn-sm w-100" data-bs-toggle="modal" data-bs-target="#rateModal">
                                <i class="bi bi-star"></i> Rate this consultation
                            </button>
                            <?php endif; ?>

                            <?php if ($consultation['advice_summary']): ?>
                            <div class="mt-3">
                                <h6 class="small fw-semibold">Expert advice summary</h6>
                                <p class="small text-muted mb-0"><?= nl2br(e($consultation['advice_summary'])) ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Concern details -->
                    <?php if ($consultation['concern_details']): ?>
                    <div class="card shadow-sm mb-3">
                        <div class="card-header small">Your concern</div>
                        <div class="card-body small text-muted">
                            <?= nl2br(e($consultation['concern_details'])) ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Rate Modal -->
<?php if ($canRate): ?>
<div class="modal fade" id="rateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" action="<?= e(url('actions/consultations/rate.php')) ?>" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="consultation_id" value="<?= $id ?>">
            <div class="modal-header">
                <h5 class="modal-title">Rate your consultation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3 text-center">
                    <div class="fs-2 text-warning" id="rateStars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <label>
                            <input type="radio" name="user_rating" value="<?= $i ?>" class="visually-hidden" required>
                            <i class="bi bi-star" data-star="<?= $i ?>"></i>
                        </label>
                        <?php endfor; ?>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="user_feedback" class="form-label">Feedback <span class="text-muted">(optional)</span></label>
                    <textarea id="user_feedback" name="user_feedback" class="form-control" rows="3" maxlength="500"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Skip</button>
                <button type="submit" class="btn btn-primary">Submit rating</button>
            </div>
        </form>
    </div>
</div>
<script>
document.querySelectorAll('#rateStars label').forEach(label => {
    const inp = label.querySelector('input');
    label.addEventListener('mouseenter', () => highlightStars(parseInt(inp.value)));
    label.addEventListener('mouseleave', () => {
        const c = document.querySelector('#rateStars input:checked');
        highlightStars(c ? parseInt(c.value) : 0);
    });
    inp.addEventListener('change', () => highlightStars(parseInt(inp.value)));
});
function highlightStars(n) {
    document.querySelectorAll('#rateStars i').forEach((s,i) => s.className = i < n ? 'bi bi-star-fill' : 'bi bi-star');
}
</script>
<?php endif; ?>

<?php include _DIR_ . '/includes/footer.php'; ?>
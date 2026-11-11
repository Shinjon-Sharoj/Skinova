<?php
require_once _DIR_ . '/../includes/init.php';
require_once _DIR_ . '/../includes/consultation.php';
$authUser = require_role(ROLE_CONSULTANT);

$pageTitle = 'Consultation Details';
$panel     = 'consultant';
$extraJs   = ['js/consultation.js'];
$extraCss  = ['css/consultation.css'];

$consultant = get_consultant_by_user_id((int) $authUser['id']);
if (!$consultant) redirect('consultant/dashboard.php');

$id           = get_int('id');
$consultation = $id ? get_consultation($id, null, (int) $authUser['id']) : null;
if (!$consultation) not_found();

$messages = get_consultation_messages($id);
mark_messages_read($id, (int) $authUser['id']);

$canSend = in_array($consultation['status'], ['confirmed', 'in_progress'], true);

// Customer skin profile (if shared)
$sharedProfile = null;
if ($consultation['share_skin_profile']) {
    $sharedProfile = get_skin_profile((int) $consultation['user_id']);
}

// Handle inline POST actions
if (is_post() && csrf_is_valid()) {
    $act = input('action');
    if ($act === 'confirm') {
        db_exec("UPDATE consultations SET status='confirmed', started_at=NOW(), updated_at=NOW() WHERE id=?", [$id]);
        notify((int)$consultation['user_id'], 'consultation', 'Consultation confirmed', 'Your consultation with ' . $authUser['full_name'] . ' has been confirmed.', 'consultation-details.php?id=' . $id);
        flash('success', 'Consultation confirmed.');
    } elseif ($act === 'start') {
        db_exec("UPDATE consultations SET status='in_progress', started_at=COALESCE(started_at,NOW()), updated_at=NOW() WHERE id=?", [$id]);
        flash('success', 'Consultation started.');
    } elseif ($act === 'complete') {
        $advice = mb_substr(clean_text(input('advice_summary')), 0, 5000);
        db_exec("UPDATE consultations SET status='completed', advice_summary=?, completed_at=NOW(), updated_at=NOW() WHERE id=?", [$advice, $id]);
        refresh_consultant_rating((int) $consultant['id']);
        notify((int)$consultation['user_id'], 'consultation', 'Consultation completed', 'Your consultation is complete. Check the advice summary.', 'consultation-details.php?id=' . $id);
        log_activity('consultation_completed', 'consultation', $id, null, (int) $authUser['id']);
        flash('success', 'Consultation completed.');
    } elseif ($act === 'reject') {
        db_exec("UPDATE consultations SET status='rejected', updated_at=NOW() WHERE id=?", [$id]);
        notify((int)$consultation['user_id'], 'consultation', 'Consultation declined', 'Unfortunately your consultation request was declined.', 'consultations.php');
        flash('info', 'Consultation rejected.');
    }
    redirect('consultant/consultation-details.php?id=' . $id);
}

// Reload after POST redirect
$consultation = get_consultation($id, null, (int) $authUser['id']);

include _DIR_ . '/../admin/includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <a href="<?= e(url('consultant/consultations.php')) ?>" class="text-muted small"><i class="bi bi-arrow-left"></i> Back</a>
        <h2 class="h4 mt-1 mb-0"><?= e($consultation['subject']) ?></h2>
        <div class="text-muted small">Customer: <?= e($consultation['customer_name']) ?> · <?= format_datetime($consultation['created_at']) ?></div>
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
            <div class="card-header small text-muted">Messages</div>
            <div class="overflow-auto flex-grow-1 p-3" id="chatMessages"
                 data-consultation-id="<?= $id ?>"
                 data-last-id="<?= $messages ? end($messages)['id'] : 0 ?>"
                 data-bs-user-id="<?= $authUser['id'] ?>">
                <?php if (!$messages): ?>
                <div class="text-center text-muted py-4 small">No messages yet.</div>
                <?php endif; ?>
                <?php foreach ($messages as $msg): $isMine = $msg['sender_id'] == $authUser['id']; ?>
                <div class="d-flex gap-2 mb-3 chat-msg <?= $isMine ? 'flex-row-reverse' : '' ?>" id="msg-<?= $msg['id'] ?>">
                    <img src="<?= e(media_url($msg['sender_avatar'] ?? null, 'avatar')) ?>" class="rounded-circle flex-shrink-0 align-self-end" width="32" height="32" style="object-fit:cover" alt="">
                    <div class="message-bubble <?= $isMine ? 'bg-primary text-white' : 'bg-light border' ?> rounded-3 px-3 py-2" style="max-width:75%">
                        <div class="small"><?= nl2br(e($msg['message'])) ?></div>
                        <?php if ($msg['attachment_path']): ?>
                        <a href="<?= e(private_file_url('message', $msg['id'])) ?>" class="d-block mt-1 small <?= $isMine ? 'text-white-50' : 'text-muted' ?>" target="_blank">
                            <i class="bi bi-paperclip"></i> <?= e($msg['attachment_name'] ?? 'Attachment') ?>
                        </a>
                        <?php endif; ?>
                        <div style="font-size:.65rem;opacity:.6;text-align:<?= $isMine?'right':'left' ?>"><?= time_ago($msg['created_at']) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
                <div id="typingEnd"></div>
            </div>
            <?php if ($canSend): ?>
            <div class="card-footer p-2">
                <form id="msgForm" enctype="multipart/form-data">
                    <div class="input-group">
                        <label class="btn btn-outline-secondary btn-sm" for="attachFile"><i class="bi bi-paperclip"></i>
                            <input type="file" id="attachFile" name="attachment" class="d-none" accept=".pdf,.jpg,.jpeg,.png,.webp">
                        </label>
                        <input type="text" id="msgInput" class="form-control form-control-sm" placeholder="Type a message…" maxlength="5000" autocomplete="off">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-send"></i></button>
                    </div>
                    <div id="attachName" class="text-muted small mt-1 d-none"></div>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Actions sidebar -->
    <div class="col-lg-4">
        <!-- Status actions -->
        <div class="card shadow-sm mb-3">
            <div class="card-header fw-semibold small">Actions</div>
            <div class="card-body d-grid gap-2">
                <?php if ($consultation['status'] === 'requested'): ?>
                <form method="post"><input type="hidden" name="action" value="confirm"><?= csrf_field() ?>
                    <button class="btn btn-success btn-sm w-100"><i class="bi bi-check-circle"></i> Confirm consultation</button>
                </form>
                <form method="post" onsubmit="return confirm('Reject this consultation?')"><input type="hidden" name="action" value="reject"><?= csrf_field() ?>
                    <button class="btn btn-outline-danger btn-sm w-100"><i class="bi bi-x-circle"></i> Reject</button>
                </form>
                <?php elseif ($consultation['status'] === 'confirmed'): ?>
                <form method="post"><input type="hidden" name="action" value="start"><?= csrf_field() ?>
                    <button class="btn btn-primary btn-sm w-100"><i class="bi bi-play-circle"></i> Start session</button>
                </form>
                <?php elseif ($consultation['status'] === 'in_progress'): ?>
                <button class="btn btn-success btn-sm w-100" data-bs-toggle="modal" data-bs-target="#completeModal">
                    <i class="bi bi-check2-all"></i> Mark as completed
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Customer info -->
        <div class="card shadow-sm mb-3">
            <div class="card-header small fw-semibold">Customer</div>
            <div class="card-body small">
                <div class="fw-semibold"><?= e($consultation['customer_name']) ?></div>
                <div class="text-muted"><?= e($consultation['customer_email']) ?></div>
                <?php if ($consultation['preferred_at']): ?>
                <div class="mt-1 text-muted"><i class="bi bi-calendar"></i> Preferred: <?= format_datetime($consultation['preferred_at']) ?></div>
                <?php endif; ?>
                <?php if ($consultation['concern_details']): ?>
                <div class="mt-2 border-top pt-2"><?= nl2br(e(truncate($consultation['concern_details'], 200))) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Shared skin profile -->
        <?php if ($sharedProfile && $sharedProfile['profile']): $sp = $sharedProfile['profile']; ?>
        <div class="card shadow-sm mb-3">
            <div class="card-header small fw-semibold">Shared skin profile</div>
            <div class="card-body small text-muted">
                <div>Skin type: <strong><?= e(ucfirst($sp['skin_type'] ?? '-')) ?></strong></div>
                <div>Age: <?= e($sp['age'] ?? '-') ?></div>
                <?php if ($sharedProfile['concerns']): ?>
                <div>Concerns: <?= e(implode(', ', array_column($sharedProfile['concerns'], 'name'))) ?></div>
                <?php endif; ?>
                <?php if ($sp['allergies']): ?><div>Allergies: <?= e($sp['allergies']) ?></div><?php endif; ?>
                <?php if ($sp['preferences']): ?><div>Preferences: <?= e($sp['preferences']) ?></div><?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($consultation['advice_summary']): ?>
        <div class="card shadow-sm">
            <div class="card-header small fw-semibold">Advice summary</div>
            <div class="card-body small text-muted"><?= nl2br(e($consultation['advice_summary'])) ?></div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Complete Modal -->
<?php if ($consultation['status'] === 'in_progress'): ?>
<div class="modal fade" id="completeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="post" class="modal-content">
            <?= csrf_field() ?><input type="hidden" name="action" value="complete">
            <div class="modal-header"><h5 class="modal-title">Complete Consultation</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <label for="advice_summary" class="form-label">Expert advice summary <span class="text-danger">*</span></label>
                <textarea id="advice_summary" name="advice_summary" class="form-control" rows="6" required maxlength="5000"
                          placeholder="Write your diagnosis-free advice, product recommendations, and next steps for the customer…"><?= e($consultation['advice_summary'] ?? '') ?></textarea>
                <div class="form-text">This will be visible to the customer. Do not include medical diagnoses.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success">Complete &amp; send advice</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>document.body.dataset.userId = '<?= (int)$authUser['id'] ?>';</script>
<?php include _DIR_ . '/../admin/includes/footer.php'; ?>
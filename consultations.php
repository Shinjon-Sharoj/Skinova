<?php
require_once _DIR_ . '/includes/init.php';
require_once _DIR_ . '/includes/consultation.php';
require_once _DIR_ . '/includes/auth-check.php';

$pageTitle = 'Consultations';
$extraCss  = ['css/consultation.css'];

// Pre-fill booking from ?book=<consultant_user_id>&topic=<text>
$bookConsultantUserId = get_int('book');
$bookTopic            = get_str('topic');
$bookConsultant       = $bookConsultantUserId ? db_one(
    "SELECT c.*, u.full_name, up.avatar FROM consultants c JOIN users u ON u.id=c.user_id LEFT JOIN user_profiles up ON up.user_id=c.user_id WHERE c.user_id=? AND c.status='active'",
    [$bookConsultantUserId]
) : null;

// Available consultants
$consultants = get_available_consultants(12);

// My consultations
$perPage = 10;
$page    = get_int('page', 1);
$total   = (int) db_value('SELECT COUNT(*) FROM consultations WHERE user_id = ?', [$authUser['id']], 0);
$pg      = paginate($total, $perPage, $page);
$myCons  = db_all(
    "SELECT cn.*, u.full_name AS consultant_name, up.avatar AS consultant_avatar, c.specialization
     FROM consultations cn
     JOIN consultants c ON c.id = cn.consultant_id
     JOIN users u ON u.id = c.user_id
     LEFT JOIN user_profiles up ON up.user_id = c.user_id
     WHERE cn.user_id = ? ORDER BY cn.created_at DESC LIMIT ? OFFSET ?",
    [$authUser['id'], $pg['per_page'], $pg['offset']]
);

include _DIR_ . '/includes/header.php';
?>
<div class="container py-4">
    <div class="row">
        <div class="col-lg-3 mb-4"><?php include _DIR_ . '/includes/sidebar.php'; ?></div>
        <div class="col-lg-9">

            <!-- Book a consultation -->
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold">Book a Consultation</div>
                <div class="card-body">
                    <form method="post" action="<?= e(url('actions/consultations/book.php')) ?>" novalidate class="needs-validation">
                        <?= csrf_field() ?>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="consultant_id" class="form-label">Choose a consultant</label>
                                <select id="consultant_id" name="consultant_id" class="form-select<?= invalid_class('consultant_id') ?>" required>
                                    <option value="">Select a consultant…</option>
                                    <?php foreach ($consultants as $cons): ?>
                                    <option value="<?= $cons['id'] ?>"
                                            <?= ($bookConsultant && $bookConsultant['id'] == $cons['id']) ? 'selected' : '' ?>>
                                        <?= e($cons['full_name']) ?> — <?= e($cons['specialization']) ?>
                                        (<?= e(money($cons['consultation_fee'])) ?>)
                                        <?= $cons['rating_count'] > 0 ? '★ ' . number_format((float)$cons['rating_avg'],1) : '' ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <?= invalid_feedback('consultant_id') ?>
                            </div>
                            <div class="col-md-6">
                                <label for="preferred_at" class="form-label">Preferred date/time <span class="text-muted">(optional)</span></label>
                                <input type="datetime-local" id="preferred_at" name="preferred_at"
                                       class="form-control" min="<?= date('Y-m-d\TH:i') ?>">
                            </div>
                            <div class="col-12">
                                <label for="subject" class="form-label">Subject</label>
                                <input type="text" id="subject" name="subject" required maxlength="190"
                                       class="form-control<?= invalid_class('subject') ?>"
                                       value="<?= e(old('subject', $bookTopic)) ?>"
                                       placeholder="e.g. Acne treatment, dry skin routine…">
                                <?= invalid_feedback('subject') ?>
                            </div>
                            <div class="col-12">
                                <label for="concern_details" class="form-label">Tell us more <span class="text-muted">(optional)</span></label>
                                <textarea id="concern_details" name="concern_details" class="form-control" rows="3"
                                          maxlength="2000" placeholder="Describe your skin concerns in detail…"></textarea>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="share_skin_profile" name="share_skin_profile" value="1">
                                    <label class="form-check-label small" for="share_skin_profile">
                                        Share my skin profile with the consultant (recommended for better advice)
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">Request consultation</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Consultant cards -->
            <?php if ($consultants): ?>
            <h5 class="mb-3">Available Consultants</h5>
            <div class="row g-3 mb-4">
                <?php foreach ($consultants as $cons): ?>
                <div class="col-md-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-body d-flex gap-3">
                            <img src="<?= e(media_url($cons['avatar'] ?? null, 'avatar')) ?>"
                                 class="rounded-circle flex-shrink-0" width="56" height="56"
                                 style="object-fit:cover" alt="">
                            <div class="flex-grow-1">
                                <div class="fw-semibold"><?= e(($cons['title'] ? $cons['title'] . ' ' : '') . $cons['full_name']) ?></div>
                                <div class="text-muted small"><?= e($cons['specialization']) ?></div>
                                <?php if ($cons['rating_count'] > 0): ?>
                                <div class="small"><?= render_stars((float)$cons['rating_avg']) ?> <span class="text-muted">(<?= $cons['rating_count'] ?>)</span></div>
                                <?php endif; ?>
                                <div class="small text-primary fw-semibold mt-1"><?= e(money($cons['consultation_fee'])) ?> / session</div>
                                <?php if ($cons['languages']): ?>
                                <div class="text-muted small"><i class="bi bi-translate"></i> <?= e($cons['languages']) ?></div>
                                <?php endif; ?>
                                <?php if ($cons['availability_note']): ?>
                                <div class="text-muted small"><i class="bi bi-clock"></i> <?= e($cons['availability_note']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent">
                            <a href="?book=<?= $cons['user_id'] ?>" class="btn btn-outline-primary btn-sm w-100">Book with <?= e(explode(' ', $cons['full_name'])[0]) ?></a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- My consultations -->
            <h5 class="mb-3">My Consultations</h5>
            <?php if ($myCons): ?>
            <div class="list-group shadow-sm">
                <?php foreach ($myCons as $cn): ?>
                <a href="<?= e(url('consultation-details.php?id=' . $cn['id'])) ?>"
                   class="list-group-item list-group-item-action py-3">
                    <div class="d-flex justify-content-between flex-wrap gap-2">
                        <div class="d-flex gap-3 align-items-start">
                            <img src="<?= e(media_url($cn['consultant_avatar'] ?? null, 'avatar')) ?>"
                                 class="rounded-circle flex-shrink-0" width="40" height="40"
                                 style="object-fit:cover" alt="">
                            <div>
                                <div class="fw-semibold small"><?= e($cn['subject']) ?></div>
                                <div class="text-muted small">with <?= e($cn['consultant_name']) ?> · <?= time_ago($cn['created_at']) ?></div>
                            </div>
                        </div>
                        <div class="text-end">
                            <?= status_badge($cn['status']) ?>
                            <div class="small text-muted mt-1"><?= status_badge($cn['payment_status']) ?></div>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <div class="mt-3"><?= render_pagination($pg) ?></div>
            <?php else: ?>
            <div class="text-center py-4 text-muted">
                <i class="bi bi-person-heart" style="font-size:2.5rem"></i>
                <p class="mt-2 small">No consultations yet. Book your first one above!</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include _DIR_ . '/includes/footer.php'; ?>
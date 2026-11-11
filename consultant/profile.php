<?php
require_once _DIR_ . '/../includes/init.php';
require_once _DIR_ . '/../includes/consultation.php';
$authUser = require_role(ROLE_CONSULTANT);

$pageTitle = 'My Profile';
$panel     = 'consultant';

$consultant = get_consultant_by_user_id((int) $authUser['id']);
if (!$consultant) redirect('consultant/dashboard.php');
$userProfile = db_one('SELECT * FROM user_profiles WHERE user_id = ?', [$authUser['id']]);

// Handle save
if (is_post() && csrf_is_valid()) {
    $v = new Validator($_POST);
    $v->required('specialization', 'Specialization');
    if ($v->errors()) { set_old($_POST, $v->errors()); redirect('consultant/profile.php'); }

    // Avatar
    $avatarPath = null;
    if (!empty($_FILES['avatar']['size'])) {
        $res = save_image_upload($_FILES['avatar'], 'profiles');
        if (!isset($res['error'])) {
            if ($consultant['avatar']) delete_upload($consultant['avatar']);
            $avatarPath = $res['path'];
        }
    }

    db_exec('UPDATE users SET full_name=?, phone=? WHERE id=?', [clean_text(input('full_name')), input('phone') ?: null, $authUser['id']]);
    db_exec('INSERT INTO user_profiles (user_id,bio,avatar) VALUES(?,?,?) ON DUPLICATE KEY UPDATE bio=VALUES(bio), avatar=COALESCE(IF(VALUES(avatar) IS NOT NULL,VALUES(avatar),NULL),avatar)',
        [$authUser['id'], clean_text(input('bio')), $avatarPath]);

    db_exec('UPDATE consultants SET title=?,specialization=?,bio=?,qualifications=?,experience_years=?,languages=?,consultation_fee=?,availability_note=?,is_available=? WHERE id=?',
        [input('title') ?: null, clean_text(input('specialization')), clean_text(input('consultant_bio')),
         input('qualifications') ? clean_text(input('qualifications')) : null,
         input_int('experience_years'), input('languages') ?: 'English',
         input_float('consultation_fee'), input('availability_note') ? clean_text(input('availability_note')) : null,
         isset($_POST['is_available']) ? 1 : 0, $consultant['id']]);

    log_activity('consultant_profile_updated', 'consultant', (int)$consultant['id'], null, (int)$authUser['id']);
    flash('success', 'Profile updated.');
    redirect('consultant/profile.php');
}

include _DIR_ . '/../admin/includes/header.php';
admin_page_header('My Consultant Profile');
?>
<div class="row g-4">
    <div class="col-lg-8">
        <form method="post" enctype="multipart/form-data" novalidate class="needs-validation">
            <?= csrf_field() ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header">Personal information</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full name</label>
                            <input name="full_name" class="form-control<?= invalid_class('full_name') ?>" value="<?= e(old('full_name', $authUser['full_name'])) ?>" required maxlength="120">
                            <?= invalid_feedback('full_name') ?>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Title</label>
                            <input name="title" class="form-control" value="<?= e(old('title', $consultant['title'] ?? '')) ?>" placeholder="Dr., Esthetician…" maxlength="80">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Phone</label>
                            <input name="phone" class="form-control" value="<?= e(old('phone', $authUser['phone'] ?? '')) ?>" maxlength="30">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Profile photo</label>
                            <input type="file" name="avatar" class="form-control" accept="image/*" data-preview="avatarPreview">
                            <img id="avatarPreview" src="<?= e(media_url($consultant['avatar'] ?? null, 'avatar')) ?>" class="rounded-circle mt-2" width="64" height="64" style="object-fit:cover" alt="">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Short bio (shown to customers)</label>
                            <textarea name="bio" class="form-control" rows="2" maxlength="500"><?= e(old('bio', $userProfile['bio'] ?? '')) ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card shadow-sm mb-4">
                <div class="card-header">Professional information</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Specialization <span class="text-danger">*</span></label>
                            <input name="specialization" class="form-control<?= invalid_class('specialization') ?>" required maxlength="190" value="<?= e(old('specialization', $consultant['specialization'])) ?>">
                            <?= invalid_feedback('specialization') ?>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Experience (years)</label>
                            <input type="number" name="experience_years" class="form-control" min="0" max="60" value="<?= e(old('experience_years', $consultant['experience_years'])) ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Full professional bio</label>
                            <textarea name="consultant_bio" class="form-control" rows="3" maxlength="3000"><?= e(old('consultant_bio', $consultant['bio'] ?? '')) ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Qualifications</label>
                            <input name="qualifications" class="form-control" maxlength="400" value="<?= e(old('qualifications', $consultant['qualifications'] ?? '')) ?>" placeholder="e.g. BSc Cosmetology, Cert. Dermatology">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Languages</label>
                            <input name="languages" class="form-control" maxlength="190" value="<?= e(old('languages', $consultant['languages'] ?? 'English')) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Consultation fee (<?= e(CURRENCY_SYMBOL) ?>)</label>
                            <input type="number" name="consultation_fee" class="form-control" min="0" step="0.01" value="<?= e(old('consultation_fee', $consultant['consultation_fee'])) ?>">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Availability note</label>
                            <input name="availability_note" class="form-control" maxlength="255" value="<?= e(old('availability_note', $consultant['availability_note'] ?? '')) ?>" placeholder="e.g. Mon–Fri 9am–6pm UTC">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_available" name="is_available" value="1" <?= old('is_available', $consultant['is_available'] ? '1' : '0') === '1' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_available">Accepting new consultations</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Save profile</button>
        </form>
    </div>
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-body text-center">
                <img src="<?= e(media_url($consultant['avatar'] ?? null, 'avatar')) ?>" class="rounded-circle mb-2" width="80" height="80" style="object-fit:cover" alt="">
                <div class="fw-semibold"><?= e(($consultant['title']?' '.$consultant['title']:'') . ' ' . $authUser['full_name']) ?></div>
                <div class="text-muted small"><?= e($consultant['specialization']) ?></div>
                <?php if ($consultant['rating_count'] > 0): ?>
                <div class="mt-1"><?= render_stars((float)$consultant['rating_avg'], true) ?> <span class="text-muted small">(<?= $consultant['rating_count'] ?> reviews)</span></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php include _DIR_ . '/../admin/includes/footer.php'; ?>
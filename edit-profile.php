<?php
require_once _DIR_ . '/includes/init.php';
require_once _DIR_ . '/includes/auth-check.php';

$pageTitle = 'Edit Profile';
$profile   = db_one('SELECT * FROM user_profiles WHERE user_id = ?', [$authUser['id']]);

include _DIR_ . '/includes/header.php';
?>
<div class="container py-4">
    <div class="row">
        <div class="col-lg-3 mb-4"><?php include _DIR_ . '/includes/sidebar.php'; ?></div>
        <div class="col-lg-9">
            <h2 class="h4 mb-4">Edit Profile</h2>

            <!-- Profile info -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">Personal information</div>
                <div class="card-body">
                    <form method="post" action="<?= e(url('actions/profile/update.php')) ?>" enctype="multipart/form-data" novalidate class="needs-validation">
                        <?= csrf_field() ?>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="full_name" class="form-label">Full name</label>
                                <input type="text" id="full_name" name="full_name"
                                       class="form-control<?= invalid_class('full_name') ?>"
                                       value="<?= e(old('full_name', $authUser['full_name'])) ?>"
                                       required maxlength="120">
                                <?= invalid_feedback('full_name') ?>
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" id="phone" name="phone"
                                       class="form-control<?= invalid_class('phone') ?>"
                                       value="<?= e(old('phone', $authUser['phone'] ?? '')) ?>"
                                       maxlength="30">
                                <?= invalid_feedback('phone') ?>
                            </div>
                            <div class="col-md-4">
                                <label for="date_of_birth" class="form-label">Date of birth</label>
                                <input type="date" id="date_of_birth" name="date_of_birth"
                                       class="form-control<?= invalid_class('date_of_birth') ?>"
                                       value="<?= e(old('date_of_birth', $profile['date_of_birth'] ?? '')) ?>">
                                <?= invalid_feedback('date_of_birth') ?>
                            </div>
                            <div class="col-md-4">
                                <label for="gender" class="form-label">Gender</label>
                                <select id="gender" name="gender" class="form-select">
                                    <option value="">Prefer not to say</option>
                                    <?php foreach (['female' => 'Female', 'male' => 'Male', 'other' => 'Other', 'prefer_not_to_say' => 'Prefer not to say'] as $val => $label): ?>
                                    <option value="<?= e($val) ?>" <?= old('gender', $profile['gender'] ?? '') === $val ? 'selected' : '' ?>><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="avatar" class="form-label">Profile photo</label>
                                <input type="file" id="avatar" name="avatar" class="form-control" accept="image/*" data-preview="avatarPreview">
                                <img id="avatarPreview" src="<?= e(media_url($profile['avatar'] ?? null, 'avatar')) ?>" class="rounded-circle mt-2" width="64" height="64" style="object-fit:cover" alt="">
                            </div>
                            <div class="col-12">
                                <label for="bio" class="form-label">Bio <span class="text-muted">(optional)</span></label>
                                <textarea id="bio" name="bio" class="form-control" rows="3" maxlength="500"><?= e(old('bio', $profile['bio'] ?? '')) ?></textarea>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="marketing_opt_in" name="marketing_opt_in" value="1"
                                           <?= old('marketing_opt_in', (string)($profile['marketing_opt_in'] ?? '0')) === '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="marketing_opt_in">I'd like to receive skincare tips and offers by email</label>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">Save changes</button>
                            <a href="<?= e(url('profile.php')) ?>" class="btn btn-outline-secondary ms-2">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Change password -->
            <div class="card shadow-sm">
                <div class="card-header">Change password</div>
                <div class="card-body">
                    <form method="post" action="<?= e(url('actions/profile/change-password.php')) ?>" novalidate class="needs-validation">
                        <?= csrf_field() ?>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="current_password" class="form-label">Current password</label>
                                <input type="password" id="current_password" name="current_password"
                                       class="form-control<?= invalid_class('current_password') ?>"
                                       autocomplete="current-password" required>
                                <?= invalid_feedback('current_password') ?>
                            </div>
                            <div class="col-md-4">
                                <label for="new_password" class="form-label">New password</label>
                                <input type="password" id="new_password" name="new_password"
                                       class="form-control<?= invalid_class('new_password') ?>"
                                       autocomplete="new-password" required minlength="<?= PASSWORD_MIN_LENGTH ?>">
                                <?= invalid_feedback('new_password') ?>
                            </div>
                            <div class="col-md-4">
                                <label for="password_confirm" class="form-label">Confirm new password</label>
                                <input type="password" id="password_confirm" name="password_confirm"
                                       class="form-control<?= invalid_class('password_confirm') ?>"
                                       autocomplete="new-password" required>
                                <?= invalid_feedback('password_confirm') ?>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-warning">Update password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include _DIR_ . '/includes/footer.php'; ?>
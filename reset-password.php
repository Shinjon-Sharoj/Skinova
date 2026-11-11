<?php
require_once __DIR__ . '/includes/init.php';

if (is_logged_in()) {
    redirect('index.php');
}

$token = get_str('token');
if ($token === '') {
    flash('danger', 'Invalid or missing reset link.');
    redirect('forgot-password.php');
}

// Validate token exists (consume happens in action)
$tokenHash = hash('sha256', $token);
$tokenRow  = db_one(
    "SELECT vt.id, vt.user_id FROM verification_tokens vt WHERE vt.token_hash = ? AND vt.type = 'password_reset' AND vt.used_at IS NULL AND vt.expires_at > NOW() LIMIT 1",
    [$tokenHash]
);
if (!$tokenRow) {
    flash('danger', 'This reset link is invalid or has expired. Please request a new one.');
    redirect('forgot-password.php');
}

$pageTitle   = 'Reset Password';
$extraCss    = ['css/auth.css'];
$hideFloatingChat = true;

include __DIR__ . '/includes/header.php';
?>
<div class="auth-wrapper d-flex align-items-center justify-content-center py-5">
    <div class="auth-card card shadow-sm w-100" style="max-width:440px">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <i class="bi bi-key text-primary" style="font-size:3rem"></i>
                <h1 class="h4 mt-2 mb-0">Choose a new password</h1>
                <p class="text-muted small">Make it strong and memorable</p>
            </div>

            <form method="post" action="<?= e(url('actions/auth/reset-password.php')) ?>" novalidate class="needs-validation">
                <?= csrf_field() ?>
                <input type="hidden" name="token" value="<?= e($token) ?>">

                <div class="mb-3">
                    <label for="password" class="form-label">New password</label>
                    <div class="input-group">
                        <input type="password" id="password" name="password"
                               class="form-control<?= invalid_class('password') ?>"
                               autocomplete="new-password" required
                               minlength="<?= PASSWORD_MIN_LENGTH ?>">
                        <button class="btn btn-outline-secondary" type="button" data-password-toggle="password" aria-label="Show password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <div class="form-text">Minimum <?= PASSWORD_MIN_LENGTH ?> characters.</div>
                    <?= invalid_feedback('password') ?>
                </div>

                <div class="mb-3">
                    <label for="password_confirm" class="form-label">Confirm new password</label>
                    <input type="password" id="password_confirm" name="password_confirm"
                           class="form-control<?= invalid_class('password_confirm') ?>"
                           autocomplete="new-password" required>
                    <?= invalid_feedback('password_confirm') ?>
                </div>

                <button class="btn btn-primary w-100" type="submit">Set new password</button>
            </form>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

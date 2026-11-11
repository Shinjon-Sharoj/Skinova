<?php
require_once __DIR__ . '/includes/init.php';

$pageTitle   = 'Verify Your Account';
$extraCss    = ['css/auth.css'];
$hideFloatingChat = true;

// Handle email-link verification (token in URL)
$tokenParam = get_str('token');
$typeParam  = get_str('type', 'email');
if ($tokenParam !== '' && in_array($typeParam, ['email', 'password_reset'], true)) {
    // Handled by action
}

$user = is_logged_in() ? current_user() : null;

include __DIR__ . '/includes/header.php';
?>
<div class="auth-wrapper d-flex align-items-center justify-content-center py-5">
    <div class="auth-card card shadow-sm w-100" style="max-width:480px">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <i class="bi bi-shield-check text-primary" style="font-size:3rem"></i>
                <h1 class="h4 mt-2 mb-0">Verify your account</h1>
                <p class="text-muted small">Check your email or enter the code we sent you</p>
            </div>

            <?php if ($user && $user['status'] === 'active'): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle"></i> Your account is already verified.
                    <a href="<?= e(url('index.php')) ?>" class="alert-link ms-1">Continue shopping</a>
                </div>
            <?php else: ?>

                <?php if ($user): ?>
                <p class="text-muted text-center small">
                    We sent a verification email to <strong><?= e($user['email']) ?></strong>.
                    Click the link in the email, or enter your 6-digit phone code below.
                </p>
                <?php endif; ?>

                <!-- Email link: handled server-side via actions/auth/verify-email.php -->
                <!-- Phone OTP form -->
                <?php if ($user && $user['phone']): ?>
                <form method="post" action="<?= e(url('actions/auth/verify-otp.php')) ?>" novalidate class="needs-validation mb-4">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label for="otp" class="form-label">Phone verification code</label>
                        <input type="text" id="otp" name="otp" inputmode="numeric" pattern="[0-9]{6}"
                               class="form-control text-center fs-4 letter-spacing-3<?= invalid_class('otp') ?>"
                               maxlength="6" required autocomplete="one-time-code" placeholder="000000">
                        <?= invalid_feedback('otp') ?>
                    </div>
                    <button class="btn btn-primary w-100" type="submit">Verify phone</button>
                </form>
                <?php endif; ?>

                <div class="text-center">
                    <?php if ($user): ?>
                    <form method="post" action="<?= e(url('actions/auth/resend-verification.php')) ?>" class="d-inline">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-link btn-sm p-0">Resend verification email</button>
                    </form>
                    <?php else: ?>
                    <a href="<?= e(url('login.php')) ?>">Back to sign in</a>
                    <?php endif; ?>
                </div>

                <?php if (SHOW_DEV_VERIFICATION && $user): ?>
                <div class="alert alert-info mt-3 small">
                    <strong>Dev mode:</strong> Check <code>storage/logs/mail.log</code> for the verification link/OTP.
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

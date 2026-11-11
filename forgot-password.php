<?php
require_once __DIR__ . '/includes/init.php';

if (is_logged_in()) {
    redirect('index.php');
}

$pageTitle   = 'Forgot Password';
$extraCss    = ['css/auth.css'];
$hideFloatingChat = true;

include __DIR__ . '/includes/header.php';
?>
<div class="auth-wrapper d-flex align-items-center justify-content-center py-5">
    <div class="auth-card card shadow-sm w-100" style="max-width:440px">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <i class="bi bi-lock text-primary" style="font-size:3rem"></i>
                <h1 class="h4 mt-2 mb-0">Reset your password</h1>
                <p class="text-muted small">We'll send a reset link to your email</p>
            </div>

            <form method="post" action="<?= e(url('actions/auth/forgot-password.php')) ?>" novalidate class="needs-validation">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label for="email" class="form-label">Email address</label>
                    <input type="email" id="email" name="email"
                           class="form-control<?= invalid_class('email') ?>"
                           value="<?= e(old('email')) ?>"
                           autocomplete="email" required autofocus>
                    <?= invalid_feedback('email') ?>
                </div>

                <button class="btn btn-primary w-100" type="submit">Send reset link</button>
            </form>

            <div class="text-center mt-4">
                <a href="<?= e(url('login.php')) ?>"><i class="bi bi-arrow-left"></i> Back to sign in</a>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

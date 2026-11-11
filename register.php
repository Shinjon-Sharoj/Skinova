<?php
require_once __DIR__ . '/includes/init.php';

if (is_logged_in()) {
    redirect(home_url_for_role(current_user()['role']));
}

$pageTitle   = 'Create Account';
$extraCss    = ['css/auth.css'];
$hideFloatingChat = true;

include __DIR__ . '/includes/header.php';
?>
<div class="auth-wrapper d-flex align-items-center justify-content-center py-5">
    <div class="auth-card card shadow-sm w-100" style="max-width:520px">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <a href="<?= e(url('index.php')) ?>">
                    <img src="<?= e(url('assets/images/placeholders/favicon.svg')) ?>" alt="<?= e(APP_NAME) ?>" width="56" height="56">
                </a>
                <h1 class="h4 mt-2 mb-0">Create your account</h1>
                <p class="text-muted small">Join <?= e(APP_NAME) ?> for personalised skincare</p>
            </div>

            <form method="post" action="<?= e(url('actions/auth/register.php')) ?>" novalidate class="needs-validation">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label for="full_name" class="form-label">Full name</label>
                    <input type="text" id="full_name" name="full_name"
                           class="form-control<?= invalid_class('full_name') ?>"
                           value="<?= e(old('full_name')) ?>"
                           autocomplete="name" required maxlength="120">
                    <?= invalid_feedback('full_name') ?>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email"
                           class="form-control<?= invalid_class('email') ?>"
                           value="<?= e(old('email')) ?>"
                           autocomplete="email" required maxlength="190">
                    <?= invalid_feedback('email') ?>
                </div>

                <div class="mb-3">
                    <label for="phone" class="form-label">Phone <span class="text-muted">(optional)</span></label>
                    <input type="tel" id="phone" name="phone"
                           class="form-control<?= invalid_class('phone') ?>"
                           value="<?= e(old('phone')) ?>"
                           autocomplete="tel" maxlength="30"
                           placeholder="+1 555 000 0000">
                    <?= invalid_feedback('phone') ?>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
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
                    <label for="password_confirm" class="form-label">Confirm password</label>
                    <input type="password" id="password_confirm" name="password_confirm"
                           class="form-control<?= invalid_class('password_confirm') ?>"
                           autocomplete="new-password" required>
                    <?= invalid_feedback('password_confirm') ?>
                </div>

                <div class="mb-3 form-check">
                    <input class="form-check-input<?= invalid_class('terms') ?>" type="checkbox" id="terms" name="terms" value="1" required>
                    <label class="form-check-label" for="terms">
                        I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>
                    </label>
                    <?= invalid_feedback('terms') ?>
                </div>

                <button class="btn btn-primary w-100" type="submit">Create account</button>
            </form>

            <hr class="my-4">
            <p class="text-center mb-0">Already have an account?
                <a href="<?= e(url('login.php')) ?>">Sign in</a>
            </p>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

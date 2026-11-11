<?php
require_once __DIR__ . '/includes/init.php';

// Already logged in? Go home.
if (is_logged_in()) {
    redirect(home_url_for_role(current_user()['role']));
}

$pageTitle   = 'Sign In';
$extraCss    = ['css/auth.css'];
$hideFloatingChat = true;
$next        = safe_next_path(get_str('next'), 'index.php');

include __DIR__ . '/includes/header.php';
?>
<div class="auth-wrapper d-flex align-items-center justify-content-center py-5">
    <div class="auth-card card shadow-sm w-100" style="max-width:440px">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <a href="<?= e(url('index.php')) ?>">
                    <img src="<?= e(url('assets/images/placeholders/favicon.svg')) ?>" alt="<?= e(APP_NAME) ?>" width="56" height="56">
                </a>
                <h1 class="h4 mt-2 mb-0">Welcome back</h1>
                <p class="text-muted small">Sign in to your <?= e(APP_NAME) ?> account</p>
            </div>

            <form method="post" action="<?= e(url('actions/auth/login.php')) ?>" novalidate id="loginForm" class="needs-validation">
                <?= csrf_field() ?>
                <input type="hidden" name="next" value="<?= e($next) ?>">

                <div class="mb-3">
                    <label for="login" class="form-label">Email or phone</label>
                    <input type="text" id="login" name="login"
                           class="form-control<?= invalid_class('login') ?>"
                           value="<?= e(old('login')) ?>"
                           autocomplete="username"
                           required autofocus>
                    <?= invalid_feedback('login') ?>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label d-flex justify-content-between">
                        Password
                        <a href="<?= e(url('forgot-password.php')) ?>" class="small">Forgot password?</a>
                    </label>
                    <div class="input-group">
                        <input type="password" id="password" name="password"
                               class="form-control<?= invalid_class('password') ?>"
                               autocomplete="current-password"
                               required>
                        <button class="btn btn-outline-secondary" type="button" data-password-toggle="password" aria-label="Show password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <?= invalid_feedback('password') ?>
                </div>

                <div class="mb-3 form-check">
                    <input class="form-check-input" type="checkbox" id="remember" name="remember" value="1" <?= old('remember') ? 'checked' : '' ?>>
                    <label class="form-check-label" for="remember">Keep me signed in</label>
                </div>

                <button class="btn btn-primary w-100" type="submit">Sign in</button>
            </form>

            <hr class="my-4">
            <p class="text-center mb-0">Don't have an account?
                <a href="<?= e(url('register.php')) ?>">Create one</a>
            </p>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

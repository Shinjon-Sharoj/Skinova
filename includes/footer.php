<?php
/**
 * Closes <main>, prints the footer and scripts.
 * Optional before including: $extraJs = ['js/products.js'];  $hideFooter = true;  $hideFloatingChat = true;
 */
$extraJs = $extraJs ?? [];
$__loggedIn = is_logged_in();
?>
</main>
<?php if (empty($hideFooter)): ?>
<footer class="site-footer bg-dark text-light mt-5 pt-5 pb-3">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <h5 class="text-white d-flex align-items-center gap-2"><img src="<?= e(url('assets/images/placeholders/favicon.svg')) ?>" alt="" width="28" height="28"> <?= e(APP_NAME) ?></h5>
                <p class="small text-white-50">Skincare shopping, personalised guidance and one-to-one chat with real skincare professionals - all in one place.</p>
                <p class="small mb-1"><i class="bi bi-envelope"></i> <?= e(SUPPORT_EMAIL) ?></p>
                <p class="small"><i class="bi bi-telephone"></i> <?= e(SUPPORT_PHONE) ?></p>
            </div>
            <div class="col-6 col-lg-2">
                <h6 class="text-white">Shop</h6>
                <ul class="list-unstyled small">
                    <li><a href="<?= e(url('products.php')) ?>">All products</a></li>
                    <li><a href="<?= e(url('categories.php')) ?>">Categories</a></li>
                    <li><a href="<?= e(url('products.php?sort=newest')) ?>">New arrivals</a></li>
                    <li><a href="<?= e(url('products.php?sort=popular')) ?>">Best sellers</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-2">
                <h6 class="text-white">Skin care</h6>
                <ul class="list-unstyled small">
                    <li><a href="<?= e(url('recommendations.php')) ?>">Recommendations</a></li>
                    <li><a href="<?= e(url('skincare-routine.php')) ?>">Routine generator</a></li>
                    <li><a href="<?= e(url('consultations.php')) ?>">Expert consultation</a></li>
                    <li><a href="<?= e(url('blog.php')) ?>">Blog</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-2">
                <h6 class="text-white">Help</h6>
                <ul class="list-unstyled small">
                    <li><a href="<?= e(url('faq.php')) ?>">FAQ</a></li>
                    <li><a href="<?= e(url('track-order.php')) ?>">Track order</a></li>
                    <li><a href="<?= e(url('returns.php')) ?>">Returns</a></li>
                    <li><a href="<?= e(url('contact.php')) ?>">Contact us</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-2">
                <h6 class="text-white">Company</h6>
                <ul class="list-unstyled small">
                    <li><a href="<?= e(url('about.php')) ?>">About us</a></li>
                    <li><a href="<?= e(url('login.php')) ?>">Account</a></li>
                </ul>
            </div>
        </div>
        <hr class="border-secondary">
        <p class="small text-white-50 mb-1"><i class="bi bi-info-circle"></i> <?= e(AI_DISCLAIMER) ?></p>
        <p class="small text-white-50 mb-0">&copy; <?= date('Y') ?> <?= e(APP_NAME) ?>. All rights reserved.</p>
    </div>
</footer>
<?php endif; ?>

<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer" aria-live="polite" aria-atomic="true"></div>

<?php if (empty($hideFloatingChat)): ?>
<a href="<?= e(url('consultations.php')) ?>" class="floating-chat btn btn-primary rounded-pill shadow d-flex align-items-center gap-2" aria-label="Chat with a skincare expert">
    <i class="bi bi-chat-heart-fill"></i><span class="d-none d-md-inline">Ask an expert</span>
</a>
<?php endif; ?>

<script>
    window.SKINOVA = {
        baseUrl: <?= json_encode(app_base_path()) ?>,
        csrf: <?= json_encode(csrf_token()) ?>,
        loggedIn: <?= $__loggedIn ? 'true' : 'false' ?>,
        currency: <?= json_encode(CURRENCY_SYMBOL) ?>,
        chatPollMs: <?= (int) CHAT_POLL_INTERVAL_MS ?>
    };
</script>
<script src="<?= e(url('assets/vendor/bootstrap/bootstrap.bundle.min.js')) ?>"></script>
<script src="<?= e(asset('js/main.js')) ?>"></script>
<?php foreach ($extraJs as $js): ?>
    <script src="<?= e(asset($js)) ?>"></script>
<?php endforeach; ?>
</body>
</html>
<?php clear_old(); ?>

<?php
/** Closes the dashboard layout. Optional: $extraJs = ['js/foo.js'] */
$extraJs = $extraJs ?? [];
?>
        </div><!-- /.admin-content -->
    </main>
</div><!-- /.admin-shell -->

<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer" aria-live="polite" aria-atomic="true"></div>
<script>
    window.SKINOVA = {
        baseUrl: <?= json_encode(app_base_path()) ?>,
        csrf: <?= json_encode(csrf_token()) ?>,
        loggedIn: true,
        currency: <?= json_encode(CURRENCY_SYMBOL) ?>,
        chatPollMs: <?= (int) CHAT_POLL_INTERVAL_MS ?>
    };
</script>
<script src="<?= e(url('assets/vendor/bootstrap/bootstrap.bundle.min.js')) ?>"></script>
<script src="<?= e(asset('js/main.js')) ?>"></script>
<script src="<?= e(asset('js/admin.js')) ?>"></script>
<?php foreach ($extraJs as $js): ?>
    <script src="<?= e(asset($js)) ?>"></script>
<?php endforeach; ?>
</body>
</html>
<?php clear_old(); ?>

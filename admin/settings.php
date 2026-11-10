<?php
require_once __DIR__ . '/../admin/includes/auth-check.php';
$pageTitle = 'Settings';
$panel     = 'admin';

if (is_post() && csrf_is_valid()) {
    $keys = ['shipping_flat_fee','free_shipping_threshold','loyalty_earn_rate','loyalty_point_value',
             'loyalty_max_redeem_percent','return_window_days','consultation_default_fee','low_stock_alert_threshold'];
    foreach ($keys as $key) {
        if (isset($_POST[$key])) {
            set_setting($key, trim((string)$_POST[$key]));
        }
    }
    log_activity('admin_settings_saved','settings',null,null,(int)$authUser['id']);
    flash('success','Settings saved.');
    redirect('admin/settings.php');
}

include __DIR__ . '/../admin/includes/header.php';
admin_page_header('Site Settings');
?>
<form method="post" novalidate>
    <?= csrf_field() ?>
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold">Shipping</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Flat shipping fee (<?= e(CURRENCY_SYMBOL) ?>)</label>
                        <input type="number" name="shipping_flat_fee" class="form-control" step="0.01" min="0" value="<?= e(get_setting('shipping_flat_fee','5.99')) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Free shipping threshold (<?= e(CURRENCY_SYMBOL) ?>)</label>
                        <input type="number" name="free_shipping_threshold" class="form-control" step="0.01" min="0" value="<?= e(get_setting('free_shipping_threshold','60')) ?>">
                        <div class="form-text">Orders above this amount get free shipping.</div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold">Returns</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Return window (days)</label>
                        <input type="number" name="return_window_days" class="form-control" min="1" max="365" value="<?= e(get_setting('return_window_days','30')) ?>">
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold">Inventory</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Low stock alert threshold</label>
                        <input type="number" name="low_stock_alert_threshold" class="form-control" min="0" value="<?= e(get_setting('low_stock_alert_threshold','5')) ?>">
                        <div class="form-text">Show warning badge when stock falls to or below this number.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold">Loyalty Points</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Points earned per <?= e(CURRENCY_SYMBOL) ?> spent</label>
                        <input type="number" name="loyalty_earn_rate" class="form-control" step="0.01" min="0" value="<?= e(get_setting('loyalty_earn_rate','1')) ?>">
                        <div class="form-text">e.g. 1 = earn 1 point per <?= e(CURRENCY_SYMBOL) ?> spent.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Point value (<?= e(CURRENCY_SYMBOL) ?> per point)</label>
                        <input type="number" name="loyalty_point_value" class="form-control" step="0.001" min="0" value="<?= e(get_setting('loyalty_point_value','0.01')) ?>">
                        <div class="form-text">e.g. 0.01 = 100 points = <?= e(CURRENCY_SYMBOL) ?>1.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Max redemption % of order total</label>
                        <input type="number" name="loyalty_max_redeem_percent" class="form-control" step="1" min="0" max="100" value="<?= e(get_setting('loyalty_max_redeem_percent','50')) ?>">
                        <div class="form-text">Max % of order value that can be paid with points.</div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold">Consultations</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Default consultation fee (<?= e(CURRENCY_SYMBOL) ?>)</label>
                        <input type="number" name="consultation_default_fee" class="form-control" step="0.01" min="0" value="<?= e(get_setting('consultation_default_fee','0')) ?>">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <button type="submit" class="btn btn-primary px-4">Save settings</button>
</form>
<?php include __DIR__ . '/../admin/includes/footer.php'; ?>

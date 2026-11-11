<?php
/**
 * Account sidebar for logged-in customers (My account area).
 * Usage inside a page:
 *   <div class="row"><div class="col-lg-3 mb-4"><?php include _DIR_ . '/includes/sidebar.php'; ?></div><div class="col-lg-9"> ... </div></div>
 */
$__current = basename($_SERVER['SCRIPT_NAME'] ?? '');
$__user = current_user();
$__items = [
    ['profile.php', 'bi-person', 'My profile', ['profile.php', 'edit-profile.php']],
    ['orders.php', 'bi-box-seam', 'Orders', ['orders.php', 'order-details.php', 'track-order.php']],
    ['returns.php', 'bi-arrow-return-left', 'Returns & refunds', ['returns.php']],
    ['wishlist.php', 'bi-heart', 'Wishlist', ['wishlist.php']],
    ['addresses.php', 'bi-geo-alt', 'Addresses', ['addresses.php']],
    ['skincare-profile.php', 'bi-droplet-half', 'Skin profile', ['skincare-profile.php']],
    ['skincare-routine.php', 'bi-list-check', 'My routines', ['skincare-routine.php']],
    ['consultations.php', 'bi-person-heart', 'Consultations', ['consultations.php', 'consultation-details.php']],
    ['messages.php', 'bi-chat-dots', 'Messages', ['messages.php']],
    ['notifications.php', 'bi-bell', 'Notifications', ['notifications.php']],
];
?>
<div class="card account-sidebar shadow-sm">
    <?php if ($__user): ?>
    <div class="card-body text-center border-bottom">
        <img src="<?= e(media_url($__user['avatar'] ?? null, 'avatar')) ?>" alt="" class="rounded-circle mb-2" width="72" height="72" style="object-fit:cover">
        <div class="fw-semibold"><?= e($__user['full_name']) ?></div>
        <small class="text-muted d-block"><?= e($__user['email']) ?></small>
        <span class="badge text-bg-light border mt-2"><i class="bi bi-gem text-primary"></i> <?= number_format((int) $__user['loyalty_points']) ?> points</span>
    </div>
    <?php endif; ?>
    <div class="list-group list-group-flush">
        <?php foreach ($__items as [$__file, $__icon, $__label, $__actives]): ?>
            <a class="list-group-item list-group-item-action d-flex align-items-center gap-2<?= in_array($__current, $__actives, true) ? ' active' : '' ?>" href="<?= e(url($__file)) ?>">
                <i class="bi <?= e($__icon) ?>"></i> <?= e($__label) ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>
<?php unset($__current, $__user, $__items, $__file, $__icon, $__label, $__actives); ?>
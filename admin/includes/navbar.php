<?php
/** Top bar of the dashboard layout (admin + consultant). */
$__u = current_user();
$__unread = $__u ? unread_notification_count((int) $__u['id']) : 0;
?>
<nav class="navbar admin-topbar navbar-dark bg-dark fixed-top px-3" aria-label="Dashboard top bar">
    <div class="d-flex align-items-center gap-2">
        <button class="btn btn-outline-light btn-sm d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminSidebar" aria-controls="adminSidebar" aria-label="Open menu">
            <i class="bi bi-list"></i>
        </button>
        <a class="navbar-brand mb-0 d-flex align-items-center gap-2" href="<?= e(url($panel === 'admin' ? 'admin/dashboard.php' : 'consultant/dashboard.php')) ?>">
            <img src="<?= e(url('assets/images/placeholders/favicon.svg')) ?>" alt="" width="26" height="26">
            <?= e(APP_NAME) ?> <small class="text-white-50"><?= $panel === 'admin' ? 'Admin' : 'Consultant' ?></small>
        </a>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a class="btn btn-outline-light btn-sm" href="<?= e(url('index.php')) ?>" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right"></i> <span class="d-none d-sm-inline">View site</span></a>
        <?php if ($__u): ?>
        <div class="dropdown">
            <a class="btn btn-outline-light btn-sm dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-person-circle"></i> <span class="d-none d-sm-inline"><?= e($__u['full_name']) ?></span>
                <?php if ($__unread): ?><span class="badge bg-danger"><?= (int) $__unread ?></span><?php endif; ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="<?= e(url('notifications.php')) ?>"><i class="bi bi-bell me-2"></i>Notifications <?= $__unread ? '(' . (int) $__unread . ')' : '' ?></a></li>
                <li><a class="dropdown-item" href="<?= e(url('profile.php')) ?>"><i class="bi bi-person me-2"></i>My profile</a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="post" action="<?= e(url('logout.php')) ?>" class="px-3">
                        <?= csrf_field() ?>
                        <button class="btn btn-outline-danger btn-sm w-100" type="submit"><i class="bi bi-box-arrow-right"></i> Log out</button>
                    </form>
                </li>
            </ul>
        </div>
        <?php endif; ?>
    </div>
</nav>
<?php unset($__u, $__unread); ?>

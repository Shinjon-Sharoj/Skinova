<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/auth-check.php';

$pageTitle = 'My Profile';
$profile   = db_one('SELECT * FROM user_profiles WHERE user_id = ?', [$authUser['id']]);
$orderCount  = (int) db_value("SELECT COUNT(*) FROM orders WHERE user_id = ? AND status != 'cancelled'", [$authUser['id']], 0);
$loyaltyTxns = db_all('SELECT * FROM loyalty_transactions WHERE user_id = ? ORDER BY id DESC LIMIT 5', [$authUser['id']]);

include __DIR__ . '/includes/header.php';
?>
<div class="container py-4">
    <div class="row">
        <div class="col-lg-3 mb-4"><?php include __DIR__ . '/includes/sidebar.php'; ?></div>
        <div class="col-lg-9">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h2 class="h4 mb-0">My Profile</h2>
                <a href="<?= e(url('edit-profile.php')) ?>" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-pencil"></i> Edit profile
                </a>
            </div>

            <!-- Profile card -->
            <div class="card shadow-sm mb-4">
                <div class="card-body d-flex gap-4 flex-wrap">
                    <img src="<?= e(media_url($profile['avatar'] ?? null, 'avatar')) ?>"
                         alt="<?= e($authUser['full_name']) ?>"
                         class="rounded-circle flex-shrink-0"
                         width="100" height="100" style="object-fit:cover">
                    <div class="flex-grow-1">
                        <h3 class="h5 mb-1"><?= e($authUser['full_name']) ?></h3>
                        <p class="mb-1 text-muted"><i class="bi bi-envelope"></i> <?= e($authUser['email']) ?>
                            <?php if ($authUser['email_verified_at']): ?>
                                <span class="badge text-bg-success ms-1">Verified</span>
                            <?php else: ?>
                                <a href="<?= e(url('verify-account.php')) ?>" class="badge text-bg-warning text-decoration-none ms-1">Unverified</a>
                            <?php endif; ?>
                        </p>
                        <?php if ($authUser['phone']): ?>
                        <p class="mb-1 text-muted"><i class="bi bi-telephone"></i> <?= e($authUser['phone']) ?>
                            <?php if ($authUser['phone_verified_at']): ?>
                                <span class="badge text-bg-success ms-1">Verified</span>
                            <?php endif; ?>
                        </p>
                        <?php endif; ?>
                        <?php if ($profile && $profile['bio']): ?>
                        <p class="mb-0 text-muted small"><?= e($profile['bio']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Stats row -->
            <div class="row g-3 mb-4">
                <div class="col-sm-4">
                    <div class="card text-center shadow-sm h-100">
                        <div class="card-body">
                            <div class="display-6 text-primary fw-bold"><?= number_format($orderCount) ?></div>
                            <div class="small text-muted">Orders placed</div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="card text-center shadow-sm h-100">
                        <div class="card-body">
                            <div class="display-6 text-warning fw-bold"><?= number_format((int) $authUser['loyalty_points']) ?></div>
                            <div class="small text-muted">Loyalty points</div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="card text-center shadow-sm h-100 cursor-pointer" onclick="location.href='<?= e(url('skincare-profile.php')) ?>'">
                        <div class="card-body">
                            <?php $sp = db_one('SELECT skin_type FROM skin_profiles WHERE user_id = ?', [$authUser['id']]); ?>
                            <div class="display-6 text-success fw-bold"><i class="bi bi-droplet-half"></i></div>
                            <div class="small text-muted"><?= $sp ? ucfirst($sp['skin_type'] ?? 'View') . ' skin' : 'Set skin profile' ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Loyalty transactions -->
            <?php if ($loyaltyTxns): ?>
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Recent loyalty activity</span>
                </div>
                <ul class="list-group list-group-flush">
                    <?php foreach ($loyaltyTxns as $tx): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small fw-semibold"><?= e($tx['description'] ?? humanize($tx['type'])) ?></div>
                            <div class="text-muted small"><?= format_datetime($tx['created_at']) ?></div>
                        </div>
                        <span class="badge <?= $tx['points'] >= 0 ? 'text-bg-success' : 'text-bg-danger' ?> fs-6">
                            <?= $tx['points'] >= 0 ? '+' : '' ?><?= number_format($tx['points']) ?>
                        </span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

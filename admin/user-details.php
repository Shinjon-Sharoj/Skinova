<?php
require_once __DIR__ . '/../admin/includes/auth-check.php';
$pageTitle = 'User Details';
$panel     = 'admin';

$id   = get_int('id');
$user = db_one("SELECT u.*, up.avatar, up.bio, up.date_of_birth, up.gender FROM users u LEFT JOIN user_profiles up ON up.user_id=u.id WHERE u.id=?", [$id]);
if (!$user) not_found();

// Handle status / role changes
if (is_post() && csrf_is_valid()) {
    $act = input('action');
    if ($act === 'set_status') {
        $newStatus = input('status');
        if (in_array($newStatus, ['active','pending','suspended'], true) && $user['id'] != $authUser['id']) {
            db_exec('UPDATE users SET status=? WHERE id=?', [$newStatus, $id]);
            log_activity('admin_user_status', 'user', $id, 'status=' . $newStatus, (int)$authUser['id']);
            flash('success', 'User status updated.');
        }
    } elseif ($act === 'adjust_points') {
        $pts = input_int('points'); $reason = mb_substr(clean_text(input('reason')),0,255);
        if ($pts !== 0) {
            award_loyalty($id, $pts, 'adjusted', $reason ?: 'Admin adjustment', null);
            log_activity('admin_loyalty_adjusted', 'user', $id, 'pts=' . $pts, (int)$authUser['id']);
            flash('success', 'Loyalty points adjusted.');
        }
    }
    redirect('admin/user-details.php?id=' . $id);
}

$orders  = db_all("SELECT id, order_number, total, status, payment_status, created_at FROM orders WHERE user_id=? ORDER BY created_at DESC LIMIT 10", [$id]);
$reviews = db_all("SELECT r.id, r.rating, r.title, r.status, r.created_at, p.name AS product_name FROM reviews r JOIN products p ON p.id=r.product_id WHERE r.user_id=? ORDER BY r.created_at DESC LIMIT 5", [$id]);
$loyalty = db_all("SELECT * FROM loyalty_transactions WHERE user_id=? ORDER BY created_at DESC LIMIT 10", [$id]);

include __DIR__ . '/../admin/includes/header.php';
admin_page_header('User: ' . $user['full_name'], '<a href="' . e(url('admin/users.php')) . '" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>');
?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card shadow-sm mb-3">
            <div class="card-body text-center">
                <img src="<?= e(media_url($user['avatar']??null,'avatar')) ?>" class="rounded-circle mb-2" width="80" height="80" style="object-fit:cover" alt="">
                <h5 class="mb-0"><?= e($user['full_name']) ?></h5>
                <div class="text-muted small"><?= e($user['email']) ?></div>
                <?php if ($user['phone']): ?><div class="text-muted small"><?= e($user['phone']) ?></div><?php endif; ?>
                <div class="mt-2"><?= status_badge($user['role']) ?> <?= status_badge($user['status']) ?></div>
                <div class="mt-2 small text-muted">Joined <?= format_date($user['created_at']) ?></div>
                <div class="mt-1 small"><i class="bi bi-gem text-primary"></i> <?= number_format($user['loyalty_points']) ?> points</div>
            </div>
        </div>

        <!-- Change status -->
        <?php if ($user['id'] != $authUser['id']): ?>
        <div class="card shadow-sm mb-3">
            <div class="card-header small fw-semibold">Change Status</div>
            <div class="card-body">
                <form method="post" class="d-flex gap-2">
                    <?= csrf_field() ?><input type="hidden" name="action" value="set_status">
                    <select name="status" class="form-select form-select-sm">
                        <?php foreach (['active','pending','suspended'] as $s): ?>
                        <option value="<?= $s ?>" <?= $user['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-primary btn-sm">Save</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- Adjust loyalty -->
        <div class="card shadow-sm">
            <div class="card-header small fw-semibold">Adjust Loyalty Points</div>
            <div class="card-body">
                <form method="post">
                    <?= csrf_field() ?><input type="hidden" name="action" value="adjust_points">
                    <div class="mb-2"><input type="number" name="points" class="form-control form-control-sm" placeholder="e.g. 100 or -50" required></div>
                    <div class="mb-2"><input type="text" name="reason" class="form-control form-control-sm" placeholder="Reason…" maxlength="255"></div>
                    <button class="btn btn-warning btn-sm w-100">Adjust</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <!-- Orders -->
        <div class="card shadow-sm mb-4">
            <div class="card-header fw-semibold">Recent Orders</div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light"><tr><th>Order #</th><th>Total</th><th>Status</th><th>Date</th><th></th></tr></thead>
                    <tbody>
                        <?php if ($orders): foreach ($orders as $o): ?>
                        <tr>
                            <td class="small fw-semibold"><?= e($o['order_number']) ?></td>
                            <td class="small"><?= e(money($o['total'])) ?></td>
                            <td><?= status_badge($o['status']) ?></td>
                            <td class="small text-muted"><?= format_date($o['created_at']) ?></td>
                            <td><a href="<?= e(url('admin/order-details.php?id='.$o['id'])) ?>" class="btn btn-xs btn-outline-primary btn-sm">View</a></td>
                        </tr>
                        <?php endforeach; else: admin_empty_row(5,'No orders.'); endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Reviews -->
        <div class="card shadow-sm mb-4">
            <div class="card-header fw-semibold">Recent Reviews</div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light"><tr><th>Product</th><th>Rating</th><th>Status</th><th>Date</th></tr></thead>
                    <tbody>
                        <?php if ($reviews): foreach ($reviews as $r): ?>
                        <tr>
                            <td class="small"><?= e(truncate($r['product_name'],40)) ?></td>
                            <td><?= render_stars((float)$r['rating']) ?></td>
                            <td><?= status_badge($r['status']) ?></td>
                            <td class="small text-muted"><?= format_date($r['created_at']) ?></td>
                        </tr>
                        <?php endforeach; else: admin_empty_row(4,'No reviews.'); endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Loyalty transactions -->
        <div class="card shadow-sm">
            <div class="card-header fw-semibold">Loyalty Transactions</div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light"><tr><th>Points</th><th>Type</th><th>Description</th><th>Balance</th><th>Date</th></tr></thead>
                    <tbody>
                        <?php if ($loyalty): foreach ($loyalty as $lt): ?>
                        <tr>
                            <td class="small fw-semibold <?= $lt['points']>=0?'text-success':'text-danger' ?>"><?= $lt['points']>=0?'+':'' ?><?= number_format($lt['points']) ?></td>
                            <td><?= status_badge($lt['type']) ?></td>
                            <td class="small text-muted"><?= e(truncate($lt['description']??'',50)) ?></td>
                            <td class="small"><?= number_format($lt['balance_after']) ?></td>
                            <td class="small text-muted"><?= time_ago($lt['created_at']) ?></td>
                        </tr>
                        <?php endforeach; else: admin_empty_row(5,'No transactions.'); endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../admin/includes/footer.php'; ?>

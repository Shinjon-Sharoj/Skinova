<?php
require_once __DIR__ . '/../admin/includes/auth-check.php';
$pageTitle = 'Users';
$panel     = 'admin';

$role    = get_str('role');
$status  = get_str('status');
$q       = get_str('q');
$perPage = ADMIN_PER_PAGE;
$page    = get_int('page', 1);

$where = []; $params = [];
if ($role && in_array($role, [ROLE_CUSTOMER, ROLE_ADMIN, ROLE_CONSULTANT], true)) { $where[] = 'u.role = ?'; $params[] = $role; }
if ($status && in_array($status, ['active','pending','suspended'], true)) { $where[] = 'u.status = ?'; $params[] = $status; }
if ($q) { $like = '%' . addcslashes($q,'%_\\') . '%'; $where[] = '(u.full_name LIKE ? OR u.email LIKE ?)'; $params[] = $like; $params[] = $like; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total = (int) db_value("SELECT COUNT(*) FROM users u $whereSql", $params, 0);
$pg    = paginate($total, $perPage, $page);
$users = db_all(
    "SELECT u.id, u.full_name, u.email, u.phone, u.role, u.status, u.loyalty_points, u.created_at, u.last_login_at,
            up.avatar
     FROM users u LEFT JOIN user_profiles up ON up.user_id=u.id
     $whereSql ORDER BY u.created_at DESC LIMIT ? OFFSET ?",
    array_merge($params, [$pg['per_page'], $pg['offset']])
);

include __DIR__ . '/../admin/includes/header.php';
admin_page_header('Users', '<a href="' . e(url('admin/users.php')) . '" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-clockwise"></i></a>', $total . ' total');
?>
<!-- Filters -->
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-sm-4"><input type="search" name="q" class="form-control form-control-sm" placeholder="Search name / email…" value="<?= e($q) ?>"></div>
            <div class="col-sm-auto">
                <select name="role" class="form-select form-select-sm">
                    <option value="">All roles</option>
                    <option value="customer" <?= $role==='customer'?'selected':'' ?>>Customer</option>
                    <option value="admin" <?= $role==='admin'?'selected':'' ?>>Admin</option>
                    <option value="consultant" <?= $role==='consultant'?'selected':'' ?>>Consultant</option>
                </select>
            </div>
            <div class="col-sm-auto">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All statuses</option>
                    <option value="active" <?= $status==='active'?'selected':'' ?>>Active</option>
                    <option value="pending" <?= $status==='pending'?'selected':'' ?>>Pending</option>
                    <option value="suspended" <?= $status==='suspended'?'selected':'' ?>>Suspended</option>
                </select>
            </div>
            <div class="col-sm-auto"><button class="btn btn-primary btn-sm">Filter</button></div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>User</th><th>Role</th><th>Status</th><th>Points</th><th>Joined</th><th>Last login</th><th></th></tr></thead>
            <tbody>
                <?php if ($users): foreach ($users as $u): ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <img src="<?= e(media_url($u['avatar']??null,'avatar')) ?>" class="rounded-circle" width="32" height="32" style="object-fit:cover" alt="">
                            <div>
                                <div class="small fw-semibold"><?= e($u['full_name']) ?></div>
                                <div class="text-muted" style="font-size:.7rem"><?= e($u['email']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td><?= status_badge($u['role']) ?></td>
                    <td><?= status_badge($u['status']) ?></td>
                    <td class="small"><?= number_format($u['loyalty_points']) ?></td>
                    <td class="small text-muted"><?= format_date($u['created_at']) ?></td>
                    <td class="small text-muted"><?= $u['last_login_at'] ? time_ago($u['last_login_at']) : '—' ?></td>
                    <td><a href="<?= e(url('admin/user-details.php?id=' . $u['id'])) ?>" class="btn btn-sm btn-outline-primary">View</a></td>
                </tr>
                <?php endforeach; else: admin_empty_row(7,'No users found.'); endif; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3"><?= render_pagination($pg) ?></div>
<?php include __DIR__ . '/../admin/includes/footer.php'; ?>

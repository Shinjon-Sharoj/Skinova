<?php
require_once __DIR__ . '/../admin/includes/auth-check.php';
require_once __DIR__ . '/../includes/consultation.php';
$pageTitle = 'Consultants';
$panel     = 'admin';

// Handle status toggle
if (is_post() && csrf_is_valid()) {
    $cid = input_int('consultant_id');
    $act = input('action');
    if ($act === 'toggle_available') {
        db_exec('UPDATE consultants SET is_available = NOT is_available WHERE id=?', [$cid]);
        flash('success', 'Availability updated.');
    } elseif ($act === 'toggle_status') {
        $newSt = db_value('SELECT status FROM consultants WHERE id=?', [$cid]) === 'active' ? 'inactive' : 'active';
        db_exec('UPDATE consultants SET status=? WHERE id=?', [$newSt, $cid]);
        flash('success', 'Status updated.');
    }
    log_activity('admin_consultant_'.$act,'consultant',$cid,null,(int)$authUser['id']);
    redirect('admin/consultants.php');
}

$consultants = db_all(
    "SELECT c.*, u.full_name, u.email, up.avatar
     FROM consultants c JOIN users u ON u.id=c.user_id LEFT JOIN user_profiles up ON up.user_id=c.user_id
     ORDER BY c.status, u.full_name"
);

include __DIR__ . '/../admin/includes/header.php';
admin_page_header('Consultants', '', count($consultants) . ' total');
?>
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Consultant</th><th>Specialization</th><th>Fee</th><th>Rating</th><th>Status</th><th>Available</th><th></th></tr></thead>
            <tbody>
                <?php if ($consultants): foreach ($consultants as $c): ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <img src="<?= e(media_url($c['avatar']??null,'avatar')) ?>" class="rounded-circle" width="36" height="36" style="object-fit:cover" alt="">
                            <div>
                                <div class="small fw-semibold"><?= e(($c['title']?$c['title'].' ':'') . $c['full_name']) ?></div>
                                <div class="text-muted" style="font-size:.7rem"><?= e($c['email']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="small"><?= e(truncate($c['specialization'],40)) ?></td>
                    <td class="small"><?= e(money($c['consultation_fee'])) ?></td>
                    <td class="small"><?= $c['rating_count']>0 ? render_stars((float)$c['rating_avg']).' ('.number_format($c['rating_count']).')' : '—' ?></td>
                    <td><?= status_badge($c['status']) ?></td>
                    <td><?= $c['is_available'] ? '<span class="badge text-bg-success">Yes</span>' : '<span class="badge text-bg-secondary">No</span>' ?></td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="<?= e(url('admin/user-details.php?id='.$c['user_id'])) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-person"></i></a>
                            <form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="consultant_id" value="<?= $c['id'] ?>"><input type="hidden" name="action" value="toggle_available"><button class="btn btn-sm btn-outline-<?= $c['is_available']?'warning':'success' ?>" title="Toggle availability"><i class="bi bi-calendar-check"></i></button></form>
                            <form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="consultant_id" value="<?= $c['id'] ?>"><input type="hidden" name="action" value="toggle_status"><button class="btn btn-sm btn-outline-<?= $c['status']==='active'?'danger':'success' ?>" title="Toggle status"><i class="bi bi-toggle-on"></i></button></form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; else: admin_empty_row(7,'No consultants yet.'); endif; ?>
            </tbody>
        </table>
    </div>
</div>
<p class="text-muted small mt-2">To add a consultant: create a user with the <strong>consultant</strong> role, then <a href="<?= e(url('admin/users.php?role=consultant')) ?>">view their profile</a> — the consultant row is created automatically when they log in to the consultant dashboard and save their profile.</p>
<?php include __DIR__ . '/../admin/includes/footer.php'; ?>

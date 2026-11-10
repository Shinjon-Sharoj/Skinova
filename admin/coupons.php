<?php
require_once __DIR__ . '/../admin/includes/auth-check.php';
$pageTitle = 'Coupons';
$panel     = 'admin';

// Save coupon
if (is_post() && csrf_is_valid() && input('action') === 'save') {
    $id = input_int('id');
    $data = [
        strtoupper(trim(input('code'))), input('description') ?: null,
        in_array(input('discount_type'),['percentage','fixed'],true) ? input('discount_type') : 'percentage',
        input_float('discount_value'), input_float('min_order_amount'),
        input_float('max_discount_amount') ?: null, input_int('usage_limit') ?: null,
        max(1, input_int('per_user_limit', 1)), isset($_POST['first_order_only'])?1:0,
        input('starts_at') ?: null, input('expires_at') ?: null, isset($_POST['is_active'])?1:0,
    ];
    if ($id) {
        array_push($data, $id);
        db_exec('UPDATE coupons SET code=?,description=?,discount_type=?,discount_value=?,min_order_amount=?,max_discount_amount=?,usage_limit=?,per_user_limit=?,first_order_only=?,starts_at=?,expires_at=?,is_active=? WHERE id=?', $data);
    } else {
        db_insert('INSERT INTO coupons (code,description,discount_type,discount_value,min_order_amount,max_discount_amount,usage_limit,per_user_limit,first_order_only,starts_at,expires_at,is_active) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)', $data);
    }
    log_activity('admin_coupon_saved','coupon',$id,null,(int)$authUser['id']);
    flash('success','Coupon saved.');
    redirect('admin/coupons.php');
}

$coupons = db_all('SELECT * FROM coupons ORDER BY created_at DESC');
$editId  = get_int('edit');
$editing = $editId ? db_one('SELECT * FROM coupons WHERE id=?', [$editId]) : null;

include __DIR__ . '/../admin/includes/header.php';
admin_page_header('Coupons', '<button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#couponModal" onclick="resetForm()"><i class="bi bi-plus-lg"></i> New coupon</button>');
?>
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Code</th><th>Discount</th><th>Min order</th><th>Uses</th><th>Expires</th><th>Status</th><th></th></tr></thead>
            <tbody>
                <?php if ($coupons): foreach ($coupons as $c): ?>
                <tr>
                    <td><span class="badge text-bg-light border fs-6 font-monospace"><?= e($c['code']) ?></span></td>
                    <td class="small"><?= $c['discount_type']==='percentage'?e($c['discount_value']).'%':e(money($c['discount_value'])) ?></td>
                    <td class="small"><?= $c['min_order_amount']>0?e(money($c['min_order_amount'])):'—' ?></td>
                    <td class="small"><?= number_format($c['used_count']) ?><?= $c['usage_limit']?' / '.number_format($c['usage_limit']):'/ ∞' ?></td>
                    <td class="small text-muted"><?= $c['expires_at']?format_date($c['expires_at']):'Never' ?></td>
                    <td><?= $c['is_active']?'<span class="badge text-bg-success">Active</span>':'<span class="badge text-bg-secondary">Inactive</span>' ?></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#couponModal"
                            onclick="fillForm(<?= htmlspecialchars(json_encode($c),ENT_QUOTES) ?>)"><i class="bi bi-pencil"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; else: admin_empty_row(7,'No coupons yet.'); endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="couponModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="post" class="modal-content">
            <?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" id="cpId" value="">
            <div class="modal-header"><h5 class="modal-title">Coupon</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label small">Code *</label><input name="code" id="cpCode" class="form-control form-control-sm text-uppercase" required maxlength="40"></div>
                    <div class="col-md-4"><label class="form-label small">Type</label><select name="discount_type" id="cpType" class="form-select form-select-sm"><option value="percentage">Percentage %</option><option value="fixed">Fixed <?= e(CURRENCY_SYMBOL) ?></option></select></div>
                    <div class="col-md-4"><label class="form-label small">Value *</label><input type="number" name="discount_value" id="cpVal" class="form-control form-control-sm" required min="0" step="0.01"></div>
                    <div class="col-md-4"><label class="form-label small">Min order</label><input type="number" name="min_order_amount" id="cpMin" class="form-control form-control-sm" min="0" step="0.01" value="0"></div>
                    <div class="col-md-4"><label class="form-label small">Max discount cap</label><input type="number" name="max_discount_amount" id="cpMaxDisc" class="form-control form-control-sm" min="0" step="0.01"></div>
                    <div class="col-md-4"><label class="form-label small">Per user limit</label><input type="number" name="per_user_limit" id="cpPerUser" class="form-control form-control-sm" min="1" value="1"></div>
                    <div class="col-md-4"><label class="form-label small">Total uses limit</label><input type="number" name="usage_limit" id="cpUsage" class="form-control form-control-sm" min="0" placeholder="∞ unlimited"></div>
                    <div class="col-md-4"><label class="form-label small">Starts at</label><input type="datetime-local" name="starts_at" id="cpStart" class="form-control form-control-sm"></div>
                    <div class="col-md-4"><label class="form-label small">Expires at</label><input type="datetime-local" name="expires_at" id="cpExpires" class="form-control form-control-sm"></div>
                    <div class="col-12"><input name="description" id="cpDesc" class="form-control form-control-sm" placeholder="Description (optional)" maxlength="255"></div>
                    <div class="col-12 d-flex gap-3">
                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" id="cpActive" value="1" checked><label class="form-check-label small" for="cpActive">Active</label></div>
                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="first_order_only" id="cpFirst" value="1"><label class="form-check-label small" for="cpFirst">First order only</label></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary btn-sm">Save coupon</button></div>
        </form>
    </div>
</div>
<script>
function resetForm(){['cpId','cpCode','cpVal','cpDesc','cpUsage','cpStart','cpExpires','cpMaxDisc'].forEach(id=>document.getElementById(id).value='');document.getElementById('cpMin').value='0';document.getElementById('cpPerUser').value='1';document.getElementById('cpType').value='percentage';document.getElementById('cpActive').checked=true;document.getElementById('cpFirst').checked=false;}
function fillForm(d){document.getElementById('cpId').value=d.id;document.getElementById('cpCode').value=d.code;document.getElementById('cpType').value=d.discount_type;document.getElementById('cpVal').value=d.discount_value;document.getElementById('cpMin').value=d.min_order_amount;document.getElementById('cpMaxDisc').value=d.max_discount_amount||'';document.getElementById('cpPerUser').value=d.per_user_limit;document.getElementById('cpUsage').value=d.usage_limit||'';document.getElementById('cpDesc').value=d.description||'';document.getElementById('cpStart').value=(d.starts_at||'').replace(' ','T').substring(0,16);document.getElementById('cpExpires').value=(d.expires_at||'').replace(' ','T').substring(0,16);document.getElementById('cpActive').checked=d.is_active==1;document.getElementById('cpFirst').checked=d.first_order_only==1;}
</script>
<?php include __DIR__ . '/../admin/includes/footer.php'; ?>

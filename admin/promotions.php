<?php
require_once __DIR__ . '/../admin/includes/auth-check.php';
$pageTitle = 'Promotions';
$panel     = 'admin';

if (is_post() && csrf_is_valid() && input('action') === 'save') {
    $id = input_int('id');
    $data = [
        clean_text(input('title')), input('description') ? clean_text(input('description')) : null,
        in_array(input('promo_type'),['product_discount','flash_sale','bundle','bogo','seasonal','first_order'],true)?input('promo_type'):'product_discount',
        in_array(input('discount_type'),['percentage','fixed'],true)?input('discount_type'):'percentage',
        input_float('discount_value'), input_int('category_id') ?: null,
        input_int('priority'), input('starts_at') ?: null, input('ends_at') ?: null, isset($_POST['is_active'])?1:0,
    ];
    if ($id) { array_push($data,$id); db_exec('UPDATE promotions SET title=?,description=?,promo_type=?,discount_type=?,discount_value=?,category_id=?,priority=?,starts_at=?,ends_at=?,is_active=? WHERE id=?',$data); }
    else { $id = db_insert('INSERT INTO promotions (title,description,promo_type,discount_type,discount_value,category_id,priority,starts_at,ends_at,is_active) VALUES (?,?,?,?,?,?,?,?,?,?)',$data); }
    // Product IDs
    db_exec('DELETE FROM promotion_products WHERE promotion_id=?',[$id]);
    foreach (array_map('intval',array_filter(explode(',',input('product_ids')))) as $pid) {
        if ($pid>0) db_exec('INSERT IGNORE INTO promotion_products (promotion_id,product_id) VALUES(?,?)',[$id,$pid]);
    }
    log_activity('admin_promotion_saved','promotion',$id,null,(int)$authUser['id']);
    flash('success','Promotion saved.');
    redirect('admin/promotions.php');
}

$promos     = db_all('SELECT p.*,c.name AS category_name FROM promotions p LEFT JOIN categories c ON c.id=p.category_id ORDER BY p.created_at DESC');
$categories = get_categories(false);

include __DIR__ . '/../admin/includes/header.php';
admin_page_header('Promotions','<button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#promoModal"><i class="bi bi-plus-lg"></i> New promotion</button>');
?>
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Title</th><th>Type</th><th>Discount</th><th>Category</th><th>Priority</th><th>Dates</th><th>Status</th><th></th></tr></thead>
            <tbody>
                <?php if ($promos): foreach ($promos as $pr): ?>
                <tr>
                    <td class="small fw-semibold"><?= e(truncate($pr['title'],40)) ?></td>
                    <td><span class="badge text-bg-info"><?= e(humanize($pr['promo_type'])) ?></span></td>
                    <td class="small"><?= $pr['discount_type']==='percentage'?e($pr['discount_value']).'%':e(money($pr['discount_value'])) ?></td>
                    <td class="small text-muted"><?= $pr['category_name']?e($pr['category_name']):'All' ?></td>
                    <td class="small"><?= $pr['priority'] ?></td>
                    <td class="small text-muted"><?= $pr['starts_at']?format_date($pr['starts_at']):'—' ?> → <?= $pr['ends_at']?format_date($pr['ends_at']):'∞' ?></td>
                    <td><?= $pr['is_active']?'<span class="badge text-bg-success">Active</span>':'<span class="badge text-bg-secondary">Inactive</span>' ?></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#promoModal"
                            onclick="fillPromo(<?= htmlspecialchars(json_encode($pr),ENT_QUOTES) ?>)"><i class="bi bi-pencil"></i></button>
                    </td>
                </tr>
                <?php endforeach; else: admin_empty_row(8,'No promotions yet.'); endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="promoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="post" class="modal-content">
            <?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" id="prId" value="">
            <div class="modal-header"><h5 class="modal-title">Promotion</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12"><label class="form-label small">Title *</label><input name="title" id="prTitle" class="form-control form-control-sm" required maxlength="150"></div>
                    <div class="col-md-6"><label class="form-label small">Type</label><select name="promo_type" id="prType" class="form-select form-select-sm"><option value="product_discount">Product discount</option><option value="flash_sale">Flash sale</option><option value="bundle">Bundle</option><option value="bogo">BOGO</option><option value="seasonal">Seasonal</option><option value="first_order">First order</option></select></div>
                    <div class="col-md-3"><label class="form-label small">Discount type</label><select name="discount_type" id="prDiscType" class="form-select form-select-sm"><option value="percentage">%</option><option value="fixed"><?= e(CURRENCY_SYMBOL) ?></option></select></div>
                    <div class="col-md-3"><label class="form-label small">Value *</label><input type="number" name="discount_value" id="prDiscVal" class="form-control form-control-sm" required min="0" step="0.01"></div>
                    <div class="col-md-6"><label class="form-label small">Category (optional)</label><select name="category_id" id="prCat" class="form-select form-select-sm"><option value="">All categories</option><?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-2"><label class="form-label small">Priority</label><input type="number" name="priority" id="prPri" class="form-control form-control-sm" value="0"></div>
                    <div class="col-md-4"><label class="form-label small">Product IDs <span class="text-muted">(comma-sep)</span></label><input name="product_ids" id="prPids" class="form-control form-control-sm" placeholder="1,2,3"></div>
                    <div class="col-md-6"><label class="form-label small">Starts at</label><input type="datetime-local" name="starts_at" id="prStart" class="form-control form-control-sm"></div>
                    <div class="col-md-6"><label class="form-label small">Ends at</label><input type="datetime-local" name="ends_at" id="prEnd" class="form-control form-control-sm"></div>
                    <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" id="prActive" value="1" checked><label class="form-check-label small" for="prActive">Active</label></div></div>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary btn-sm">Save</button></div>
        </form>
    </div>
</div>
<script>
function fillPromo(d){document.getElementById('prId').value=d.id;document.getElementById('prTitle').value=d.title;document.getElementById('prType').value=d.promo_type;document.getElementById('prDiscType').value=d.discount_type;document.getElementById('prDiscVal').value=d.discount_value;document.getElementById('prCat').value=d.category_id||'';document.getElementById('prPri').value=d.priority;document.getElementById('prStart').value=(d.starts_at||'').replace(' ','T').substring(0,16);document.getElementById('prEnd').value=(d.ends_at||'').replace(' ','T').substring(0,16);document.getElementById('prActive').checked=d.is_active==1;}
</script>
<?php include __DIR__ . '/../admin/includes/footer.php'; ?>

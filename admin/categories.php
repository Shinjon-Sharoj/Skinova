<?php
require_once __DIR__ . '/../admin/includes/auth-check.php';
$pageTitle = 'Categories & Brands';
$panel     = 'admin';

// Handle category save
if (is_post() && csrf_is_valid()) {
    $act = input('action');

    if ($act === 'save_category') {
        $cid  = input_int('id');
        $name = clean_text(input('name'));
        $desc = input('description') ? clean_text(input('description')) : null;
        $sort = input_int('sort_order');
        $active = isset($_POST['is_active']) ? 1 : 0;
        $parentId = input_int('parent_id') ?: null;
        $imgPath = null;
        if (!empty($_FILES['image']['size'])) { $r = save_image_upload($_FILES['image'],'categories'); if (!isset($r['error'])) $imgPath = $r['path']; }

        if ($cid) {
            db_exec('UPDATE categories SET name=?,description=?,sort_order=?,is_active=?,parent_id=?,image=COALESCE(?,image) WHERE id=?',[$name,$desc,$sort,$active,$parentId,$imgPath,$cid]);
        } else {
            $slug = unique_slug('categories',$name);
            db_insert('INSERT INTO categories (name,slug,description,sort_order,is_active,parent_id,image) VALUES(?,?,?,?,?,?,?)',[$name,$slug,$desc,$sort,$active,$parentId,$imgPath]);
        }
        flash('success','Category saved.');
    } elseif ($act === 'save_brand') {
        $bid = input_int('id');
        $name = clean_text(input('name'));
        $desc = input('description') ? clean_text(input('description')) : null;
        $active = isset($_POST['is_active']) ? 1 : 0;
        $logoPath = null;
        if (!empty($_FILES['logo']['size'])) { $r = save_image_upload($_FILES['logo'],'categories'); if (!isset($r['error'])) $logoPath = $r['path']; }

        if ($bid) {
            db_exec('UPDATE brands SET name=?,description=?,is_active=?,logo=COALESCE(?,logo) WHERE id=?',[$name,$desc,$active,$logoPath,$bid]);
        } else {
            $slug = unique_slug('brands',$name);
            db_insert('INSERT INTO brands (name,slug,description,is_active,logo) VALUES(?,?,?,?,?)',[$name,$slug,$desc,$active,$logoPath]);
        }
        flash('success','Brand saved.');
    }

    log_activity('admin_' . input('action'), null, null, null, (int)$authUser['id']);
    redirect('admin/categories.php');
}

$categories = get_categories(false);
$brands     = get_brands(false);

include __DIR__ . '/../admin/includes/header.php';
admin_page_header('Categories & Brands');
?>
<div class="row g-4">
    <!-- Categories -->
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Categories</span>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#catModal" onclick="resetCatForm()">
                    <i class="bi bi-plus-lg"></i> Add
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>Name</th><th>Parent</th><th>Products</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="<?= e(media_url($cat['image']??null,'category')) ?>" width="32" height="32" style="object-fit:cover;border-radius:4px" alt="">
                                    <span class="small"><?= e($cat['name']) ?></span>
                                </div>
                            </td>
                            <td class="small text-muted"><?= $cat['parent_id'] ? e(db_value('SELECT name FROM categories WHERE id=?',[$cat['parent_id']],'—')) : '—' ?></td>
                            <td class="small"><?= number_format($cat['product_count']??0) ?></td>
                            <td><?= $cat['is_active'] ? '<span class="badge text-bg-success">Active</span>' : '<span class="badge text-bg-secondary">Inactive</span>' ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#catModal"
                                    onclick="fillCatForm(<?= htmlspecialchars(json_encode($cat), ENT_QUOTES) ?>)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Brands -->
    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Brands</span>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#brandModal" onclick="resetBrandForm()">
                    <i class="bi bi-plus-lg"></i> Add
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>Brand</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($brands as $b): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="<?= e(media_url($b['logo']??null,'category')) ?>" width="28" height="28" style="object-fit:contain;border-radius:4px" alt="">
                                    <span class="small"><?= e($b['name']) ?></span>
                                </div>
                            </td>
                            <td><?= $b['is_active'] ? '<span class="badge text-bg-success">Active</span>' : '<span class="badge text-bg-secondary">Inactive</span>' ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#brandModal"
                                    onclick="fillBrandForm(<?= htmlspecialchars(json_encode($b), ENT_QUOTES) ?>)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Category Modal -->
<div class="modal fade" id="catModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" enctype="multipart/form-data" class="modal-content">
            <?= csrf_field() ?><input type="hidden" name="action" value="save_category">
            <input type="hidden" name="id" id="catId" value="">
            <div class="modal-header"><h5 class="modal-title" id="catModalTitle">Category</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-2"><label class="form-label small">Name *</label><input name="name" id="catName" class="form-control form-control-sm" required></div>
                <div class="mb-2"><label class="form-label small">Parent category</label>
                    <select name="parent_id" id="catParent" class="form-select form-select-sm">
                        <option value="">None (top-level)</option>
                        <?php foreach ($categories as $cat): if ($cat['parent_id']) continue; ?>
                        <option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-2"><label class="form-label small">Description</label><textarea name="description" id="catDesc" class="form-control form-control-sm" rows="2"></textarea></div>
                <div class="row g-2 mb-2">
                    <div class="col-6"><label class="form-label small">Sort order</label><input type="number" name="sort_order" id="catSort" class="form-control form-control-sm" value="0"></div>
                </div>
                <div class="mb-2"><label class="form-label small">Image</label><input type="file" name="image" class="form-control form-control-sm" accept="image/*"></div>
                <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" id="catActive" value="1" checked><label class="form-check-label small" for="catActive">Active</label></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary btn-sm">Save</button></div>
        </form>
    </div>
</div>

<!-- Brand Modal -->
<div class="modal fade" id="brandModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" enctype="multipart/form-data" class="modal-content">
            <?= csrf_field() ?><input type="hidden" name="action" value="save_brand">
            <input type="hidden" name="id" id="brandId" value="">
            <div class="modal-header"><h5 class="modal-title">Brand</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-2"><label class="form-label small">Name *</label><input name="name" id="brandName" class="form-control form-control-sm" required></div>
                <div class="mb-2"><label class="form-label small">Description</label><textarea name="description" id="brandDesc" class="form-control form-control-sm" rows="2"></textarea></div>
                <div class="mb-2"><label class="form-label small">Logo</label><input type="file" name="logo" class="form-control form-control-sm" accept="image/*"></div>
                <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" id="brandActive" value="1" checked><label class="form-check-label small" for="brandActive">Active</label></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary btn-sm">Save</button></div>
        </form>
    </div>
</div>

<script>
function resetCatForm() { document.getElementById('catId').value=''; document.getElementById('catName').value=''; document.getElementById('catDesc').value=''; document.getElementById('catSort').value='0'; document.getElementById('catActive').checked=true; }
function fillCatForm(d) { document.getElementById('catId').value=d.id; document.getElementById('catName').value=d.name; document.getElementById('catDesc').value=d.description||''; document.getElementById('catSort').value=d.sort_order; document.getElementById('catActive').checked=d.is_active==1; document.getElementById('catParent').value=d.parent_id||''; }
function resetBrandForm() { document.getElementById('brandId').value=''; document.getElementById('brandName').value=''; document.getElementById('brandDesc').value=''; document.getElementById('brandActive').checked=true; }
function fillBrandForm(d) { document.getElementById('brandId').value=d.id; document.getElementById('brandName').value=d.name; document.getElementById('brandDesc').value=d.description||''; document.getElementById('brandActive').checked=d.is_active==1; }
</script>
<?php include __DIR__ . '/../admin/includes/footer.php'; ?>

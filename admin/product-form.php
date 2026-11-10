<?php
require_once __DIR__ . '/../admin/includes/auth-check.php';
$pageTitle = 'Product';
$panel     = 'admin';

$id      = get_int('id');
$product = $id ? db_one('SELECT * FROM products WHERE id=?', [$id]) : null;
if ($id && !$product) not_found();
$isEdit  = (bool) $product;

$categories   = get_categories(false);
$brands       = get_brands(false);
$allIngred    = get_all_ingredients();
$allConcerns  = get_skin_concerns();

$prodIngr     = $isEdit ? db_all('SELECT ingredient_id FROM product_ingredients WHERE product_id=? ORDER BY position', [$id]) : [];
$prodSkinTypes = $isEdit ? array_column(db_all('SELECT skin_type FROM product_skin_types WHERE product_id=?', [$id]), 'skin_type') : [];
$prodConcerns  = $isEdit ? array_column(db_all('SELECT skin_concern_id FROM product_skin_concerns WHERE product_id=?', [$id]), 'skin_concern_id') : [];
$prodImages    = $isEdit ? db_all('SELECT * FROM product_images WHERE product_id=? ORDER BY is_primary DESC, sort_order, id', [$id]) : [];
$inventory     = $isEdit ? db_one('SELECT quantity, low_stock_threshold FROM inventory WHERE product_id=?', [$id]) : null;

// Handle save
if (is_post() && csrf_is_valid()) {
    $v = new Validator($_POST);
    $v->required('name','Name')->max('name',190,'Name');
    $v->required('category_id','Category');
    $v->required('price','Price');
    $v->required('sku','SKU');
    $errors = $v->errors();

    if (!$errors) {
        $slug    = unique_slug('products', input('name'), $isEdit ? $id : null);
        $price   = round(input_float('price'),2);
        $disc    = min(100, max(0, input_float('discount_percent')));
        $data = [
            input_int('category_id'), input('brand_id') ?: null, clean_text(input('name')), $slug,
            strtoupper(trim(input('sku'))), clean_text(input('short_description')), input('description') ? sanitize_html(input('description')) : null,
            input('benefits') ?: null, input('usage_instructions') ?: null, input('precautions') ?: null,
            input('size_label') ?: null, $price, $disc,
            in_array(input('routine_step'), ROUTINE_STEPS, true) ? input('routine_step') : 'other',
            isset($_POST['is_featured']) ? 1 : 0, isset($_POST['is_active']) ? 1 : 0,
        ];

        if ($isEdit) {
            $data[] = $id;
            db_exec('UPDATE products SET category_id=?,brand_id=?,name=?,slug=?,sku=?,short_description=?,description=?,benefits=?,usage_instructions=?,precautions=?,size_label=?,price=?,discount_percent=?,routine_step=?,is_featured=?,is_active=? WHERE id=?', $data);
        } else {
            $id = db_insert('INSERT INTO products (category_id,brand_id,name,slug,sku,short_description,description,benefits,usage_instructions,precautions,size_label,price,discount_percent,routine_step,is_featured,is_active) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', $data);
        }

        // Inventory
        $qty = input_int('quantity');
        $lst = input_int('low_stock_threshold', 5);
        db_exec('INSERT INTO inventory (product_id,quantity,low_stock_threshold) VALUES (?,?,?) ON DUPLICATE KEY UPDATE quantity=VALUES(quantity),low_stock_threshold=VALUES(low_stock_threshold)', [$id,$qty,$lst]);

        // Skin types
        db_exec('DELETE FROM product_skin_types WHERE product_id=?', [$id]);
        foreach ((array)($_POST['skin_types']??[]) as $st) { if (in_array($st,SKIN_TYPES,true)) db_exec('INSERT IGNORE INTO product_skin_types (product_id,skin_type) VALUES(?,?)',[$id,$st]); }

        // Concerns
        db_exec('DELETE FROM product_skin_concerns WHERE product_id=?', [$id]);
        foreach (array_map('intval',(array)($_POST['concerns']??[])) as $cid) { if ($cid>0) db_exec('INSERT IGNORE INTO product_skin_concerns (product_id,skin_concern_id) VALUES(?,?)',[$id,$cid]); }

        // Images
        if (!empty($_FILES['images']['name'][0])) {
            $files = normalize_files_array($_FILES['images']);
            $first = !$prodImages;
            foreach ($files as $file) {
                $r = save_image_upload($file,'products');
                if (!isset($r['error'])) {
                    db_insert('INSERT INTO product_images (product_id,image_path,is_primary) VALUES(?,?,?)',[$id,$r['path'],$first?1:0]);
                    $first = false;
                }
            }
        }

        // Notify wishlist watchers of price change
        notify_wishlist_price_drop($id);

        log_activity($isEdit?'admin_product_updated':'admin_product_created','product',$id,null,(int)$authUser['id']);
        flash('success','Product ' . ($isEdit?'updated':'created') . '.');
        redirect('admin/product-form.php?id=' . $id);
    }
    set_old($_POST,$errors);
}

$pageTitle = $isEdit ? 'Edit: ' . truncate($product['name'],30) : 'New Product';
include __DIR__ . '/../admin/includes/header.php';
admin_page_header($pageTitle, '<a href="' . e(url('admin/products.php')) . '" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Products</a>' . ($isEdit ? ' <a href="' . e(url('product-details.php?slug=' . $product['slug'])) . '" class="btn btn-outline-info btn-sm" target="_blank"><i class="bi bi-eye"></i> View</a>' : ''));
?>
<form method="post" enctype="multipart/form-data" novalidate class="needs-validation">
    <?= csrf_field() ?>
    <div class="row g-4">
        <div class="col-lg-8">
            <!-- Basic info -->
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold">Basic Information</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Product name <span class="text-danger">*</span></label>
                            <input name="name" class="form-control<?= invalid_class('name') ?>" required maxlength="190" value="<?= e(old('name',$product['name']??'')) ?>">
                            <?= invalid_feedback('name') ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">SKU <span class="text-danger">*</span></label>
                            <input name="sku" class="form-control<?= invalid_class('sku') ?>" required maxlength="60" value="<?= e(old('sku',$product['sku']??'')) ?>">
                            <?= invalid_feedback('sku') ?>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Price <span class="text-danger">*</span></label>
                            <div class="input-group"><span class="input-group-text"><?= e(CURRENCY_SYMBOL) ?></span>
                            <input type="number" name="price" class="form-control<?= invalid_class('price') ?>" required min="0" step="0.01" value="<?= e(old('price',$product['price']??'')) ?>"></div>
                            <?= invalid_feedback('price') ?>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Discount %</label>
                            <input type="number" name="discount_percent" class="form-control" min="0" max="100" step="0.01" value="<?= e(old('discount_percent',$product['discount_percent']??'0')) ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Short description</label>
                            <input name="short_description" class="form-control" maxlength="300" value="<?= e(old('short_description',$product['short_description']??'')) ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Full description</label>
                            <textarea name="description" class="form-control" rows="6"><?= e(old('description',$product['description']??'')) ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Benefits <span class="text-muted small">(one per line)</span></label>
                            <textarea name="benefits" class="form-control" rows="3" placeholder="Hydrates deeply&#10;Reduces redness"><?= e(old('benefits',$product['benefits']??'')) ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Usage instructions</label>
                            <textarea name="usage_instructions" class="form-control" rows="3"><?= e(old('usage_instructions',$product['usage_instructions']??'')) ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Precautions</label>
                            <textarea name="precautions" class="form-control" rows="3"><?= e(old('precautions',$product['precautions']??'')) ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Images -->
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold">Product Images</div>
                <div class="card-body">
                    <?php if ($prodImages): ?>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <?php foreach ($prodImages as $img): ?>
                        <div class="position-relative">
                            <img src="<?= e(media_url($img['image_path'],'product')) ?>" width="80" height="80" style="object-fit:cover;border-radius:6px" alt="">
                            <?php if ($img['is_primary']): ?><span class="badge bg-primary position-absolute bottom-0 start-0" style="font-size:.5rem">Primary</span><?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <label class="form-label">Upload new images</label>
                    <input type="file" name="images[]" class="form-control" accept="image/*" multiple>
                    <div class="form-text">JPEG/PNG/WebP, max 5MB each.</div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Category & brand -->
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold">Organisation</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Category <span class="text-danger">*</span></label>
                        <select name="category_id" class="form-select<?= invalid_class('category_id') ?>" required>
                            <option value="">Select…</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= old('category_id',$product['category_id']??0)==$cat['id']?'selected':'' ?>><?= e($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?= invalid_feedback('category_id') ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Brand</label>
                        <select name="brand_id" class="form-select">
                            <option value="">None</option>
                            <?php foreach ($brands as $b): ?>
                            <option value="<?= $b['id'] ?>" <?= old('brand_id',$product['brand_id']??'')==$b['id']?'selected':'' ?>><?= e($b['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Routine step</label>
                        <select name="routine_step" class="form-select">
                            <?php foreach (ROUTINE_STEPS as $rs): ?>
                            <option value="<?= $rs ?>" <?= old('routine_step',$product['routine_step']??'other')===$rs?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$rs)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <input type="text" name="size_label" class="form-control form-control-sm" placeholder="Size e.g. 50ml" value="<?= e(old('size_label',$product['size_label']??'')) ?>">
                    </div>
                    <div class="form-check form-switch mb-1">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" <?= old('is_active',$product['is_active']??'1')?'checked':'' ?>>
                        <label class="form-check-label small" for="is_active">Active</label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" value="1" <?= old('is_featured',$product['is_featured']??'0')?'checked':'' ?>>
                        <label class="form-check-label small" for="is_featured">Featured</label>
                    </div>
                </div>
            </div>

            <!-- Inventory -->
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold">Inventory</div>
                <div class="card-body">
                    <div class="mb-2">
                        <label class="form-label small">Stock quantity</label>
                        <input type="number" name="quantity" class="form-control form-control-sm" min="0" value="<?= e(old('quantity',$inventory['quantity']??0)) ?>">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Low stock threshold</label>
                        <input type="number" name="low_stock_threshold" class="form-control form-control-sm" min="0" value="<?= e(old('low_stock_threshold',$inventory['low_stock_threshold']??5)) ?>">
                    </div>
                </div>
            </div>

            <!-- Skin types -->
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold">Skin Types</div>
                <div class="card-body">
                    <?php foreach (SKIN_TYPES as $st): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="skin_types[]" value="<?= $st ?>" id="st_<?= $st ?>"
                               <?= in_array($st, (array)old('skin_types', $prodSkinTypes), true)?'checked':'' ?>>
                        <label class="form-check-label small" for="st_<?= $st ?>"><?= ucfirst($st) ?></label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Skin concerns -->
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold">Skin Concerns</div>
                <div class="card-body" style="max-height:200px;overflow-y:auto">
                    <?php foreach ($allConcerns as $sc): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="concerns[]" value="<?= $sc['id'] ?>" id="sc_<?= $sc['id'] ?>"
                               <?= in_array($sc['id'], array_map('intval',(array)old('concerns',$prodConcerns)), true)?'checked':'' ?>>
                        <label class="form-check-label small" for="sc_<?= $sc['id'] ?>"><?= e($sc['name']) ?></label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="d-flex gap-2 mb-4">
        <button type="submit" class="btn btn-primary"><?= $isEdit?'Save changes':'Create product' ?></button>
        <a href="<?= e(url('admin/products.php')) ?>" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>
<?php include __DIR__ . '/../admin/includes/footer.php'; ?>

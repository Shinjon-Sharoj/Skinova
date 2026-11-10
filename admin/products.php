<?php
require_once __DIR__ . '/../admin/includes/auth-check.php';
$pageTitle = 'Products';
$panel     = 'admin';

$q       = get_str('q');
$catId   = get_int('category');
$status  = get_str('status');
$perPage = ADMIN_PER_PAGE;
$page    = get_int('page', 1);

$where = []; $params = [];
if ($q) { $like = '%' . addcslashes($q,'%_\\') . '%'; $where[] = '(p.name LIKE ? OR p.sku LIKE ?)'; $params[] = $like; $params[] = $like; }
if ($catId) { $where[] = 'p.category_id = ?'; $params[] = $catId; }
if ($status === 'active') { $where[] = 'p.is_active = 1'; }
elseif ($status === 'inactive') { $where[] = 'p.is_active = 0'; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total = (int) db_value("SELECT COUNT(*) FROM products p $whereSql", $params, 0);
$pg    = paginate($total, $perPage, $page);
$products = db_all(
    "SELECT p.id, p.name, p.sku, p.price, p.discount_percent, p.is_active, p.is_featured, p.sold_count, p.rating_avg, p.rating_count, p.created_at,
            c.name AS category_name, COALESCE(i.quantity,0) AS stock,
            ROUND(p.price*(1-p.discount_percent/100),2) AS final_price
     FROM products p JOIN categories c ON c.id=p.category_id LEFT JOIN inventory i ON i.product_id=p.id
     $whereSql ORDER BY p.created_at DESC LIMIT ? OFFSET ?",
    array_merge($params, [$pg['per_page'], $pg['offset']])
);

$categories = get_categories(false);

// Handle quick toggle active/featured
if (is_post() && csrf_is_valid()) {
    $act = input('action');
    $pid = input_int('product_id');
    if ($act === 'toggle_active') { db_exec('UPDATE products SET is_active = NOT is_active WHERE id=?', [$pid]); }
    elseif ($act === 'toggle_featured') { db_exec('UPDATE products SET is_featured = NOT is_featured WHERE id=?', [$pid]); }
    elseif ($act === 'delete') {
        db_exec('UPDATE products SET is_active=0 WHERE id=?', [$pid]);
        flash('info', 'Product deactivated (soft delete).');
    }
    log_activity('admin_product_' . $act, 'product', $pid, null, (int)$authUser['id']);
    redirect('admin/products.php?' . http_build_query(array_filter(['q'=>$q,'category'=>$catId,'status'=>$status,'page'=>$page])));
}

include __DIR__ . '/../admin/includes/header.php';
admin_page_header('Products',
    '<a href="' . e(url('admin/product-form.php')) . '" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Add product</a>',
    $total . ' total'
);
?>
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-sm-4"><input type="search" name="q" class="form-control form-control-sm" placeholder="Name or SKU…" value="<?= e($q) ?>"></div>
            <div class="col-sm-auto">
                <select name="category" class="form-select form-select-sm">
                    <option value="">All categories</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $catId==$cat['id']?'selected':'' ?>><?= e($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-auto">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="active" <?= $status==='active'?'selected':'' ?>>Active</option>
                    <option value="inactive" <?= $status==='inactive'?'selected':'' ?>>Inactive</option>
                </select>
            </div>
            <div class="col-sm-auto"><button class="btn btn-primary btn-sm">Filter</button></div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Product</th><th>SKU</th><th>Category</th><th>Price</th><th>Stock</th><th>Sales</th><th>Status</th><th></th></tr></thead>
            <tbody>
                <?php if ($products): foreach ($products as $p): ?>
                <tr>
                    <td>
                        <div class="small fw-semibold"><?= e(truncate($p['name'],40)) ?></div>
                        <?php if ($p['is_featured']): ?><span class="badge text-bg-warning" style="font-size:.6rem">Featured</span><?php endif; ?>
                    </td>
                    <td class="small text-muted"><?= e($p['sku']) ?></td>
                    <td class="small"><?= e($p['category_name']) ?></td>
                    <td class="small">
                        <div class="fw-semibold text-primary"><?= e(money($p['final_price'])) ?></div>
                        <?php if ($p['discount_percent']>0): ?><small class="text-muted text-decoration-line-through"><?= e(money($p['price'])) ?></small><?php endif; ?>
                    </td>
                    <td class="small <?= $p['stock']<=0?'text-danger':($p['stock']<=5?'text-warning':'') ?>"><?= number_format($p['stock']) ?></td>
                    <td class="small"><?= number_format($p['sold_count']) ?></td>
                    <td><?= $p['is_active'] ? '<span class="badge text-bg-success">Active</span>' : '<span class="badge text-bg-secondary">Inactive</span>' ?></td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="<?= e(url('admin/product-form.php?id=' . $p['id'])) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <form method="post" class="d-inline">
                                <?= csrf_field() ?><input type="hidden" name="action" value="toggle_active"><input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                <button class="btn btn-sm btn-outline-<?= $p['is_active']?'warning':'success' ?>" title="<?= $p['is_active']?'Deactivate':'Activate' ?>"><i class="bi bi-<?= $p['is_active']?'eye-slash':'eye' ?>"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; else: admin_empty_row(8,'No products found.'); endif; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3"><?= render_pagination($pg) ?></div>
<?php include __DIR__ . '/../admin/includes/footer.php'; ?>

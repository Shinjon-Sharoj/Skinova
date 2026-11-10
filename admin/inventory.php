<?php
require_once __DIR__ . '/../admin/includes/auth-check.php';
$pageTitle = 'Inventory';
$panel     = 'admin';

$filter  = get_str('filter');  // '' | 'low' | 'out'
$q       = get_str('q');
$perPage = ADMIN_PER_PAGE;
$page    = get_int('page', 1);

$where = []; $params = [];
if ($q) { $like = '%' . addcslashes($q,'%_\\') . '%'; $where[] = '(p.name LIKE ? OR p.sku LIKE ?)'; $params[] = $like; $params[] = $like; }
if ($filter === 'low')  { $where[] = 'i.quantity > 0 AND i.quantity <= i.low_stock_threshold'; }
if ($filter === 'out')  { $where[] = 'i.quantity <= 0'; }
$where[] = 'p.is_active = 1';
$whereSql = 'WHERE ' . implode(' AND ', $where);

// Bulk update
if (is_post() && csrf_is_valid() && input('action') === 'bulk_update') {
    $updates = $_POST['qty'] ?? [];
    foreach ($updates as $pid => $qty) {
        $pid = (int)$pid; $qty = max(0,(int)$qty);
        db_exec('INSERT INTO inventory (product_id,quantity) VALUES(?,?) ON DUPLICATE KEY UPDATE quantity=VALUES(quantity)', [$pid,$qty]);
    }
    log_activity('admin_inventory_bulk_update','inventory',null,null,(int)$authUser['id']);
    flash('success','Inventory updated.');
    redirect('admin/inventory.php?' . http_build_query(array_filter(['filter'=>$filter,'q'=>$q])));
}

$total = (int) db_value("SELECT COUNT(*) FROM products p LEFT JOIN inventory i ON i.product_id=p.id $whereSql", $params, 0);
$pg    = paginate($total, $perPage, $page);
$items = db_all(
    "SELECT p.id, p.name, p.sku, c.name AS category_name,
            COALESCE(i.quantity,0) AS quantity, COALESCE(i.low_stock_threshold,5) AS low_stock_threshold
     FROM products p JOIN categories c ON c.id=p.category_id LEFT JOIN inventory i ON i.product_id=p.id
     $whereSql ORDER BY i.quantity ASC, p.name LIMIT ? OFFSET ?",
    array_merge($params, [$pg['per_page'], $pg['offset']])
);

include __DIR__ . '/../admin/includes/header.php';
admin_page_header('Inventory', '', $total . ' products');
?>
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-sm-4"><input type="search" name="q" class="form-control form-control-sm" placeholder="Product name / SKU…" value="<?= e($q) ?>"></div>
            <div class="col-sm-auto">
                <div class="btn-group btn-group-sm">
                    <a href="?<?= $q?'q='.urlencode($q).'&':'' ?>" class="btn <?= !$filter?'btn-primary':'btn-outline-secondary' ?>">All</a>
                    <a href="?filter=low<?= $q?'&q='.urlencode($q):'' ?>" class="btn <?= $filter==='low'?'btn-warning':'btn-outline-secondary' ?>">Low stock</a>
                    <a href="?filter=out<?= $q?'&q='.urlencode($q):'' ?>" class="btn <?= $filter==='out'?'btn-danger':'btn-outline-secondary' ?>">Out of stock</a>
                </div>
            </div>
        </form>
    </div>
</div>

<form method="post">
    <?= csrf_field() ?><input type="hidden" name="action" value="bulk_update">
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th>Product</th><th>SKU</th><th>Category</th><th>Stock</th><th>Low threshold</th><th>Status</th></tr></thead>
                <tbody>
                    <?php if ($items): foreach ($items as $it): ?>
                    <tr>
                        <td class="small fw-semibold"><?= e(truncate($it['name'],40)) ?></td>
                        <td class="small text-muted"><?= e($it['sku']) ?></td>
                        <td class="small"><?= e($it['category_name']) ?></td>
                        <td>
                            <input type="number" name="qty[<?= $it['id'] ?>]" class="form-control form-control-sm" style="width:90px" min="0" value="<?= (int)$it['quantity'] ?>">
                        </td>
                        <td class="small text-muted"><?= (int)$it['low_stock_threshold'] ?></td>
                        <td>
                            <?php if ($it['quantity'] <= 0): ?>
                            <span class="badge text-bg-danger">Out of stock</span>
                            <?php elseif ($it['quantity'] <= $it['low_stock_threshold']): ?>
                            <span class="badge text-bg-warning">Low stock</span>
                            <?php else: ?>
                            <span class="badge text-bg-success">In stock</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; else: admin_empty_row(6,'No products found.'); endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($items): ?>
    <div class="mt-3 d-flex gap-2">
        <button type="submit" class="btn btn-primary">Save changes</button>
        <a href="admin/inventory.php" class="btn btn-outline-secondary">Cancel</a>
    </div>
    <?php endif; ?>
</form>
<div class="mt-2"><?= render_pagination($pg) ?></div>
<?php include __DIR__ . '/../admin/includes/footer.php'; ?>

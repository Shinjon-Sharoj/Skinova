<?php
require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Compare Products';
$extraCss  = ['css/skincare.css', 'css/products.css'];

// Parse ids from ?ids=1,2,3
$idsParam = get_str('ids');
$ids      = array_unique(array_filter(array_map('intval', explode(',', $idsParam))));
$ids      = array_slice($ids, 0, 4); // Max 4 products

$products = $ids ? get_products_by_ids($ids) : [];

// Full details for each
$details = [];
foreach ($products as $p) {
    $d = get_product_details((int) $p['id']);
    if ($d) $details[$p['id']] = $d;
}

// Fields to compare
$compareFields = [
    'Brand'             => fn($p) => e($p['brand_name'] ?? '-'),
    'Category'          => fn($p) => e($p['category_name'] ?? '-'),
    'Price'             => fn($p) => '<span class="fw-bold text-primary">' . e(money($p['final_price'])) . '</span>',
    'Discount'          => fn($p) => $p['discount_percent'] > 0 ? '<span class="text-danger">' . rtrim(rtrim(number_format((float)$p['discount_percent'],2),'0'),'.') . '%</span>' : '-',
    'Rating'            => fn($p) => render_stars((float)$p['rating_avg'], true),
    'Reviews'           => fn($p) => number_format((int)$p['rating_count']),
    'Stock'             => fn($p) => (int)$p['stock'] > 0 ? '<span class="text-success">In stock</span>' : '<span class="text-danger">Out of stock</span>',
    'Routine step'      => fn($p) => e(ucfirst(str_replace('_',' ',$p['routine_step']))),
    'Size'              => fn($p) => e($p['size_label'] ?? '-'),
    'Skin types'        => function($p) use ($details) {
        $d = $details[$p['id']] ?? null;
        if (!$d || !$d['skin_types']) return '-';
        return implode(', ', array_map('ucfirst', $d['skin_types']));
    },
    'Concerns'          => function($p) use ($details) {
        $d = $details[$p['id']] ?? null;
        if (!$d || !$d['concerns']) return '-';
        return implode(', ', array_column($d['concerns'], 'name'));
    },
];

include __DIR__ . '/includes/header.php';
?>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Compare Products</h1>
        <?php if ($products): ?>
        <a href="?" class="btn btn-outline-secondary btn-sm">Clear comparison</a>
        <?php endif; ?>
    </div>

    <!-- Add product form -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="get" action="" class="d-flex gap-2 align-items-end flex-wrap">
                <div class="flex-grow-1">
                    <label class="form-label small fw-semibold">Add a product to compare (up to 4)</label>
                    <input type="text" name="add_slug" class="form-control form-control-sm"
                           placeholder="Type product name or slug…"
                           list="productSuggest" autocomplete="off">
                    <datalist id="productSuggest">
                        <?php
                        $allProds = db_all('SELECT id, name, slug FROM products WHERE is_active=1 ORDER BY sold_count DESC LIMIT 50');
                        foreach ($allProds as $ap): ?>
                        <option value="<?= e($ap['slug']) ?>"><?= e($ap['name']) ?></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <input type="hidden" name="ids" value="<?= e($idsParam) ?>">
                <button type="submit" class="btn btn-primary btn-sm">Add</button>
            </form>
            <?php
            // Handle add_slug
            $addSlug = get_str('add_slug');
            if ($addSlug) {
                $addProd = db_one('SELECT id FROM products WHERE slug = ? AND is_active = 1', [$addSlug]);
                if ($addProd && !in_array((int)$addProd['id'], $ids, true)) {
                    $ids[] = (int)$addProd['id'];
                    $newIds = implode(',', array_slice($ids, 0, 4));
                    header('Location: ' . url('product-comparison.php?ids=' . $newIds));
                    exit;
                }
            }
            ?>
        </div>
    </div>

    <?php if ($products): ?>
    <!-- Comparison table -->
    <div class="table-responsive">
        <table class="table table-bordered comparison-table">
            <thead>
                <tr>
                    <th style="width:160px">Product</th>
                    <?php foreach ($products as $p): ?>
                    <th class="text-center">
                        <div class="mb-2">
                            <img src="<?= e(product_image_url($p['image_path'] ?? null)) ?>"
                                 alt="<?= e($p['name']) ?>" class="img-fluid"
                                 style="max-height:100px;object-fit:contain;background:#f8f9fa">
                        </div>
                        <a href="<?= e(url('product-details.php?slug=' . urlencode($p['slug']))) ?>"
                           class="text-decoration-none small fw-semibold text-dark"><?= e(truncate($p['name'], 50)) ?></a>
                        <div class="mt-1">
                            <a href="<?= e(url('product-comparison.php?ids=' . implode(',', array_filter($ids, fn($i) => $i !== (int)$p['id'])))) ?>"
                               class="badge text-bg-light border text-decoration-none">✕ Remove</a>
                        </div>
                    </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($compareFields as $label => $fn): ?>
                <tr>
                    <td class="text-muted small fw-semibold"><?= e($label) ?></td>
                    <?php foreach ($products as $p): ?>
                    <td class="text-center small"><?= $fn($p) ?></td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
                <!-- Add to cart row -->
                <tr>
                    <td></td>
                    <?php foreach ($products as $p): ?>
                    <td class="text-center">
                        <button class="btn btn-primary btn-sm w-100" data-action="add-to-cart"
                                data-product-id="<?= $p['id'] ?>"
                                <?= $p['stock'] <= 0 ? 'disabled' : '' ?>>
                            <i class="bi bi-cart-plus"></i> <?= $p['stock'] > 0 ? 'Add to cart' : 'Out of stock' ?>
                        </button>
                    </td>
                    <?php endforeach; ?>
                </tr>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="text-center py-5 text-muted">
        <i class="bi bi-columns-gap" style="font-size:3rem"></i>
        <p class="mt-2">No products selected for comparison yet.</p>
        <p class="small">Add products using the form above, or click the "Compare" button on any product page.</p>
    </div>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

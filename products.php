<?php
require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Products';
$extraCss  = ['css/products.css'];

// Filters from GET
$f = [
    'q'          => get_str('q'),
    'category'   => get_str('category'),
    'brand'      => get_str('brand'),
    'skin_type'  => get_str('skin_type'),
    'concern'    => get_str('concern'),
    'min_price'  => get_str('min_price'),
    'max_price'  => get_str('max_price'),
    'sort'       => get_str('sort') ?: 'popular',
    'in_stock'   => get_str('in_stock') ? true : false,
    'on_sale'    => get_str('on_sale') ? true : false,
    'featured'   => get_str('featured') ? true : false,
];

$page    = get_int('page', 1);
$result  = get_products($f, $page);
$items   = $result['items'];
$pg      = $result['page'];

// Sidebar data
$categories    = get_categories();
$brands        = get_brands();
$skinConcerns  = get_skin_concerns();

// Active category/brand names for breadcrumb
$activeCat   = $f['category'] ? db_one('SELECT name, slug FROM categories WHERE ' . (is_numeric($f['category']) ? 'id' : 'slug') . ' = ?', [$f['category']]) : null;
$activeBrand = $f['brand']    ? db_one('SELECT name, slug FROM brands WHERE '    . (is_numeric($f['brand'])    ? 'id' : 'slug') . ' = ?', [$f['brand']]) : null;

if ($activeCat)   $pageTitle = $activeCat['name'];
elseif ($activeBrand) $pageTitle = $activeBrand['name'];
elseif ($f['q'])  $pageTitle = 'Search: ' . $f['q'];

// Log search
if ($f['q']) log_search($f['q'], $pg['total']);

$sortOptions = [
    'popular'    => 'Most popular',
    'newest'     => 'Newest first',
    'price_asc'  => 'Price: low to high',
    'price_desc' => 'Price: high to low',
    'rating'     => 'Top rated',
    'name'       => 'Name A–Z',
];

include __DIR__ . '/includes/header.php';
?>
<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= e(url('index.php')) ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= e(url('products.php')) ?>">Products</a></li>
            <?php if ($activeCat): ?>
            <li class="breadcrumb-item active"><?= e($activeCat['name']) ?></li>
            <?php elseif ($activeBrand): ?>
            <li class="breadcrumb-item active"><?= e($activeBrand['name']) ?></li>
            <?php elseif ($f['q']): ?>
            <li class="breadcrumb-item active">Search results</li>
            <?php endif; ?>
        </ol>
    </nav>

    <div class="row g-4">
        <!-- ===== Sidebar ===== -->
        <div class="col-lg-3">
            <form method="get" action="" id="filterForm">
                <div class="card shadow-sm mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Filters</span>
                        <a href="<?= e(url('products.php')) ?>" class="btn btn-link btn-sm p-0">Clear all</a>
                    </div>
                    <div class="card-body p-3">

                        <!-- Search -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Search</label>
                            <input type="search" name="q" class="form-control form-control-sm" value="<?= e($f['q']) ?>" placeholder="Product name, brand…">
                        </div>

                        <!-- Categories -->
                        <?php if ($categories): ?>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Category</label>
                            <?php foreach ($categories as $cat): if ($cat['parent_id']) continue; ?>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="category"
                                       id="cat_<?= $cat['id'] ?>" value="<?= e($cat['slug']) ?>"
                                       <?= ($f['category'] === $cat['slug'] || $f['category'] == $cat['id']) ? 'checked' : '' ?>>
                                <label class="form-check-label small" for="cat_<?= $cat['id'] ?>">
                                    <?= e($cat['name']) ?> <span class="text-muted">(<?= (int)($cat['product_count'] ?? 0) ?>)</span>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <!-- Brands -->
                        <?php if ($brands): ?>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Brand</label>
                            <div style="max-height:160px;overflow-y:auto">
                                <?php foreach ($brands as $brand): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="brand"
                                           id="brand_<?= $brand['id'] ?>" value="<?= e($brand['slug']) ?>"
                                           <?= ($f['brand'] === $brand['slug'] || $f['brand'] == $brand['id']) ? 'checked' : '' ?>>
                                    <label class="form-check-label small" for="brand_<?= $brand['id'] ?>"><?= e($brand['name']) ?></label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Price -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Price range</label>
                            <div class="input-group input-group-sm">
                                <input type="number" name="min_price" class="form-control" placeholder="Min" value="<?= e($f['min_price']) ?>" min="0" step="0.01">
                                <span class="input-group-text">–</span>
                                <input type="number" name="max_price" class="form-control" placeholder="Max" value="<?= e($f['max_price']) ?>" min="0" step="0.01">
                            </div>
                        </div>

                        <!-- Skin type -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Skin type</label>
                            <?php foreach (SKIN_TYPES as $st): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="skin_type"
                                       id="st_<?= $st ?>" value="<?= $st ?>"
                                       <?= $f['skin_type'] === $st ? 'checked' : '' ?>>
                                <label class="form-check-label small" for="st_<?= $st ?>"><?= ucfirst($st) ?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Concerns -->
                        <?php if ($skinConcerns): ?>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Skin concern</label>
                            <div style="max-height:160px;overflow-y:auto">
                                <?php foreach ($skinConcerns as $sc): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="concern"
                                           id="sc_<?= $sc['id'] ?>" value="<?= e($sc['slug']) ?>"
                                           <?= ($f['concern'] === $sc['slug'] || $f['concern'] == $sc['id']) ? 'checked' : '' ?>>
                                    <label class="form-check-label small" for="sc_<?= $sc['id'] ?>"><?= e($sc['name']) ?></label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Toggles -->
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="in_stock" id="in_stock" value="1" <?= $f['in_stock'] ? 'checked' : '' ?>>
                                <label class="form-check-label small" for="in_stock">In stock only</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="on_sale" id="on_sale" value="1" <?= $f['on_sale'] ? 'checked' : '' ?>>
                                <label class="form-check-label small" for="on_sale">On sale only</label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-sm w-100">Apply filters</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- ===== Product grid ===== -->
        <div class="col-lg-9">
            <!-- Sort / results bar -->
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                <p class="mb-0 text-muted small">
                    <?= number_format($pg['total']) ?> product<?= $pg['total'] !== 1 ? 's' : '' ?>
                    <?php if ($f['q']): ?> for <strong><?= e($f['q']) ?></strong><?php endif; ?>
                </p>
                <div class="d-flex gap-2">
                    <select class="form-select form-select-sm" style="width:auto" onchange="this.form?.submit()" id="sortSelect"
                            onchange="window.location.href=this.dataset.base+'&sort='+this.value"
                            data-base="<?= e(url('products.php?' . http_build_query(array_filter(array_diff_key($f, ['sort'=>1]))))) ?>">
                        <?php foreach ($sortOptions as $val => $label): ?>
                        <option value="<?= e($val) ?>" <?= $f['sort'] === $val ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <?php render_product_grid($items, 'No products match your filters.'); ?>
            <div class="mt-4"><?= render_pagination($pg) ?></div>
        </div>
    </div>
</div>

<script>
document.getElementById('sortSelect')?.addEventListener('change', function() {
    var base = this.dataset.base;
    window.location.href = base + (base.includes('?') ? '&' : '?') + 'sort=' + this.value;
});
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>

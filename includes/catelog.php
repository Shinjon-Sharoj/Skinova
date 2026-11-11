<?php
/**
 * Catalog helpers: product queries, filtering/sorting, product cards, categories, brands.
 *
 * All product list queries go through PRODUCT_SELECT so every list exposes the same columns:
 *   p.* plus brand_name, brand_slug, category_name, category_slug, final_price, stock, image_path
 */

const PRODUCT_SELECT = "SELECT p.*, b.name AS brand_name, b.slug AS brand_slug, c.name AS category_name, c.slug AS category_slug,
        ROUND(p.price * (1 - p.discount_percent / 100), 2) AS final_price,
        COALESCE(i.quantity, 0) AS stock,
        (SELECT pi.image_path FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.is_primary DESC, pi.sort_order ASC, pi.id ASC LIMIT 1) AS image_path
    FROM products p
    JOIN categories c ON c.id = p.category_id
    LEFT JOIN brands b ON b.id = p.brand_id
    LEFT JOIN inventory i ON i.product_id = p.id";

function effective_price(float $price, float $discountPercent): float
{
    return round($price * (1 - $discountPercent / 100), 2);
}

function like_escape(string $s): string
{
    return addcslashes($s, '%_\\');
}

function product_url(array $p): string
{
    return url('product-details.php?slug=' . urlencode($p['slug']));
}

function product_image_url(?string $path): string
{
    return media_url($path, 'product');
}

/**
 * Filtered / sorted / paginated product list.
 *
 * Filters ($f): q, category (id|slug), brand (id|slug), min_price, max_price, skin_type, concern (id|slug),
 *               featured (bool), in_stock (bool), on_sale (bool), exclude_ids (array), include_inactive (bool), sort
 * sort: popular | newest | price_asc | price_desc | rating | name
 *
 * @return array{items:array,total:int,page:array}
 */
function get_products(array $f = [], ?int $page = null, int $perPage = PRODUCTS_PER_PAGE): array
{
    $where = [];
    $params = [];

    if (empty($f['include_inactive'])) {
        $where[] = 'p.is_active = 1 AND c.is_active = 1';
    }
    if (!empty($f['q'])) {
        $like = '%' . like_escape(trim((string) $f['q'])) . '%';
        $where[] = '(p.name LIKE ? OR b.name LIKE ? OR p.short_description LIKE ? OR c.name LIKE ?)';
        array_push($params, $like, $like, $like, $like);
    }
    if (!empty($f['category'])) {
        $cat = is_numeric($f['category'])
            ? db_one('SELECT id FROM categories WHERE id = ?', [(int) $f['category']])
            : db_one('SELECT id FROM categories WHERE slug = ?', [(string) $f['category']]);
        if ($cat) {
            $where[] = '(p.category_id = ? OR c.parent_id = ?)';
            array_push($params, $cat['id'], $cat['id']);
        } else {
            $where[] = '1 = 0';
        }
    }
    if (!empty($f['brand'])) {
        if (is_numeric($f['brand'])) {
            $where[] = 'p.brand_id = ?';
            $params[] = (int) $f['brand'];
        } else {
            $where[] = 'b.slug = ?';
            $params[] = (string) $f['brand'];
        }
    }
    $finalExpr = 'ROUND(p.price * (1 - p.discount_percent / 100), 2)';
    if (isset($f['min_price']) && $f['min_price'] !== '' && is_numeric($f['min_price'])) {
        $where[] = "$finalExpr >= ?";
        $params[] = (float) $f['min_price'];
    }
    if (isset($f['max_price']) && $f['max_price'] !== '' && is_numeric($f['max_price'])) {
        $where[] = "$finalExpr <= ?";
        $params[] = (float) $f['max_price'];
    }
    if (!empty($f['skin_type']) && in_array($f['skin_type'], SKIN_TYPES, true)) {
        $where[] = 'EXISTS (SELECT 1 FROM product_skin_types pst WHERE pst.product_id = p.id AND pst.skin_type = ?)';
        $params[] = $f['skin_type'];
    }
    if (!empty($f['concern'])) {
        if (is_numeric($f['concern'])) {
            $where[] = 'EXISTS (SELECT 1 FROM product_skin_concerns psc WHERE psc.product_id = p.id AND psc.skin_concern_id = ?)';
            $params[] = (int) $f['concern'];
        } else {
            $where[] = 'EXISTS (SELECT 1 FROM product_skin_concerns psc JOIN skin_concerns sc ON sc.id = psc.skin_concern_id WHERE psc.product_id = p.id AND sc.slug = ?)';
            $params[] = (string) $f['concern'];
        }
    }
    if (!empty($f['featured'])) {
        $where[] = 'p.is_featured = 1';
    }
    if (!empty($f['in_stock'])) {
        $where[] = 'COALESCE(i.quantity, 0) > 0';
    }
    if (!empty($f['on_sale'])) {
        $where[] = 'p.discount_percent > 0';
    }
    if (!empty($f['exclude_ids'])) {
        $ids = array_map('intval', (array) $f['exclude_ids']);
        $where[] = 'p.id NOT IN (' . db_placeholders($ids) . ')';
        array_push($params, ...$ids);
    }

    $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
    $orderSql = [
        'popular'    => 'p.sold_count DESC, p.view_count DESC, p.id DESC',
        'newest'     => 'p.created_at DESC, p.id DESC',
        'price_asc'  => 'final_price ASC, p.id ASC',
        'price_desc' => 'final_price DESC, p.id ASC',
        'rating'     => 'p.rating_avg DESC, p.rating_count DESC, p.id DESC',
        'name'       => 'p.name ASC',
    ][$f['sort'] ?? 'popular'] ?? 'p.sold_count DESC, p.id DESC';

    $total = (int) db_value(
        'SELECT COUNT(*) FROM products p JOIN categories c ON c.id = p.category_id LEFT JOIN brands b ON b.id = p.brand_id LEFT JOIN inventory i ON i.product_id = p.id' . $whereSql,
        $params,
        0
    );
    $pg = paginate($total, $perPage, $page);
    $items = db_all(PRODUCT_SELECT . $whereSql . " ORDER BY $orderSql LIMIT {$pg['per_page']} OFFSET {$pg['offset']}", $params);

    return ['items' => $items, 'total' => $total, 'page' => $pg];
}

function get_product(int $id, bool $includeInactive = false): ?array
{
    return db_one(PRODUCT_SELECT . ' WHERE p.id = ?' . ($includeInactive ? '' : ' AND p.is_active = 1'), [$id]);
}

function get_product_by_slug(string $slug, bool $includeInactive = false): ?array
{
    return db_one(PRODUCT_SELECT . ' WHERE p.slug = ?' . ($includeInactive ? '' : ' AND p.is_active = 1'), [$slug]);
}

/** Products by id list preserving the given order. */
function get_products_by_ids(array $ids): array
{
    $ids = array_values(array_unique(array_map('intval', $ids)));
    if (!$ids) return [];
    $rows = db_all(PRODUCT_SELECT . ' WHERE p.is_active = 1 AND p.id IN (' . db_placeholders($ids) . ')', $ids);
    $byId = [];
    foreach ($rows as $r) $byId[(int) $r['id']] = $r;
    $out = [];
    foreach ($ids as $id) if (isset($byId[$id])) $out[] = $byId[$id];
    return $out;
}

/** Full details for the product page: images, ingredients, skin_types, concerns. */
function get_product_details(int $id): ?array
{
    $p = get_product($id);
    if (!$p) return null;
    $p['images'] = db_all('SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order, id', [$id]);
    $p['ingredients'] = db_all(
        'SELECT i.* FROM product_ingredients pi JOIN ingredients i ON i.id = pi.ingredient_id WHERE pi.product_id = ? ORDER BY pi.position, i.name',
        [$id]
    );
    $p['skin_types'] = array_column(db_all('SELECT skin_type FROM product_skin_types WHERE product_id = ? ORDER BY skin_type', [$id]), 'skin_type');
    $p['concerns'] = db_all(
        'SELECT sc.* FROM product_skin_concerns psc JOIN skin_concerns sc ON sc.id = psc.skin_concern_id WHERE psc.product_id = ? ORDER BY sc.name',
        [$id]
    );
    return $p;
}

/** Related/similar: same category first, then shared concerns / skin types. */
function get_related_products(array $product, int $limit = 4): array
{
    $id = (int) $product['id'];
    return db_all(
        PRODUCT_SELECT . ' WHERE p.is_active = 1 AND p.id <> ?
         ORDER BY (p.category_id = ?) DESC,
                  (SELECT COUNT(*) FROM product_skin_concerns a JOIN product_skin_concerns b ON b.skin_concern_id = a.skin_concern_id AND b.product_id = p.id WHERE a.product_id = ?) DESC,
                  (SELECT COUNT(*) FROM product_skin_types a JOIN product_skin_types b ON b.skin_type = a.skin_type AND b.product_id = p.id WHERE a.product_id = ?) DESC,
                  p.sold_count DESC
         LIMIT ' . (int) $limit,
        [$id, $product['category_id'], $id, $id]
    );
}

function get_best_sellers(int $limit = 8): array
{
    return get_products(['sort' => 'popular'], 1, $limit)['items'];
}

function get_new_arrivals(int $limit = 8): array
{
    return get_products(['sort' => 'newest'], 1, $limit)['items'];
}

function get_featured_products(int $limit = 8): array
{
    return get_products(['featured' => true, 'sort' => 'popular'], 1, $limit)['items'];
}

function get_categories(bool $onlyActive = true): array
{
    return db_all(
        'SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.is_active = 1 AND (p.category_id = c.id OR p.category_id IN (SELECT id FROM categories ch WHERE ch.parent_id = c.id))) AS product_count
         FROM categories c ' . ($onlyActive ? 'WHERE c.is_active = 1 ' : '') . 'ORDER BY c.sort_order, c.name'
    );
}

function get_brands(bool $onlyActive = true): array
{
    return db_all('SELECT * FROM brands ' . ($onlyActive ? 'WHERE is_active = 1 ' : '') . 'ORDER BY name');
}

function get_skin_concerns(): array
{
    return db_all('SELECT * FROM skin_concerns ORDER BY name');
}

function get_all_ingredients(): array
{
    return db_all('SELECT * FROM ingredients ORDER BY name');
}

/**
 * A user's skin profile for personalisation (recommendations, routines, consultations).
 * @return array{profile:?array,concerns:array,sensitivities:array,consent:bool}
 *   profile: skin_profiles row or null; concerns: [{id,name,slug,severity}];
 *   sensitivities: ingredient rows the user reported; consent: user agreed to skin-data use
 */
function get_skin_profile(int $userId): array
{
    $profile = db_one('SELECT * FROM skin_profiles WHERE user_id = ?', [$userId]);
    $concerns = db_all(
        'SELECT sc.id, sc.name, sc.slug, usc.severity FROM user_skin_concerns usc JOIN skin_concerns sc ON sc.id = usc.skin_concern_id WHERE usc.user_id = ? ORDER BY sc.name',
        [$userId]
    );
    $sens = db_all(
        'SELECT i.* FROM user_ingredient_sensitivities uis JOIN ingredients i ON i.id = uis.ingredient_id WHERE uis.user_id = ? ORDER BY i.name',
        [$userId]
    );
    return ['profile' => $profile, 'concerns' => $concerns, 'sensitivities' => $sens, 'consent' => $profile ? (bool) $profile['consent_given'] : false];
}

/** Recalculate products.rating_avg / rating_count from APPROVED reviews. Call after review moderation. */
function refresh_product_rating(int $productId): void
{
    db_exec(
        "UPDATE products SET rating_avg = COALESCE((SELECT ROUND(AVG(rating), 2) FROM reviews WHERE product_id = ? AND status = 'approved'), 0),
                rating_count = (SELECT COUNT(*) FROM reviews WHERE product_id = ? AND status = 'approved') WHERE id = ?",
        [$productId, $productId, $productId]
    );
}

/** Increment the view counter (once per session per product) and record recently-viewed. */
function track_product_view(int $productId): void
{
    if (empty($_SESSION['_viewed'][$productId])) {
        $_SESSION['_viewed'][$productId] = 1;
        db_exec('UPDATE products SET view_count = view_count + 1 WHERE id = ?', [$productId]);
    }
    if ($uid = current_user_id()) {
        db_exec(
            'INSERT INTO recently_viewed (user_id, product_id, viewed_at) VALUES (?,?,NOW()) ON DUPLICATE KEY UPDATE viewed_at = NOW()',
            [$uid, $productId]
        );
        db_exec(
            'DELETE FROM recently_viewed WHERE user_id = ? AND id NOT IN (SELECT id FROM (SELECT id FROM recently_viewed WHERE user_id = ? ORDER BY viewed_at DESC LIMIT 20) t)',
            [$uid, $uid]
        );
    }
}

function get_recently_viewed(int $userId, int $limit = 6, ?int $excludeProductId = null): array
{
    $params = [$userId];
    $extra = '';
    if ($excludeProductId) {
        $extra = ' AND p.id <> ?';
        $params[] = $excludeProductId;
    }
    return db_all(
        PRODUCT_SELECT . ' JOIN recently_viewed rv ON rv.product_id = p.id WHERE rv.user_id = ? AND p.is_active = 1' . $extra . ' ORDER BY rv.viewed_at DESC LIMIT ' . (int) $limit,
        $params
    );
}

function log_search(string $term, int $resultsCount): void
{
    $term = mb_strtolower(mb_substr(trim($term), 0, 190));
    if ($term === '') return;
    try {
        db_exec('INSERT INTO search_logs (user_id, term, results_count) VALUES (?,?,?)', [current_user_id(), $term, $resultsCount]);
    } catch (Throwable $e) {
    }
}

/** Set (flipped array) of product ids in the user's wishlist, cached per request. */
function wishlist_product_ids(?int $userId = null): array
{
    static $cache = [];
    $userId = $userId ?? current_user_id();
    if (!$userId) return [];
    if (!isset($cache[$userId])) {
        $rows = db_all('SELECT wi.product_id FROM wishlist_items wi JOIN wishlists w ON w.id = wi.wishlist_id WHERE w.user_id = ?', [$userId]);
        $cache[$userId] = array_flip(array_map('intval', array_column($rows, 'product_id')));
    }
    return $cache[$userId];
}

/**
 * Echo a Bootstrap product card. Buttons carry data attributes handled globally by
 * assets/js/main.js (add to cart / wishlist toggle through api/cart.php & api/wishlist.php).
 */
function render_product_card(array $p, string $colClass = 'col-6 col-md-4 col-lg-3'): void
{
    $final = (float) ($p['final_price'] ?? effective_price((float) $p['price'], (float) $p['discount_percent']));
    $inStock = (int) ($p['stock'] ?? 0) > 0;
    $inWishlist = isset(wishlist_product_ids()[(int) $p['id']]);
    $link = product_url($p);
    ?>
    <div class="<?= e($colClass) ?>">
        <div class="card product-card h-100 shadow-sm">
            <div class="product-card-img position-relative">
                <a href="<?= e($link) ?>">
                    <img src="<?= e(product_image_url($p['image_path'] ?? null)) ?>" class="card-img-top" alt="<?= e($p['name']) ?>" loading="lazy" width="400" height="400">
                </a>
                <?php if ((float) $p['discount_percent'] > 0): ?>
                    <span class="badge bg-danger position-absolute top-0 start-0 m-2">-<?= e(rtrim(rtrim(number_format((float) $p['discount_percent'], 2), '0'), '.')) ?>%</span>
                <?php endif; ?>
                <?php if (!$inStock): ?>
                    <span class="badge bg-secondary position-absolute top-0 end-0 m-2">Out of stock</span>
                <?php endif; ?>
                <button type="button" class="btn btn-light btn-sm rounded-circle wishlist-btn position-absolute bottom-0 end-0 m-2 shadow-sm<?= $inWishlist ? ' active' : '' ?>"
                        data-action="toggle-wishlist" data-product-id="<?= (int) $p['id'] ?>" aria-label="Toggle wishlist" aria-pressed="<?= $inWishlist ? 'true' : 'false' ?>">
                    <i class="bi <?= $inWishlist ? 'bi-heart-fill text-danger' : 'bi-heart' ?>"></i>
                </button>
            </div>
            <div class="card-body d-flex flex-column">
                <?php if (!empty($p['brand_name'])): ?><small class="text-muted text-uppercase"><?= e($p['brand_name']) ?></small><?php endif; ?>
                <h3 class="h6 card-title mb-1"><a class="text-decoration-none text-dark" href="<?= e($link) ?>"><?= e($p['name']) ?></a></h3>
                <div class="mb-2 small">
                    <?= render_stars((float) $p['rating_avg']) ?> <span class="text-muted">(<?= (int) $p['rating_count'] ?>)</span>
                </div>
                <div class="mt-auto">
                    <div class="mb-2">
                        <span class="fw-bold text-primary"><?= e(money($final)) ?></span>
                        <?php if ((float) $p['discount_percent'] > 0): ?>
                            <small class="text-muted text-decoration-line-through ms-1"><?= e(money($p['price'])) ?></small>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm w-100" data-action="add-to-cart" data-product-id="<?= (int) $p['id'] ?>" <?= $inStock ? '' : 'disabled' ?>>
                        <i class="bi bi-cart-plus"></i> <?= $inStock ? 'Add to cart' : 'Unavailable' ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php
}

/** Echo a responsive row of product cards (or an empty state). */
function render_product_grid(array $products, string $emptyMessage = 'No products found.', string $colClass = 'col-6 col-md-4 col-lg-3'): void
{
    if (!$products) {
        echo '<div class="empty-state text-center py-5"><i class="bi bi-search display-4 text-muted"></i><p class="mt-3 text-muted">' . e($emptyMessage) . '</p></div>';
        return;
    }
    echo '<div class="row g-3 g-lg-4">';
    foreach ($products as $p) {
        render_product_card($p, $colClass);
    }
    echo '</div>';
}
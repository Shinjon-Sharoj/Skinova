<?php
/** Main site navigation. Included by includes/header.php. */
$__u = current_user();
$__cartCount = $__u ? cart_count((int) $__u['id']) : 0;
$__wishCount = $__u ? count(wishlist_product_ids((int) $__u['id'])) : 0;
$__unread = $__u ? unread_notification_count((int) $__u['id']) : 0;
$__recent = $__u ? recent_notifications((int) $__u['id'], 5) : [];
$__navCategories = get_categories();
$__script = basename($_SERVER['SCRIPT_NAME'] ?? '');
$__isActive = function (array $files) use ($__script) {
    return in_array($__script, $files, true) ? ' active' : '';
};
?>
<header class="site-header sticky-top shadow-sm">
    <div class="topbar bg-primary text-white small d-none d-md-block">
        <div class="container d-flex justify-content-between py-1">
            <span><i class="bi bi-truck"></i> Free delivery over <?= e(money(get_setting('free_shipping_threshold', 60))) ?></span>
            <span><a class="text-white text-decoration-none" href="<?= e(url('consultations.php')) ?>"><i class="bi bi-person-heart"></i> Chat with a skincare expert</a></span>
        </div>
    </div>
    <nav class="navbar navbar-expand-lg bg-white" aria-label="Main navigation">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="<?= e(url('index.php')) ?>">
                <img src="<?= e(url('assets/images/placeholders/favicon.svg')) ?>" alt="" width="32" height="32">
                <span class="brand-text"><?= e(APP_NAME) ?></span>
            </a>

            <div class="d-flex align-items-center gap-1 order-lg-last">
                <a class="btn btn-link nav-icon position-relative" href="<?= e(url('wishlist.php')) ?>" aria-label="Wishlist">
                    <i class="bi bi-heart fs-5"></i>
                    <span class="badge rounded-pill bg-danger nav-badge<?= $__wishCount ? '' : ' d-none' ?>" data-badge="wishlist"><?= (int) $__wishCount ?></span>
                </a>
                <a class="btn btn-link nav-icon position-relative" href="<?= e(url('cart.php')) ?>" aria-label="Shopping cart">
                    <i class="bi bi-bag fs-5"></i>
                    <span class="badge rounded-pill bg-danger nav-badge<?= $__cartCount ? '' : ' d-none' ?>" data-badge="cart"><?= (int) $__cartCount ?></span>
                </a>
                <?php if ($__u): ?>
                    <div class="dropdown">
                        <a class="btn btn-link nav-icon position-relative" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications">
                            <i class="bi bi-bell fs-5"></i>
                            <span class="badge rounded-pill bg-danger nav-badge<?= $__unread ? '' : ' d-none' ?>" data-badge="notifications"><?= (int) $__unread ?></span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end p-0 notif-menu" style="min-width:320px">
                            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                                <strong>Notifications</strong>
                                <a class="small" href="<?= e(url('notifications.php')) ?>">View all</a>
                            </div>
                            <div>
                                <?php foreach ($__recent as $__n): ?>
                                    <a class="dropdown-item d-flex gap-2 py-2 border-bottom<?= $__n['is_read'] ? '' : ' bg-light fw-semibold' ?>" href="<?= e(url('notifications.php?open=' . (int) $__n['id'])) ?>">
                                        <i class="bi <?= e(notification_icon($__n['type'])) ?> text-primary mt-1"></i>
                                        <span class="text-wrap small"><?= e($__n['title']) ?><br><span class="text-muted fw-normal"><?= e(time_ago($__n['created_at'])) ?></span></span>
                                    </a>
                                <?php endforeach; ?>
                                <?php if (!$__recent): ?>
                                    <div class="p-3 text-muted small text-center">No notifications yet.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="dropdown">
                        <a class="btn btn-link nav-icon d-flex align-items-center gap-1" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Account menu">
                            <img src="<?= e(media_url($__u['avatar'] ?? null, 'avatar')) ?>" class="rounded-circle" width="30" height="30" alt="" style="object-fit:cover">
                            <span class="d-none d-xl-inline small"><?= e(explode(' ', $__u['full_name'])[0]) ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><h6 class="dropdown-header"><?= e($__u['full_name']) ?></h6></li>
                            <?php if ($__u['role'] === ROLE_ADMIN): ?>
                                <li><a class="dropdown-item" href="<?= e(url('admin/dashboard.php')) ?>"><i class="bi bi-speedometer2 me-2"></i>Admin panel</a></li>
                            <?php elseif ($__u['role'] === ROLE_CONSULTANT): ?>
                                <li><a class="dropdown-item" href="<?= e(url('consultant/dashboard.php')) ?>"><i class="bi bi-person-workspace me-2"></i>Consultant dashboard</a></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item" href="<?= e(url('profile.php')) ?>"><i class="bi bi-person me-2"></i>My profile</a></li>
                            <li><a class="dropdown-item" href="<?= e(url('orders.php')) ?>"><i class="bi bi-box-seam me-2"></i>My orders</a></li>
                            <li><a class="dropdown-item" href="<?= e(url('consultations.php')) ?>"><i class="bi bi-person-heart me-2"></i>Consultations</a></li>
                            <li><a class="dropdown-item" href="<?= e(url('skincare-profile.php')) ?>"><i class="bi bi-droplet-half me-2"></i>Skin profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="post" action="<?= e(url('logout.php')) ?>" class="px-3">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-outline-danger btn-sm w-100" type="submit"><i class="bi bi-box-arrow-right"></i> Log out</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a class="btn btn-outline-primary btn-sm ms-1" href="<?= e(url('login.php')) ?>">Log in</a>
                    <a class="btn btn-primary btn-sm d-none d-sm-inline-block" href="<?= e(url('register.php')) ?>">Sign up</a>
                <?php endif; ?>
                <button class="navbar-toggler ms-1" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>

            <div class="collapse navbar-collapse" id="mainNav">
                <form class="d-flex my-3 my-lg-0 mx-lg-3 flex-grow-1 nav-search" role="search" action="<?= e(url('search.php')) ?>" method="get">
                    <div class="input-group">
                        <input class="form-control" type="search" name="q" placeholder="Search products or brands" aria-label="Search" value="<?= e($_GET['q'] ?? '') ?>" maxlength="100" autocomplete="off">
                        <button class="btn btn-primary" type="submit" aria-label="Search"><i class="bi bi-search"></i></button>
                    </div>
                </form>
                <ul class="navbar-nav mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link<?= $__isActive(['index.php']) ?>" href="<?= e(url('index.php')) ?>">Home</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle<?= $__isActive(['products.php', 'categories.php', 'product-details.php']) ?>" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Shop</a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= e(url('products.php')) ?>">All products</a></li>
                            <li><a class="dropdown-item" href="<?= e(url('categories.php')) ?>">All categories</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <?php foreach ($__navCategories as $__c): if ($__c['parent_id']) continue; ?>
                                <li><a class="dropdown-item" href="<?= e(url('products.php?category=' . urlencode($__c['slug']))) ?>"><?= e($__c['name']) ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle<?= $__isActive(['skincare-profile.php', 'skincare-routine.php', 'recommendations.php', 'ingredient-analyzer.php', 'product-comparison.php', 'skin-analysis.php', 'ai-chatbot.php']) ?>" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Skin care tools</a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= e(url('skincare-profile.php')) ?>"><i class="bi bi-droplet-half me-2"></i>My skin profile</a></li>
                            <li><a class="dropdown-item" href="<?= e(url('recommendations.php')) ?>"><i class="bi bi-magic me-2"></i>Recommendations</a></li>
                            <li><a class="dropdown-item" href="<?= e(url('skincare-routine.php')) ?>"><i class="bi bi-list-check me-2"></i>Routine generator</a></li>
                            <li><a class="dropdown-item" href="<?= e(url('ingredient-analyzer.php')) ?>"><i class="bi bi-eyedropper me-2"></i>Ingredient analyzer</a></li>
                            <li><a class="dropdown-item" href="<?= e(url('product-comparison.php')) ?>"><i class="bi bi-columns-gap me-2"></i>Compare products</a></li>
                            <li><a class="dropdown-item" href="<?= e(url('skin-analysis.php')) ?>"><i class="bi bi-camera me-2"></i>Skin image analysis</a></li>
                            <li><a class="dropdown-item" href="<?= e(url('ai-chatbot.php')) ?>"><i class="bi bi-chat-heart me-2"></i>Skincare assistant</a></li>
                        </ul>
                    </li>
                    <li class="nav-item"><a class="nav-link<?= $__isActive(['consultations.php', 'consultation-details.php', 'messages.php']) ?>" href="<?= e(url('consultations.php')) ?>">Consult an expert</a></li>
                    <li class="nav-item"><a class="nav-link<?= $__isActive(['blog.php', 'blog-details.php']) ?>" href="<?= e(url('blog.php')) ?>">Blog</a></li>
                </ul>
            </div>
        </div>
    </nav>
</header>
<?php unset($__u, $__cartCount, $__wishCount, $__unread, $__recent, $__navCategories, $__script, $__isActive, $__c, $__n); ?>
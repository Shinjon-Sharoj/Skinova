<?php
/** Sidebar for the dashboard layout. Menu depends on $panel ('admin' | 'consultant'). */
$__cur = basename($_SERVER['SCRIPT_NAME'] ?? '');
if (($panel ?? 'admin') === 'consultant') {
    $__menu = [
        ['Overview', [
            ['consultant/dashboard.php', 'bi-speedometer2', 'Dashboard', ['dashboard.php']],
            ['consultant/consultations.php', 'bi-person-heart', 'Consultations', ['consultations.php', 'consultation-details.php']],
            ['consultant/messages.php', 'bi-chat-dots', 'Messages', ['messages.php']],
            ['consultant/profile.php', 'bi-person-badge', 'My profile', ['profile.php']],
        ]],
    ];
} else {
    $__menu = [
        ['Overview', [
            ['admin/dashboard.php', 'bi-speedometer2', 'Dashboard', ['dashboard.php']],
            ['admin/analytics.php', 'bi-graph-up', 'Analytics', ['analytics.php']],
            ['admin/reports.php', 'bi-file-earmark-bar-graph', 'Reports', ['reports.php']],
        ]],
        ['Catalog', [
            ['admin/products.php', 'bi-box', 'Products', ['products.php', 'product-form.php']],
            ['admin/categories.php', 'bi-diagram-3', 'Categories & brands', ['categories.php']],
            ['admin/inventory.php', 'bi-boxes', 'Inventory', ['inventory.php']],
        ]],
        ['Sales', [
            ['admin/orders.php', 'bi-bag-check', 'Orders', ['orders.php', 'order-details.php']],
            ['admin/payments.php', 'bi-credit-card', 'Payments', ['payments.php']],
            ['admin/refunds.php', 'bi-arrow-return-left', 'Returns & refunds', ['refunds.php']],
            ['admin/reviews.php', 'bi-star', 'Reviews', ['reviews.php']],
            ['admin/coupons.php', 'bi-ticket-perforated', 'Coupons', ['coupons.php']],
            ['admin/promotions.php', 'bi-lightning-charge', 'Promotions', ['promotions.php']],
        ]],
        ['People', [
            ['admin/users.php', 'bi-people', 'Users', ['users.php', 'user-details.php']],
            ['admin/consultants.php', 'bi-person-badge', 'Consultants', ['consultants.php']],
            ['admin/consultations.php', 'bi-person-heart', 'Consultations', ['consultations.php']],
        ]],
        ['Content & AI', [
            ['admin/blogs.php', 'bi-journal-text', 'Blog', ['blogs.php', 'blog-form.php']],
            ['admin/ai-knowledge.php', 'bi-book', 'AI knowledge base', ['ai-knowledge.php']],
            ['admin/ai-queries.php', 'bi-chat-square-text', 'AI queries', ['ai-queries.php']],
            ['admin/ai-recommendations.php', 'bi-magic', 'AI recommendations', ['ai-recommendations.php']],
        ]],
        ['System', [
            ['admin/notifications.php', 'bi-megaphone', 'Notifications', ['notifications.php']],
            ['admin/settings.php', 'bi-gear', 'Settings', ['settings.php']],
        ]],
    ];
}
$__dir = ($panel ?? 'admin') === 'consultant' ? 'consultant' : 'admin';
?>
<div class="offcanvas-lg offcanvas-start admin-sidebar" tabindex="-1" id="adminSidebar" aria-labelledby="adminSidebarLabel">
    <div class="offcanvas-header d-lg-none border-bottom">
        <h5 class="offcanvas-title" id="adminSidebarLabel">Menu</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#adminSidebar" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-0">
        <nav class="w-100 py-3" aria-label="Dashboard menu">
            <?php foreach ($__menu as [$__group, $__items]): ?>
                <div class="admin-menu-group"><?= e($__group) ?></div>
                <ul class="nav flex-column mb-2">
                    <?php foreach ($__items as [$__path, $__icon, $__label, $__actives]): ?>
                        <li class="nav-item">
                            <a class="nav-link<?= (strpos($__path, $__dir . '/') === 0 && in_array($__cur, $__actives, true)) ? ' active' : '' ?>" href="<?= e(url($__path)) ?>">
                                <i class="bi <?= e($__icon) ?>"></i> <?= e($__label) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endforeach; ?>
        </nav>
    </div>
</div>
<?php unset($__cur, $__menu, $__dir, $__group, $__items, $__path, $__icon, $__label, $__actives); ?>

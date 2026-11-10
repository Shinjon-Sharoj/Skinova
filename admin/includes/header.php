<?php
/**
 * Dashboard layout shared by the admin panel AND the consultant dashboard.
 *
 * Before including set:
 *   $pageTitle   string
 *   $panel       'admin' (default) | 'consultant'
 *   $extraCss    array  (asset paths)
 * Pair with admin/includes/footer.php. Pages must call their own auth guard first
 * (admin/includes/auth-check.php or require_role(ROLE_CONSULTANT)).
 */
require_once __DIR__ . '/../../includes/init.php';
$panel = $panel ?? 'admin';
$pageTitle = $pageTitle ?? 'Dashboard';
$extraCss = $extraCss ?? [];

if (!function_exists('admin_page_header')) {
    /** Print the page title row. $actionsHtml is trusted HTML (buttons). */
    function admin_page_header(string $title, string $actionsHtml = '', string $subtitle = ''): void
    {
        echo '<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">'
           . '<div><h1 class="h3 mb-0">' . e($title) . '</h1>' . ($subtitle ? '<p class="text-muted mb-0 small">' . e($subtitle) . '</p>' : '') . '</div>'
           . ($actionsHtml ? '<div class="d-flex gap-2 flex-wrap">' . $actionsHtml . '</div>' : '')
           . '</div>';
    }

    /** Stat card for dashboards. */
    function admin_stat_card(string $label, string $value, string $icon = 'bi-graph-up', string $color = 'primary', string $sub = ''): string
    {
        return '<div class="card stat-card h-100 shadow-sm"><div class="card-body d-flex align-items-center gap-3">'
             . '<div class="stat-icon text-bg-' . e($color) . '"><i class="bi ' . e($icon) . '"></i></div>'
             . '<div><div class="text-muted small">' . e($label) . '</div><div class="h4 mb-0">' . e($value) . '</div>'
             . ($sub ? '<div class="small text-muted">' . e($sub) . '</div>' : '') . '</div></div></div>';
    }

    /** Print the standard "no rows" table row. */
    function admin_empty_row(int $colspan, string $message = 'Nothing to show yet.'): void
    {
        echo '<tr><td colspan="' . $colspan . '" class="text-center text-muted py-4">' . e($message) . '</td></tr>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <?= csrf_meta() ?>
    <title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?> <?= $panel === 'admin' ? 'Admin' : 'Consultant' ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= e(url('assets/images/placeholders/favicon.svg')) ?>">
    <link rel="stylesheet" href="<?= e(url('assets/vendor/bootstrap/bootstrap.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('assets/vendor/bootstrap-icons/bootstrap-icons.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/responsive.css')) ?>">
    <?php foreach ($extraCss as $css): ?>
        <link rel="stylesheet" href="<?= e(asset($css)) ?>">
    <?php endforeach; ?>
</head>
<body class="admin-body">
<?php include __DIR__ . '/navbar.php'; ?>
<div class="admin-shell">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <main class="admin-main" id="main-content">
        <div class="admin-content container-fluid py-4 px-3 px-lg-4">
            <?php $__f = render_flashes(); if ($__f !== '') echo '<div aria-live="polite">' . $__f . '</div>'; unset($__f); ?>

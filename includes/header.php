<?php
/**
 * Customer-site layout: opens <html>, <head>, <body>, the navbar and <main>.
 * Pair with includes/footer.php.
 *
 * Optional variables set BEFORE including:
 *   $pageTitle        string   <title> prefix
 *   $metaDescription  string
 *   $extraCss         array    e.g. ['css/products.css']
 *   $bodyClass        string
 *   $hideNavbar       bool
 *   $containerFlash   bool     (default true) print flash messages in a container
 */
require_once _DIR_ . '/init.php';
$pageTitle = $pageTitle ?? APP_NAME;
$metaDescription = $metaDescription ?? APP_TAGLINE;
$extraCss = $extraCss ?? [];
$bodyClass = $bodyClass ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= e($metaDescription) ?>">
    <?= csrf_meta() ?>
    <title><?= e($pageTitle === APP_NAME ? APP_NAME . ' - ' . APP_TAGLINE : $pageTitle . ' | ' . APP_NAME) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= e(url('assets/images/placeholders/favicon.svg')) ?>">
    <link rel="stylesheet" href="<?= e(url('assets/vendor/bootstrap/bootstrap.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('assets/vendor/bootstrap-icons/bootstrap-icons.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/responsive.css')) ?>">
    <?php foreach ($extraCss as $css): ?>
        <link rel="stylesheet" href="<?= e(asset($css)) ?>">
    <?php endforeach; ?>
</head>
<body class="<?= e($bodyClass) ?>">
<a class="visually-hidden-focusable skip-link" href="#main-content">Skip to main content</a>
<?php if (empty($hideNavbar)) include _DIR_ . '/navbar.php'; ?>
<main id="main-content">
<?php
$__flashes = render_flashes();
if ($__flashes !== '' && ($containerFlash ?? true)) {
    echo '<div class="container mt-3" aria-live="polite">' . $__flashes . '</div>';
}
unset($__flashes);
?>
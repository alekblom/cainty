<!DOCTYPE html>
<html lang="<?= e($site['site_locale'] ?? 'en') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? $site['site_name'] ?? 'Cainty') ?></title>
    <?php if (!empty($metaDescription)): ?>
    <meta name="description" content="<?= e($metaDescription) ?>">
    <?php endif; ?>
    <link rel="icon" type="image/svg+xml" href="<?= e(($site['site_url'] ?? '') . '/favicon.svg') ?>">
    <meta property="og:title" content="<?= e($pageTitle ?? $site['site_name'] ?? 'Cainty') ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= e($site['site_url'] ?? '') ?>">
    <?php if (!empty($metaDescription)): ?>
    <meta property="og:description" content="<?= e($metaDescription) ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= cainty_asset('assets/css/style.css') ?>">
    <?php Cainty\Plugins\Hook::fire('head_meta'); ?>
</head>
<body>
    <?php $theme->renderPart('header', compact('site')); ?>

    <?php Cainty\Plugins\Hook::fire('header_after'); ?>

    <main class="site-main">
        <?php if (isset($contentTemplate) && file_exists($contentTemplate)): ?>
            <?php include $contentTemplate; ?>
        <?php endif; ?>
    </main>

    <?php Cainty\Plugins\Hook::fire('footer_before'); ?>

    <?php $theme->renderPart('footer', compact('site')); ?>

    <script src="<?= cainty_asset('assets/js/main.js') ?>"></script>
</body>
</html>

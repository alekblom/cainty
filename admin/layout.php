<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($adminPageTitle ?? 'Admin') ?> — Cainty</title>
    <link rel="stylesheet" href="<?= cainty_url('admin/assets/css/admin.css') ?>?v=<?= time() ?>">
</head>
<body class="admin-body">
    <div class="admin-wrapper">
        <?php include CAINTY_ROOT . '/admin/parts/sidebar-nav.php'; ?>

        <main class="admin-main">
            <div class="admin-content">
                <?php
                $page = $adminPage ?? 'dashboard';
                $templateMap = [
                    'dashboard' => '/admin/dashboard.php',
                    'posts' => '/admin/posts/index.php',
                    'editor' => '/admin/posts/editor.php',
                    'categories' => '/admin/categories/index.php',
                    'tags' => '/admin/tags/index.php',
                    'media' => '/admin/media/index.php',
                    'agents' => '/admin/agents/index.php',
                    'agent-editor' => '/admin/agents/editor.php',
                    'agent-runs' => '/admin/agents/runs.php',
                    'queue' => '/admin/queue/index.php',
                    'queue-review' => '/admin/queue/review.php',
                    'settings' => '/admin/settings/index.php',
                    'llm-keys' => '/admin/settings/llm-keys.php',
                ];
                $template = $templateMap[$page] ?? '/admin/dashboard.php';
                include CAINTY_ROOT . $template;
                ?>
            </div>
        </main>
    </div>

    <script>
        const CAINTY = {
            adminUrl: '<?= cainty_admin_url() ?>',
            siteUrl: '<?= cainty_url() ?>',
            csrfToken: '<?= cainty_csrf_token() ?>',
        };
    </script>
    <script src="<?= cainty_url('admin/assets/js/admin.js') ?>?v=<?= time() ?>"></script>
    <?php if ($page === 'editor'): ?>
        <script src="<?= cainty_url('admin/assets/js/editor.js') ?>?v=<?= time() ?>"></script>
    <?php endif; ?>
    <?php if ($page === 'agent-editor'): ?>
        <script src="<?= cainty_url('admin/assets/js/agent-editor.js') ?>?v=<?= time() ?>"></script>
    <?php endif; ?>
</body>
</html>

<header class="site-header">
    <div class="container header-inner">
        <a href="<?= cainty_url() ?>" class="site-logo">
            <img src="<?= cainty_asset('assets/img/cainty-logo.svg') ?>" alt="<?= e($site['site_name'] ?? 'Cainty') ?>" height="32">
        </a>
        <nav class="site-nav">
            <button class="nav-toggle" aria-label="Toggle menu">
                <span></span><span></span><span></span>
            </button>
            <ul class="nav-links">
                <li><a href="<?= cainty_url() ?>">Home</a></li>
                <li><a href="<?= cainty_url('docs') ?>">Docs</a></li>
                <li><a href="<?= cainty_url('search') ?>">Search</a></li>
                <?php if (cainty_is_admin()): ?>
                    <li><a href="<?= cainty_admin_url() ?>">Admin</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>

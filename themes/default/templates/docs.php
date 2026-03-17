<div class="docs-layout">
    <nav class="docs-nav">
        <div class="docs-nav-title">Documentation</div>
        <ul>
            <li><a href="<?= cainty_url('docs') ?>" class="<?= ($currentPage ?? '') === 'index' ? 'active' : '' ?>">Overview</a></li>
            <?php foreach ($docPages as $slug => $title): ?>
                <li><a href="<?= cainty_url('docs/' . $slug) ?>" class="<?= ($currentPage ?? '') === $slug ? 'active' : '' ?>"><?= e($title) ?></a></li>
            <?php endforeach; ?>
        </ul>
    </nav>
    <div class="docs-content">
        <?= $docContent ?? '' ?>
    </div>
</div>

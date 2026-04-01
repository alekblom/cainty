<aside class="admin-sidebar">
    <div class="sidebar-brand">
        <a href="<?= cainty_admin_url() ?>">Cainty</a>
    </div>
    <nav class="sidebar-nav">
        <a href="<?= cainty_admin_url() ?>" class="nav-item <?= ($adminPage ?? '') === 'dashboard' ? 'active' : '' ?>">
            <span class="nav-icon">&#9632;</span> Dashboard
        </a>
        <a href="<?= cainty_admin_url('posts') ?>" class="nav-item <?= in_array($adminPage ?? '', ['posts', 'editor']) ? 'active' : '' ?>">
            <span class="nav-icon">&#9998;</span> Posts
        </a>
        <a href="<?= cainty_admin_url('categories') ?>" class="nav-item <?= ($adminPage ?? '') === 'categories' ? 'active' : '' ?>">
            <span class="nav-icon">&#9776;</span> Categories
        </a>
        <a href="<?= cainty_admin_url('tags') ?>" class="nav-item <?= ($adminPage ?? '') === 'tags' ? 'active' : '' ?>">
            <span class="nav-icon">&#9830;</span> Tags
        </a>
        <a href="<?= cainty_admin_url('media') ?>" class="nav-item <?= ($adminPage ?? '') === 'media' ? 'active' : '' ?>">
            <span class="nav-icon">&#9881;</span> Media
        </a>
        <div class="nav-divider"></div>
        <a href="<?= cainty_admin_url('agents') ?>" class="nav-item <?= in_array($adminPage ?? '', ['agents', 'agent-editor', 'agent-runs']) ? 'active' : '' ?>">
            <span class="nav-icon">&#9733;</span> Agents
        </a>
        <a href="<?= cainty_admin_url('queue') ?>" class="nav-item <?= in_array($adminPage ?? '', ['queue', 'queue-review']) ? 'active' : '' ?>">
            <span class="nav-icon">&#9993;</span> Queue
        </a>
        <a href="<?= cainty_admin_url('template-agent') ?>" class="nav-item <?= ($adminPage ?? '') === 'template-agent' ? 'active' : '' ?>">
            <span class="nav-icon">&#9878;</span> Template Agent
        </a>
        <div class="nav-divider"></div>
        <a href="<?= cainty_admin_url('settings') ?>" class="nav-item <?= in_array($adminPage ?? '', ['settings', 'llm-keys']) ? 'active' : '' ?>">
            <span class="nav-icon">&#9881;</span> Settings
        </a>
    </nav>
    <div class="sidebar-footer">
        <a href="<?= cainty_url() ?>" class="nav-item" target="_blank">
            <span class="nav-icon">&#8599;</span> View Site
        </a>
        <a href="<?= cainty_url('logout') ?>" class="nav-item">
            <span class="nav-icon">&#10005;</span> Logout
        </a>
    </div>
</aside>

<?php $adminPageTitle = 'Dashboard'; ?>

<h1>Dashboard</h1>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-number"><?= $postCounts['published'] ?? 0 ?></div>
        <div class="stat-label">Published</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= $postCounts['draft'] ?? 0 ?></div>
        <div class="stat-label">Drafts</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= $categoryCount ?? 0 ?></div>
        <div class="stat-label">Categories</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= $mediaCount ?? 0 ?></div>
        <div class="stat-label">Media</div>
    </div>
</div>

<div class="section">
    <div class="section-header">
        <h2>Recent Posts</h2>
        <a href="<?= cainty_admin_url('posts/new') ?>" class="btn btn-accent">New Post</a>
    </div>

    <?php if (!empty($recentPosts)): ?>
    <table class="admin-table">
        <thead>
            <tr>
                <th>Title</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recentPosts as $p): ?>
            <tr>
                <td><a href="<?= cainty_admin_url('posts/' . $p['post_id'] . '/edit') ?>"><?= e($p['title']) ?></a></td>
                <td><span class="status-badge status-<?= e($p['status']) ?>"><?= e($p['status']) ?></span></td>
                <td class="text-muted"><?= cainty_time_ago($p['updated_at']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p class="text-muted">No posts yet. <a href="<?= cainty_admin_url('posts/new') ?>">Create your first post</a>.</p>
    <?php endif; ?>
</div>

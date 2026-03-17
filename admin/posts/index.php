<?php $adminPageTitle = 'Posts'; ?>

<div class="section-header">
    <h1>Posts</h1>
    <a href="<?= cainty_admin_url('posts/new') ?>" class="btn btn-accent">New Post</a>
</div>

<div class="status-tabs">
    <a href="<?= cainty_admin_url('posts') ?>" class="tab <?= empty($_GET['status']) ? 'active' : '' ?>">
        All (<?= $postCounts['total'] ?? 0 ?>)
    </a>
    <a href="<?= cainty_admin_url('posts?status=published') ?>" class="tab <?= ($_GET['status'] ?? '') === 'published' ? 'active' : '' ?>">
        Published (<?= $postCounts['published'] ?? 0 ?>)
    </a>
    <a href="<?= cainty_admin_url('posts?status=draft') ?>" class="tab <?= ($_GET['status'] ?? '') === 'draft' ? 'active' : '' ?>">
        Drafts (<?= $postCounts['draft'] ?? 0 ?>)
    </a>
    <a href="<?= cainty_admin_url('posts?status=archived') ?>" class="tab <?= ($_GET['status'] ?? '') === 'archived' ? 'active' : '' ?>">
        Archived (<?= $postCounts['archived'] ?? 0 ?>)
    </a>
</div>

<?php if (!empty($posts)): ?>
<table class="admin-table">
    <thead>
        <tr>
            <th>Title</th>
            <th>Status</th>
            <th>Author</th>
            <th>Date</th>
            <th>Views</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($posts as $p): ?>
        <tr>
            <td>
                <a href="<?= cainty_admin_url('posts/' . $p['post_id'] . '/edit') ?>" class="post-title-link">
                    <?= e($p['title']) ?>
                </a>
            </td>
            <td><span class="status-badge status-<?= e($p['status']) ?>"><?= e($p['status']) ?></span></td>
            <td class="text-muted"><?= e($p['author_name'] ?? $p['username'] ?? '—') ?></td>
            <td class="text-muted"><?= cainty_time_ago($p['updated_at']) ?></td>
            <td class="text-muted"><?= number_format($p['view_count'] ?? 0) ?></td>
            <td>
                <a href="<?= cainty_admin_url('posts/' . $p['post_id'] . '/edit') ?>" class="action-link">Edit</a>
                <?php if ($p['status'] === 'published'): ?>
                    <a href="<?= cainty_url($p['slug']) ?>" class="action-link" target="_blank">View</a>
                <?php endif; ?>
                <button class="action-link danger" onclick="deletePost(<?= $p['post_id'] ?>)">Delete</button>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
    <p class="text-muted" style="padding: 32px 0;">No posts found.</p>
<?php endif; ?>

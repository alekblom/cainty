<?php $adminPageTitle = 'Tags'; ?>

<h1>Tags</h1>

<div class="two-col-layout">
    <div class="col-form">
        <div class="sidebar-box">
            <h3>Add Tag</h3>
            <form id="tag-form" onsubmit="saveTag(event)">
                <input type="hidden" name="_csrf_token" value="<?= cainty_csrf_token() ?>">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="tag_name" id="tag-name" class="form-input" required>
                </div>
                <button type="submit" class="btn btn-accent">Add Tag</button>
            </form>
        </div>
    </div>

    <div class="col-list">
        <?php if (!empty($tags)): ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Slug</th>
                    <th>Posts</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tags as $tag): ?>
                <tr>
                    <td><?= e($tag['tag_name']) ?></td>
                    <td class="text-muted"><?= e($tag['tag_slug']) ?></td>
                    <td class="text-muted"><?= $tag['post_count'] ?></td>
                    <td>
                        <button class="action-link danger" onclick="deleteTag(<?= $tag['tag_id'] ?>)">Delete</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p class="text-muted">No tags yet.</p>
        <?php endif; ?>
    </div>
</div>

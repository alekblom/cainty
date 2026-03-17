<?php $adminPageTitle = 'Categories'; ?>

<h1>Categories</h1>

<div class="two-col-layout">
    <div class="col-form">
        <div class="sidebar-box">
            <h3 id="cat-form-title">Add Category</h3>
            <form id="category-form" onsubmit="saveCategory(event)">
                <input type="hidden" name="category_id" id="cat-id" value="">
                <input type="hidden" name="_csrf_token" value="<?= cainty_csrf_token() ?>">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="cat_name" id="cat-name" class="form-input" required>
                </div>
                <div class="form-group">
                    <label>Slug</label>
                    <input type="text" name="cat_slug" id="cat-slug" class="form-input" placeholder="auto-generated">
                </div>
                <div class="form-group">
                    <label>Parent</label>
                    <select name="parent_id" id="cat-parent" class="form-input">
                        <option value="">None</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['category_id'] ?>"><?= e($cat['cat_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="cat_desc" id="cat-desc" class="form-input" rows="3"></textarea>
                </div>
                <button type="submit" class="btn btn-accent">Save Category</button>
                <button type="button" class="btn btn-secondary" onclick="resetCatForm()" style="display:none;" id="cat-cancel">Cancel</button>
            </form>
        </div>
    </div>

    <div class="col-list">
        <?php if (!empty($categories)): ?>
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
                <?php foreach ($categories as $cat): ?>
                <tr>
                    <td><?= e($cat['cat_name']) ?></td>
                    <td class="text-muted"><?= e($cat['cat_slug']) ?></td>
                    <td class="text-muted"><?= $cat['post_count'] ?></td>
                    <td>
                        <button class="action-link" onclick="editCategory(<?= htmlspecialchars(json_encode($cat)) ?>)">Edit</button>
                        <button class="action-link danger" onclick="deleteCategory(<?= $cat['category_id'] ?>)">Delete</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p class="text-muted">No categories yet.</p>
        <?php endif; ?>
    </div>
</div>

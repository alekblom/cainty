<?php
$adminPageTitle = $post ? 'Edit Post' : 'New Post';
$isEdit = !empty($post);
?>

<form id="post-form" class="editor-layout">
    <input type="hidden" name="post_id" value="<?= $post['post_id'] ?? '' ?>">
    <input type="hidden" name="_csrf_token" value="<?= cainty_csrf_token() ?>">

    <div class="editor-main">
        <div class="editor-title-wrap">
            <input type="text" name="title" id="post-title" placeholder="Post title..."
                   value="<?= e($post['title'] ?? '') ?>" class="editor-title-input">
        </div>

        <div class="editor-toolbar">
            <button type="button" onclick="execCmd('bold')" title="Bold"><b>B</b></button>
            <button type="button" onclick="execCmd('italic')" title="Italic"><i>I</i></button>
            <button type="button" onclick="execCmd('underline')" title="Underline"><u>U</u></button>
            <span class="toolbar-sep"></span>
            <button type="button" onclick="formatBlock('h2')" title="Heading 2">H2</button>
            <button type="button" onclick="formatBlock('h3')" title="Heading 3">H3</button>
            <button type="button" onclick="formatBlock('p')" title="Paragraph">P</button>
            <span class="toolbar-sep"></span>
            <button type="button" onclick="execCmd('insertUnorderedList')" title="Bullet List">&#8226;</button>
            <button type="button" onclick="execCmd('insertOrderedList')" title="Numbered List">1.</button>
            <button type="button" onclick="insertBlockquote()" title="Blockquote">&ldquo;</button>
            <span class="toolbar-sep"></span>
            <button type="button" onclick="insertLink()" title="Insert Link">&#128279;</button>
            <button type="button" onclick="insertImage()" title="Insert Image">&#128247;</button>
            <span class="toolbar-sep"></span>
            <button type="button" onclick="toggleSource()" title="View Source" id="source-toggle">&lt;/&gt;</button>
        </div>

        <div id="editor-content" contenteditable="true" class="editor-area"><?= $post['content'] ?? '' ?></div>
        <textarea id="editor-source" name="content" class="editor-source" style="display:none;"><?= e($post['content'] ?? '') ?></textarea>
    </div>

    <div class="editor-sidebar">
        <!-- Publish Box -->
        <div class="sidebar-box">
            <h3>Publish</h3>
            <div class="form-group">
                <label>Status</label>
                <select name="status" id="post-status">
                    <option value="draft" <?= ($post['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="published" <?= ($post['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published</option>
                    <option value="archived" <?= ($post['status'] ?? '') === 'archived' ? 'selected' : '' ?>>Archived</option>
                </select>
            </div>
            <div class="form-group">
                <label>Type</label>
                <select name="post_type">
                    <option value="post" <?= ($post['post_type'] ?? 'post') === 'post' ? 'selected' : '' ?>>Post</option>
                    <option value="page" <?= ($post['post_type'] ?? '') === 'page' ? 'selected' : '' ?>>Page</option>
                </select>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="savePost('draft')">Save Draft</button>
                <button type="button" class="btn btn-accent" onclick="savePost()">
                    <?= $isEdit ? 'Update' : 'Publish' ?>
                </button>
            </div>
            <div id="save-status" class="save-status"></div>
        </div>

        <!-- Slug -->
        <div class="sidebar-box">
            <h3>Slug</h3>
            <input type="text" name="slug" id="post-slug" value="<?= e($post['slug'] ?? '') ?>" class="form-input" placeholder="auto-generated">
        </div>

        <!-- Categories -->
        <div class="sidebar-box">
            <h3>Categories</h3>
            <div class="category-list">
                <?php if (!empty($categories)): ?>
                    <?php foreach ($categories as $cat): ?>
                    <label class="checkbox-label">
                        <input type="checkbox" name="categories[]" value="<?= $cat['category_id'] ?>"
                               <?= in_array($cat['category_id'], $postCategories ?? []) ? 'checked' : '' ?>>
                        <?= e($cat['cat_name']) ?>
                    </label>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">No categories yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tags -->
        <div class="sidebar-box">
            <h3>Tags</h3>
            <input type="text" name="tags" id="post-tags" class="form-input"
                   value="<?= e(implode(', ', $postTags ?? [])) ?>"
                   placeholder="tag1, tag2, tag3">
            <small class="text-muted">Comma-separated</small>
        </div>

        <!-- Featured Image -->
        <div class="sidebar-box">
            <h3>Featured Image</h3>
            <div id="featured-image-preview">
                <?php if (!empty($post['featured_image'])): ?>
                    <img src="<?= cainty_upload_url($post['featured_image']) ?>" alt="">
                <?php endif; ?>
            </div>
            <input type="file" name="image" id="featured-image-input" accept="image/*" class="form-input">
            <input type="hidden" name="featured_image" id="featured-image-value" value="<?= e($post['featured_image'] ?? '') ?>">
        </div>

        <!-- SEO -->
        <div class="sidebar-box">
            <h3>SEO</h3>
            <div class="form-group">
                <label>Meta Title</label>
                <input type="text" name="meta_title" value="<?= e($post['meta_title'] ?? '') ?>" class="form-input" maxlength="60" placeholder="Max 60 characters">
            </div>
            <div class="form-group">
                <label>Meta Description</label>
                <textarea name="meta_description" class="form-input" rows="3" maxlength="160" placeholder="Max 160 characters"><?= e($post['meta_description'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label>Excerpt</label>
                <textarea name="excerpt" class="form-input" rows="3" placeholder="Short summary..."><?= e($post['excerpt'] ?? '') ?></textarea>
            </div>
        </div>

        <?php \Cainty\Plugins\Hook::fire('admin_post_sidebar', $post ?? null); ?>
    </div>
</form>

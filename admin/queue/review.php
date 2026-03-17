<?php $isPending = ($item['status'] === 'pending_review'); ?>

<div class="editor-header">
    <h1>Review: <?= e($item['title']) ?></h1>
    <div class="editor-actions">
        <a href="<?= cainty_admin_url('queue') ?>" class="btn btn-outline">&larr; Back</a>
        <?php if ($isPending): ?>
        <button class="btn btn-accent" id="approveBtn">Approve as Draft</button>
        <button class="btn btn-published" id="publishBtn">Approve & Publish</button>
        <button class="btn btn-danger" id="rejectBtn">Reject</button>
        <?php endif; ?>
    </div>
</div>

<div class="review-layout">
    <div class="review-content">
        <div class="review-meta-bar">
            <span class="text-muted">Agent: <strong><?= e($item['agent_name'] ?? '—') ?></strong></span>
            <span class="text-muted">Model: <?= e($item['model_used'] ?? '—') ?></span>
            <span class="text-muted">Tokens: <?= number_format($item['input_tokens'] ?? 0) ?> / <?= number_format($item['output_tokens'] ?? 0) ?></span>
            <span class="text-muted">Duration: <?= number_format(($item['duration_ms'] ?? 0) / 1000, 1) ?>s</span>
        </div>

        <?php if ($isPending): ?>
        <div class="form-group">
            <label>Title</label>
            <input type="text" id="review-title" value="<?= e($item['title']) ?>">
        </div>
        <div class="form-group">
            <label>Slug</label>
            <input type="text" id="review-slug" value="<?= e($item['slug']) ?>">
        </div>
        <div class="form-group">
            <label>Content</label>
            <div id="review-editor" class="review-editor" contenteditable="true"><?= $item['content'] ?></div>
        </div>
        <div class="form-group">
            <label>Excerpt</label>
            <textarea id="review-excerpt" rows="3"><?= e($item['excerpt'] ?? '') ?></textarea>
        </div>
        <?php else: ?>
        <div class="rendered-content">
            <h2><?= e($item['title']) ?></h2>
            <div class="content-body"><?= $item['content'] ?></div>
        </div>
        <?php if ($item['review_notes']): ?>
            <div class="review-notes">
                <strong>Review Notes:</strong> <?= e($item['review_notes']) ?>
            </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="review-sidebar">
        <div class="sidebar-section">
            <h3>SEO</h3>
            <?php if ($isPending): ?>
            <div class="form-group">
                <label>Meta Title</label>
                <input type="text" id="review-meta-title" value="<?= e($item['meta_title'] ?? '') ?>" maxlength="60">
            </div>
            <div class="form-group">
                <label>Meta Description</label>
                <textarea id="review-meta-desc" rows="3" maxlength="155"><?= e($item['meta_description'] ?? '') ?></textarea>
            </div>
            <?php else: ?>
            <p class="text-muted"><?= e($item['meta_title'] ?? '—') ?></p>
            <p class="text-muted"><?= e($item['meta_description'] ?? '—') ?></p>
            <?php endif; ?>
        </div>

        <div class="sidebar-section">
            <h3>Categories</h3>
            <?php $cats = json_decode($item['categories'] ?? '[]', true) ?: []; ?>
            <p class="text-muted"><?= !empty($cats) ? implode(', ', $cats) : '—' ?></p>
        </div>

        <div class="sidebar-section">
            <h3>Tags</h3>
            <?php $tags = json_decode($item['tags'] ?? '[]', true) ?: []; ?>
            <p class="text-muted"><?= !empty($tags) ? implode(', ', $tags) : '—' ?></p>
        </div>

        <?php if (!empty($item['image_prompt'])): ?>
        <div class="sidebar-section">
            <h3>Image Prompt</h3>
            <p class="text-muted"><?= e($item['image_prompt']) ?></p>
        </div>
        <?php endif; ?>

        <div class="sidebar-section">
            <h3>Topic Prompt</h3>
            <p class="text-muted"><?= e($item['topic_prompt'] ?? '—') ?></p>
        </div>

        <?php if ($isPending): ?>
        <div class="sidebar-section">
            <h3>Review Notes</h3>
            <textarea id="review-notes" rows="3" placeholder="Optional notes..."></textarea>
        </div>

        <button class="btn btn-outline btn-block" id="saveEditsBtn" style="margin-top:8px;">Save Edits</button>
        <?php endif; ?>
    </div>
</div>

<?php if ($isPending): ?>
<script>
const queueId = <?= $item['queue_id'] ?>;

function doAction(url, data) {
    return fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-TOKEN': CAINTY.csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: new URLSearchParams(data),
    }).then(r => r.json());
}

document.getElementById('saveEditsBtn').onclick = function() {
    doAction(CAINTY.adminUrl + '/queue/' + queueId + '/update', {
        title: document.getElementById('review-title').value,
        slug: document.getElementById('review-slug').value,
        content: document.getElementById('review-editor').innerHTML,
        excerpt: document.getElementById('review-excerpt').value,
        meta_title: document.getElementById('review-meta-title').value,
        meta_description: document.getElementById('review-meta-desc').value,
    }).then(d => {
        alert(d.message || d.error);
    });
};

document.getElementById('approveBtn').onclick = function() {
    if (!confirm('Approve this content and create a draft post?')) return;
    doAction(CAINTY.adminUrl + '/queue/' + queueId + '/approve', {
        notes: document.getElementById('review-notes').value,
        post_status: 'draft',
    }).then(d => {
        if (d.success && d.redirect) window.location = d.redirect;
        else alert(d.error || d.message);
    });
};

document.getElementById('publishBtn').onclick = function() {
    if (!confirm('Approve and immediately publish this content?')) return;
    doAction(CAINTY.adminUrl + '/queue/' + queueId + '/approve', {
        notes: document.getElementById('review-notes').value,
        post_status: 'published',
    }).then(d => {
        if (d.success && d.redirect) window.location = d.redirect;
        else alert(d.error || d.message);
    });
};

document.getElementById('rejectBtn').onclick = function() {
    const reason = prompt('Reason for rejection (optional):');
    if (reason === null) return;
    doAction(CAINTY.adminUrl + '/queue/' + queueId + '/reject', {
        reason: reason,
    }).then(d => {
        if (d.success) window.location = CAINTY.adminUrl + '/queue';
        else alert(d.error || d.message);
    });
};
</script>
<?php endif; ?>

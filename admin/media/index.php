<?php $adminPageTitle = 'Media'; ?>

<div class="section-header">
    <h1>Media Library</h1>
    <form id="upload-form" enctype="multipart/form-data" style="display:inline;">
        <input type="hidden" name="_csrf_token" value="<?= cainty_csrf_token() ?>">
        <label class="btn btn-accent" style="cursor:pointer;">
            Upload
            <input type="file" name="file" id="media-upload" accept="image/*" style="display:none;" onchange="uploadMedia(this)">
        </label>
    </form>
</div>

<?php if (!empty($mediaItems)): ?>
<div class="media-grid">
    <?php foreach ($mediaItems as $item): ?>
    <div class="media-card" id="media-<?= $item['media_id'] ?>">
        <div class="media-preview">
            <img src="<?= cainty_upload_url($item['filepath']) ?>" alt="<?= e($item['alt_text'] ?? '') ?>" loading="lazy">
        </div>
        <div class="media-info">
            <div class="media-filename"><?= e($item['filename']) ?></div>
            <div class="media-meta text-muted">
                <?= round(($item['filesize'] ?? 0) / 1024) ?>KB
                &middot; <?= cainty_time_ago($item['created_at']) ?>
            </div>
            <div class="media-actions">
                <button class="action-link" onclick="copyMediaUrl('<?= cainty_upload_url($item['filepath']) ?>')">Copy URL</button>
                <button class="action-link danger" onclick="deleteMedia(<?= $item['media_id'] ?>)">Delete</button>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
    <p class="text-muted" style="padding: 32px 0;">No media uploaded yet.</p>
<?php endif; ?>

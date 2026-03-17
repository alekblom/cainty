<article class="post-card">
    <a href="<?= cainty_url($post['slug']) ?>" class="post-card-link">
        <?php if (!empty($post['featured_image'])): ?>
            <div class="post-card-image">
                <img src="<?= cainty_upload_url($post['featured_image']) ?>"
                     alt="<?= e($post['title']) ?>"
                     loading="lazy">
            </div>
        <?php else: ?>
            <div class="post-card-image post-card-image-placeholder"></div>
        <?php endif; ?>

        <div class="post-card-body">
            <h2 class="post-card-title"><?= e($post['title']) ?></h2>
            <?php if (!empty($post['excerpt'])): ?>
                <p class="post-card-excerpt"><?= e($post['excerpt']) ?></p>
            <?php endif; ?>
            <div class="post-card-meta">
                <time><?= cainty_date($post['published_at'] ?? $post['created_at']) ?></time>
                <span>&middot;</span>
                <span><?= cainty_read_time($post['content'] ?? '') ?></span>
            </div>
        </div>
    </a>
</article>

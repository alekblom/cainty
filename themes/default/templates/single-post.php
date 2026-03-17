<article class="single-post container">
    <header class="post-header">
        <h1><?= e($post['title']) ?></h1>
        <div class="post-meta">
            <span class="post-author"><?= e($post['author_name'] ?? $post['username'] ?? 'Unknown') ?></span>
            <span class="meta-sep">&middot;</span>
            <time datetime="<?= e($post['published_at'] ?? $post['created_at']) ?>">
                <?= cainty_date($post['published_at'] ?? $post['created_at']) ?>
            </time>
            <span class="meta-sep">&middot;</span>
            <span class="read-time"><?= cainty_read_time($post['content'] ?? '') ?></span>
        </div>
    </header>

    <?php if (!empty($post['featured_image'])): ?>
        <div class="featured-image">
            <img src="<?= cainty_upload_url($post['featured_image']) ?>"
                 alt="<?= e($post['title']) ?>"
                 loading="lazy">
        </div>
    <?php endif; ?>

    <div class="post-content">
        <?= Cainty\Themes\ThemeLoader::processContent($post['content'] ?? '') ?>
    </div>

    <?php Cainty\Plugins\Hook::fire('single_post_after_content', $post); ?>

    <?php if (!empty($tags)): ?>
        <div class="post-tags">
            <?php foreach ($tags as $tag): ?>
                <a href="<?= cainty_url('tag/' . $tag['tag_slug']) ?>" class="tag"><?= e($tag['tag_name']) ?></a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($categories)): ?>
        <div class="post-categories">
            Filed in:
            <?php foreach ($categories as $i => $cat): ?>
                <?php if ($i > 0) echo ', '; ?>
                <a href="<?= cainty_url($cat['cat_slug']) ?>"><?= e($cat['cat_name']) ?></a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($related)): ?>
        <section class="related-posts">
            <h2>Related Posts</h2>
            <div class="post-grid post-grid-small">
                <?php foreach ($related as $relPost): ?>
                    <?php $theme->renderPart('post-card', ['post' => $relPost]); ?>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</article>

<div class="container">
    <div class="page-header">
        <?php if (!empty($category)): ?>
            <h1><?= e($category['cat_name']) ?></h1>
            <?php if (!empty($category['cat_desc'])): ?>
                <p class="archive-description"><?= e($category['cat_desc']) ?></p>
            <?php endif; ?>
        <?php elseif (!empty($tag)): ?>
            <h1>Tagged: <?= e($tag['tag_name']) ?></h1>
        <?php elseif (!empty($author)): ?>
            <h1>Posts by <?= e($author['display_name'] ?? $author['username']) ?></h1>
        <?php else: ?>
            <h1>Archive</h1>
        <?php endif; ?>
    </div>

    <?php if (!empty($posts)): ?>
        <div class="post-grid">
            <?php foreach ($posts as $post): ?>
                <?php $theme->renderPart('post-card', ['post' => $post]); ?>
            <?php endforeach; ?>
        </div>

        <?php
        $baseUrl = cainty_url(
            !empty($category) ? $category['cat_slug'] . '/page' :
            (!empty($tag) ? 'tag/' . $tag['tag_slug'] . '/page' :
            'page')
        );
        $theme->renderPart('pagination', [
            'currentPage' => $currentPage ?? 1,
            'totalPages' => $totalPages ?? 1,
            'baseUrl' => $baseUrl,
        ]);
        ?>
    <?php else: ?>
        <p class="no-posts">No posts found.</p>
    <?php endif; ?>
</div>

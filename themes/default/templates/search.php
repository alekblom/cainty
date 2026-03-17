<div class="container">
    <div class="page-header">
        <h1>Search Results</h1>
        <form class="search-form" action="<?= cainty_url('search') ?>" method="get">
            <input type="text" name="q" value="<?= e($query ?? '') ?>" placeholder="Search posts..." class="search-input">
            <button type="submit" class="btn btn-accent">Search</button>
        </form>
    </div>

    <?php if (!empty($query)): ?>
        <p class="search-meta"><?= count($posts ?? []) ?> result<?= count($posts ?? []) !== 1 ? 's' : '' ?> for "<?= e($query) ?>"</p>
    <?php endif; ?>

    <?php if (!empty($posts)): ?>
        <div class="post-grid">
            <?php foreach ($posts as $post): ?>
                <?php $theme->renderPart('post-card', ['post' => $post]); ?>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <?php if (!empty($query)): ?>
            <p class="no-posts">No posts found matching your query.</p>
        <?php endif; ?>
    <?php endif; ?>
</div>

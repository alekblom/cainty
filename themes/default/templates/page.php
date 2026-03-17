<article class="single-post container">
    <header class="post-header">
        <h1><?= e($post['title']) ?></h1>
    </header>

    <div class="post-content">
        <?= Cainty\Themes\ThemeLoader::processContent($post['content'] ?? '') ?>
    </div>
</article>

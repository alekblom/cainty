<?php if (($totalPages ?? 1) > 1): ?>
<nav class="pagination">
    <?php if ($currentPage > 1): ?>
        <a href="<?= e($baseUrl) ?>/<?= $currentPage - 1 ?>" class="pagination-link prev">&laquo; Previous</a>
    <?php endif; ?>

    <span class="pagination-info">Page <?= (int)$currentPage ?> of <?= (int)$totalPages ?></span>

    <?php if ($currentPage < $totalPages): ?>
        <a href="<?= e($baseUrl) ?>/<?= $currentPage + 1 ?>" class="pagination-link next">Next &raquo;</a>
    <?php endif; ?>
</nav>
<?php endif; ?>

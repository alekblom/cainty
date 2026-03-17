<?php

namespace Cainty\Controllers;

use Cainty\Content\Post;
use Cainty\Content\Category;
use Cainty\Content\Media;

class AdminDashboardController
{
    public function index(array $params): void
    {
        $siteId = cainty_site_id();

        $postCounts = Post::countByStatus($siteId);
        $recentPosts = Post::getAll($siteId, null, 5);
        $categoryCount = count(Category::getBySite($siteId));
        $mediaCount = Media::count($siteId);

        include CAINTY_ROOT . '/admin/layout.php';
    }
}

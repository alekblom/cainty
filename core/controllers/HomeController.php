<?php

namespace Cainty\Controllers;

use Cainty\Content\Post;
use Cainty\Themes\ThemeLoader;

class HomeController
{
    public function index(array $params): void
    {
        $siteId = cainty_site_id();
        $perPage = 12;
        $page = max(1, (int) ($params['num'] ?? ($_GET['page'] ?? 1)));
        $offset = ($page - 1) * $perPage;

        $posts = Post::getPublished($siteId, $perPage, $offset);
        $total = Post::countPublished($siteId);
        $totalPages = max(1, (int) ceil($total / $perPage));

        $theme = new ThemeLoader();
        $theme->render('home', [
            'posts' => $posts,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'pageTitle' => cainty_current_site()['site_name'] ?? 'Home',
        ]);
    }
}

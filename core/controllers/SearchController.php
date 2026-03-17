<?php

namespace Cainty\Controllers;

use Cainty\Content\Post;
use Cainty\Themes\ThemeLoader;

class SearchController
{
    public function index(array $params): void
    {
        $query = trim($_GET['q'] ?? '');
        $posts = [];

        if (!empty($query)) {
            $posts = Post::search($query, cainty_site_id());
        }

        $theme = new ThemeLoader();
        $theme->render('search', [
            'query' => $query,
            'posts' => $posts,
            'pageTitle' => $query ? "Search: {$query}" : 'Search',
        ]);
    }
}

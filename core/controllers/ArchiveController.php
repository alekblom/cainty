<?php

namespace Cainty\Controllers;

use Cainty\Content\Post;
use Cainty\Content\Category;
use Cainty\Content\Tag;
use Cainty\Database\Database;
use Cainty\Themes\ThemeLoader;
use Cainty\Router\Response;

class ArchiveController
{
    public function byCategory(array $params): void
    {
        $siteId = cainty_site_id();
        $category = Category::findBySlug($params['slug'], $siteId);

        if (!$category) {
            Response::notFound();
            return;
        }

        $perPage = 12;
        $page = max(1, (int) ($params['num'] ?? 1));
        $offset = ($page - 1) * $perPage;

        $posts = Post::getByCategory($category['category_id'], $siteId, $perPage, $offset);
        $total = (int) Database::fetchColumn(
            "SELECT COUNT(*) FROM posts_categories pc
             INNER JOIN posts p ON pc.post_id = p.post_id
             WHERE pc.category_id = ? AND p.status = 'published'",
            [$category['category_id']]
        );

        $theme = new ThemeLoader();
        $theme->render('archive', [
            'posts' => $posts,
            'category' => $category,
            'currentPage' => $page,
            'totalPages' => max(1, (int) ceil($total / $perPage)),
            'pageTitle' => $category['cat_name'],
            'metaDescription' => $category['cat_desc'] ?? '',
        ]);
    }

    public function byTag(array $params): void
    {
        $siteId = cainty_site_id();
        $tag = Tag::findBySlug($params['tag_slug']);

        if (!$tag) {
            Response::notFound();
            return;
        }

        $perPage = 12;
        $page = max(1, (int) ($params['num'] ?? 1));
        $offset = ($page - 1) * $perPage;

        $posts = Post::getByTag($tag['tag_id'], $siteId, $perPage, $offset);

        $theme = new ThemeLoader();
        $theme->render('archive', [
            'posts' => $posts,
            'tag' => $tag,
            'currentPage' => $page,
            'totalPages' => max(1, (int) ceil(($tag['post_count'] ?? 0) / $perPage)),
            'pageTitle' => 'Tagged: ' . $tag['tag_name'],
        ]);
    }

    public function byAuthor(array $params): void
    {
        $siteId = cainty_site_id();
        $author = Database::fetchOne(
            "SELECT user_id, username, display_name, bio FROM users WHERE username = ?",
            [$params['slug']]
        );

        if (!$author) {
            Response::notFound();
            return;
        }

        $perPage = 12;
        $page = max(1, (int) ($params['num'] ?? 1));
        $offset = ($page - 1) * $perPage;

        $posts = Post::getByAuthor($author['user_id'], $siteId, $perPage, $offset);

        $theme = new ThemeLoader();
        $theme->render('archive', [
            'posts' => $posts,
            'author' => $author,
            'currentPage' => $page,
            'totalPages' => 1,
            'pageTitle' => 'Posts by ' . ($author['display_name'] ?? $author['username']),
        ]);
    }
}

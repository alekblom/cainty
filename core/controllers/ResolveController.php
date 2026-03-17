<?php

namespace Cainty\Controllers;

use Cainty\Content\Post;
use Cainty\Content\Category;
use Cainty\Themes\ThemeLoader;
use Cainty\Router\Response;

/**
 * Slug resolver — checks posts first, then categories
 */
class ResolveController
{
    public function resolve(array $params): void
    {
        $slug = $params['slug'] ?? '';
        $siteId = cainty_site_id();

        // Try post first
        $post = Post::findBySlug($slug, $siteId);
        if ($post && $post['status'] === 'published') {
            Post::incrementViews($post['post_id']);

            $categories = Post::getCategories($post['post_id']);
            $tags = Post::getTags($post['post_id']);
            $categoryIds = array_map(fn($c) => $c['category_id'], $categories);
            $related = Post::getRelated($post['post_id'], $siteId, $categoryIds);

            $template = $post['post_type'] === 'page' ? 'page' : 'single-post';

            $theme = new ThemeLoader();
            $theme->render($template, [
                'post' => $post,
                'categories' => $categories,
                'tags' => $tags,
                'related' => $related,
                'pageTitle' => $post['meta_title'] ?: $post['title'],
                'metaDescription' => $post['meta_description'] ?: cainty_excerpt($post['content'] ?? '', 160),
            ]);
            return;
        }

        // Try category
        $category = Category::findBySlug($slug, $siteId);
        if ($category) {
            $archive = new ArchiveController();
            $archive->byCategory(array_merge($params, ['slug' => $slug]));
            return;
        }

        Response::notFound();
    }

    public function resolveWithPagination(array $params): void
    {
        // For /{slug}/page/{num} — only categories have pagination
        $slug = $params['slug'] ?? '';
        $siteId = cainty_site_id();

        $category = Category::findBySlug($slug, $siteId);
        if ($category) {
            $archive = new ArchiveController();
            $archive->byCategory($params);
            return;
        }

        Response::notFound();
    }
}

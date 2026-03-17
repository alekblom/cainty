<?php

namespace Cainty\Controllers;

use Cainty\Auth\Auth;
use Cainty\Content\Post;
use Cainty\Content\Category;
use Cainty\Content\Tag;
use Cainty\Content\Media;
use Cainty\Router\Response;

class AdminPostController
{
    public function index(array $params): void
    {
        $siteId = cainty_site_id();
        $status = $_GET['status'] ?? null;
        $posts = Post::getAll($siteId, $status);
        $postCounts = Post::countByStatus($siteId);
        $adminPage = 'posts';

        include CAINTY_ROOT . '/admin/layout.php';
    }

    public function create(array $params): void
    {
        $siteId = cainty_site_id();
        $post = null;
        $categories = Category::getBySite($siteId);
        $postCategories = [];
        $postTags = [];
        $adminPage = 'editor';

        include CAINTY_ROOT . '/admin/layout.php';
    }

    public function edit(array $params): void
    {
        $siteId = cainty_site_id();
        $post = Post::findById((int) $params['id'], $siteId);

        if (!$post) {
            Response::redirect(cainty_admin_url('posts'));
            return;
        }

        $categories = Category::getBySite($siteId);
        $postCategories = array_map(fn($c) => $c['category_id'], Post::getCategories($post['post_id']));
        $postTags = array_map(fn($t) => $t['tag_name'], Post::getTags($post['post_id']));
        $adminPage = 'editor';

        include CAINTY_ROOT . '/admin/layout.php';
    }

    public function save(array $params): void
    {
        if (!cainty_verify_csrf()) {
            Response::json(['success' => false, 'error' => 'Invalid CSRF token'], 403);
            return;
        }

        $siteId = cainty_site_id();
        $postId = !empty($_POST['post_id']) ? (int) $_POST['post_id'] : null;

        $data = [
            'site_id' => $siteId,
            'author_id' => Auth::id(),
            'title' => trim($_POST['title'] ?? ''),
            'slug' => cainty_slug($_POST['slug'] ?? $_POST['title'] ?? ''),
            'content' => $_POST['content'] ?? '',
            'excerpt' => trim($_POST['excerpt'] ?? ''),
            'status' => $_POST['status'] ?? 'draft',
            'post_type' => $_POST['post_type'] ?? 'post',
            'meta_title' => trim($_POST['meta_title'] ?? ''),
            'meta_description' => trim($_POST['meta_description'] ?? ''),
            'featured_image' => $_POST['featured_image'] ?? '',
        ];

        if (empty($data['title'])) {
            Response::json(['success' => false, 'error' => 'Title is required']);
            return;
        }

        // Auto-generate excerpt if empty
        if (empty($data['excerpt']) && !empty($data['content'])) {
            $data['excerpt'] = cainty_excerpt($data['content']);
        }

        try {
            if ($postId) {
                Post::update($postId, $data);
            } else {
                $postId = Post::create($data);
            }

            // Sync categories
            $categoryIds = $_POST['categories'] ?? [];
            if (is_array($categoryIds)) {
                Post::syncCategories($postId, $categoryIds);
            }

            // Sync tags
            $tagString = trim($_POST['tags'] ?? '');
            if (!empty($tagString)) {
                $tagSlugs = array_map('trim', explode(',', $tagString));
                Post::syncTags($postId, $tagSlugs);
            } else {
                Post::syncTags($postId, []);
            }

            // Sync authors
            Post::syncAuthors($postId, [Auth::id()], Auth::id());

            // Handle image upload if present
            if (!empty($_FILES['image']['name'])) {
                $result = Media::upload($_FILES['image'], $siteId, Auth::id());
                if ($result['success']) {
                    Post::update($postId, ['featured_image' => $result['filename']]);
                }
            }

            // Fire hook
            \Cainty\Plugins\Hook::fire('post_after_save', Post::findById($postId));
            if ($data['status'] === 'published') {
                \Cainty\Plugins\Hook::fire('post_published', Post::findById($postId));
            }

            Response::json([
                'success' => true,
                'post_id' => $postId,
                'redirect' => cainty_admin_url('posts/' . $postId . '/edit'),
            ]);
        } catch (\Exception $e) {
            Response::json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function delete(array $params): void
    {
        if (!cainty_verify_csrf()) {
            Response::json(['success' => false, 'error' => 'Invalid CSRF token'], 403);
            return;
        }

        $postId = (int) $params['id'];
        Post::delete($postId);

        if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            Response::json(['success' => true]);
        } else {
            Response::redirect(cainty_admin_url('posts'));
        }
    }
}

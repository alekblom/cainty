<?php

namespace Cainty\Controllers;

use Cainty\Content\Category;
use Cainty\Router\Response;

class AdminCategoryController
{
    public function index(array $params): void
    {
        $siteId = cainty_site_id();
        $categories = Category::getBySite($siteId);
        $adminPage = 'categories';

        include CAINTY_ROOT . '/admin/layout.php';
    }

    public function save(array $params): void
    {
        if (!cainty_verify_csrf()) {
            Response::json(['success' => false, 'error' => 'Invalid CSRF token'], 403);
            return;
        }

        $siteId = cainty_site_id();
        $catId = !empty($_POST['category_id']) ? (int) $_POST['category_id'] : null;

        $data = [
            'site_id' => $siteId,
            'cat_name' => trim($_POST['cat_name'] ?? ''),
            'cat_slug' => cainty_slug($_POST['cat_slug'] ?? $_POST['cat_name'] ?? ''),
            'cat_desc' => trim($_POST['cat_desc'] ?? ''),
            'parent_id' => !empty($_POST['parent_id']) ? (int) $_POST['parent_id'] : null,
        ];

        if (empty($data['cat_name'])) {
            Response::json(['success' => false, 'error' => 'Name is required']);
            return;
        }

        if ($catId) {
            Category::update($catId, $data);
        } else {
            $catId = Category::create($data);
        }

        Response::json(['success' => true, 'category_id' => $catId]);
    }

    public function delete(array $params): void
    {
        if (!cainty_verify_csrf()) {
            Response::json(['success' => false, 'error' => 'Invalid CSRF token'], 403);
            return;
        }

        $catId = (int) ($params['id'] ?? $_POST['category_id'] ?? 0);
        Category::delete($catId);

        Response::json(['success' => true]);
    }
}

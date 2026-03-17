<?php

namespace Cainty\Controllers;

use Cainty\Content\Tag;
use Cainty\Router\Response;

class AdminTagController
{
    public function index(array $params): void
    {
        $tags = Tag::getAll();
        $adminPage = 'tags';

        include CAINTY_ROOT . '/admin/layout.php';
    }

    public function save(array $params): void
    {
        if (!cainty_verify_csrf()) {
            Response::json(['success' => false, 'error' => 'Invalid CSRF token'], 403);
            return;
        }

        $name = trim($_POST['tag_name'] ?? '');
        if (empty($name)) {
            Response::json(['success' => false, 'error' => 'Name is required']);
            return;
        }

        $tag = Tag::findOrCreate($name);
        Response::json(['success' => true, 'tag' => $tag]);
    }

    public function delete(array $params): void
    {
        if (!cainty_verify_csrf()) {
            Response::json(['success' => false, 'error' => 'Invalid CSRF token'], 403);
            return;
        }

        $tagId = (int) ($params['id'] ?? $_POST['tag_id'] ?? 0);
        Tag::delete($tagId);

        Response::json(['success' => true]);
    }
}

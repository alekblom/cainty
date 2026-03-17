<?php

namespace Cainty\Controllers;

use Cainty\Auth\Auth;
use Cainty\AI\ContentQueue;
use Cainty\Router\Response;

class AdminQueueController
{
    public function index(array $params): void
    {
        $siteId = cainty_site_id();
        $status = $_GET['status'] ?? null;

        if ($status) {
            $items = ContentQueue::getByStatus($siteId, $status);
        } else {
            $items = ContentQueue::getAll($siteId);
        }

        $counts = ContentQueue::countByStatus($siteId);
        $adminPage = 'queue';
        $adminPageTitle = 'Content Queue';

        include CAINTY_ROOT . '/admin/layout.php';
    }

    public function review(array $params): void
    {
        $item = ContentQueue::getById((int) $params['id']);

        if (!$item) {
            Response::redirect(cainty_admin_url('queue'));
            return;
        }

        $adminPage = 'queue-review';
        $adminPageTitle = 'Review: ' . $item['title'];

        include CAINTY_ROOT . '/admin/layout.php';
    }

    public function approve(array $params): void
    {
        if (!cainty_verify_csrf()) {
            Response::json(['success' => false, 'error' => 'Invalid CSRF token'], 403);
            return;
        }

        $queueId = (int) $params['id'];
        $notes = trim($_POST['notes'] ?? '');
        $postStatus = $_POST['post_status'] ?? 'draft';

        $postId = ContentQueue::approve($queueId, Auth::id(), $notes ?: null, $postStatus);

        if ($postId) {
            Response::json([
                'success' => true,
                'post_id' => $postId,
                'message' => 'Content approved and post created.',
                'redirect' => cainty_admin_url('posts/' . $postId . '/edit'),
            ]);
        } else {
            Response::json(['success' => false, 'error' => 'Could not approve item.']);
        }
    }

    public function reject(array $params): void
    {
        if (!cainty_verify_csrf()) {
            Response::json(['success' => false, 'error' => 'Invalid CSRF token'], 403);
            return;
        }

        $queueId = (int) $params['id'];
        $reason = trim($_POST['reason'] ?? '');

        $result = ContentQueue::reject($queueId, Auth::id(), $reason ?: null);

        Response::json([
            'success' => $result,
            'message' => $result ? 'Content rejected.' : 'Could not reject item.',
        ]);
    }

    public function update(array $params): void
    {
        if (!cainty_verify_csrf()) {
            Response::json(['success' => false, 'error' => 'Invalid CSRF token'], 403);
            return;
        }

        $queueId = (int) $params['id'];
        $edits = [];

        foreach (['title', 'slug', 'content', 'excerpt', 'meta_title', 'meta_description'] as $field) {
            if (isset($_POST[$field])) {
                $edits[$field] = $_POST[$field];
            }
        }

        $result = ContentQueue::updateContent($queueId, $edits);

        Response::json([
            'success' => $result,
            'message' => $result ? 'Content updated.' : 'No changes made.',
        ]);
    }
}

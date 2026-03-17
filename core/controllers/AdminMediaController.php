<?php

namespace Cainty\Controllers;

use Cainty\Auth\Auth;
use Cainty\Content\Media;
use Cainty\Router\Response;

class AdminMediaController
{
    public function index(array $params): void
    {
        $siteId = cainty_site_id();
        $mediaItems = Media::getBySite($siteId);
        $adminPage = 'media';

        include CAINTY_ROOT . '/admin/layout.php';
    }

    public function upload(array $params): void
    {
        if (!cainty_verify_csrf()) {
            Response::json(['success' => false, 'error' => 'Invalid CSRF token'], 403);
            return;
        }

        $file = $_FILES['file'] ?? null;
        if (!$file) {
            Response::json(['success' => false, 'error' => 'No file uploaded']);
            return;
        }

        $result = Media::upload($file, cainty_site_id(), Auth::id(), $_POST['alt_text'] ?? null);
        Response::json($result);
    }

    public function delete(array $params): void
    {
        if (!cainty_verify_csrf()) {
            Response::json(['success' => false, 'error' => 'Invalid CSRF token'], 403);
            return;
        }

        $mediaId = (int) ($params['id'] ?? $_POST['media_id'] ?? 0);
        Media::delete($mediaId);

        Response::json(['success' => true]);
    }
}

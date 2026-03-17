<?php

namespace Cainty\Content;

use Cainty\Database\Database;

/**
 * Media Model
 */
class Media
{
    private const ALLOWED_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    /**
     * Upload a file from $_FILES
     */
    public static function upload(array $file, int $siteId, int $uploadedBy, ?string $altText = null): array
    {
        // Validate upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'Upload failed with error code: ' . $file['error']];
        }

        $maxSize = (int) cainty_config('UPLOAD_MAX_SIZE', 10485760);
        if ($file['size'] > $maxSize) {
            return ['success' => false, 'error' => 'File too large. Maximum: ' . round($maxSize / 1048576) . 'MB'];
        }

        $mimeType = mime_content_type($file['tmp_name']);
        if (!isset(self::ALLOWED_TYPES[$mimeType])) {
            return ['success' => false, 'error' => 'File type not allowed. Allowed: JPEG, PNG, WebP, GIF'];
        }

        $ext = self::ALLOWED_TYPES[$mimeType];
        $filename = date('Y-m') . '-' . bin2hex(random_bytes(8)) . '.' . $ext;

        $uploadDir = cainty_upload_path();
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filepath = $uploadDir . '/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            return ['success' => false, 'error' => 'Failed to move uploaded file'];
        }

        $mediaId = Database::insert('media', [
            'site_id' => $siteId,
            'uploaded_by' => $uploadedBy,
            'filename' => $filename,
            'filepath' => $filename,
            'filetype' => $mimeType,
            'filesize' => $file['size'],
            'alt_text' => $altText,
        ]);

        return [
            'success' => true,
            'media_id' => $mediaId,
            'filename' => $filename,
            'url' => cainty_upload_url($filename),
        ];
    }

    public static function findById(int $id): ?array
    {
        return Database::fetchOne("SELECT * FROM media WHERE media_id = ?", [$id]);
    }

    public static function getBySite(int $siteId, int $limit = 50, int $offset = 0): array
    {
        return Database::fetchAll(
            "SELECT m.*, u.username
             FROM media m
             LEFT JOIN users u ON m.uploaded_by = u.user_id
             WHERE m.site_id = ?
             ORDER BY m.created_at DESC
             LIMIT ? OFFSET ?",
            [$siteId, $limit, $offset]
        );
    }

    public static function delete(int $id): bool
    {
        $media = self::findById($id);
        if ($media) {
            $filepath = cainty_upload_path($media['filepath']);
            if (file_exists($filepath)) {
                unlink($filepath);
            }
        }
        return Database::delete('media', 'media_id = ?', [$id]) > 0;
    }

    public static function count(int $siteId): int
    {
        return (int) Database::fetchColumn(
            "SELECT COUNT(*) FROM media WHERE site_id = ?",
            [$siteId]
        );
    }
}

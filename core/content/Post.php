<?php

namespace Cainty\Content;

use Cainty\Database\Database;

/**
 * Post Model
 */
class Post
{
    public static function findById(int $id, ?int $siteId = null): ?array
    {
        $sql = "SELECT p.*, u.username, u.display_name as author_name
                FROM posts p
                LEFT JOIN users u ON p.author_id = u.user_id
                WHERE p.post_id = ?";
        $params = [$id];

        if ($siteId !== null) {
            $sql .= " AND p.site_id = ?";
            $params[] = $siteId;
        }

        return Database::fetchOne($sql, $params);
    }

    public static function findBySlug(string $slug, int $siteId): ?array
    {
        return Database::fetchOne(
            "SELECT p.*, u.username, u.display_name as author_name
             FROM posts p
             LEFT JOIN users u ON p.author_id = u.user_id
             WHERE p.slug = ? AND p.site_id = ?",
            [$slug, $siteId]
        );
    }

    public static function create(array $data): int
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        if (($data['status'] ?? '') === 'published' && empty($data['published_at'])) {
            $data['published_at'] = date('Y-m-d H:i:s');
        }

        return Database::insert('posts', $data);
    }

    public static function update(int $id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');

        // Set published_at on first publish
        if (($data['status'] ?? '') === 'published') {
            $existing = Database::fetchOne("SELECT published_at FROM posts WHERE post_id = ?", [$id]);
            if ($existing && empty($existing['published_at'])) {
                $data['published_at'] = date('Y-m-d H:i:s');
            }
        }

        return Database::update('posts', $data, 'post_id = ?', [$id]) > 0;
    }

    public static function delete(int $id): bool
    {
        // Clean up junctions
        Database::delete('posts_categories', 'post_id = ?', [$id]);
        Database::delete('posts_tags', 'post_id = ?', [$id]);
        Database::delete('posts_authors', 'post_id = ?', [$id]);
        return Database::delete('posts', 'post_id = ?', [$id]) > 0;
    }

    public static function getPublished(int $siteId, int $limit = 12, int $offset = 0): array
    {
        return Database::fetchAll(
            "SELECT p.*, u.username, u.display_name as author_name
             FROM posts p
             LEFT JOIN users u ON p.author_id = u.user_id
             WHERE p.site_id = ? AND p.status = 'published' AND p.post_type = 'post'
             ORDER BY p.published_at DESC
             LIMIT ? OFFSET ?",
            [$siteId, $limit, $offset]
        );
    }

    public static function getAll(int $siteId, ?string $status = null, int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT p.*, u.username, u.display_name as author_name
                FROM posts p
                LEFT JOIN users u ON p.author_id = u.user_id
                WHERE p.site_id = ?";
        $params = [$siteId];

        if ($status) {
            $sql .= " AND p.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY p.updated_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        return Database::fetchAll($sql, $params);
    }

    public static function getByCategory(int $categoryId, int $siteId, int $limit = 12, int $offset = 0): array
    {
        return Database::fetchAll(
            "SELECT p.*, u.username, u.display_name as author_name
             FROM posts p
             INNER JOIN posts_categories pc ON p.post_id = pc.post_id
             LEFT JOIN users u ON p.author_id = u.user_id
             WHERE pc.category_id = ? AND p.site_id = ? AND p.status = 'published'
             ORDER BY p.published_at DESC
             LIMIT ? OFFSET ?",
            [$categoryId, $siteId, $limit, $offset]
        );
    }

    public static function getByTag(int $tagId, int $siteId, int $limit = 12, int $offset = 0): array
    {
        return Database::fetchAll(
            "SELECT p.*, u.username, u.display_name as author_name
             FROM posts p
             INNER JOIN posts_tags pt ON p.post_id = pt.post_id
             LEFT JOIN users u ON p.author_id = u.user_id
             WHERE pt.tag_id = ? AND p.site_id = ? AND p.status = 'published'
             ORDER BY p.published_at DESC
             LIMIT ? OFFSET ?",
            [$tagId, $siteId, $limit, $offset]
        );
    }

    public static function getByAuthor(int $userId, int $siteId, int $limit = 12, int $offset = 0): array
    {
        return Database::fetchAll(
            "SELECT p.*, u.username, u.display_name as author_name
             FROM posts p
             LEFT JOIN users u ON p.author_id = u.user_id
             WHERE p.author_id = ? AND p.site_id = ? AND p.status = 'published'
             ORDER BY p.published_at DESC
             LIMIT ? OFFSET ?",
            [$userId, $siteId, $limit, $offset]
        );
    }

    public static function search(string $query, int $siteId, int $limit = 20): array
    {
        $like = '%' . $query . '%';
        return Database::fetchAll(
            "SELECT p.*, u.username, u.display_name as author_name
             FROM posts p
             LEFT JOIN users u ON p.author_id = u.user_id
             WHERE p.site_id = ? AND p.status = 'published'
             AND (p.title LIKE ? OR p.content LIKE ? OR p.excerpt LIKE ?)
             ORDER BY p.published_at DESC
             LIMIT ?",
            [$siteId, $like, $like, $like, $limit]
        );
    }

    public static function countByStatus(int $siteId): array
    {
        $rows = Database::fetchAll(
            "SELECT status, COUNT(*) as count FROM posts WHERE site_id = ? GROUP BY status",
            [$siteId]
        );
        $counts = ['draft' => 0, 'published' => 0, 'archived' => 0, 'pending_review' => 0, 'total' => 0];
        foreach ($rows as $row) {
            $counts[$row['status']] = (int) $row['count'];
            $counts['total'] += (int) $row['count'];
        }
        return $counts;
    }

    public static function countPublished(int $siteId): int
    {
        return (int) Database::fetchColumn(
            "SELECT COUNT(*) FROM posts WHERE site_id = ? AND status = 'published'",
            [$siteId]
        );
    }

    public static function getRelated(int $postId, int $siteId, array $categoryIds, int $limit = 4): array
    {
        if (empty($categoryIds)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
        $params = array_merge($categoryIds, [$postId, $siteId, $limit]);

        return Database::fetchAll(
            "SELECT DISTINCT p.*, u.display_name as author_name
             FROM posts p
             INNER JOIN posts_categories pc ON p.post_id = pc.post_id
             LEFT JOIN users u ON p.author_id = u.user_id
             WHERE pc.category_id IN ({$placeholders})
             AND p.post_id != ? AND p.site_id = ? AND p.status = 'published'
             ORDER BY p.published_at DESC
             LIMIT ?",
            $params
        );
    }

    // --- Relationship methods ---

    public static function syncCategories(int $postId, array $categoryIds): void
    {
        Database::delete('posts_categories', 'post_id = ?', [$postId]);
        foreach ($categoryIds as $catId) {
            Database::insert('posts_categories', [
                'post_id' => $postId,
                'category_id' => (int) $catId,
            ]);
        }
    }

    public static function syncTags(int $postId, array $tagSlugs): void
    {
        Database::delete('posts_tags', 'post_id = ?', [$postId]);
        foreach ($tagSlugs as $tagSlug) {
            $tagSlug = trim($tagSlug);
            if (empty($tagSlug)) continue;
            $tag = Tag::findOrCreate($tagSlug);
            Database::insert('posts_tags', [
                'post_id' => $postId,
                'tag_id' => $tag['tag_id'],
            ]);
        }
        Tag::recount();
    }

    public static function syncAuthors(int $postId, array $userIds, int $primaryId): void
    {
        Database::delete('posts_authors', 'post_id = ?', [$postId]);
        foreach ($userIds as $uid) {
            Database::insert('posts_authors', [
                'post_id' => $postId,
                'user_id' => (int) $uid,
                'is_primary' => ($uid == $primaryId) ? 1 : 0,
            ]);
        }
    }

    public static function getCategories(int $postId): array
    {
        return Database::fetchAll(
            "SELECT c.* FROM categories c
             INNER JOIN posts_categories pc ON c.category_id = pc.category_id
             WHERE pc.post_id = ?
             ORDER BY c.sort_order, c.cat_name",
            [$postId]
        );
    }

    public static function getTags(int $postId): array
    {
        return Database::fetchAll(
            "SELECT t.* FROM tags t
             INNER JOIN posts_tags pt ON t.tag_id = pt.tag_id
             WHERE pt.post_id = ?
             ORDER BY t.tag_name",
            [$postId]
        );
    }

    public static function getAuthors(int $postId): array
    {
        return Database::fetchAll(
            "SELECT u.user_id, u.username, u.display_name, u.avatar, u.bio, pa.is_primary
             FROM users u
             INNER JOIN posts_authors pa ON u.user_id = pa.user_id
             WHERE pa.post_id = ?
             ORDER BY pa.is_primary DESC",
            [$postId]
        );
    }

    public static function incrementViews(int $postId): void
    {
        Database::query("UPDATE posts SET view_count = view_count + 1 WHERE post_id = ?", [$postId]);
    }
}

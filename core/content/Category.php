<?php

namespace Cainty\Content;

use Cainty\Database\Database;

/**
 * Category Model
 */
class Category
{
    public static function findById(int $id): ?array
    {
        return Database::fetchOne("SELECT * FROM categories WHERE category_id = ?", [$id]);
    }

    public static function findBySlug(string $slug, int $siteId): ?array
    {
        return Database::fetchOne(
            "SELECT * FROM categories WHERE cat_slug = ? AND site_id = ?",
            [$slug, $siteId]
        );
    }

    public static function create(array $data): int
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        return Database::insert('categories', $data);
    }

    public static function update(int $id, array $data): bool
    {
        return Database::update('categories', $data, 'category_id = ?', [$id]) > 0;
    }

    public static function delete(int $id): bool
    {
        // Remove category associations
        Database::delete('posts_categories', 'category_id = ?', [$id]);
        // Move children to parent
        $cat = self::findById($id);
        if ($cat) {
            Database::update('categories', [
                'parent_id' => $cat['parent_id'],
            ], 'parent_id = ?', [$id]);
        }
        return Database::delete('categories', 'category_id = ?', [$id]) > 0;
    }

    public static function getBySite(int $siteId): array
    {
        return Database::fetchAll(
            "SELECT * FROM categories WHERE site_id = ? ORDER BY sort_order, cat_name",
            [$siteId]
        );
    }

    /**
     * Get categories as a tree structure
     */
    public static function getTree(int $siteId): array
    {
        $all = self::getBySite($siteId);
        return self::buildTree($all);
    }

    /**
     * Update the post count for a category
     */
    public static function updatePostCount(int $categoryId): void
    {
        $count = Database::fetchColumn(
            "SELECT COUNT(*) FROM posts_categories pc
             INNER JOIN posts p ON pc.post_id = p.post_id
             WHERE pc.category_id = ? AND p.status = 'published'",
            [$categoryId]
        );
        Database::update('categories', ['post_count' => (int) $count], 'category_id = ?', [$categoryId]);
    }

    /**
     * Recount all categories for a site
     */
    public static function recountAll(int $siteId): void
    {
        $categories = self::getBySite($siteId);
        foreach ($categories as $cat) {
            self::updatePostCount($cat['category_id']);
        }
    }

    /**
     * Build a tree from flat array
     */
    private static function buildTree(array $items, ?int $parentId = null): array
    {
        $tree = [];
        foreach ($items as $item) {
            $itemParent = $item['parent_id'] ? (int) $item['parent_id'] : null;
            if ($itemParent === $parentId) {
                $item['children'] = self::buildTree($items, (int) $item['category_id']);
                $tree[] = $item;
            }
        }
        return $tree;
    }
}

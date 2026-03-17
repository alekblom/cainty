<?php

namespace Cainty\Content;

use Cainty\Database\Database;

/**
 * Tag Model (global, not per-site)
 */
class Tag
{
    public static function findById(int $id): ?array
    {
        return Database::fetchOne("SELECT * FROM tags WHERE tag_id = ?", [$id]);
    }

    public static function findBySlug(string $slug): ?array
    {
        return Database::fetchOne("SELECT * FROM tags WHERE tag_slug = ?", [$slug]);
    }

    public static function create(string $name): int
    {
        $slug = cainty_slug($name);
        return Database::insert('tags', [
            'tag_name' => $name,
            'tag_slug' => $slug,
        ]);
    }

    public static function delete(int $id): bool
    {
        Database::delete('posts_tags', 'tag_id = ?', [$id]);
        return Database::delete('tags', 'tag_id = ?', [$id]) > 0;
    }

    /**
     * Find a tag by slug or create it
     */
    public static function findOrCreate(string $nameOrSlug): array
    {
        $slug = cainty_slug($nameOrSlug);
        $tag = self::findBySlug($slug);
        if ($tag) {
            return $tag;
        }

        $name = $nameOrSlug;
        $id = Database::insert('tags', [
            'tag_name' => $name,
            'tag_slug' => $slug,
        ]);

        return [
            'tag_id' => $id,
            'tag_name' => $name,
            'tag_slug' => $slug,
            'post_count' => 0,
        ];
    }

    /**
     * Get all tags ordered by name
     */
    public static function getAll(): array
    {
        return Database::fetchAll("SELECT * FROM tags ORDER BY tag_name");
    }

    /**
     * Get popular tags
     */
    public static function getPopular(int $limit = 20): array
    {
        return Database::fetchAll(
            "SELECT * FROM tags WHERE post_count > 0 ORDER BY post_count DESC LIMIT ?",
            [$limit]
        );
    }

    /**
     * Recount all tag post counts
     */
    public static function recount(): void
    {
        Database::query("
            UPDATE tags SET post_count = (
                SELECT COUNT(*) FROM posts_tags pt
                INNER JOIN posts p ON pt.post_id = p.post_id
                WHERE pt.tag_id = tags.tag_id AND p.status = 'published'
            )
        ");
    }
}

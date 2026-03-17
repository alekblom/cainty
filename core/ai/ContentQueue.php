<?php

namespace Cainty\AI;

use Cainty\Database\Database;
use Cainty\Content\Post;
use Cainty\Content\Category;
use Cainty\Content\Tag;
use Cainty\Plugins\Hook;

/**
 * Content review queue
 *
 * Manages AI-generated content awaiting human review.
 */
class ContentQueue
{
    /**
     * Add content to the queue from an agent run
     */
    public static function addFromRun(int $runId, int $siteId, array $parsed): int
    {
        return Database::insert('content_queue', [
            'site_id' => $siteId,
            'agent_run_id' => $runId,
            'title' => $parsed['title'] ?? 'Untitled',
            'slug' => $parsed['slug'] ?? cainty_slug($parsed['title'] ?? 'untitled'),
            'content' => $parsed['content'] ?? '',
            'excerpt' => $parsed['excerpt'] ?? null,
            'meta_title' => $parsed['meta_title'] ?? null,
            'meta_description' => $parsed['meta_description'] ?? null,
            'categories' => !empty($parsed['categories']) ? json_encode($parsed['categories']) : null,
            'tags' => !empty($parsed['tags']) ? json_encode($parsed['tags']) : null,
            'image_prompt' => $parsed['image_prompt'] ?? null,
            'status' => 'pending_review',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get pending review items for a site
     */
    public static function getPending(int $siteId, int $limit = 50): array
    {
        return Database::fetchAll(
            "SELECT q.*, a.name as agent_name
             FROM content_queue q
             LEFT JOIN agent_runs r ON q.agent_run_id = r.run_id
             LEFT JOIN agents a ON r.agent_id = a.agent_id
             WHERE q.site_id = ? AND q.status = 'pending_review'
             ORDER BY q.created_at DESC
             LIMIT ?",
            [$siteId, $limit]
        );
    }

    /**
     * Get queue items by status
     */
    public static function getByStatus(int $siteId, string $status, int $limit = 50): array
    {
        return Database::fetchAll(
            "SELECT q.*, a.name as agent_name
             FROM content_queue q
             LEFT JOIN agent_runs r ON q.agent_run_id = r.run_id
             LEFT JOIN agents a ON r.agent_id = a.agent_id
             WHERE q.site_id = ? AND q.status = ?
             ORDER BY q.created_at DESC
             LIMIT ?",
            [$siteId, $status, $limit]
        );
    }

    /**
     * Get all queue items for a site
     */
    public static function getAll(int $siteId, int $limit = 100): array
    {
        return Database::fetchAll(
            "SELECT q.*, a.name as agent_name
             FROM content_queue q
             LEFT JOIN agent_runs r ON q.agent_run_id = r.run_id
             LEFT JOIN agents a ON r.agent_id = a.agent_id
             WHERE q.site_id = ?
             ORDER BY q.created_at DESC
             LIMIT ?",
            [$siteId, $limit]
        );
    }

    /**
     * Get a queue item by ID
     */
    public static function getById(int $queueId): ?array
    {
        return Database::fetchOne(
            "SELECT q.*, a.name as agent_name, r.topic_prompt, r.model_used, r.provider_used,
                    r.input_tokens, r.output_tokens, r.duration_ms
             FROM content_queue q
             LEFT JOIN agent_runs r ON q.agent_run_id = r.run_id
             LEFT JOIN agents a ON r.agent_id = a.agent_id
             WHERE q.queue_id = ?",
            [$queueId]
        );
    }

    /**
     * Approve a queue item → create a post with status 'draft' or 'published'
     */
    public static function approve(int $queueId, int $reviewerId, ?string $notes = null, string $postStatus = 'draft'): ?int
    {
        $item = self::getById($queueId);
        if (!$item || $item['status'] !== 'pending_review') {
            return null;
        }

        // Create the post
        $postData = [
            'site_id' => $item['site_id'],
            'author_id' => $reviewerId,
            'title' => $item['title'],
            'slug' => $item['slug'],
            'content' => $item['content'],
            'excerpt' => $item['excerpt'],
            'meta_title' => $item['meta_title'],
            'meta_description' => $item['meta_description'],
            'status' => $postStatus,
            'post_type' => 'post',
            'agent_run_id' => $item['agent_run_id'],
        ];

        $postId = Post::create($postData);

        // Sync categories
        $categories = json_decode($item['categories'] ?? '[]', true);
        if (!empty($categories)) {
            $catIds = [];
            foreach ($categories as $catSlug) {
                $cat = Category::findBySlug($catSlug, $item['site_id']);
                if ($cat) {
                    $catIds[] = $cat['category_id'];
                }
            }
            if (!empty($catIds)) {
                Post::syncCategories($postId, $catIds);
            }
        }

        // Sync tags
        $tags = json_decode($item['tags'] ?? '[]', true);
        if (!empty($tags)) {
            $tagIds = [];
            foreach ($tags as $tagName) {
                $tag = Tag::findOrCreate($tagName);
                if ($tag) {
                    $tagIds[] = $tag['tag_id'];
                }
            }
            if (!empty($tagIds)) {
                Post::syncTags($postId, $tagIds);
            }
        }

        // Update queue item
        Database::update('content_queue', [
            'status' => 'approved',
            'reviewed_by' => $reviewerId,
            'review_notes' => $notes,
            'reviewed_at' => date('Y-m-d H:i:s'),
        ], 'queue_id = ?', [$queueId]);

        // Update agent run with post_id
        if ($item['agent_run_id']) {
            Database::update('agent_runs', [
                'post_id' => $postId,
            ], 'run_id = ?', [$item['agent_run_id']]);
        }

        // Fire hook
        Hook::fire('queue_item_approved', $queueId, $postId, $item);

        return $postId;
    }

    /**
     * Reject a queue item
     */
    public static function reject(int $queueId, int $reviewerId, ?string $reason = null): bool
    {
        $item = self::getById($queueId);
        if (!$item || $item['status'] !== 'pending_review') {
            return false;
        }

        Database::update('content_queue', [
            'status' => 'rejected',
            'reviewed_by' => $reviewerId,
            'review_notes' => $reason,
            'reviewed_at' => date('Y-m-d H:i:s'),
        ], 'queue_id = ?', [$queueId]);

        Hook::fire('queue_item_rejected', $queueId, $item);

        return true;
    }

    /**
     * Update queue item content (inline edit before approve)
     */
    public static function updateContent(int $queueId, array $edits): bool
    {
        $allowed = ['title', 'slug', 'content', 'excerpt', 'meta_title', 'meta_description', 'categories', 'tags'];
        $data = [];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $edits)) {
                $value = $edits[$field];
                if (in_array($field, ['categories', 'tags']) && is_array($value)) {
                    $value = json_encode($value);
                }
                $data[$field] = $value;
            }
        }

        if (empty($data)) {
            return false;
        }

        return Database::update('content_queue', $data, 'queue_id = ?', [$queueId]);
    }

    /**
     * Count items by status for a site
     */
    public static function countByStatus(int $siteId): array
    {
        $rows = Database::fetchAll(
            "SELECT status, COUNT(*) as cnt FROM content_queue WHERE site_id = ? GROUP BY status",
            [$siteId]
        );

        $counts = ['pending_review' => 0, 'approved' => 0, 'rejected' => 0, 'published' => 0];
        foreach ($rows as $row) {
            $counts[$row['status']] = (int) $row['cnt'];
        }
        return $counts;
    }

    /**
     * Publish a queue item (approve + set post status to published)
     */
    public static function publish(int $queueId, int $reviewerId, ?string $notes = null): ?int
    {
        return self::approve($queueId, $reviewerId, $notes, 'published');
    }
}

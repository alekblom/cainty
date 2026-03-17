<?php

namespace Cainty\AI;

use Cainty\Database\Database;

/**
 * AI Agent
 *
 * Loads agent config from DB, builds prompts, validates output.
 */
class Agent
{
    private array $data;
    private AgentMemory $memory;

    public function __construct(array $data)
    {
        $this->data = $data;
        $this->memory = new AgentMemory($data['agent_id']);
    }

    /**
     * Load an agent by ID
     */
    public static function findById(int $id): ?self
    {
        $row = Database::fetchOne("SELECT * FROM agents WHERE agent_id = ?", [$id]);
        return $row ? new self($row) : null;
    }

    /**
     * Load an agent by slug and site
     */
    public static function findBySlug(string $slug, int $siteId): ?self
    {
        $row = Database::fetchOne(
            "SELECT * FROM agents WHERE slug = ? AND site_id = ?",
            [$slug, $siteId]
        );
        return $row ? new self($row) : null;
    }

    /**
     * Get agent data field
     */
    public function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Get the agent memory instance
     */
    public function getMemory(): AgentMemory
    {
        return $this->memory;
    }

    /**
     * Build the full system prompt with context
     */
    public function buildSystemPrompt(int $siteId): string
    {
        $parts = [];

        // Core system prompt
        $parts[] = $this->data['system_prompt'];

        // Voice/style rules
        $voiceRules = $this->decodeJSON('voice_rules');
        if (!empty($voiceRules)) {
            $parts[] = "\n## Voice & Style Rules";
            foreach ($voiceRules as $rule) {
                $parts[] = "- {$rule}";
            }
        }

        // Shortcode rules
        $shortcodeRules = $this->decodeJSON('shortcode_rules');
        if (!empty($shortcodeRules)) {
            $parts[] = "\n## Available Shortcodes";
            foreach ($shortcodeRules as $code => $desc) {
                $parts[] = "- [{$code}]: {$desc}";
            }
        }

        // Agent memory context
        $memoryContext = $this->memory->buildContextString();
        if ($memoryContext) {
            $parts[] = "\n" . $memoryContext;
        }

        // Site categories
        $categories = $this->getSiteCategories($siteId);
        if (!empty($categories)) {
            $parts[] = "\n## Available Categories";
            foreach ($categories as $cat) {
                $parts[] = "- {$cat['cat_name']} (slug: {$cat['cat_slug']})";
            }
        }

        // Existing slugs to avoid duplicates
        $existingSlugs = $this->getRecentSlugs($siteId);
        if (!empty($existingSlugs)) {
            $parts[] = "\n## Existing Post Slugs (do NOT reuse)";
            $parts[] = implode(', ', $existingSlugs);
        }

        // Output schema
        $outputSchema = $this->decodeJSON('output_schema');
        if (!empty($outputSchema)) {
            $parts[] = "\n## Required Output Schema (JSON)";
            $parts[] = "You MUST respond with a JSON object matching this schema:";
            $parts[] = "```json\n" . json_encode($outputSchema, JSON_PRETTY_PRINT) . "\n```";
        } else {
            // Default output schema
            $parts[] = "\n## Required Output Format";
            $parts[] = "You MUST respond with a JSON object containing:";
            $parts[] = '```json
{
  "title": "Post title",
  "slug": "url-friendly-slug",
  "content": "Full HTML content of the post",
  "excerpt": "1-2 sentence summary",
  "meta_title": "SEO title (max 60 chars)",
  "meta_description": "SEO description (max 155 chars)",
  "categories": ["category-slug"],
  "tags": ["tag1", "tag2"],
  "image_prompt": "Prompt for generating a featured image"
}
```';
        }

        // Post length guidance
        $minLen = $this->data['post_length_min'] ?? 800;
        $maxLen = $this->data['post_length_max'] ?? 1500;
        $parts[] = "\n## Post Length";
        $parts[] = "Target word count: {$minLen}-{$maxLen} words.";

        // Quality checklist
        $checklist = $this->decodeJSON('quality_checklist');
        if (!empty($checklist)) {
            $parts[] = "\n## Quality Checklist";
            foreach ($checklist as $item) {
                $parts[] = "- [ ] {$item}";
            }
        }

        return implode("\n", $parts);
    }

    /**
     * Build the user prompt for a given topic
     */
    public function buildUserPrompt(string $topic): string
    {
        return "Write a blog post about the following topic:\n\n{$topic}";
    }

    /**
     * Validate parsed output against the quality checklist and required fields
     */
    public function validateOutput(?array $parsed): array
    {
        if ($parsed === null) {
            return ['valid' => false, 'errors' => ['Output is not valid JSON']];
        }

        $errors = [];

        // Required fields
        $required = ['title', 'slug', 'content'];
        foreach ($required as $field) {
            if (empty($parsed[$field])) {
                $errors[] = "Missing required field: {$field}";
            }
        }

        // Content length check
        if (!empty($parsed['content'])) {
            $wordCount = str_word_count(strip_tags($parsed['content']));
            $minLen = $this->data['post_length_min'] ?? 800;
            $maxLen = $this->data['post_length_max'] ?? 1500;

            if ($wordCount < $minLen * 0.5) {
                $errors[] = "Content too short: {$wordCount} words (minimum ~{$minLen})";
            }
        }

        // Slug format
        if (!empty($parsed['slug']) && !preg_match('/^[a-z0-9\-]+$/', $parsed['slug'])) {
            $errors[] = "Slug contains invalid characters: {$parsed['slug']}";
        }

        // Quality checklist
        $checklist = $this->decodeJSON('quality_checklist');
        if (!empty($checklist) && !empty($parsed['content'])) {
            $content = strtolower(strip_tags($parsed['content']));
            foreach ($checklist as $item) {
                // Simple keyword presence check for checklist items that look like keywords
                if (str_starts_with($item, 'contains:')) {
                    $keyword = trim(substr($item, 9));
                    if (stripos($content, $keyword) === false) {
                        $errors[] = "Quality check failed: content should contain '{$keyword}'";
                    }
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Decode a JSON column value
     */
    private function decodeJSON(string $field): ?array
    {
        $value = $this->data[$field] ?? null;
        if (empty($value)) {
            return null;
        }
        $decoded = json_decode($value, true);
        return (json_last_error() === JSON_ERROR_NONE) ? $decoded : null;
    }

    /**
     * Get site categories
     */
    private function getSiteCategories(int $siteId): array
    {
        try {
            return Database::fetchAll(
                "SELECT cat_name, cat_slug FROM categories WHERE site_id = ? ORDER BY cat_name",
                [$siteId]
            );
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Get recent post slugs to avoid duplicates
     */
    private function getRecentSlugs(int $siteId, int $limit = 100): array
    {
        try {
            $rows = Database::fetchAll(
                "SELECT slug FROM posts WHERE site_id = ? ORDER BY created_at DESC LIMIT ?",
                [$siteId, $limit]
            );
            return array_column($rows, 'slug');
        } catch (\PDOException $e) {
            return [];
        }
    }
}

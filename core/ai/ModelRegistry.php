<?php

namespace Cainty\AI;

use Cainty\Database\Database;

/**
 * Registry of known LLM models per provider
 */
class ModelRegistry
{
    private static array $models = [
        'anthropic' => [
            ['slug' => 'claude-opus-4-6', 'name' => 'Claude Opus 4.6', 'max_output' => 32000],
            ['slug' => 'claude-sonnet-4-5-20250929', 'name' => 'Claude 4.5 Sonnet', 'max_output' => 16384],
            ['slug' => 'claude-haiku-4-5-20251001', 'name' => 'Claude 4.5 Haiku', 'max_output' => 8192],
        ],
        'openai' => [
            ['slug' => 'gpt-5', 'name' => 'GPT-5', 'max_output' => 16384],
            ['slug' => 'gpt-5-mini', 'name' => 'GPT-5 Mini', 'max_output' => 16384],
            ['slug' => 'gpt-4o', 'name' => 'GPT-4o', 'max_output' => 16384],
            ['slug' => 'gpt-4o-mini', 'name' => 'GPT-4o Mini', 'max_output' => 16384],
            ['slug' => 'o4-mini', 'name' => 'o4-mini', 'max_output' => 16384],
        ],
        'google' => [
            ['slug' => 'gemini-2.5-pro', 'name' => 'Gemini 2.5 Pro', 'max_output' => 65536],
            ['slug' => 'gemini-2.5-flash', 'name' => 'Gemini 2.5 Flash', 'max_output' => 65536],
            ['slug' => 'gemini-2.5-flash-lite', 'name' => 'Gemini 2.5 Flash Lite', 'max_output' => 65536],
        ],
        'deepseek' => [
            ['slug' => 'deepseek-chat', 'name' => 'DeepSeek V3', 'max_output' => 8192],
            ['slug' => 'deepseek-reasoner', 'name' => 'DeepSeek R1', 'max_output' => 8192],
        ],
        'xai' => [
            ['slug' => 'grok-4-0709', 'name' => 'Grok 4', 'max_output' => 16384],
            ['slug' => 'grok-4-fast-non-reasoning', 'name' => 'Grok 4 Fast', 'max_output' => 16384],
        ],
        'ollama' => [
            ['slug' => 'llama3.3', 'name' => 'Llama 3.3', 'max_output' => 8192],
            ['slug' => 'mistral', 'name' => 'Mistral', 'max_output' => 8192],
            ['slug' => 'qwen2.5', 'name' => 'Qwen 2.5', 'max_output' => 8192],
            ['slug' => 'gemma2', 'name' => 'Gemma 2', 'max_output' => 8192],
        ],
    ];

    /**
     * Get models for a specific provider
     */
    public static function getForProvider(string $provider): array
    {
        return self::$models[$provider] ?? [];
    }

    /**
     * Get all models grouped by provider
     */
    public static function getAll(): array
    {
        return self::$models;
    }

    /**
     * Find a model by slug across all providers
     */
    public static function findBySlug(string $slug): ?array
    {
        foreach (self::$models as $provider => $models) {
            foreach ($models as $model) {
                if ($model['slug'] === $slug) {
                    $model['provider'] = $provider;
                    return $model;
                }
            }
        }
        return null;
    }

    /**
     * Get all providers that have a configured API key for a site
     */
    public static function getAvailableProviders(int $siteId): array
    {
        try {
            $rows = Database::fetchAll(
                "SELECT provider FROM llm_api_keys WHERE site_id = ? AND is_active = 1",
                [$siteId]
            );
            $providers = array_column($rows, 'provider');

            // Also check .env keys
            $envProviders = [
                'anthropic' => 'ANTHROPIC_API_KEY',
                'openai' => 'OPENAI_API_KEY',
                'google' => 'GOOGLE_API_KEY',
                'deepseek' => 'DEEPSEEK_API_KEY',
                'xai' => 'XAI_API_KEY',
                'ollama' => 'OLLAMA_BASE_URL',
            ];

            foreach ($envProviders as $prov => $envKey) {
                if (!in_array($prov, $providers) && !empty(cainty_config($envKey))) {
                    $providers[] = $prov;
                }
            }

            return array_unique($providers);
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Get models available for a site (only from configured providers)
     */
    public static function getAvailable(int $siteId): array
    {
        $providers = self::getAvailableProviders($siteId);
        $available = [];
        foreach ($providers as $provider) {
            if (isset(self::$models[$provider])) {
                foreach (self::$models[$provider] as $model) {
                    $model['provider'] = $provider;
                    $available[] = $model;
                }
            }
        }
        return $available;
    }
}

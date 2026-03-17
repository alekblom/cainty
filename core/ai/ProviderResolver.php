<?php

namespace Cainty\AI;

use Cainty\Database\Database;
use Cainty\AI\Providers\AnthropicProvider;
use Cainty\AI\Providers\OpenAIProvider;
use Cainty\AI\Providers\GoogleProvider;
use Cainty\AI\Providers\DeepSeekProvider;
use Cainty\AI\Providers\XAIProvider;
use Cainty\AI\Providers\OllamaProvider;

/**
 * Resolves which LLM provider to use for a given request
 *
 * Priority: own API key (DB) → own API key (.env) → Oomph → error
 */
class ProviderResolver
{
    /**
     * Resolve a provider instance for the given provider name and site
     */
    public static function resolve(string $provider, int $siteId): LLMProviderInterface
    {
        // 1. Check DB for stored API key
        $key = self::getStoredKey($siteId, $provider);
        if ($key) {
            return self::createProvider($provider, $key['api_key'], $key['base_url'] ?? null);
        }

        // 2. Check .env config
        $envKey = self::getEnvKey($provider);
        if ($envKey) {
            $baseUrl = ($provider === 'ollama') ? cainty_config('OLLAMA_BASE_URL') : null;
            return self::createProvider($provider, $envKey, $baseUrl);
        }

        // 3. Check if Oomph is enabled
        if (cainty_config('OOMPH_ENABLED') === 'true') {
            // Oomph provider handles its own API routing
            $oomphProvider = CAINTY_ROOT . '/core/ai/providers/OomphProvider.php';
            if (file_exists($oomphProvider)) {
                require_once $oomphProvider;
                return new \Cainty\AI\Providers\OomphProvider($provider);
            }
        }

        throw new \RuntimeException("No API key configured for provider: {$provider}. Add one in Settings > LLM Keys.");
    }

    /**
     * Get a stored API key from the database
     */
    private static function getStoredKey(int $siteId, string $provider): ?array
    {
        try {
            $row = Database::fetchOne(
                "SELECT api_key_enc, base_url FROM llm_api_keys WHERE site_id = ? AND provider = ? AND is_active = 1",
                [$siteId, $provider]
            );
            if ($row) {
                return [
                    'api_key' => self::decrypt($row['api_key_enc']),
                    'base_url' => $row['base_url'],
                ];
            }
        } catch (\PDOException $e) {
            // Table may not exist yet
        }
        return null;
    }

    /**
     * Get API key from .env
     */
    private static function getEnvKey(string $provider): ?string
    {
        $map = [
            'anthropic' => 'ANTHROPIC_API_KEY',
            'openai' => 'OPENAI_API_KEY',
            'google' => 'GOOGLE_API_KEY',
            'deepseek' => 'DEEPSEEK_API_KEY',
            'xai' => 'XAI_API_KEY',
            'ollama' => 'OLLAMA_BASE_URL',
        ];

        $envKey = $map[$provider] ?? null;
        if ($envKey) {
            $value = cainty_config($envKey);
            return !empty($value) ? $value : null;
        }
        return null;
    }

    /**
     * Create a provider instance
     */
    private static function createProvider(string $provider, string $apiKey, ?string $baseUrl = null): LLMProviderInterface
    {
        return match ($provider) {
            'anthropic' => new AnthropicProvider($apiKey),
            'openai' => new OpenAIProvider($apiKey),
            'google' => new GoogleProvider($apiKey),
            'deepseek' => new DeepSeekProvider($apiKey),
            'xai' => new XAIProvider($apiKey),
            'ollama' => new OllamaProvider($baseUrl ?: $apiKey),
            default => throw new \RuntimeException("Unknown provider: {$provider}"),
        };
    }

    /**
     * Encrypt an API key for storage
     */
    public static function encrypt(string $plaintext): string
    {
        $secret = cainty_config('APP_SECRET', 'default-secret');
        $key = hash('sha256', $secret, true);
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($plaintext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt a stored API key
     */
    private static function decrypt(string $ciphertext): string
    {
        $secret = cainty_config('APP_SECRET', 'default-secret');
        $key = hash('sha256', $secret, true);
        $data = base64_decode($ciphertext);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        $decrypted = openssl_decrypt($encrypted, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        return $decrypted ?: '';
    }
}

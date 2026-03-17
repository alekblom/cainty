<?php

namespace Cainty\AI;

/**
 * Unified LLM Client
 *
 * High-level interface for calling any LLM provider.
 */
class LLMClient
{
    private LLMProviderInterface $provider;

    public function __construct(LLMProviderInterface $provider)
    {
        $this->provider = $provider;
    }

    /**
     * Create a client for a given provider and site
     */
    public static function forProvider(string $provider, int $siteId): self
    {
        $resolved = ProviderResolver::resolve($provider, $siteId);
        return new self($resolved);
    }

    /**
     * Send a chat message and get a text response
     */
    public function chat(string $model, string $systemPrompt, string $userPrompt, array $options = []): array
    {
        $startTime = microtime(true);

        try {
            $result = $this->provider->send($model, $systemPrompt, $userPrompt, $options);

            $result['duration_ms'] = (int) ((microtime(true) - $startTime) * 1000);
            $result['provider'] = $this->provider->getName();
            $result['model'] = $model;
            $result['success'] = true;

            return $result;
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'content' => '',
                'input_tokens' => 0,
                'output_tokens' => 0,
                'duration_ms' => (int) ((microtime(true) - $startTime) * 1000),
                'provider' => $this->provider->getName(),
                'model' => $model,
            ];
        }
    }

    /**
     * Send a chat message expecting JSON response
     */
    public function chatJSON(string $model, string $systemPrompt, string $userPrompt, array $options = []): array
    {
        // Add JSON instruction to system prompt if provider supports it
        if ($this->provider->supportsJSON()) {
            $options['response_format'] = ['type' => 'json_object'];
        } else {
            $systemPrompt .= "\n\nIMPORTANT: You MUST respond with valid JSON only. No markdown, no code fences, no explanations — just the JSON object.";
        }

        $result = $this->chat($model, $systemPrompt, $userPrompt, $options);

        if ($result['success'] && !empty($result['content'])) {
            // Try to parse JSON from response
            $content = $result['content'];

            // Strip markdown code fences if present
            $content = preg_replace('/^```(?:json)?\s*\n?/i', '', $content);
            $content = preg_replace('/\n?```\s*$/', '', $content);
            $content = trim($content);

            $parsed = json_decode($content, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $result['parsed'] = $parsed;
            } else {
                $result['parse_error'] = json_last_error_msg();
                $result['parsed'] = null;
            }
        }

        return $result;
    }

    /**
     * Get the underlying provider
     */
    public function getProvider(): LLMProviderInterface
    {
        return $this->provider;
    }
}

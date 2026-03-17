<?php

namespace Cainty\AI\Providers;

use Cainty\AI\LLMProviderInterface;

/**
 * Anthropic Claude provider (Messages API)
 */
class AnthropicProvider implements LLMProviderInterface
{
    private string $apiKey;
    private string $baseUrl = 'https://api.anthropic.com/v1/messages';

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function send(string $model, string $systemPrompt, string $userPrompt, array $options = []): array
    {
        $body = [
            'model' => $model,
            'max_tokens' => $options['max_tokens'] ?? 4096,
            'system' => $systemPrompt,
            'messages' => [
                ['role' => 'user', 'content' => $userPrompt],
            ],
        ];

        if (!empty($options['temperature'])) {
            $body['temperature'] = (float) $options['temperature'];
        }

        if (!empty($options['top_p'])) {
            $body['top_p'] = (float) $options['top_p'];
        }

        $headers = [
            'Content-Type: application/json',
            'x-api-key: ' . $this->apiKey,
            'anthropic-version: 2023-06-01',
        ];

        $response = $this->request($body, $headers);

        if (isset($response['error'])) {
            throw new \RuntimeException('Anthropic API error: ' . ($response['error']['message'] ?? json_encode($response['error'])));
        }

        $content = '';
        if (isset($response['content'])) {
            foreach ($response['content'] as $block) {
                if ($block['type'] === 'text') {
                    $content .= $block['text'];
                }
            }
        }

        return [
            'content' => $content,
            'input_tokens' => $response['usage']['input_tokens'] ?? 0,
            'output_tokens' => $response['usage']['output_tokens'] ?? 0,
        ];
    }

    public function supportsJSON(): bool
    {
        return false;
    }

    public function getName(): string
    {
        return 'anthropic';
    }

    private function request(array $body, array $headers): array
    {
        $ch = curl_init($this->baseUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \RuntimeException('Anthropic request failed: ' . $error);
        }

        if ($httpCode >= 400) {
            $decoded = json_decode($result, true);
            $msg = $decoded['error']['message'] ?? "HTTP {$httpCode}";
            throw new \RuntimeException('Anthropic API error: ' . $msg);
        }

        return json_decode($result, true) ?: [];
    }
}

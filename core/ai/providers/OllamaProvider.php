<?php

namespace Cainty\AI\Providers;

use Cainty\AI\LLMProviderInterface;

/**
 * Ollama provider (OpenAI-compatible API at configurable base URL)
 */
class OllamaProvider implements LLMProviderInterface
{
    private string $baseUrl;

    public function __construct(string $baseUrl)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    public function send(string $model, string $systemPrompt, string $userPrompt, array $options = []): array
    {
        $url = $this->baseUrl . '/v1/chat/completions';

        $body = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
        ];

        if (!empty($options['max_tokens'])) {
            $body['max_tokens'] = (int) $options['max_tokens'];
        }

        if (!empty($options['temperature'])) {
            $body['temperature'] = (float) $options['temperature'];
        }

        if (!empty($options['top_p'])) {
            $body['top_p'] = (float) $options['top_p'];
        }

        if (!empty($options['response_format'])) {
            $body['response_format'] = $options['response_format'];
        }

        $response = $this->request($url, $body);

        if (isset($response['error'])) {
            throw new \RuntimeException('Ollama API error: ' . ($response['error']['message'] ?? json_encode($response['error'])));
        }

        $content = $response['choices'][0]['message']['content'] ?? '';

        return [
            'content' => $content,
            'input_tokens' => $response['usage']['prompt_tokens'] ?? 0,
            'output_tokens' => $response['usage']['completion_tokens'] ?? 0,
        ];
    }

    public function supportsJSON(): bool
    {
        return true;
    }

    public function getName(): string
    {
        return 'ollama';
    }

    private function request(string $url, array $body): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 300,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \RuntimeException('Ollama request failed: ' . $error);
        }

        if ($httpCode >= 400) {
            $decoded = json_decode($result, true);
            $msg = $decoded['error']['message'] ?? "HTTP {$httpCode}";
            throw new \RuntimeException('Ollama API error: ' . $msg);
        }

        return json_decode($result, true) ?: [];
    }
}

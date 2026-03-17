<?php

namespace Cainty\AI\Providers;

use Cainty\AI\LLMProviderInterface;

/**
 * OpenAI provider (Chat Completions API)
 */
class OpenAIProvider implements LLMProviderInterface
{
    private string $apiKey;
    private string $baseUrl = 'https://api.openai.com/v1/chat/completions';

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function send(string $model, string $systemPrompt, string $userPrompt, array $options = []): array
    {
        $body = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            'max_completion_tokens' => $options['max_tokens'] ?? 4096,
        ];

        if (!empty($options['temperature'])) {
            $body['temperature'] = (float) $options['temperature'];
        }

        if (!empty($options['top_p'])) {
            $body['top_p'] = (float) $options['top_p'];
        }

        if (!empty($options['response_format'])) {
            $body['response_format'] = $options['response_format'];
        }

        $response = $this->request($body);

        if (isset($response['error'])) {
            throw new \RuntimeException('OpenAI API error: ' . ($response['error']['message'] ?? json_encode($response['error'])));
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
        return 'openai';
    }

    protected function getHeaders(): array
    {
        return [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey,
        ];
    }

    protected function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    protected function request(array $body): array
    {
        $ch = curl_init($this->getBaseUrl());
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_HTTPHEADER => $this->getHeaders(),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \RuntimeException('OpenAI request failed: ' . $error);
        }

        if ($httpCode >= 400) {
            $decoded = json_decode($result, true);
            $msg = $decoded['error']['message'] ?? "HTTP {$httpCode}";
            throw new \RuntimeException('OpenAI API error: ' . $msg);
        }

        return json_decode($result, true) ?: [];
    }
}

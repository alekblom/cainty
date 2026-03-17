<?php

namespace Cainty\AI\Providers;

use Cainty\AI\LLMProviderInterface;

/**
 * DeepSeek provider (OpenAI-compatible API)
 */
class DeepSeekProvider implements LLMProviderInterface
{
    private string $apiKey;
    private string $baseUrl = 'https://api.deepseek.com/chat/completions';

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
            'max_tokens' => $options['max_tokens'] ?? 4096,
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
            throw new \RuntimeException('DeepSeek API error: ' . ($response['error']['message'] ?? json_encode($response['error'])));
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
        return 'deepseek';
    }

    private function request(array $body): array
    {
        $ch = curl_init($this->baseUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \RuntimeException('DeepSeek request failed: ' . $error);
        }

        if ($httpCode >= 400) {
            $decoded = json_decode($result, true);
            $msg = $decoded['error']['message'] ?? "HTTP {$httpCode}";
            throw new \RuntimeException('DeepSeek API error: ' . $msg);
        }

        return json_decode($result, true) ?: [];
    }
}

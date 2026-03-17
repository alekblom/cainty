<?php

namespace Cainty\AI\Providers;

use Cainty\AI\LLMProviderInterface;

/**
 * Google Gemini provider (Gemini API)
 */
class GoogleProvider implements LLMProviderInterface
{
    private string $apiKey;
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function send(string $model, string $systemPrompt, string $userPrompt, array $options = []): array
    {
        $url = "{$this->baseUrl}/{$model}:generateContent?key={$this->apiKey}";

        $body = [
            'system_instruction' => [
                'parts' => [['text' => $systemPrompt]],
            ],
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [['text' => $userPrompt]],
                ],
            ],
            'generationConfig' => [
                'maxOutputTokens' => $options['max_tokens'] ?? 4096,
            ],
        ];

        if (!empty($options['temperature'])) {
            $body['generationConfig']['temperature'] = (float) $options['temperature'];
        }

        if (!empty($options['top_p'])) {
            $body['generationConfig']['topP'] = (float) $options['top_p'];
        }

        if (!empty($options['response_format']) && $options['response_format']['type'] === 'json_object') {
            $body['generationConfig']['responseMimeType'] = 'application/json';
        }

        $response = $this->request($url, $body);

        if (isset($response['error'])) {
            throw new \RuntimeException('Google API error: ' . ($response['error']['message'] ?? json_encode($response['error'])));
        }

        $content = '';
        if (isset($response['candidates'][0]['content']['parts'])) {
            foreach ($response['candidates'][0]['content']['parts'] as $part) {
                if (isset($part['text'])) {
                    $content .= $part['text'];
                }
            }
        }

        $inputTokens = $response['usageMetadata']['promptTokenCount'] ?? 0;
        $outputTokens = $response['usageMetadata']['candidatesTokenCount'] ?? 0;

        return [
            'content' => $content,
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
        ];
    }

    public function supportsJSON(): bool
    {
        return true;
    }

    public function getName(): string
    {
        return 'google';
    }

    private function request(string $url, array $body): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \RuntimeException('Google request failed: ' . $error);
        }

        if ($httpCode >= 400) {
            $decoded = json_decode($result, true);
            $msg = $decoded['error']['message'] ?? "HTTP {$httpCode}";
            throw new \RuntimeException('Google API error: ' . $msg);
        }

        return json_decode($result, true) ?: [];
    }
}

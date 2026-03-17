<?php

namespace Cainty\AI;

/**
 * Interface for LLM providers
 *
 * Each provider implements this to normalize API differences.
 */
interface LLMProviderInterface
{
    /**
     * Send a chat completion request
     *
     * @return array ['content' => string, 'input_tokens' => int, 'output_tokens' => int]
     */
    public function send(string $model, string $systemPrompt, string $userPrompt, array $options = []): array;

    /**
     * Whether this provider supports forced JSON output mode
     */
    public function supportsJSON(): bool;

    /**
     * Get the provider name
     */
    public function getName(): string;
}

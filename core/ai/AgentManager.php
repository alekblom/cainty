<?php

namespace Cainty\AI;

use Cainty\Database\Database;
use Cainty\Plugins\Hook;

/**
 * Agent execution orchestrator
 *
 * Handles agent CRUD, execution, and run history.
 */
class AgentManager
{
    /**
     * Execute an agent: create run → build prompts → call LLM → parse → validate → queue
     */
    public static function executeAgent(int $agentId, string $topicPrompt, ?int $triggeredBy = null): array
    {
        $agent = Agent::findById($agentId);
        if (!$agent) {
            return ['success' => false, 'error' => 'Agent not found'];
        }

        $siteId = (int) $agent->get('site_id');
        $provider = $agent->get('model_provider', 'anthropic');
        $model = $agent->get('model_slug', 'claude-sonnet-4-5-20250929');

        // Fire pre-execute hook
        Hook::fire('agent_before_execute', $agent, $topicPrompt);

        // Create run record
        $runId = Database::insert('agent_runs', [
            'agent_id' => $agentId,
            'site_id' => $siteId,
            'triggered_by' => $triggeredBy,
            'topic_prompt' => $topicPrompt,
            'model_used' => $model,
            'provider_used' => $provider,
            'status' => 'running',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        try {
            // Build prompts
            $systemPrompt = $agent->buildSystemPrompt($siteId);
            $userPrompt = $agent->buildUserPrompt($topicPrompt);

            // Resolve provider and call LLM
            $client = LLMClient::forProvider($provider, $siteId);
            $result = $client->chatJSON($model, $systemPrompt, $userPrompt, [
                'max_tokens' => $agent->get('post_length_max', 1500) * 4,
            ]);

            // Update run with LLM result
            $updateData = [
                'input_tokens' => $result['input_tokens'] ?? 0,
                'output_tokens' => $result['output_tokens'] ?? 0,
                'duration_ms' => $result['duration_ms'] ?? 0,
                'raw_output' => $result['content'] ?? '',
                'provider_used' => $result['provider'] ?? $provider,
                'model_used' => $result['model'] ?? $model,
            ];

            if (!$result['success']) {
                $updateData['status'] = 'failed';
                $updateData['error_message'] = $result['error'] ?? 'LLM call failed';
                $updateData['completed_at'] = date('Y-m-d H:i:s');
                Database::update('agent_runs', $updateData, 'run_id = ?', [$runId]);

                return ['success' => false, 'error' => $updateData['error_message'], 'run_id' => $runId];
            }

            // Parse the JSON output
            $parsed = $result['parsed'] ?? null;
            if ($parsed === null) {
                $updateData['status'] = 'failed';
                $updateData['error_message'] = 'Failed to parse JSON: ' . ($result['parse_error'] ?? 'unknown');
                $updateData['completed_at'] = date('Y-m-d H:i:s');
                Database::update('agent_runs', $updateData, 'run_id = ?', [$runId]);

                return ['success' => false, 'error' => $updateData['error_message'], 'run_id' => $runId];
            }

            // Fire parsed hook (allows plugins to transform output)
            $parsed = Hook::apply('agent_output_parsed', $parsed, $agent);

            $updateData['parsed_output'] = json_encode($parsed);

            // Validate output
            $validation = $agent->validateOutput($parsed);
            if (!$validation['valid']) {
                $updateData['status'] = 'failed';
                $updateData['error_message'] = 'Validation failed: ' . implode('; ', $validation['errors']);
                $updateData['completed_at'] = date('Y-m-d H:i:s');
                Database::update('agent_runs', $updateData, 'run_id = ?', [$runId]);

                return [
                    'success' => false,
                    'error' => $updateData['error_message'],
                    'run_id' => $runId,
                    'parsed' => $parsed,
                    'validation_errors' => $validation['errors'],
                ];
            }

            // Success — add to content queue
            $updateData['status'] = 'completed';
            $updateData['completed_at'] = date('Y-m-d H:i:s');
            Database::update('agent_runs', $updateData, 'run_id = ?', [$runId]);

            $queueId = ContentQueue::addFromRun($runId, $siteId, $parsed);

            // Update agent memory with last topic
            $agent->getMemory()->set('last_topic', $topicPrompt);
            $agent->getMemory()->appendToKey('topics_covered', $parsed['title'] ?? $topicPrompt);

            // Fire post-execute hook
            Hook::fire('agent_after_execute', $agent, $runId, $parsed);

            return [
                'success' => true,
                'run_id' => $runId,
                'queue_id' => $queueId,
                'parsed' => $parsed,
                'tokens' => [
                    'input' => $result['input_tokens'] ?? 0,
                    'output' => $result['output_tokens'] ?? 0,
                ],
                'duration_ms' => $result['duration_ms'] ?? 0,
            ];

        } catch (\Exception $e) {
            Database::update('agent_runs', [
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => date('Y-m-d H:i:s'),
            ], 'run_id = ?', [$runId]);

            return ['success' => false, 'error' => $e->getMessage(), 'run_id' => $runId];
        }
    }

    /**
     * Get all agents for a site
     */
    public static function getAgentsForSite(int $siteId): array
    {
        return Database::fetchAll(
            "SELECT a.*,
                    (SELECT COUNT(*) FROM agent_runs WHERE agent_id = a.agent_id) as run_count,
                    (SELECT MAX(created_at) FROM agent_runs WHERE agent_id = a.agent_id) as last_run_at
             FROM agents a
             WHERE a.site_id = ?
             ORDER BY a.name",
            [$siteId]
        );
    }

    /**
     * Get a single agent by ID
     */
    public static function getAgent(int $id): ?array
    {
        return Database::fetchOne("SELECT * FROM agents WHERE agent_id = ?", [$id]);
    }

    /**
     * Create a new agent
     */
    public static function createAgent(array $data): int
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        if (empty($data['slug']) && !empty($data['name'])) {
            $data['slug'] = cainty_slug($data['name']);
        }

        return Database::insert('agents', $data);
    }

    /**
     * Update an agent
     */
    public static function updateAgent(int $id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return Database::update('agents', $data, 'agent_id = ?', [$id]);
    }

    /**
     * Delete an agent
     */
    public static function deleteAgent(int $id): bool
    {
        // Delete memory
        Database::delete('agent_memory', 'agent_id = ?', [$id]);
        // Delete agent
        return Database::delete('agents', 'agent_id = ?', [$id]);
    }

    /**
     * Get run history for an agent (or all agents for a site)
     */
    public static function getRunHistory(?int $agentId = null, ?int $siteId = null, int $limit = 50): array
    {
        if ($agentId) {
            return Database::fetchAll(
                "SELECT r.*, a.name as agent_name
                 FROM agent_runs r
                 LEFT JOIN agents a ON r.agent_id = a.agent_id
                 WHERE r.agent_id = ?
                 ORDER BY r.created_at DESC
                 LIMIT ?",
                [$agentId, $limit]
            );
        }

        if ($siteId) {
            return Database::fetchAll(
                "SELECT r.*, a.name as agent_name
                 FROM agent_runs r
                 LEFT JOIN agents a ON r.agent_id = a.agent_id
                 WHERE r.site_id = ?
                 ORDER BY r.created_at DESC
                 LIMIT ?",
                [$siteId, $limit]
            );
        }

        return [];
    }

    /**
     * Get a single run by ID
     */
    public static function getRun(int $runId): ?array
    {
        return Database::fetchOne(
            "SELECT r.*, a.name as agent_name
             FROM agent_runs r
             LEFT JOIN agents a ON r.agent_id = a.agent_id
             WHERE r.run_id = ?",
            [$runId]
        );
    }
}

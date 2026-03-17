<?php

namespace Cainty\AI;

use Cainty\Database\Database;

/**
 * Persistent key-value memory for AI agents
 *
 * Stored in agent_memory table, injected into system prompts.
 */
class AgentMemory
{
    private int $agentId;

    public function __construct(int $agentId)
    {
        $this->agentId = $agentId;
    }

    /**
     * Get a memory value by key
     */
    public function get(string $key): ?string
    {
        $row = Database::fetchOne(
            "SELECT memory_value FROM agent_memory WHERE agent_id = ? AND memory_key = ?",
            [$this->agentId, $key]
        );
        return $row ? $row['memory_value'] : null;
    }

    /**
     * Set a memory value (upsert)
     */
    public function set(string $key, string $value): void
    {
        $existing = Database::fetchOne(
            "SELECT memory_id FROM agent_memory WHERE agent_id = ? AND memory_key = ?",
            [$this->agentId, $key]
        );

        if ($existing) {
            Database::update('agent_memory', [
                'memory_value' => $value,
                'updated_at' => date('Y-m-d H:i:s'),
            ], 'memory_id = ?', [$existing['memory_id']]);
        } else {
            Database::insert('agent_memory', [
                'agent_id' => $this->agentId,
                'memory_key' => $key,
                'memory_value' => $value,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    /**
     * Get all memory entries for this agent
     */
    public function getAll(): array
    {
        return Database::fetchAll(
            "SELECT memory_key, memory_value, updated_at FROM agent_memory WHERE agent_id = ? ORDER BY memory_key",
            [$this->agentId]
        );
    }

    /**
     * Delete a memory entry
     */
    public function delete(string $key): bool
    {
        return Database::delete(
            'agent_memory',
            'agent_id = ? AND memory_key = ?',
            [$this->agentId, $key]
        );
    }

    /**
     * Append text to an existing memory value (or create if not exists)
     */
    public function appendToKey(string $key, string $text, string $separator = "\n"): void
    {
        $current = $this->get($key);
        if ($current !== null) {
            $this->set($key, $current . $separator . $text);
        } else {
            $this->set($key, $text);
        }
    }

    /**
     * Build a context string from all memory entries for injection into prompts
     */
    public function buildContextString(): string
    {
        $entries = $this->getAll();
        if (empty($entries)) {
            return '';
        }

        $lines = [];
        foreach ($entries as $entry) {
            $lines[] = "- {$entry['memory_key']}: {$entry['memory_value']}";
        }

        return "## Agent Memory\n" . implode("\n", $lines);
    }
}

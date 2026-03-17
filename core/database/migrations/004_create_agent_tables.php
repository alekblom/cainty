<?php
/**
 * Migration: Create AI agent system tables
 */

use Cainty\Database\Schema;

$ai = Schema::autoIncrement();
$ti = Schema::tinyInt();
$text = Schema::textType();
$dec = Schema::decimal(10, 6);
$engine = Schema::engine();

// Agents
$db->exec("
    CREATE TABLE IF NOT EXISTS agents (
        agent_id        {$ai},
        site_id         INTEGER NOT NULL,
        name            VARCHAR(200) NOT NULL,
        slug            VARCHAR(200) NOT NULL,
        description     TEXT DEFAULT NULL,
        system_prompt   {$text} NOT NULL,
        model_provider  VARCHAR(50) DEFAULT NULL,
        model_slug      VARCHAR(100) DEFAULT NULL,
        voice_rules     TEXT DEFAULT NULL,
        shortcode_rules TEXT DEFAULT NULL,
        output_schema   TEXT DEFAULT NULL,
        quality_checklist TEXT DEFAULT NULL,
        categories      TEXT DEFAULT NULL,
        tags_strategy   TEXT DEFAULT NULL,
        post_length_min INTEGER DEFAULT 800,
        post_length_max INTEGER DEFAULT 1500,
        is_active       {$ti} DEFAULT 1,
        created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE (slug, site_id)
    ) {$engine}
");

// Agent Memory (persistent key-value context per agent)
$db->exec("
    CREATE TABLE IF NOT EXISTS agent_memory (
        memory_id       {$ai},
        agent_id        INTEGER NOT NULL,
        memory_key      VARCHAR(200) NOT NULL,
        memory_value    {$text} NOT NULL,
        updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE (agent_id, memory_key)
    ) {$engine}
");

// Agent Runs (execution history)
$db->exec("
    CREATE TABLE IF NOT EXISTS agent_runs (
        run_id          {$ai},
        agent_id        INTEGER NOT NULL,
        site_id         INTEGER NOT NULL,
        triggered_by    INTEGER DEFAULT NULL,
        topic_prompt    TEXT NOT NULL,
        model_used      VARCHAR(100) NOT NULL,
        provider_used   VARCHAR(50) NOT NULL,
        input_tokens    INTEGER DEFAULT 0,
        output_tokens   INTEGER DEFAULT 0,
        cost_oomph      INTEGER DEFAULT 0,
        cost_usd        {$dec} DEFAULT 0,
        duration_ms     INTEGER DEFAULT 0,
        status          VARCHAR(20) DEFAULT 'pending',
        error_message   TEXT DEFAULT NULL,
        raw_output      {$text} DEFAULT NULL,
        parsed_output   {$text} DEFAULT NULL,
        post_id         INTEGER DEFAULT NULL,
        created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
        completed_at    DATETIME DEFAULT NULL
    ) {$engine}
");

// Content Queue (review pipeline)
$db->exec("
    CREATE TABLE IF NOT EXISTS content_queue (
        queue_id        {$ai},
        site_id         INTEGER NOT NULL,
        agent_run_id    INTEGER DEFAULT NULL,
        title           VARCHAR(500) NOT NULL,
        slug            VARCHAR(500) NOT NULL,
        content         {$text} NOT NULL,
        excerpt         TEXT DEFAULT NULL,
        meta_title      VARCHAR(200) DEFAULT NULL,
        meta_description VARCHAR(500) DEFAULT NULL,
        categories      TEXT DEFAULT NULL,
        tags            TEXT DEFAULT NULL,
        image_prompt    TEXT DEFAULT NULL,
        status          VARCHAR(20) DEFAULT 'pending_review',
        reviewed_by     INTEGER DEFAULT NULL,
        review_notes    TEXT DEFAULT NULL,
        created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
        reviewed_at     DATETIME DEFAULT NULL,
        published_at    DATETIME DEFAULT NULL
    ) {$engine}
");

// Indexes for MySQL
if ($driver === 'mysql') {
    try {
        $db->exec("CREATE INDEX idx_agents_site ON agents(site_id)");
        $db->exec("CREATE INDEX idx_agent_runs_agent ON agent_runs(agent_id)");
        $db->exec("CREATE INDEX idx_agent_runs_status ON agent_runs(status)");
        $db->exec("CREATE INDEX idx_content_queue_site_status ON content_queue(site_id, status)");
    } catch (\PDOException $e) {}
}

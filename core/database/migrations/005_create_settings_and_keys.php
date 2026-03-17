<?php
/**
 * Migration: Create settings and LLM API keys tables
 */

use Cainty\Database\Schema;

$ai = Schema::autoIncrement();
$ti = Schema::tinyInt();
$engine = Schema::engine();

// Settings (key-value per site)
$db->exec("
    CREATE TABLE IF NOT EXISTS settings (
        setting_id      {$ai},
        site_id         INTEGER DEFAULT NULL,
        setting_key     VARCHAR(200) NOT NULL,
        setting_value   TEXT DEFAULT NULL,
        UNIQUE (site_id, setting_key)
    ) {$engine}
");

// LLM API Keys (encrypted, per site per provider)
$db->exec("
    CREATE TABLE IF NOT EXISTS llm_api_keys (
        key_id          {$ai},
        site_id         INTEGER NOT NULL,
        provider        VARCHAR(50) NOT NULL,
        api_key_enc     VARCHAR(500) NOT NULL,
        base_url        VARCHAR(500) DEFAULT NULL,
        is_active       {$ti} DEFAULT 1,
        created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE (site_id, provider)
    ) {$engine}
");

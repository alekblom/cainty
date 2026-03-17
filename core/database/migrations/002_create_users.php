<?php
/**
 * Migration: Create users table
 */

use Cainty\Database\Schema;

$ai = Schema::autoIncrement();
$ti = Schema::tinyInt();
$engine = Schema::engine();

$db->exec("
    CREATE TABLE IF NOT EXISTS users (
        user_id         {$ai},
        site_id         INTEGER DEFAULT NULL,
        email           VARCHAR(255) NOT NULL,
        username        VARCHAR(100) NOT NULL,
        password_hash   VARCHAR(255) DEFAULT '',
        role            VARCHAR(20) DEFAULT 'subscriber',
        display_name    VARCHAR(200) DEFAULT NULL,
        avatar          VARCHAR(500) DEFAULT NULL,
        bio             TEXT DEFAULT NULL,
        website         VARCHAR(500) DEFAULT NULL,
        social_links    TEXT DEFAULT NULL,
        alexiuz_user_id INTEGER DEFAULT NULL,
        session_hash    VARCHAR(128) DEFAULT NULL,
        session_ip_hash VARCHAR(128) DEFAULT NULL,
        status          {$ti} DEFAULT 1,
        last_login_at   DATETIME DEFAULT NULL,
        created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP
    ) {$engine}
");

// Indexes for MySQL (SQLite handles these differently)
if ($driver === 'mysql') {
    try {
        $db->exec("CREATE INDEX idx_users_email ON users(email)");
        $db->exec("CREATE INDEX idx_users_session ON users(session_hash)");
        $db->exec("CREATE INDEX idx_users_alexiuz ON users(alexiuz_user_id)");
    } catch (\PDOException $e) {
        // Indexes may already exist
    }
}

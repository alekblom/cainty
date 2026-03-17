<?php
/**
 * Migration: Create sites table
 */

use Cainty\Database\Database;
use Cainty\Database\Schema;

$ai = Schema::autoIncrement();
$ti = Schema::tinyInt();
$engine = Schema::engine();

$db->exec("
    CREATE TABLE IF NOT EXISTS sites (
        site_id         {$ai},
        site_name       VARCHAR(200) NOT NULL,
        site_slug       VARCHAR(100) NOT NULL UNIQUE,
        site_domain     VARCHAR(255) DEFAULT NULL,
        site_tagline    VARCHAR(500) DEFAULT NULL,
        site_locale     VARCHAR(10) DEFAULT 'en',
        is_active       {$ti} DEFAULT 1,
        created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP
    ) {$engine}
");

<?php
/**
 * Migration: Create content tables (posts, categories, tags, junctions, media)
 */

use Cainty\Database\Schema;

$ai = Schema::autoIncrement();
$ti = Schema::tinyInt();
$text = Schema::textType();
$engine = Schema::engine();

// Posts
$db->exec("
    CREATE TABLE IF NOT EXISTS posts (
        post_id             {$ai},
        site_id             INTEGER NOT NULL,
        author_id           INTEGER NOT NULL,
        title               VARCHAR(500) NOT NULL,
        slug                VARCHAR(500) NOT NULL,
        content             {$text},
        excerpt             TEXT DEFAULT NULL,
        featured_image      VARCHAR(500) DEFAULT NULL,
        featured_image_model VARCHAR(100) DEFAULT NULL,
        status              VARCHAR(20) DEFAULT 'draft',
        post_type           VARCHAR(20) DEFAULT 'post',
        meta_title          VARCHAR(200) DEFAULT NULL,
        meta_description    VARCHAR(500) DEFAULT NULL,
        view_count          INTEGER DEFAULT 0,
        agent_run_id        INTEGER DEFAULT NULL,
        published_at        DATETIME DEFAULT NULL,
        created_at          DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at          DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE (slug, site_id)
    ) {$engine}
");

// Categories
$db->exec("
    CREATE TABLE IF NOT EXISTS categories (
        category_id     {$ai},
        site_id         INTEGER NOT NULL,
        parent_id       INTEGER DEFAULT NULL,
        cat_name        VARCHAR(200) NOT NULL,
        cat_slug        VARCHAR(200) NOT NULL,
        cat_desc        TEXT DEFAULT NULL,
        post_count      INTEGER DEFAULT 0,
        sort_order      INTEGER DEFAULT 0,
        created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE (cat_slug, site_id)
    ) {$engine}
");

// Tags (global, not per-site)
$db->exec("
    CREATE TABLE IF NOT EXISTS tags (
        tag_id          {$ai},
        tag_name        VARCHAR(100) NOT NULL,
        tag_slug        VARCHAR(100) NOT NULL UNIQUE,
        post_count      INTEGER DEFAULT 0
    ) {$engine}
");

// Post-Category junction
$db->exec("
    CREATE TABLE IF NOT EXISTS posts_categories (
        post_id         INTEGER NOT NULL,
        category_id     INTEGER NOT NULL,
        PRIMARY KEY (post_id, category_id)
    ) {$engine}
");

// Post-Tag junction
$db->exec("
    CREATE TABLE IF NOT EXISTS posts_tags (
        post_id         INTEGER NOT NULL,
        tag_id          INTEGER NOT NULL,
        PRIMARY KEY (post_id, tag_id)
    ) {$engine}
");

// Post-Author junction (supports multiple authors)
$db->exec("
    CREATE TABLE IF NOT EXISTS posts_authors (
        post_id         INTEGER NOT NULL,
        user_id         INTEGER NOT NULL,
        is_primary      {$ti} DEFAULT 0,
        PRIMARY KEY (post_id, user_id)
    ) {$engine}
");

// Media
$db->exec("
    CREATE TABLE IF NOT EXISTS media (
        media_id        {$ai},
        site_id         INTEGER NOT NULL,
        uploaded_by     INTEGER NOT NULL,
        filename        VARCHAR(500) NOT NULL,
        filepath        VARCHAR(500) NOT NULL,
        filetype        VARCHAR(100) DEFAULT NULL,
        filesize        INTEGER DEFAULT 0,
        alt_text        VARCHAR(500) DEFAULT NULL,
        ai_model        VARCHAR(100) DEFAULT NULL,
        ai_prompt       TEXT DEFAULT NULL,
        created_at      DATETIME DEFAULT CURRENT_TIMESTAMP
    ) {$engine}
");

// Indexes for MySQL
if ($driver === 'mysql') {
    try {
        $db->exec("CREATE INDEX idx_posts_site_status ON posts(site_id, status)");
        $db->exec("CREATE INDEX idx_posts_published ON posts(published_at)");
        $db->exec("CREATE INDEX idx_posts_author ON posts(author_id)");
        $db->exec("CREATE INDEX idx_categories_site ON categories(site_id)");
        $db->exec("CREATE INDEX idx_media_site ON media(site_id)");
    } catch (\PDOException $e) {
        // Indexes may already exist
    }
}

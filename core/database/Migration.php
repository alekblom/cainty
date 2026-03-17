<?php

namespace Cainty\Database;

use PDO;

/**
 * Database Migration Runner
 *
 * Runs migration files from core/database/migrations/ in order.
 * Tracks applied migrations in a _migrations table.
 */
class Migration
{
    private PDO $db;
    private string $migrationsDir;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->migrationsDir = CAINTY_ROOT . '/core/database/migrations';
    }

    /**
     * Run all pending migrations
     */
    public function run(): void
    {
        $this->createMigrationsTable();

        $pending = $this->getPending();
        foreach ($pending as $file) {
            $this->executeMigration($file);
        }
    }

    /**
     * Get list of applied migration filenames
     */
    public function getApplied(): array
    {
        try {
            $stmt = $this->db->query("SELECT migration FROM _migrations ORDER BY migration");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Get list of pending migration filenames
     */
    public function getPending(): array
    {
        $applied = $this->getApplied();
        $all = $this->getAllMigrationFiles();

        return array_diff($all, $applied);
    }

    /**
     * Get all migration files sorted by name
     */
    private function getAllMigrationFiles(): array
    {
        if (!is_dir($this->migrationsDir)) {
            return [];
        }

        $files = glob($this->migrationsDir . '/*.php');
        $names = array_map('basename', $files);
        sort($names);

        return $names;
    }

    /**
     * Create the migrations tracking table if it doesn't exist
     */
    private function createMigrationsTable(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS _migrations (
                migration VARCHAR(255) NOT NULL PRIMARY KEY,
                applied_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }

    /**
     * Execute a single migration file
     */
    private function executeMigration(string $file): void
    {
        $path = $this->migrationsDir . '/' . $file;
        if (!file_exists($path)) {
            return;
        }

        // Migration files should return a callable or define an $up function
        $db = $this->db;
        $driver = Database::getDriver();

        try {
            $this->db->beginTransaction();

            require $path;

            // Record the migration
            $stmt = $this->db->prepare("INSERT INTO _migrations (migration) VALUES (?)");
            $stmt->execute([$file]);

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("Migration failed [{$file}]: " . $e->getMessage());
            if (cainty_config('APP_DEBUG') === 'true') {
                throw $e;
            }
        }
    }
}

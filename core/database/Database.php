<?php

namespace Cainty\Database;

use PDO;
use PDOStatement;
use PDOException;

/**
 * PDO Database Wrapper
 *
 * Supports SQLite and MySQL/MariaDB via .env configuration.
 */
class Database
{
    private static ?PDO $instance = null;
    private static string $driver = 'sqlite';

    /**
     * Connect to the database
     */
    public static function connect(): PDO
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $driver = cainty_config('DB_DRIVER', 'sqlite');
        self::$driver = $driver;

        try {
            if ($driver === 'sqlite') {
                $dbPath = cainty_config('DB_PATH', 'storage/cainty.db');
                if (!str_starts_with($dbPath, '/')) {
                    $dbPath = CAINTY_ROOT . '/' . $dbPath;
                }
                // Ensure directory exists
                $dir = dirname($dbPath);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                self::$instance = new PDO("sqlite:{$dbPath}");
                // Enable WAL mode for better concurrency
                self::$instance->exec('PRAGMA journal_mode=WAL');
                self::$instance->exec('PRAGMA foreign_keys=ON');
            } else {
                $host = cainty_config('DB_HOST', 'localhost');
                $port = cainty_config('DB_PORT', '3306');
                $name = cainty_config('DB_NAME', 'cainty');
                $user = cainty_config('DB_USER', 'root');
                $pass = cainty_config('DB_PASS', '');

                $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
                self::$instance = new PDO($dsn, $user, $pass);
            }

            self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            self::$instance->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        } catch (PDOException $e) {
            if (cainty_config('APP_DEBUG') === 'true') {
                throw $e;
            }
            error_log('Cainty DB Error: ' . $e->getMessage());
            die('Database connection failed. Check your configuration.');
        }

        return self::$instance;
    }

    /**
     * Get the raw PDO instance
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            self::connect();
        }
        return self::$instance;
    }

    /**
     * Get the current database driver
     */
    public static function getDriver(): string
    {
        return self::$driver;
    }

    /**
     * Check if using SQLite
     */
    public static function isSQLite(): bool
    {
        return self::$driver === 'sqlite';
    }

    /**
     * Execute a query with bound parameters
     */
    public static function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Insert a row and return the new ID
     */
    public static function insert(string $table, array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        self::query($sql, array_values($data));

        return (int) self::getInstance()->lastInsertId();
    }

    /**
     * Update rows matching a condition
     */
    public static function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $setParts = [];
        foreach (array_keys($data) as $column) {
            $setParts[] = "{$column} = ?";
        }
        $setClause = implode(', ', $setParts);

        $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
        $stmt = self::query($sql, array_merge(array_values($data), $whereParams));

        return $stmt->rowCount();
    }

    /**
     * Delete rows matching a condition
     */
    public static function delete(string $table, string $where, array $whereParams = []): int
    {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = self::query($sql, $whereParams);

        return $stmt->rowCount();
    }

    /**
     * Fetch a single row
     */
    public static function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = self::query($sql, $params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Fetch all rows
     */
    public static function fetchAll(string $sql, array $params = []): array
    {
        $stmt = self::query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Fetch a single column value
     */
    public static function fetchColumn(string $sql, array $params = []): mixed
    {
        $stmt = self::query($sql, $params);
        return $stmt->fetchColumn();
    }

    /**
     * Begin a transaction
     */
    public static function beginTransaction(): void
    {
        self::getInstance()->beginTransaction();
    }

    /**
     * Commit a transaction
     */
    public static function commit(): void
    {
        self::getInstance()->commit();
    }

    /**
     * Rollback a transaction
     */
    public static function rollback(): void
    {
        self::getInstance()->rollBack();
    }

    /**
     * Reset instance (for testing or re-connection)
     */
    public static function reset(): void
    {
        self::$instance = null;
    }
}

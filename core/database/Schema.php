<?php

namespace Cainty\Database;

/**
 * Cross-dialect schema helpers for SQLite and MySQL/MariaDB
 */
class Schema
{
    /**
     * Get the auto-increment primary key syntax
     */
    public static function autoIncrement(): string
    {
        if (Database::isSQLite()) {
            return 'INTEGER PRIMARY KEY AUTOINCREMENT';
        }
        return 'INT UNSIGNED AUTO_INCREMENT PRIMARY KEY';
    }

    /**
     * Get the large text type
     */
    public static function textType(): string
    {
        if (Database::isSQLite()) {
            return 'TEXT';
        }
        return 'MEDIUMTEXT';
    }

    /**
     * Get the current timestamp default
     */
    public static function now(): string
    {
        return 'CURRENT_TIMESTAMP';
    }

    /**
     * Get TINYINT equivalent
     */
    public static function tinyInt(): string
    {
        if (Database::isSQLite()) {
            return 'INTEGER';
        }
        return 'TINYINT';
    }

    /**
     * Get decimal type
     */
    public static function decimal(int $precision = 10, int $scale = 6): string
    {
        if (Database::isSQLite()) {
            return 'REAL';
        }
        return "DECIMAL({$precision},{$scale})";
    }

    /**
     * Get engine clause (MySQL only)
     */
    public static function engine(): string
    {
        if (Database::isSQLite()) {
            return '';
        }
        return 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
    }
}

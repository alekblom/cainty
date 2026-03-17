<?php

namespace Cainty\AI;

/**
 * Bridge to Alexiuz Oomph/Credits system for managed AI usage billing.
 * Only active when OOMPH_ENABLED=true in .env.
 */
class CreditsBridge
{
    private static ?\mysqli $link = null;
    private static bool $initialized = false;

    /**
     * Initialize the connection to the Alexiuz auth database.
     */
    private static function init(): bool
    {
        if (self::$initialized) {
            return self::$link !== null;
        }
        self::$initialized = true;

        if (cainty_config('OOMPH_ENABLED', 'false') !== 'true') {
            return false;
        }

        $configPath = '/home/internetieruser/alexiuz-auth/config.php';
        $creditsPath = '/home/internetieruser/alexiuz-auth/credits.php';

        if (!file_exists($configPath) || !file_exists($creditsPath)) {
            return false;
        }

        require_once $configPath;
        require_once $creditsPath;

        $dbConnectPath = '/home/internetieruser/alexiuz-auth/db-connect.php';
        if (file_exists($dbConnectPath)) {
            require_once $dbConnectPath;
            if (isset($alexiuz_link) && $alexiuz_link instanceof \mysqli) {
                self::$link = $alexiuz_link;
            }
        }

        if (!self::$link) {
            self::$link = new \mysqli(
                ALEXIUZ_DB_SERVER,
                ALEXIUZ_DB_USERNAME,
                ALEXIUZ_DB_PASSWORD,
                ALEXIUZ_DB_NAME
            );

            if (self::$link->connect_error) {
                error_log('CreditsBridge: DB connection failed: ' . self::$link->connect_error);
                self::$link = null;
                return false;
            }
        }

        return true;
    }

    /**
     * Check if a user has enough credits for an action.
     */
    public static function checkCredits(int $alexiuzUserId, float $cost): bool
    {
        if (!self::init()) {
            return true; // If credits system unavailable, allow action
        }

        return creditsCheck(self::$link, $alexiuzUserId, $cost);
    }

    /**
     * Deduct credits for an AI action.
     */
    public static function deductCredits(
        int $alexiuzUserId,
        float $cost,
        string $description,
        ?string $relatedId = null
    ): bool {
        if (!self::init()) {
            return true;
        }

        $service = cainty_config('OOMPH_SERVICE_NAME', 'cainty');

        return creditsDeduct(
            self::$link,
            $alexiuzUserId,
            $cost,
            'ai_usage',
            $description,
            $service,
            $relatedId
        );
    }

    /**
     * Get the current credit balance for a user.
     */
    public static function getBalance(int $alexiuzUserId): ?array
    {
        if (!self::init()) {
            return null;
        }

        return creditsGetBalance(self::$link, $alexiuzUserId);
    }

    /**
     * Check if the credits system is enabled and available.
     */
    public static function isEnabled(): bool
    {
        return self::init();
    }
}

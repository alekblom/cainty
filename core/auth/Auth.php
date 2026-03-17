<?php

namespace Cainty\Auth;

use Cainty\Database\Database;
use Cainty\Router\Response;

/**
 * Authentication manager
 *
 * Handles local login/logout, session management, and role checks.
 */
class Auth
{
    private static ?array $currentUser = null;
    private static bool $checked = false;

    /**
     * Attempt login with email and password
     */
    public static function attempt(string $email, string $password): ?array
    {
        $user = Database::fetchOne(
            "SELECT * FROM users WHERE email = ? AND status = 1",
            [$email]
        );

        if (!$user || empty($user['password_hash'])) {
            return null;
        }

        if (!password_verify($password, $user['password_hash'])) {
            return null;
        }

        return $user;
    }

    /**
     * Log in a user by setting session data
     */
    public static function loginUser(int $userId): void
    {
        $sessionHash = bin2hex(random_bytes(32));
        $ipHash = hash('sha256', self::getClientIp() . cainty_config('APP_SECRET', ''));

        Database::update('users', [
            'session_hash' => $sessionHash,
            'session_ip_hash' => $ipHash,
            'last_login_at' => date('Y-m-d H:i:s'),
        ], 'user_id = ?', [$userId]);

        $_SESSION['cainty_user_id'] = $userId;
        $_SESSION['cainty_session_hash'] = $sessionHash;

        // Reset cached user
        self::$currentUser = null;
        self::$checked = false;
    }

    /**
     * Log out the current user
     */
    public static function logout(): void
    {
        $userId = $_SESSION['cainty_user_id'] ?? null;
        if ($userId) {
            Database::update('users', [
                'session_hash' => null,
                'session_ip_hash' => null,
            ], 'user_id = ?', [$userId]);
        }

        unset($_SESSION['cainty_user_id'], $_SESSION['cainty_session_hash']);
        self::$currentUser = null;
        self::$checked = false;
    }

    /**
     * Check if a user is currently authenticated
     */
    public static function check(): bool
    {
        return self::user() !== null;
    }

    /**
     * Get the current authenticated user
     */
    public static function user(): ?array
    {
        if (self::$checked) {
            return self::$currentUser;
        }

        self::$checked = true;
        self::$currentUser = null;

        $userId = $_SESSION['cainty_user_id'] ?? null;
        $sessionHash = $_SESSION['cainty_session_hash'] ?? null;

        if (!$userId || !$sessionHash) {
            return null;
        }

        $user = Database::fetchOne(
            "SELECT * FROM users WHERE user_id = ? AND session_hash = ? AND status = 1",
            [$userId, $sessionHash]
        );

        if (!$user) {
            // Invalid session — clear it
            unset($_SESSION['cainty_user_id'], $_SESSION['cainty_session_hash']);
            return null;
        }

        // Verify IP hash
        $ipHash = hash('sha256', self::getClientIp() . cainty_config('APP_SECRET', ''));
        if ($user['session_ip_hash'] && $user['session_ip_hash'] !== $ipHash) {
            // IP changed — invalidate session
            unset($_SESSION['cainty_user_id'], $_SESSION['cainty_session_hash']);
            return null;
        }

        // Don't expose sensitive fields
        unset($user['password_hash'], $user['session_hash'], $user['session_ip_hash']);
        self::$currentUser = $user;

        return $user;
    }

    /**
     * Require authentication — redirect to login if not authenticated
     */
    public static function requireAuth(): void
    {
        if (!self::check()) {
            Response::redirect(cainty_url('login'));
        }
    }

    /**
     * Require one of the specified roles
     */
    public static function requireRole(string ...$roles): void
    {
        self::requireAuth();
        $user = self::user();
        if (!in_array($user['role'], $roles, true)) {
            Response::forbidden();
        }
    }

    /**
     * Get the current user ID
     */
    public static function id(): ?int
    {
        $user = self::user();
        return $user ? (int) $user['user_id'] : null;
    }

    /**
     * Check if the current user is an admin
     */
    public static function isAdmin(): bool
    {
        $user = self::user();
        return $user && $user['role'] === 'admin';
    }

    /**
     * Check if the current user is an editor or admin
     */
    public static function isEditor(): bool
    {
        $user = self::user();
        return $user && in_array($user['role'], ['admin', 'editor'], true);
    }

    /**
     * Get the client IP address
     */
    private static function getClientIp(): string
    {
        return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}

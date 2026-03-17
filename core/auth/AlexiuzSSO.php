<?php

namespace Cainty\Auth;

use Cainty\Database\Database;

/**
 * Alexiuz SSO Integration (optional)
 *
 * Provides single sign-on via the Alexiuz authentication hub.
 * Only active when AUTH_METHOD=alexiuz_sso in .env.
 */
class AlexiuzSSO
{
    /**
     * Check if SSO is enabled
     */
    public static function isEnabled(): bool
    {
        return cainty_config('AUTH_METHOD') === 'alexiuz_sso'
            && !empty(cainty_config('ALEXIUZ_AUTH_HUB'));
    }

    /**
     * Get the Alexiuz login URL with return redirect
     */
    public static function getLoginUrl(?string $returnUrl = null): string
    {
        $hub = rtrim(cainty_config('ALEXIUZ_AUTH_HUB', 'https://alexiuz.com'), '/');
        $return = $returnUrl ?? cainty_url('auth/callback');
        return $hub . '/login?return_to=' . urlencode($return);
    }

    /**
     * Consume the SSO callback token
     *
     * Returns user data from Alexiuz or null on failure.
     */
    public static function consumeCallback(): ?array
    {
        $token = $_GET['token'] ?? null;
        if (!$token) {
            return null;
        }

        // Try to include the central auth callback library
        $callbackPath = '/home/internetieruser/alexiuz-auth/callback.php';
        if (!file_exists($callbackPath)) {
            return null;
        }

        // The callback script validates the token and returns user data
        try {
            require_once '/home/internetieruser/alexiuz-auth/db-connect.php';

            global $alexiuz_link;
            if (!$alexiuz_link) {
                return null;
            }

            // Validate one-time token
            $stmt = $alexiuz_link->prepare(
                "SELECT user_id, email, username FROM users WHERE auth_token = ? AND auth_token_expires > NOW()"
            );
            $stmt->bind_param('s', $token);
            $stmt->execute();
            $result = $stmt->get_result();
            $userData = $result->fetch_assoc();

            if (!$userData) {
                return null;
            }

            // Clear the one-time token
            $clearStmt = $alexiuz_link->prepare("UPDATE users SET auth_token = NULL WHERE user_id = ?");
            $clearStmt->bind_param('i', $userData['user_id']);
            $clearStmt->execute();

            return [
                'alexiuz_user_id' => (int) $userData['user_id'],
                'email' => $userData['email'],
                'username' => $userData['username'],
            ];
        } catch (\Exception $e) {
            error_log('Cainty SSO Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Link a local user to an Alexiuz account
     */
    public static function linkLocalUser(int $localUserId, int $alexiuzUserId): void
    {
        Database::update('users', [
            'alexiuz_user_id' => $alexiuzUserId,
        ], 'user_id = ?', [$localUserId]);
    }

    /**
     * Find or create a local user from SSO data
     */
    public static function findOrCreateUser(array $ssoData): int
    {
        // Check if user already linked
        $user = Database::fetchOne(
            "SELECT user_id FROM users WHERE alexiuz_user_id = ?",
            [$ssoData['alexiuz_user_id']]
        );

        if ($user) {
            return (int) $user['user_id'];
        }

        // Check if email exists
        $user = Database::fetchOne(
            "SELECT user_id FROM users WHERE email = ?",
            [$ssoData['email']]
        );

        if ($user) {
            // Link existing user
            self::linkLocalUser((int) $user['user_id'], $ssoData['alexiuz_user_id']);
            return (int) $user['user_id'];
        }

        // Create new user
        return Database::insert('users', [
            'email' => $ssoData['email'],
            'username' => $ssoData['username'],
            'display_name' => $ssoData['username'],
            'role' => 'subscriber',
            'alexiuz_user_id' => $ssoData['alexiuz_user_id'],
            'status' => 1,
        ]);
    }
}

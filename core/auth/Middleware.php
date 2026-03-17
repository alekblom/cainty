<?php

namespace Cainty\Auth;

use Cainty\Router\Response;

/**
 * Route Middleware
 *
 * Authentication and authorization middleware for routes.
 */
class Middleware
{
    /**
     * Require authenticated user
     */
    public static function auth(array $params = []): bool
    {
        if (!Auth::check()) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                Response::json(['error' => 'Unauthorized'], 401);
            }
            Response::redirect(cainty_url('login'));
            return false;
        }
        return true;
    }

    /**
     * Require admin or editor role
     */
    public static function adminOrEditor(array $params = []): bool
    {
        if (!Auth::check()) {
            Response::redirect(cainty_url('login'));
            return false;
        }
        if (!Auth::isEditor()) {
            Response::forbidden();
            return false;
        }
        return true;
    }

    /**
     * Only allow guests (not logged in)
     */
    public static function guest(array $params = []): bool
    {
        if (Auth::check()) {
            Response::redirect(cainty_admin_url());
            return false;
        }
        return true;
    }
}

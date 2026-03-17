<?php

namespace Cainty\Router;

/**
 * HTTP Response helpers
 */
class Response
{
    /**
     * Send a JSON response
     */
    public static function json(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Redirect to a URL
     */
    public static function redirect(string $url, int $code = 302): void
    {
        http_response_code($code);
        header("Location: {$url}");
        exit;
    }

    /**
     * Send an HTML response
     */
    public static function html(string $content, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: text/html; charset=utf-8');
        echo $content;
        exit;
    }

    /**
     * Send a 404 Not Found response
     */
    public static function notFound(): void
    {
        http_response_code(404);
        header('Content-Type: text/html; charset=utf-8');

        // Try to render the theme 404 page
        $theme = cainty_config('THEME', 'default');
        $path = CAINTY_ROOT . '/themes/' . $theme . '/templates/404.php';
        if (file_exists($path)) {
            $site = cainty_current_site();
            $pageTitle = 'Page Not Found';
            include CAINTY_ROOT . '/themes/' . $theme . '/templates/layout.php';
        } else {
            echo '<h1>404 — Not Found</h1><p>The page you requested could not be found.</p>';
        }
        exit;
    }

    /**
     * Send a 403 Forbidden response
     */
    public static function forbidden(): void
    {
        http_response_code(403);
        echo '<h1>403 — Forbidden</h1><p>You do not have permission to access this resource.</p>';
        exit;
    }

    /**
     * Set a cookie
     */
    public static function setCookie(string $name, string $value, int $expires = 0, string $path = '/'): void
    {
        setcookie($name, $value, [
            'expires' => $expires,
            'path' => $path,
            'secure' => cainty_config('APP_ENV') === 'production',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}

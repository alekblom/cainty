<?php

namespace Cainty\Router;

/**
 * HTTP Request wrapper
 */
class Request
{
    /**
     * Get the HTTP method
     */
    public function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    /**
     * Get the request URI (without query string)
     */
    public function uri(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH);
        return $path ?: '/';
    }

    /**
     * Get a GET parameter
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    /**
     * Get a POST parameter
     */
    public function post(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    /**
     * Get an uploaded file
     */
    public function file(string $key): ?array
    {
        return $_FILES[$key] ?? null;
    }

    /**
     * Check if this is an AJAX request
     */
    public function isAjax(): bool
    {
        return (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest')
            || (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json'));
    }

    /**
     * Check if this is a POST request
     */
    public function isPost(): bool
    {
        return $this->method() === 'POST';
    }

    /**
     * Get the client IP address
     */
    public function ip(): string
    {
        return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    /**
     * Get the request host
     */
    public function host(): string
    {
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return strtolower(preg_replace('/:\d+$/', '', $host));
    }

    /**
     * Get a cookie value
     */
    public function cookie(string $name): ?string
    {
        return $_COOKIE[$name] ?? null;
    }

    /**
     * Get the raw POST body
     */
    public function body(): string
    {
        return file_get_contents('php://input') ?: '';
    }

    /**
     * Get the POST body as JSON
     */
    public function json(): ?array
    {
        $body = $this->body();
        if (empty($body)) {
            return null;
        }
        $data = json_decode($body, true);
        return is_array($data) ? $data : null;
    }

    /**
     * Get all POST data
     */
    public function all(): array
    {
        return array_merge($_GET, $_POST);
    }
}

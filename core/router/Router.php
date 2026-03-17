<?php

namespace Cainty\Router;

/**
 * Pattern-matching URL Router
 *
 * Supports named parameters ({id}), route groups with prefixes,
 * and middleware.
 */
class Router
{
    private array $routes = [];
    private array $middlewareHandlers = [];
    private array $groupStack = [];
    private array $params = [];

    /**
     * Register a GET route
     */
    public function get(string $pattern, callable|array $handler, array $middleware = []): self
    {
        return $this->addRoute('GET', $pattern, $handler, $middleware);
    }

    /**
     * Register a POST route
     */
    public function post(string $pattern, callable|array $handler, array $middleware = []): self
    {
        return $this->addRoute('POST', $pattern, $handler, $middleware);
    }

    /**
     * Register a route for any method
     */
    public function any(string $pattern, callable|array $handler, array $middleware = []): self
    {
        $this->addRoute('GET', $pattern, $handler, $middleware);
        $this->addRoute('POST', $pattern, $handler, $middleware);
        return $this;
    }

    /**
     * Create a route group with prefix and optional middleware
     */
    public function group(string $prefix, callable $callback, array $middleware = []): self
    {
        $this->groupStack[] = [
            'prefix' => $prefix,
            'middleware' => $middleware,
        ];

        $callback($this);

        array_pop($this->groupStack);

        return $this;
    }

    /**
     * Register a middleware handler by name
     */
    public function middleware(string $name, callable $handler): self
    {
        $this->middlewareHandlers[$name] = $handler;
        return $this;
    }

    /**
     * Get the route parameters from the last dispatch
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Dispatch a request to the matching route
     */
    public function dispatch(string $method, string $uri): void
    {
        $method = strtoupper($method);
        $uri = '/' . trim(parse_url($uri, PHP_URL_PATH), '/');
        if ($uri === '/') {
            $uri = '/';
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $params = $this->matchPattern($route['pattern'], $uri);
            if ($params === false) {
                continue;
            }

            $this->params = $params;

            // Run middleware
            foreach ($route['middleware'] as $mw) {
                if (isset($this->middlewareHandlers[$mw])) {
                    $result = ($this->middlewareHandlers[$mw])($params);
                    if ($result === false) {
                        return;
                    }
                }
            }

            // Call the handler
            $handler = $route['handler'];
            if (is_array($handler)) {
                [$class, $method] = $handler;
                $controller = new $class();
                $controller->$method($params);
            } else {
                $handler($params);
            }

            return;
        }

        // No route matched — 404
        Response::notFound();
    }

    /**
     * Add a route to the internal list
     */
    private function addRoute(string $method, string $pattern, callable|array $handler, array $middleware = []): self
    {
        // Apply group prefixes and middleware
        $prefix = '';
        $groupMiddleware = [];
        foreach ($this->groupStack as $group) {
            $prefix .= $group['prefix'];
            $groupMiddleware = array_merge($groupMiddleware, $group['middleware']);
        }

        // Normalize pattern: ensure leading slash, strip trailing slash (unless root)
        $fullPattern = $prefix . $pattern;
        $fullPattern = '/' . trim($fullPattern, '/');

        $this->routes[] = [
            'method' => $method,
            'pattern' => $fullPattern,
            'handler' => $handler,
            'middleware' => array_merge($groupMiddleware, $middleware),
        ];

        return $this;
    }

    /**
     * Match a route pattern against a URI
     *
     * Returns parameter array on match, false otherwise.
     */
    private function matchPattern(string $pattern, string $uri): array|false
    {
        // Exact match
        if ($pattern === $uri) {
            return [];
        }

        // Convert {param} to named capture groups
        $regex = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';

        if (preg_match($regex, $uri, $matches)) {
            $params = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }
            return $params;
        }

        return false;
    }
}

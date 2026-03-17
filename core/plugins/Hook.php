<?php

namespace Cainty\Plugins;

/**
 * Hook System for plugins and themes
 *
 * Actions: fire-and-forget events
 * Filters: transform a value through registered callbacks
 */
class Hook
{
    private static array $actions = [];
    private static array $filters = [];

    /**
     * Register an action callback
     */
    public static function on(string $name, callable $callback, int $priority = 10): void
    {
        self::$actions[$name][] = [
            'callback' => $callback,
            'priority' => $priority,
        ];
    }

    /**
     * Fire an action (all registered callbacks run)
     */
    public static function fire(string $name, mixed ...$args): void
    {
        if (empty(self::$actions[$name])) {
            return;
        }

        $callbacks = self::$actions[$name];
        usort($callbacks, fn($a, $b) => $a['priority'] <=> $b['priority']);

        foreach ($callbacks as $entry) {
            ($entry['callback'])(...$args);
        }
    }

    /**
     * Remove an action callback
     */
    public static function off(string $name, callable $callback): void
    {
        if (empty(self::$actions[$name])) {
            return;
        }

        self::$actions[$name] = array_filter(
            self::$actions[$name],
            fn($entry) => $entry['callback'] !== $callback
        );
    }

    /**
     * Register a filter callback
     */
    public static function filter(string $name, callable $callback, int $priority = 10): void
    {
        self::$filters[$name][] = [
            'callback' => $callback,
            'priority' => $priority,
        ];
    }

    /**
     * Apply filters to a value (each callback receives and returns the value)
     */
    public static function apply(string $name, mixed $value, mixed ...$args): mixed
    {
        if (empty(self::$filters[$name])) {
            return $value;
        }

        $callbacks = self::$filters[$name];
        usort($callbacks, fn($a, $b) => $a['priority'] <=> $b['priority']);

        foreach ($callbacks as $entry) {
            $value = ($entry['callback'])($value, ...$args);
        }

        return $value;
    }

    /**
     * Check if any callbacks are registered for an action
     */
    public static function hasAction(string $name): bool
    {
        return !empty(self::$actions[$name]);
    }

    /**
     * Check if any callbacks are registered for a filter
     */
    public static function hasFilter(string $name): bool
    {
        return !empty(self::$filters[$name]);
    }
}

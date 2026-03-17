<?php

namespace Cainty\Shortcodes;

/**
 * Extensible Shortcode Engine
 *
 * Plugins register shortcodes, the engine processes them in content.
 */
class ShortcodeEngine
{
    private static array $registered = [];

    /**
     * Register a shortcode handler
     */
    public static function register(string $tag, callable $handler, array $options = []): void
    {
        self::$registered[$tag] = [
            'handler' => $handler,
            'type' => $options['type'] ?? 'block',      // inline or block
            'closing' => $options['closing'] ?? true,    // has closing tag?
        ];
    }

    /**
     * Remove a registered shortcode
     */
    public static function deregister(string $tag): void
    {
        unset(self::$registered[$tag]);
    }

    /**
     * Check if a shortcode is registered
     */
    public static function isRegistered(string $tag): bool
    {
        return isset(self::$registered[$tag]);
    }

    /**
     * Process all shortcodes in content
     */
    public static function process(string $content, array $context = []): string
    {
        if (empty(self::$registered)) {
            return $content;
        }

        foreach (self::$registered as $tag => $config) {
            if ($config['closing']) {
                // Shortcodes with closing tags: [tag attrs]content[/tag]
                $pattern = '/\[' . preg_quote($tag, '/') . '([^\]]*)\](.*?)\[\/' . preg_quote($tag, '/') . '\]/s';
                $content = preg_replace_callback($pattern, function ($matches) use ($config, $context) {
                    $attrs = self::parseAttributes($matches[1]);
                    $innerContent = $matches[2];
                    return ($config['handler'])($attrs, $innerContent, $context);
                }, $content);
            } else {
                // Self-closing shortcodes: [tag attrs]
                $pattern = '/\[' . preg_quote($tag, '/') . '([^\]]*)\]/';
                $content = preg_replace_callback($pattern, function ($matches) use ($config, $context) {
                    $attrs = self::parseAttributes($matches[1]);
                    return ($config['handler'])($attrs, '', $context);
                }, $content);
            }
        }

        return $content;
    }

    /**
     * Parse shortcode attributes string into associative array
     *
     * Handles: key="value" and key='value'
     */
    private static function parseAttributes(string $attrString): array
    {
        $attrs = [];
        $attrString = trim($attrString);

        if (empty($attrString)) {
            return $attrs;
        }

        preg_match_all('/(\w+)\s*=\s*"([^"]*)"/', $attrString, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $attrs[$match[1]] = $match[2];
        }

        // Also handle single-quoted values
        preg_match_all("/(\w+)\s*=\s*'([^']*)'/", $attrString, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $attrs[$match[1]] = $match[2];
        }

        // Handle unquoted values
        preg_match_all('/(\w+)\s*=\s*(\S+)/', $attrString, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            if (!isset($attrs[$match[1]])) {
                $attrs[$match[1]] = trim($match[2], '"\'');
            }
        }

        return $attrs;
    }

    /**
     * Initialize built-in shortcodes
     */
    public static function init(): void
    {
        self::register('table', [TableShortcode::class, 'render'], [
            'type' => 'block',
            'closing' => true,
        ]);
    }
}

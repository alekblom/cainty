<?php
/**
 * Cainty CMS — Global Helper Functions
 */

/**
 * Get a configuration value from .env
 */
function cainty_config(string $key, mixed $default = null): mixed
{
    return $GLOBALS['cainty_config'][$key] ?? $default;
}

/**
 * Generate a full URL for the site
 */
function cainty_url(string $path = ''): string
{
    $base = rtrim(cainty_config('APP_URL', ''), '/');
    if ($path === '') {
        return $base;
    }
    return $base . '/' . ltrim($path, '/');
}

/**
 * Generate an admin URL
 */
function cainty_admin_url(string $path = ''): string
{
    return cainty_url('admin/' . ltrim($path, '/'));
}

/**
 * Generate a theme asset URL with cache-busting version
 */
function cainty_asset(string $path): string
{
    $theme = cainty_config('THEME', 'default');
    $filePath = CAINTY_ROOT . '/themes/' . $theme . '/' . ltrim($path, '/');
    $version = file_exists($filePath) ? filemtime($filePath) : time();
    return cainty_url('themes/' . $theme . '/' . ltrim($path, '/')) . '?v=' . $version;
}

/**
 * Generate an admin asset URL with cache-busting
 */
function cainty_admin_asset(string $path): string
{
    $filePath = CAINTY_ROOT . '/admin/' . ltrim($path, '/');
    $version = file_exists($filePath) ? filemtime($filePath) : time();
    return cainty_url('admin/' . ltrim($path, '/')) . '?v=' . $version;
}

/**
 * HTML-escape a string
 */
function cainty_escape(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Alias for cainty_escape
 */
function e(string $text): string
{
    return cainty_escape($text);
}

/**
 * Generate a URL-safe slug from text
 */
function cainty_slug(string $text): string
{
    $slug = mb_strtolower($text, 'UTF-8');
    // Replace non-alphanumeric chars with hyphens
    $slug = preg_replace('/[^a-z0-9\-]/', '-', $slug);
    // Collapse multiple hyphens
    $slug = preg_replace('/-+/', '-', $slug);
    return trim($slug, '-');
}

/**
 * Generate an excerpt from HTML content
 */
function cainty_excerpt(string $content, int $length = 200): string
{
    $text = strip_tags($content);
    $text = preg_replace('/\s+/', ' ', $text);
    $text = trim($text);
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    $excerpt = mb_substr($text, 0, $length);
    // Cut at last space to avoid breaking words
    $lastSpace = mb_strrpos($excerpt, ' ');
    if ($lastSpace !== false) {
        $excerpt = mb_substr($excerpt, 0, $lastSpace);
    }
    return $excerpt . '...';
}

/**
 * Calculate read time for content
 */
function cainty_read_time(string $content): string
{
    $text = strip_tags($content);
    $wordCount = str_word_count($text);
    $minutes = max(1, (int) ceil($wordCount / 200));
    return $minutes . ' min read';
}

/**
 * Human-readable time ago string
 */
function cainty_time_ago(string $datetime): string
{
    $now = new DateTime();
    $then = new DateTime($datetime);
    $diff = $now->diff($then);

    if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' min' . ($diff->i > 1 ? 's' : '') . ' ago';
    return 'just now';
}

/**
 * Get the current site context
 */
function cainty_current_site(): ?array
{
    return $GLOBALS['current_site'] ?? null;
}

/**
 * Get the current site ID
 */
function cainty_site_id(): int
{
    $site = cainty_current_site();
    return $site ? (int) $site['site_id'] : 1;
}

/**
 * Get the current authenticated user
 */
function cainty_current_user(): ?array
{
    return Cainty\Auth\Auth::user();
}

/**
 * Check if the current user is admin
 */
function cainty_is_admin(): bool
{
    return Cainty\Auth\Auth::isAdmin();
}

/**
 * Format a date for display
 */
function cainty_date(string $datetime, string $format = 'M j, Y'): string
{
    $date = new DateTime($datetime);
    return $date->format($format);
}

/**
 * Get upload URL for a file
 */
function cainty_upload_url(string $filepath): string
{
    return cainty_url('storage/uploads/' . ltrim($filepath, '/'));
}

/**
 * Get the upload directory path
 */
function cainty_upload_path(string $filepath = ''): string
{
    $dir = CAINTY_ROOT . '/' . trim(cainty_config('UPLOAD_DIR', 'storage/uploads'), '/');
    if ($filepath) {
        return $dir . '/' . ltrim($filepath, '/');
    }
    return $dir;
}

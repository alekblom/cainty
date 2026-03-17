<?php

namespace Cainty\Themes;

use Cainty\Plugins\Hook;
use Cainty\Shortcodes\ShortcodeEngine;

/**
 * Theme template loader and renderer
 */
class ThemeLoader
{
    private string $theme;
    private string $themePath;
    private ?array $themeConfig = null;

    public function __construct(?string $theme = null)
    {
        $this->theme = $theme ?? cainty_config('THEME', 'default');
        $this->themePath = CAINTY_ROOT . '/themes/' . $this->theme;
    }

    /**
     * Render a template with data
     */
    public function render(string $template, array $data = []): void
    {
        $templateFile = $this->resolveTemplate($template, $data);

        if (!$templateFile) {
            http_response_code(500);
            echo "Template not found: {$template}";
            return;
        }

        // Add common data
        $data['site'] = $data['site'] ?? cainty_current_site();
        $data['theme'] = $this;
        $data['is_admin'] = $data['is_admin'] ?? cainty_is_admin();

        // Extract data as variables for the template
        extract($data);

        // The template content is captured, then injected into the layout
        $templatePath = $templateFile;
        $layoutPath = $this->themePath . '/templates/layout.php';

        if (file_exists($layoutPath)) {
            // Set $contentTemplate so layout.php can include the actual page
            $contentTemplate = $templatePath;
            include $layoutPath;
        } else {
            include $templatePath;
        }
    }

    /**
     * Resolve the template file using the template hierarchy
     */
    public function resolveTemplate(string $type, array $context = []): ?string
    {
        $candidates = [];

        switch ($type) {
            case 'single-post':
                if (!empty($context['post']['slug'])) {
                    $candidates[] = "single-post-{$context['post']['slug']}.php";
                }
                $candidates[] = 'single-post.php';
                break;

            case 'archive':
                if (!empty($context['category']['cat_slug'])) {
                    $candidates[] = "archive-{$context['category']['cat_slug']}.php";
                }
                if (!empty($context['tag']['tag_slug'])) {
                    $candidates[] = "archive-tag-{$context['tag']['tag_slug']}.php";
                }
                $candidates[] = 'archive.php';
                break;

            case 'page':
                if (!empty($context['post']['slug'])) {
                    $candidates[] = "page-{$context['post']['slug']}.php";
                }
                $candidates[] = 'page.php';
                break;

            default:
                $candidates[] = "{$type}.php";
        }

        foreach ($candidates as $candidate) {
            $path = $this->themePath . '/templates/' . $candidate;
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Render a template part (partial)
     */
    public function renderPart(string $name, array $data = []): void
    {
        $path = $this->themePath . '/parts/' . $name . '.php';
        if (file_exists($path)) {
            extract($data);
            include $path;
        }
    }

    /**
     * Get a theme asset URL with cache busting
     */
    public function getAssetUrl(string $path): string
    {
        return cainty_asset($path);
    }

    /**
     * Get theme configuration from theme.json
     */
    public function getThemeConfig(): array
    {
        if ($this->themeConfig !== null) {
            return $this->themeConfig;
        }

        $configFile = $this->themePath . '/theme.json';
        if (file_exists($configFile)) {
            $this->themeConfig = json_decode(file_get_contents($configFile), true) ?: [];
        } else {
            $this->themeConfig = [];
        }

        return $this->themeConfig;
    }

    /**
     * Check if a template exists in the current theme
     */
    public function hasTemplate(string $name): bool
    {
        return file_exists($this->themePath . '/templates/' . $name . '.php');
    }

    /**
     * Get the theme path
     */
    public function getThemePath(): string
    {
        return $this->themePath;
    }

    /**
     * Process post content (shortcodes + filters)
     */
    public static function processContent(string $content): string
    {
        // Initialize shortcodes if not done
        ShortcodeEngine::init();

        // Apply content filters (plugins can modify)
        $content = Hook::apply('content_render', $content);

        // Process shortcodes
        $content = ShortcodeEngine::process($content);

        return $content;
    }
}

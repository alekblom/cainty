<?php

namespace Cainty\Plugins;

use Cainty\Database\Database;

/**
 * Plugin discovery, loading, and management
 */
class PluginLoader
{
    private string $pluginsDir;

    public function __construct()
    {
        $this->pluginsDir = CAINTY_ROOT . '/plugins';
    }

    /**
     * Discover all plugins by scanning for plugin.json files
     */
    public function discoverAll(): array
    {
        $plugins = [];
        if (!is_dir($this->pluginsDir)) {
            return $plugins;
        }

        $dirs = glob($this->pluginsDir . '/*/plugin.json');
        foreach ($dirs as $manifestPath) {
            $json = file_get_contents($manifestPath);
            $manifest = json_decode($json, true);
            if (!$manifest || empty($manifest['slug'])) {
                continue;
            }

            // Check DB for activation status
            $dbRecord = $this->getDbRecord($manifest['slug']);
            $manifest['is_active'] = $dbRecord ? (bool) $dbRecord['is_active'] : false;
            $manifest['installed'] = (bool) $dbRecord;
            $manifest['dir'] = dirname($manifestPath);

            $plugins[$manifest['slug']] = $manifest;
        }

        return $plugins;
    }

    /**
     * Load boot.php for all active plugins
     */
    public function loadActive(): void
    {
        if (!is_dir($this->pluginsDir)) {
            return;
        }

        try {
            $active = Database::fetchAll("SELECT slug FROM plugins WHERE is_active = 1");
        } catch (\PDOException $e) {
            // Table may not exist yet (during install)
            return;
        }

        foreach ($active as $row) {
            $bootFile = $this->pluginsDir . '/' . $row['slug'] . '/boot.php';
            if (file_exists($bootFile)) {
                require_once $bootFile;
            }
        }
    }

    /**
     * Activate a plugin
     */
    public function activate(string $slug): bool
    {
        $manifest = $this->getManifest($slug);
        if (!$manifest) {
            return false;
        }

        $existing = $this->getDbRecord($slug);
        if ($existing) {
            Database::update('plugins', [
                'is_active' => 1,
                'activated_at' => date('Y-m-d H:i:s'),
            ], 'slug = ?', [$slug]);
        } else {
            Database::insert('plugins', [
                'slug' => $slug,
                'name' => $manifest['name'] ?? $slug,
                'version' => $manifest['version'] ?? '1.0.0',
                'is_active' => 1,
                'activated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return true;
    }

    /**
     * Deactivate a plugin
     */
    public function deactivate(string $slug): bool
    {
        Database::update('plugins', [
            'is_active' => 0,
            'activated_at' => null,
        ], 'slug = ?', [$slug]);

        return true;
    }

    /**
     * Check if a plugin is active
     */
    public function isActive(string $slug): bool
    {
        $record = $this->getDbRecord($slug);
        return $record && (bool) $record['is_active'];
    }

    /**
     * Get plugin settings
     */
    public function getPluginSettings(string $slug): array
    {
        $record = $this->getDbRecord($slug);
        if (!$record || empty($record['settings'])) {
            return [];
        }
        return json_decode($record['settings'], true) ?: [];
    }

    /**
     * Save plugin settings
     */
    public function savePluginSettings(string $slug, array $settings): bool
    {
        return Database::update('plugins', [
            'settings' => json_encode($settings),
        ], 'slug = ?', [$slug]) > 0;
    }

    /**
     * Get plugin manifest from plugin.json
     */
    private function getManifest(string $slug): ?array
    {
        $path = $this->pluginsDir . '/' . $slug . '/plugin.json';
        if (!file_exists($path)) {
            return null;
        }
        return json_decode(file_get_contents($path), true);
    }

    /**
     * Get plugin DB record
     */
    private function getDbRecord(string $slug): ?array
    {
        try {
            return Database::fetchOne("SELECT * FROM plugins WHERE slug = ?", [$slug]);
        } catch (\PDOException $e) {
            return null;
        }
    }
}

<?php

namespace Cainty\Controllers;

use Cainty\Auth\Auth;
use Cainty\Database\Database;
use Cainty\AI\ProviderResolver;
use Cainty\AI\ModelRegistry;
use Cainty\Router\Response;

class AdminSettingsController
{
    public function index(array $params): void
    {
        $siteId = cainty_site_id();
        $settings = self::getAllSettings($siteId);
        $adminPage = 'settings';
        $adminPageTitle = 'Settings';

        include CAINTY_ROOT . '/admin/layout.php';
    }

    public function save(array $params): void
    {
        if (!cainty_verify_csrf()) {
            Response::json(['success' => false, 'error' => 'Invalid CSRF token'], 403);
            return;
        }

        if (Auth::user()['role'] !== 'admin') {
            Response::json(['success' => false, 'error' => 'Admin access required'], 403);
            return;
        }

        $siteId = cainty_site_id();
        $fields = ['site_name', 'site_tagline', 'posts_per_page', 'excerpt_length', 'theme'];

        try {
            foreach ($fields as $key) {
                if (isset($_POST[$key])) {
                    self::setSetting($siteId, $key, $_POST[$key]);
                }
            }

            // Update sites table for name/tagline
            if (isset($_POST['site_name'])) {
                Database::update('sites', [
                    'site_name' => trim($_POST['site_name']),
                    'site_tagline' => trim($_POST['site_tagline'] ?? ''),
                ], 'site_id = ?', [$siteId]);
            }

            Response::json(['success' => true, 'message' => 'Settings saved.']);
        } catch (\Exception $e) {
            Response::json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function llmKeys(array $params): void
    {
        $siteId = cainty_site_id();
        $keys = self::getLLMKeys($siteId);
        $providers = array_keys(ModelRegistry::getAll());
        $adminPage = 'llm-keys';
        $adminPageTitle = 'LLM API Keys';

        include CAINTY_ROOT . '/admin/layout.php';
    }

    public function saveLLMKey(array $params): void
    {
        if (!cainty_verify_csrf()) {
            Response::json(['success' => false, 'error' => 'Invalid CSRF token'], 403);
            return;
        }

        if (Auth::user()['role'] !== 'admin') {
            Response::json(['success' => false, 'error' => 'Admin access required'], 403);
            return;
        }

        $siteId = cainty_site_id();
        $provider = $_POST['provider'] ?? '';
        $apiKey = trim($_POST['api_key'] ?? '');
        $baseUrl = trim($_POST['base_url'] ?? '') ?: null;

        $validProviders = array_keys(ModelRegistry::getAll());
        if (!in_array($provider, $validProviders)) {
            Response::json(['success' => false, 'error' => 'Invalid provider']);
            return;
        }

        try {
            if (empty($apiKey)) {
                // Delete existing key
                Database::delete('llm_api_keys', 'site_id = ? AND provider = ?', [$siteId, $provider]);
                Response::json(['success' => true, 'message' => 'Key removed.']);
                return;
            }

            $encrypted = ProviderResolver::encrypt($apiKey);

            // Upsert
            $existing = Database::fetchOne(
                "SELECT key_id FROM llm_api_keys WHERE site_id = ? AND provider = ?",
                [$siteId, $provider]
            );

            if ($existing) {
                Database::update('llm_api_keys', [
                    'api_key_enc' => $encrypted,
                    'base_url' => $baseUrl,
                    'is_active' => 1,
                ], 'key_id = ?', [$existing['key_id']]);
            } else {
                Database::insert('llm_api_keys', [
                    'site_id' => $siteId,
                    'provider' => $provider,
                    'api_key_enc' => $encrypted,
                    'base_url' => $baseUrl,
                    'is_active' => 1,
                ]);
            }

            Response::json(['success' => true, 'message' => 'Key saved.']);
        } catch (\Exception $e) {
            Response::json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function testLLMKey(array $params): void
    {
        if (!cainty_verify_csrf()) {
            Response::json(['success' => false, 'error' => 'Invalid CSRF token'], 403);
            return;
        }

        $siteId = cainty_site_id();
        $provider = $_POST['provider'] ?? '';

        try {
            $client = \Cainty\AI\LLMClient::forProvider($provider, $siteId);
            $models = ModelRegistry::getForProvider($provider);
            $testModel = $models[0]['slug'] ?? '';

            $result = $client->chat($testModel, 'You are a test assistant.', 'Say "OK" and nothing else.', [
                'max_tokens' => 10,
            ]);

            if ($result['success']) {
                Response::json(['success' => true, 'message' => "Connection OK. Response: {$result['content']}"]);
            } else {
                Response::json(['success' => false, 'error' => $result['error']]);
            }
        } catch (\Exception $e) {
            Response::json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    // --- Setting helpers ---

    private static function getAllSettings(int $siteId): array
    {
        try {
            $rows = Database::fetchAll(
                "SELECT setting_key, setting_value FROM settings WHERE site_id = ? OR site_id IS NULL ORDER BY site_id",
                [$siteId]
            );
            $settings = [];
            foreach ($rows as $row) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
            return $settings;
        } catch (\PDOException $e) {
            return [];
        }
    }

    private static function setSetting(int $siteId, string $key, ?string $value): void
    {
        $existing = Database::fetchOne(
            "SELECT setting_id FROM settings WHERE site_id = ? AND setting_key = ?",
            [$siteId, $key]
        );

        if ($existing) {
            Database::update('settings', [
                'setting_value' => $value,
            ], 'setting_id = ?', [$existing['setting_id']]);
        } else {
            Database::insert('settings', [
                'site_id' => $siteId,
                'setting_key' => $key,
                'setting_value' => $value,
            ]);
        }
    }

    private static function getLLMKeys(int $siteId): array
    {
        try {
            return Database::fetchAll(
                "SELECT provider, base_url, is_active, created_at FROM llm_api_keys WHERE site_id = ?",
                [$siteId]
            );
        } catch (\PDOException $e) {
            return [];
        }
    }
}

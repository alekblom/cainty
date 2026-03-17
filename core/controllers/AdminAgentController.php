<?php

namespace Cainty\Controllers;

use Cainty\Auth\Auth;
use Cainty\AI\AgentManager;
use Cainty\AI\ModelRegistry;
use Cainty\Router\Response;

class AdminAgentController
{
    public function index(array $params): void
    {
        $siteId = cainty_site_id();
        $agents = AgentManager::getAgentsForSite($siteId);
        $adminPage = 'agents';
        $adminPageTitle = 'Agents';

        include CAINTY_ROOT . '/admin/layout.php';
    }

    public function create(array $params): void
    {
        $siteId = cainty_site_id();
        $agent = null;
        $models = ModelRegistry::getAll();
        $availableProviders = ModelRegistry::getAvailableProviders($siteId);
        $adminPage = 'agent-editor';
        $adminPageTitle = 'New Agent';

        include CAINTY_ROOT . '/admin/layout.php';
    }

    public function edit(array $params): void
    {
        $siteId = cainty_site_id();
        $agent = AgentManager::getAgent((int) $params['id']);

        if (!$agent || $agent['site_id'] != $siteId) {
            Response::redirect(cainty_admin_url('agents'));
            return;
        }

        $models = ModelRegistry::getAll();
        $availableProviders = ModelRegistry::getAvailableProviders($siteId);
        $adminPage = 'agent-editor';
        $adminPageTitle = 'Edit Agent: ' . $agent['name'];

        include CAINTY_ROOT . '/admin/layout.php';
    }

    public function save(array $params): void
    {
        if (!cainty_verify_csrf()) {
            Response::json(['success' => false, 'error' => 'Invalid CSRF token'], 403);
            return;
        }

        $siteId = cainty_site_id();
        $agentId = !empty($_POST['agent_id']) ? (int) $_POST['agent_id'] : null;

        $data = [
            'site_id' => $siteId,
            'name' => trim($_POST['name'] ?? ''),
            'slug' => cainty_slug($_POST['slug'] ?? $_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'system_prompt' => $_POST['system_prompt'] ?? '',
            'model_provider' => $_POST['model_provider'] ?? 'anthropic',
            'model_slug' => $_POST['model_slug'] ?? '',
            'post_length_min' => (int) ($_POST['post_length_min'] ?? 800),
            'post_length_max' => (int) ($_POST['post_length_max'] ?? 1500),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];

        // JSON fields
        foreach (['voice_rules', 'shortcode_rules', 'output_schema', 'quality_checklist', 'categories', 'tags_strategy'] as $jsonField) {
            $raw = trim($_POST[$jsonField] ?? '');
            if (!empty($raw)) {
                $decoded = json_decode($raw, true);
                $data[$jsonField] = (json_last_error() === JSON_ERROR_NONE) ? $raw : json_encode([$raw]);
            } else {
                $data[$jsonField] = null;
            }
        }

        if (empty($data['name'])) {
            Response::json(['success' => false, 'error' => 'Name is required']);
            return;
        }

        if (empty($data['system_prompt'])) {
            Response::json(['success' => false, 'error' => 'System prompt is required']);
            return;
        }

        try {
            if ($agentId) {
                unset($data['site_id']);
                AgentManager::updateAgent($agentId, $data);
            } else {
                $agentId = AgentManager::createAgent($data);
            }

            Response::json([
                'success' => true,
                'agent_id' => $agentId,
                'redirect' => cainty_admin_url('agents/' . $agentId . '/edit'),
            ]);
        } catch (\Exception $e) {
            Response::json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function execute(array $params): void
    {
        if (!cainty_verify_csrf()) {
            Response::json(['success' => false, 'error' => 'Invalid CSRF token'], 403);
            return;
        }

        $agentId = (int) ($_POST['agent_id'] ?? $params['id'] ?? 0);
        $topic = trim($_POST['topic'] ?? '');

        if (empty($topic)) {
            Response::json(['success' => false, 'error' => 'Topic prompt is required']);
            return;
        }

        $result = AgentManager::executeAgent($agentId, $topic, Auth::id());
        Response::json($result);
    }

    public function runs(array $params): void
    {
        $siteId = cainty_site_id();
        $agentId = isset($params['id']) ? (int) $params['id'] : null;

        if ($agentId) {
            $agent = AgentManager::getAgent($agentId);
            $runs = AgentManager::getRunHistory($agentId);
        } else {
            $agent = null;
            $runs = AgentManager::getRunHistory(null, $siteId);
        }

        $adminPage = 'agent-runs';
        $adminPageTitle = $agent ? "Runs: {$agent['name']}" : 'All Agent Runs';

        include CAINTY_ROOT . '/admin/layout.php';
    }

    public function delete(array $params): void
    {
        if (!cainty_verify_csrf()) {
            Response::json(['success' => false, 'error' => 'Invalid CSRF token'], 403);
            return;
        }

        $agentId = (int) $params['id'];
        AgentManager::deleteAgent($agentId);

        if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            Response::json(['success' => true]);
        } else {
            Response::redirect(cainty_admin_url('agents'));
        }
    }
}

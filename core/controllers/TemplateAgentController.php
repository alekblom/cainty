<?php

namespace Cainty\Controllers;

use Cainty\AI\LLMClient;
use Cainty\AI\ModelRegistry;
use Cainty\Router\Response;

class TemplateAgentController
{
    /**
     * Auto-discover themes that have demo/content.json
     */
    public static function discoverThemes(): array
    {
        $themesDir = CAINTY_ROOT . '/themes';
        $themes = [];

        foreach (glob($themesDir . '/*/demo/content.json') as $demoFile) {
            $themeDir = dirname(dirname($demoFile));
            $slug = basename($themeDir);

            // Skip the default theme
            if ($slug === 'default') continue;

            // Read theme.json for metadata
            $configFile = $themeDir . '/theme.json';
            $config = file_exists($configFile)
                ? json_decode(file_get_contents($configFile), true) ?? []
                : [];

            $themes[$slug] = [
                'slug' => $slug,
                'name' => $config['name'] ?? ucfirst($slug),
                'description' => $config['description'] ?? '',
                'parent' => $config['parent'] ?? null,
                'niche' => $config['niche'] ?? $slug,
            ];
        }

        ksort($themes);
        return $themes;
    }

    /**
     * Show the template agent form
     */
    public function index(array $params): void
    {
        $siteId = cainty_site_id();
        $themes = self::discoverThemes();
        $availableProviders = ModelRegistry::getAvailableProviders($siteId);
        $models = ModelRegistry::getAll();
        $adminPage = 'template-agent';
        $adminPageTitle = 'Template Agent';

        include CAINTY_ROOT . '/admin/layout.php';
    }

    /**
     * Generate customized content.json via AI
     */
    public function generate(array $params): void
    {
        if (!cainty_verify_csrf()) {
            Response::json(['success' => false, 'error' => 'Invalid CSRF token'], 403);
            return;
        }

        $themeSlug = trim($_POST['theme'] ?? '');
        $provider = trim($_POST['provider'] ?? 'anthropic');
        $modelSlug = trim($_POST['model'] ?? '');

        // Validate theme exists and has demo content
        $demoPath = CAINTY_ROOT . '/themes/' . basename($themeSlug) . '/demo/content.json';
        if (empty($themeSlug) || !file_exists($demoPath)) {
            Response::json(['success' => false, 'error' => 'Invalid theme or demo content not found']);
            return;
        }

        $demoContent = file_get_contents($demoPath);

        // Read theme config for context
        $configPath = CAINTY_ROOT . '/themes/' . basename($themeSlug) . '/theme.json';
        $config = file_exists($configPath)
            ? json_decode(file_get_contents($configPath), true) ?? []
            : [];

        // Collect business info from form
        $business = [
            'name' => trim($_POST['business_name'] ?? ''),
            'tagline' => trim($_POST['tagline'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'hours' => trim($_POST['hours'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
        ];

        if (empty($business['name'])) {
            Response::json(['success' => false, 'error' => 'Business name is required']);
            return;
        }

        // Build the AI prompt
        $themeName = $config['name'] ?? ucfirst($themeSlug);
        $systemPrompt = $this->buildSystemPrompt($themeName, $demoContent);
        $userPrompt = $this->buildUserPrompt($business, $demoContent);

        // Use default model if none selected
        if (empty($modelSlug)) {
            $modelSlug = 'claude-sonnet-4-5-20250929';
        }

        try {
            $siteId = cainty_site_id();
            $client = LLMClient::forProvider($provider, $siteId);
            $result = $client->chatJSON($modelSlug, $systemPrompt, $userPrompt, [
                'max_tokens' => 16000,
            ]);

            if (!$result['success']) {
                Response::json(['success' => false, 'error' => 'AI error: ' . ($result['error'] ?? 'Unknown error')]);
                return;
            }

            if (empty($result['parsed'])) {
                Response::json([
                    'success' => false,
                    'error' => 'Could not parse AI response as JSON: ' . ($result['parse_error'] ?? 'Unknown error'),
                    'raw' => $result['content'] ?? '',
                ]);
                return;
            }

            Response::json([
                'success' => true,
                'content' => $result['parsed'],
                'tokens' => [
                    'input' => $result['input_tokens'] ?? 0,
                    'output' => $result['output_tokens'] ?? 0,
                ],
                'duration_ms' => $result['duration_ms'] ?? 0,
            ]);
        } catch (\Exception $e) {
            Response::json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Save generated content to the theme's demo/content.json
     */
    public function save(array $params): void
    {
        if (!cainty_verify_csrf()) {
            Response::json(['success' => false, 'error' => 'Invalid CSRF token'], 403);
            return;
        }

        $themeSlug = basename(trim($_POST['theme'] ?? ''));
        $content = $_POST['content'] ?? '';

        $targetPath = CAINTY_ROOT . '/themes/' . $themeSlug . '/demo/content.json';
        if (empty($themeSlug) || !is_dir(dirname($targetPath))) {
            Response::json(['success' => false, 'error' => 'Invalid theme']);
            return;
        }

        // Parse and re-encode to ensure valid JSON with proper formatting
        $parsed = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Response::json(['success' => false, 'error' => 'Invalid JSON: ' . json_last_error_msg()]);
            return;
        }

        $formatted = json_encode($parsed, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if (file_put_contents($targetPath, $formatted . "\n") === false) {
            Response::json(['success' => false, 'error' => 'Could not write file: ' . $targetPath]);
            return;
        }

        Response::json(['success' => true, 'path' => $targetPath]);
    }

    /**
     * Build the system prompt for the AI
     *
     * Detects language from the demo content so the AI matches it.
     */
    private function buildSystemPrompt(string $themeName, string $demoContent): string
    {
        // Detect language from demo content (simple heuristic)
        $isNorwegian = (
            str_contains($demoContent, 'Kontakt') ||
            str_contains($demoContent, 'tjenester') ||
            str_contains($demoContent, 'Om oss')
        );

        if ($isNorwegian) {
            return <<<PROMPT
You are an expert at creating website content for businesses. You generate complete, realistic content in Norwegian (bokmal) for a {$themeName} business website.

RULES:
1. Keep the EXACT same JSON structure and all keys from the template you receive.
2. Replace ALL demo content with realistic content tailored to the client's business.
3. Use natural, professional Norwegian (bokmal). Avoid English words where Norwegian alternatives exist.
4. Prices, times, and addresses must be realistic for the Norwegian market.
5. Keep all technical fields (urls like "#section", placeholder image filenames, etc) unchanged.
6. Generate at least as many items in lists (services, FAQ, reviews, etc) as in the template.
7. Create realistic Norwegian names for reviews/team members (first name + last name initial).
8. Return ONLY valid JSON, no explanations or markdown.
9. Statistics/numbers should be credible for a real Norwegian business of this type.
10. Adapt hero text, CTA texts, and footer text to fit the business.
PROMPT;
        }

        return <<<PROMPT
You are an expert at creating website content for businesses. You generate complete, realistic content for a {$themeName} business website.

RULES:
1. Keep the EXACT same JSON structure and all keys from the template you receive.
2. Replace ALL demo content with realistic content tailored to the client's business.
3. Match the language used in the template content.
4. Prices, times, and addresses must be realistic for the business's market.
5. Keep all technical fields (urls like "#section", placeholder image filenames, etc) unchanged.
6. Generate at least as many items in lists (services, FAQ, reviews, etc) as in the template.
7. Create realistic names for reviews/team members.
8. Return ONLY valid JSON, no explanations or markdown.
9. Statistics/numbers should be credible for a real business of this type.
10. Adapt hero text, CTA texts, and footer text to fit the business.
PROMPT;
    }

    /**
     * Build the user prompt with business info and template
     */
    private function buildUserPrompt(array $business, string $demoContent): string
    {
        $info = "BUSINESS INFORMATION:\n";
        $info .= "- Business name: {$business['name']}\n";

        if (!empty($business['tagline'])) {
            $info .= "- Tagline: {$business['tagline']}\n";
        }
        if (!empty($business['address'])) {
            $info .= "- Address: {$business['address']}\n";
        }
        if (!empty($business['phone'])) {
            $info .= "- Phone: {$business['phone']}\n";
        }
        if (!empty($business['email'])) {
            $info .= "- Email: {$business['email']}\n";
        }
        if (!empty($business['hours'])) {
            $info .= "- Opening hours: {$business['hours']}\n";
        }
        if (!empty($business['description'])) {
            $info .= "\nADDITIONAL BUSINESS DETAILS:\n{$business['description']}\n";
        }

        return <<<PROMPT
{$info}

TEMPLATE (keep this exact JSON structure, but replace all content with realistic content for the client's business):

{$demoContent}

Generate the complete, customized content.json for this business. Return ONLY JSON.
PROMPT;
    }
}

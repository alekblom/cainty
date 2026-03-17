<?php

namespace Cainty\Controllers;

use Cainty\Themes\ThemeLoader;

class DocsController
{
    private const PAGES = [
        'installation'  => 'Installation',
        'configuration' => 'Configuration',
        'themes'        => 'Themes',
        'plugins'       => 'Plugins',
        'ai-agents'     => 'AI Agents',
        'api-reference' => 'API Reference',
        'hosting'       => 'Hosting',
    ];

    public function index(array $params): void
    {
        $theme = new ThemeLoader();
        $theme->render('docs', [
            'pageTitle'    => 'Documentation — Cainty',
            'docPages'     => self::PAGES,
            'currentPage'  => 'index',
            'docContent'   => $this->loadContent('index'),
        ]);
    }

    public function show(array $params): void
    {
        $page = $params['page'] ?? '';

        // Only allow known pages
        if (!isset(self::PAGES[$page])) {
            http_response_code(404);
            $theme = new ThemeLoader();
            $theme->render('404', ['pageTitle' => 'Not Found']);
            return;
        }

        $theme = new ThemeLoader();
        $theme->render('docs', [
            'pageTitle'    => self::PAGES[$page] . ' — Cainty Docs',
            'docPages'     => self::PAGES,
            'currentPage'  => $page,
            'docContent'   => $this->loadContent($page),
            'docPageTitle' => self::PAGES[$page],
        ]);
    }

    private function loadContent(string $page): string
    {
        $file = CAINTY_ROOT . '/_docs/' . $page . '.php';
        if (!file_exists($file)) {
            return '<p>Documentation page not found.</p>';
        }

        ob_start();
        include $file;
        return ob_get_clean();
    }
}

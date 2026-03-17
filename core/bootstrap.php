<?php
/**
 * Cainty CMS — Bootstrap
 *
 * Loads configuration, initializes autoloader, connects to database,
 * detects current site, starts session.
 */

// Error reporting based on environment
$envFile = CAINTY_ROOT . '/.env';
$config = [];

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        if (strpos($line, '=') === false) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        // Remove surrounding quotes
        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            $value = substr($value, 1, -1);
        }
        $config[$key] = $value;
    }
}

// Store config globally
$GLOBALS['cainty_config'] = $config;

// Error reporting
if (($config['APP_DEBUG'] ?? 'false') === 'true') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Timezone
date_default_timezone_set('UTC');

// Character encoding
mb_internal_encoding('UTF-8');

// Autoloader
require_once CAINTY_ROOT . '/core/autoload.php';

// Helper functions
require_once CAINTY_ROOT . '/core/helpers/functions.php';
require_once CAINTY_ROOT . '/core/helpers/csrf.php';

// Initialize database
use Cainty\Database\Database;

Database::connect();

// Run any pending migrations
use Cainty\Database\Migration;

$migration = new Migration(Database::getInstance());
$migration->run();

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400 * 30,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// Detect current site by domain
$host = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? 'localhost'));
$host = preg_replace('/^www\./', '', $host);

$site = Database::fetchOne("SELECT * FROM sites WHERE site_domain = ? AND is_active = 1", [$host]);
if (!$site) {
    $site = Database::fetchOne("SELECT * FROM sites WHERE site_id = 1");
}
$GLOBALS['current_site'] = $site;

// Initialize plugin hook system
use Cainty\Plugins\Hook;
use Cainty\Plugins\PluginLoader;

// Load active plugins
$pluginLoader = new PluginLoader();
$pluginLoader->loadActive();

// Initialize request and router
use Cainty\Router\Router;
use Cainty\Router\Request;

$request = new Request();
$router = new Router();

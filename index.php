<?php
/**
 * Cainty CMS — Front Controller
 *
 * All requests are routed through this file via .htaccess.
 */

// Define root path
define('CAINTY_ROOT', __DIR__);

// Check if installed
if (!file_exists(CAINTY_ROOT . '/storage/installed.lock') && basename($_SERVER['SCRIPT_NAME']) !== 'install.php') {
    header('Location: /install.php');
    exit;
}

// Bootstrap the application
require_once CAINTY_ROOT . '/core/bootstrap.php';

// Load routes and dispatch
require_once CAINTY_ROOT . '/core/routes.php';

$router->dispatch($request->method(), $request->uri());

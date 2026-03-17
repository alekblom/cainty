<?php
/**
 * Cainty CMS — Route Definitions
 */

use Cainty\Auth\Middleware;
use Cainty\Controllers\HomeController;
use Cainty\Controllers\ArchiveController;
use Cainty\Controllers\ResolveController;
use Cainty\Controllers\AuthController;
use Cainty\Controllers\SearchController;
use Cainty\Controllers\AdminDashboardController;
use Cainty\Controllers\AdminPostController;
use Cainty\Controllers\AdminCategoryController;
use Cainty\Controllers\AdminTagController;
use Cainty\Controllers\AdminMediaController;
use Cainty\Controllers\AdminAgentController;
use Cainty\Controllers\AdminQueueController;
use Cainty\Controllers\AdminSettingsController;
use Cainty\Controllers\DocsController;

// Register middleware handlers
$router->middleware('auth', [Middleware::class, 'auth']);
$router->middleware('admin_or_editor', [Middleware::class, 'adminOrEditor']);
$router->middleware('guest', [Middleware::class, 'guest']);

// === Public routes ===
$router->get('/', [HomeController::class, 'index']);
$router->get('/page/{num}', [HomeController::class, 'index']);

$router->get('/tag/{tag_slug}', [ArchiveController::class, 'byTag']);
$router->get('/tag/{tag_slug}/page/{num}', [ArchiveController::class, 'byTag']);

$router->get('/author/{slug}', [ArchiveController::class, 'byAuthor']);

$router->get('/search', [SearchController::class, 'index']);

// Auth
$router->get('/login', [AuthController::class, 'loginForm']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/logout', [AuthController::class, 'logout']);
$router->get('/auth/callback', [AuthController::class, 'ssoCallback']);

// === Admin routes (require auth + editor/admin role) ===
$router->group('/admin', function ($router) {
    $router->get('/', [AdminDashboardController::class, 'index']);

    // Posts
    $router->get('/posts', [AdminPostController::class, 'index']);
    $router->get('/posts/new', [AdminPostController::class, 'create']);
    $router->get('/posts/{id}/edit', [AdminPostController::class, 'edit']);
    $router->post('/posts/save', [AdminPostController::class, 'save']);
    $router->post('/posts/{id}/delete', [AdminPostController::class, 'delete']);

    // Categories
    $router->get('/categories', [AdminCategoryController::class, 'index']);
    $router->post('/categories/save', [AdminCategoryController::class, 'save']);
    $router->post('/categories/{id}/delete', [AdminCategoryController::class, 'delete']);

    // Tags
    $router->get('/tags', [AdminTagController::class, 'index']);
    $router->post('/tags/save', [AdminTagController::class, 'save']);
    $router->post('/tags/{id}/delete', [AdminTagController::class, 'delete']);

    // Media
    $router->get('/media', [AdminMediaController::class, 'index']);
    $router->post('/media/upload', [AdminMediaController::class, 'upload']);
    $router->post('/media/{id}/delete', [AdminMediaController::class, 'delete']);

    // Agents
    $router->get('/agents', [AdminAgentController::class, 'index']);
    $router->get('/agents/new', [AdminAgentController::class, 'create']);
    $router->get('/agents/runs', [AdminAgentController::class, 'runs']);
    $router->get('/agents/{id}/edit', [AdminAgentController::class, 'edit']);
    $router->get('/agents/{id}/runs', [AdminAgentController::class, 'runs']);
    $router->post('/agents/save', [AdminAgentController::class, 'save']);
    $router->post('/agents/{id}/execute', [AdminAgentController::class, 'execute']);
    $router->post('/agents/{id}/delete', [AdminAgentController::class, 'delete']);

    // Content Queue
    $router->get('/queue', [AdminQueueController::class, 'index']);
    $router->get('/queue/{id}/review', [AdminQueueController::class, 'review']);
    $router->post('/queue/{id}/approve', [AdminQueueController::class, 'approve']);
    $router->post('/queue/{id}/reject', [AdminQueueController::class, 'reject']);
    $router->post('/queue/{id}/update', [AdminQueueController::class, 'update']);

    // Settings
    $router->get('/settings', [AdminSettingsController::class, 'index']);
    $router->post('/settings/save', [AdminSettingsController::class, 'save']);
    $router->get('/settings/llm-keys', [AdminSettingsController::class, 'llmKeys']);
    $router->post('/settings/llm-keys/save', [AdminSettingsController::class, 'saveLLMKey']);
    $router->post('/settings/llm-keys/test', [AdminSettingsController::class, 'testLLMKey']);

}, ['auth', 'admin_or_editor']);

// === Documentation ===
$router->get('/docs', [DocsController::class, 'index']);
$router->get('/docs/{page}', [DocsController::class, 'show']);

// === Catch-all slug resolver (MUST be last) ===
$router->get('/{slug}/page/{num}', [ResolveController::class, 'resolveWithPagination']);
$router->get('/{slug}', [ResolveController::class, 'resolve']);

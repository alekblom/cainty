<h1>API Reference</h1>
<p>Key classes and helper functions available in Cainty.</p>

<h2>Helper Functions</h2>
<table>
    <thead>
        <tr><th>Function</th><th>Returns</th><th>Description</th></tr>
    </thead>
    <tbody>
        <tr><td><code>cainty_url($path = '')</code></td><td>string</td><td>Full URL for a path</td></tr>
        <tr><td><code>cainty_admin_url($path = '')</code></td><td>string</td><td>Admin panel URL</td></tr>
        <tr><td><code>cainty_asset($path)</code></td><td>string</td><td>Theme asset URL with cache-busting</td></tr>
        <tr><td><code>cainty_config($key, $default)</code></td><td>string</td><td>Get .env config value</td></tr>
        <tr><td><code>cainty_is_admin()</code></td><td>bool</td><td>Check if user is admin</td></tr>
        <tr><td><code>cainty_current_site()</code></td><td>array</td><td>Get current site settings</td></tr>
        <tr><td><code>cainty_site_id()</code></td><td>int</td><td>Get current site ID</td></tr>
        <tr><td><code>cainty_verify_csrf()</code></td><td>bool</td><td>Verify CSRF token</td></tr>
        <tr><td><code>e($string)</code></td><td>string</td><td>HTML-escape a string</td></tr>
    </tbody>
</table>

<h2>Core Classes</h2>

<h3>Cainty\Database\Database</h3>
<p>PDO database wrapper (static singleton):</p>
<pre><code>use Cainty\Database\Database;

$pdo = Database::get();
$stmt = $pdo->prepare("SELECT * FROM posts WHERE post_id = ?");
$stmt->execute([$id]);
$post = $stmt->fetch();</code></pre>

<h3>Cainty\Content\Post</h3>
<p>Static methods for post operations:</p>
<pre><code>use Cainty\Content\Post;

// Get published posts
$posts = Post::getPublished($siteId, $limit, $offset);
$count = Post::countPublished($siteId);

// Get single post by slug
$post = Post::getBySlug($slug, $siteId);</code></pre>

<h3>Cainty\Content\Category</h3>
<pre><code>use Cainty\Content\Category;

$categories = Category::getAll($siteId);
$category = Category::getBySlug($slug, $siteId);</code></pre>

<h3>Cainty\Content\Tag</h3>
<pre><code>use Cainty\Content\Tag;

$tags = Tag::getAll($siteId);
$tags = Tag::getForPost($postId);</code></pre>

<h3>Cainty\Content\Media</h3>
<pre><code>use Cainty\Content\Media;

$files = Media::getAll($siteId, $limit, $offset);
$file = Media::getById($id);</code></pre>

<h3>Cainty\Themes\ThemeLoader</h3>
<pre><code>use Cainty\Themes\ThemeLoader;

$theme = new ThemeLoader();
$theme->render('home', ['posts' => $posts]);
$theme->renderPart('header', compact('site'));</code></pre>

<h3>Cainty\Plugins\Hook</h3>
<pre><code>use Cainty\Plugins\Hook;

// Actions
Hook::on('event_name', $callback, $priority);
Hook::fire('event_name');

// Filters
Hook::filter('filter_name', $callback, $priority);
$result = Hook::apply('filter_name', $value);</code></pre>

<h3>Cainty\Shortcodes\ShortcodeEngine</h3>
<pre><code>use Cainty\Shortcodes\ShortcodeEngine;

ShortcodeEngine::register('tag', function ($attrs) {
    return 'output HTML';
});

$html = ShortcodeEngine::process($content);</code></pre>

<h3>Cainty\Routing\Router</h3>
<pre><code>// Route definitions (in core/routes.php)
$router->get('/path', [Controller::class, 'method']);
$router->post('/path', [Controller::class, 'method']);
$router->group('/prefix', function ($router) {
    $router->get('/sub', [Controller::class, 'method']);
}, ['middleware_name']);</code></pre>

<h1>Plugin Development</h1>
<p>Plugins extend Cainty's functionality using a hook-based system inspired by WordPress.</p>

<h2>Plugin Structure</h2>
<pre><code>plugins/my-plugin/
├── plugin.json     # Plugin metadata (required)
└── boot.php        # Entry point (required)</code></pre>

<h2>plugin.json</h2>
<pre><code>{
    "name": "My Plugin",
    "version": "1.0.0",
    "author": "Your Name",
    "description": "What this plugin does"
}</code></pre>

<h2>boot.php</h2>
<p>The entry point for your plugin. This file is included when the plugin is active:</p>
<pre><code>&lt;?php
use Cainty\Plugins\Hook;

// Your plugin code here
Hook::on('header_after', function () {
    echo '&lt;div class="notice"&gt;Hello from my plugin!&lt;/div&gt;';
});</code></pre>

<h2>Actions (Hook::on)</h2>
<p>Actions let you execute code at specific points in the page lifecycle:</p>
<pre><code>// Register an action
Hook::on('event_name', function () {
    // Your code runs here
});

// Register with priority (lower = earlier, default 10)
Hook::on('event_name', function () {
    // Runs before default priority
}, 5);</code></pre>

<h3>Available Action Hooks</h3>
<table>
    <thead>
        <tr><th>Hook</th><th>When It Fires</th></tr>
    </thead>
    <tbody>
        <tr><td><code>head_meta</code></td><td>Inside <code>&lt;head&gt;</code>, for adding meta tags or styles</td></tr>
        <tr><td><code>header_after</code></td><td>After the site header</td></tr>
        <tr><td><code>footer_before</code></td><td>Before the site footer</td></tr>
    </tbody>
</table>

<h2>Filters (Hook::filter)</h2>
<p>Filters let you modify data as it passes through the system:</p>
<pre><code>// Modify post content before rendering
Hook::filter('content_render', function ($content) {
    // Modify and return the content
    return $content . '&lt;p&gt;Added by my plugin&lt;/p&gt;';
});</code></pre>

<h3>Available Filters</h3>
<table>
    <thead>
        <tr><th>Filter</th><th>Data Passed</th></tr>
    </thead>
    <tbody>
        <tr><td><code>content_render</code></td><td>Post/page HTML content before display</td></tr>
    </tbody>
</table>

<h2>Firing Hooks</h2>
<p>In your own code or templates, you can fire custom hooks:</p>
<pre><code>// Fire an action
Hook::fire('my_custom_action');

// Apply a filter
$data = Hook::apply('my_custom_filter', $data);</code></pre>

<h2>Shortcodes</h2>
<p>Plugins can register custom shortcodes:</p>
<pre><code>use Cainty\Shortcodes\ShortcodeEngine;

ShortcodeEngine::register('greeting', function ($attrs) {
    $name = $attrs['name'] ?? 'World';
    return "&lt;p&gt;Hello, " . htmlspecialchars($name) . "!&lt;/p&gt;";
});</code></pre>
<p>Usage in content: <code>[greeting name="Cainty"]</code></p>

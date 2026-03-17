<h1>Theme Development</h1>
<p>Themes control the look and layout of your Cainty site. Each theme is a directory inside <code>themes/</code>.</p>

<h2>Theme Structure</h2>
<pre><code>themes/my-theme/
├── theme.json              # Theme metadata
├── templates/
│   ├── layout.php          # Main layout wrapper
│   ├── home.php            # Homepage template
│   ├── single-post.php     # Single post view
│   ├── archive.php         # Category/tag archive
│   ├── page.php            # Static page
│   ├── search.php          # Search results
│   └── 404.php             # Not found page
├── parts/
│   ├── header.php          # Site header partial
│   ├── footer.php          # Site footer partial
│   ├── post-card.php       # Post card component
│   └── pagination.php      # Pagination component
└── assets/
    ├── css/style.css       # Theme styles
    └── js/main.js          # Theme scripts</code></pre>

<h2>theme.json</h2>
<p>Required metadata file:</p>
<pre><code>{
    "name": "My Theme",
    "version": "1.0.0",
    "author": "Your Name",
    "description": "A custom Cainty theme"
}</code></pre>

<h2>Template Hierarchy</h2>
<p>Cainty resolves templates using a hierarchy, trying specific templates first:</p>
<table>
    <thead>
        <tr><th>Type</th><th>Resolution Order</th></tr>
    </thead>
    <tbody>
        <tr><td>Post</td><td><code>single-post-{slug}.php</code> &rarr; <code>single-post.php</code></td></tr>
        <tr><td>Page</td><td><code>page-{slug}.php</code> &rarr; <code>page.php</code></td></tr>
        <tr><td>Category</td><td><code>archive-{slug}.php</code> &rarr; <code>archive.php</code></td></tr>
        <tr><td>Tag</td><td><code>archive-tag-{slug}.php</code> &rarr; <code>archive.php</code></td></tr>
    </tbody>
</table>

<h2>Layout System</h2>
<p>The <code>layout.php</code> template wraps all pages. It receives a <code>$contentTemplate</code> variable pointing to the active page template:</p>
<pre><code>&lt;!DOCTYPE html&gt;
&lt;html&gt;
&lt;head&gt;
    &lt;title&gt;&lt;?= e($pageTitle ?? 'My Site') ?&gt;&lt;/title&gt;
    &lt;link rel="stylesheet" href="&lt;?= cainty_asset('assets/css/style.css') ?&gt;"&gt;
&lt;/head&gt;
&lt;body&gt;
    &lt;?php $theme-&gt;renderPart('header', compact('site')); ?&gt;

    &lt;main&gt;
        &lt;?php include $contentTemplate; ?&gt;
    &lt;/main&gt;

    &lt;?php $theme-&gt;renderPart('footer', compact('site')); ?&gt;
&lt;/body&gt;
&lt;/html&gt;</code></pre>

<h2>Template Variables</h2>
<p>All templates receive these variables automatically:</p>
<ul>
    <li><code>$site</code> — Array of site settings (name, tagline, URL, etc.)</li>
    <li><code>$theme</code> — The <code>ThemeLoader</code> instance</li>
    <li><code>$is_admin</code> — Whether the current user is an admin</li>
    <li><code>$pageTitle</code> — Page title for the &lt;title&gt; tag</li>
</ul>

<h2>Helper Functions</h2>
<table>
    <thead>
        <tr><th>Function</th><th>Description</th></tr>
    </thead>
    <tbody>
        <tr><td><code>cainty_url($path)</code></td><td>Generate a full URL for a path</td></tr>
        <tr><td><code>cainty_asset($path)</code></td><td>Theme asset URL with cache busting</td></tr>
        <tr><td><code>cainty_admin_url($path)</code></td><td>Admin panel URL</td></tr>
        <tr><td><code>e($string)</code></td><td>HTML-escape a string</td></tr>
        <tr><td><code>cainty_is_admin()</code></td><td>Check if current user is admin</td></tr>
    </tbody>
</table>

<h2>Activating a Theme</h2>
<p>Set the <code>THEME</code> variable in your <code>.env</code> file:</p>
<pre><code>THEME=my-theme</code></pre>

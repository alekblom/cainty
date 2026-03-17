<h1>Installation</h1>
<p>Get Cainty running on your server in minutes.</p>

<h2>Requirements</h2>
<table>
    <thead>
        <tr><th>Requirement</th><th>Minimum Version</th></tr>
    </thead>
    <tbody>
        <tr><td>PHP</td><td>8.2+</td></tr>
        <tr><td>Database</td><td>SQLite 3 (default) or MySQL/MariaDB 5.7+</td></tr>
        <tr><td>Web Server</td><td>Apache with mod_rewrite</td></tr>
        <tr><td>PHP Extensions</td><td>PDO, mbstring, json, curl</td></tr>
    </tbody>
</table>

<h2>Step 1: Download</h2>
<p>Clone the repository or download the latest release:</p>
<pre><code>git clone https://github.com/alexiuz/cainty.git
cd cainty</code></pre>

<h2>Step 2: Configure</h2>
<p>Copy the example configuration file:</p>
<pre><code>cp .env.example .env</code></pre>
<p>Edit <code>.env</code> with your settings. At minimum, set:</p>
<ul>
    <li><code>APP_URL</code> — Your site's full URL (e.g., <code>https://yourdomain.com</code>)</li>
    <li><code>APP_SECRET</code> — A random 64-character string for session security</li>
    <li><code>DB_DRIVER</code> — <code>sqlite</code> (default) or <code>mysql</code></li>
</ul>

<h2>Step 3: Set Permissions</h2>
<p>Ensure the storage directory is writable:</p>
<pre><code>chmod -R 775 storage/
chown -R www-data:www-data storage/</code></pre>

<h2>Step 4: Web Installer</h2>
<p>Point your browser to <code>https://yourdomain.com/install.php</code>. The installer will:</p>
<ol>
    <li>Check system requirements</li>
    <li>Configure the database</li>
    <li>Create your admin account</li>
    <li>Set up the site name and settings</li>
</ol>

<h2>Step 5: Apache Configuration</h2>
<p>Cainty includes an <code>.htaccess</code> file in the <code>public/</code> directory. Make sure your virtual host points to the <code>public/</code> directory:</p>
<pre><code>&lt;VirtualHost *:443&gt;
    ServerName yourdomain.com
    DocumentRoot /var/www/cainty/public

    &lt;Directory /var/www/cainty/public&gt;
        AllowOverride All
        Require all granted
    &lt;/Directory&gt;
&lt;/VirtualHost&gt;</code></pre>

<h2>Post-Install</h2>
<p>After installation:</p>
<ul>
    <li>Delete or rename <code>public/install.php</code> for security</li>
    <li>Log in to <code>/admin</code> to start creating content</li>
    <li>Configure your LLM API keys in <strong>Admin &gt; Settings &gt; LLM Keys</strong> to use AI agents</li>
</ul>

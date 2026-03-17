<h1>Configuration</h1>
<p>All configuration is managed through the <code>.env</code> file in the project root.</p>

<h2>Application Settings</h2>
<table>
    <thead>
        <tr><th>Variable</th><th>Default</th><th>Description</th></tr>
    </thead>
    <tbody>
        <tr><td><code>APP_NAME</code></td><td>Cainty</td><td>Application name</td></tr>
        <tr><td><code>APP_URL</code></td><td>—</td><td>Full URL of your site (required)</td></tr>
        <tr><td><code>APP_ENV</code></td><td>production</td><td><code>production</code> or <code>development</code></td></tr>
        <tr><td><code>APP_DEBUG</code></td><td>false</td><td>Show detailed error messages</td></tr>
        <tr><td><code>APP_SECRET</code></td><td>—</td><td>64-char random string for sessions (required)</td></tr>
    </tbody>
</table>

<h2>Database</h2>
<h3>SQLite (Default)</h3>
<pre><code>DB_DRIVER=sqlite
DB_PATH=storage/cainty.db</code></pre>
<p>SQLite requires no additional setup. The database file is created automatically during installation.</p>

<h3>MySQL / MariaDB</h3>
<pre><code>DB_DRIVER=mysql
DB_HOST=localhost
DB_PORT=3306
DB_NAME=cainty
DB_USER=root
DB_PASS=your_password</code></pre>

<h2>Theme</h2>
<table>
    <thead>
        <tr><th>Variable</th><th>Default</th><th>Description</th></tr>
    </thead>
    <tbody>
        <tr><td><code>THEME</code></td><td>default</td><td>Active theme directory name</td></tr>
    </tbody>
</table>

<h2>Authentication</h2>
<table>
    <thead>
        <tr><th>Variable</th><th>Default</th><th>Description</th></tr>
    </thead>
    <tbody>
        <tr><td><code>AUTH_METHOD</code></td><td>local</td><td><code>local</code> or <code>alexiuz_sso</code></td></tr>
        <tr><td><code>ALEXIUZ_AUTH_HUB</code></td><td>—</td><td>SSO hub URL (for managed hosting)</td></tr>
    </tbody>
</table>

<h2>LLM API Keys</h2>
<p>Add API keys for the providers you want to use with AI agents:</p>
<pre><code>ANTHROPIC_API_KEY=sk-ant-...
OPENAI_API_KEY=sk-...
GOOGLE_API_KEY=...
DEEPSEEK_API_KEY=...
XAI_API_KEY=...
OLLAMA_BASE_URL=http://localhost:11434</code></pre>
<p>Keys can also be managed through <strong>Admin &gt; Settings &gt; LLM Keys</strong> where they are stored encrypted in the database.</p>

<h2>File Uploads</h2>
<table>
    <thead>
        <tr><th>Variable</th><th>Default</th><th>Description</th></tr>
    </thead>
    <tbody>
        <tr><td><code>UPLOAD_MAX_SIZE</code></td><td>10485760</td><td>Max upload size in bytes (10MB)</td></tr>
        <tr><td><code>UPLOAD_DIR</code></td><td>storage/uploads</td><td>Upload directory path</td></tr>
    </tbody>
</table>

<h2>Credits System (Managed Hosting)</h2>
<table>
    <thead>
        <tr><th>Variable</th><th>Default</th><th>Description</th></tr>
    </thead>
    <tbody>
        <tr><td><code>OOMPH_ENABLED</code></td><td>false</td><td>Enable credit-based AI billing</td></tr>
        <tr><td><code>OOMPH_SERVICE_NAME</code></td><td>cainty</td><td>Service identifier for billing</td></tr>
    </tbody>
</table>

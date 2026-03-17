<h1>Cainty Documentation</h1>
<p>Welcome to the Cainty CMS documentation. Cainty is an AI-first open source content management system built with PHP 8.2.</p>

<h2>Quick Start</h2>
<ol>
    <li><strong>Clone the repository</strong> — <code>git clone https://github.com/alexiuz/cainty.git</code></li>
    <li><strong>Configure</strong> — Copy <code>.env.example</code> to <code>.env</code> and edit your settings</li>
    <li><strong>Install</strong> — Open your browser and navigate to <code>/install.php</code></li>
</ol>

<h2>Documentation Sections</h2>
<table>
    <thead>
        <tr><th>Section</th><th>Description</th></tr>
    </thead>
    <tbody>
        <tr><td><a href="<?= cainty_url('docs/installation') ?>">Installation</a></td><td>System requirements and step-by-step install guide</td></tr>
        <tr><td><a href="<?= cainty_url('docs/configuration') ?>">Configuration</a></td><td>All .env options, database setup, and server config</td></tr>
        <tr><td><a href="<?= cainty_url('docs/themes') ?>">Themes</a></td><td>Build and customize themes with the template hierarchy</td></tr>
        <tr><td><a href="<?= cainty_url('docs/plugins') ?>">Plugins</a></td><td>Create plugins using the hook and filter system</td></tr>
        <tr><td><a href="<?= cainty_url('docs/ai-agents') ?>">AI Agents</a></td><td>Set up AI content agents with 6 LLM providers</td></tr>
        <tr><td><a href="<?= cainty_url('docs/api-reference') ?>">API Reference</a></td><td>Key classes, functions, and helper reference</td></tr>
        <tr><td><a href="<?= cainty_url('docs/hosting') ?>">Hosting</a></td><td>Managed hosting plans and deployment info</td></tr>
    </tbody>
</table>

<h2>System Requirements</h2>
<ul>
    <li>PHP 8.2 or higher</li>
    <li>SQLite 3 (default) or MySQL/MariaDB 5.7+</li>
    <li>Apache with mod_rewrite enabled</li>
    <li>PHP extensions: PDO, mbstring, json, curl</li>
</ul>

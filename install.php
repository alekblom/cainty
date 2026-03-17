<?php
/**
 * Cainty CMS — Web Installer
 *
 * Multi-step installation wizard.
 */

define('CAINTY_ROOT', __DIR__);

// Prevent re-install
if (file_exists(CAINTY_ROOT . '/storage/installed.lock')) {
    header('Location: /');
    exit;
}

// Session must start before reading $_SESSION
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$step = (int) ($_POST['step'] ?? $_GET['step'] ?? 1);
$errors = [];
$success = false;

// Process step submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($step) {
        case 2: // DB config submitted
            $_SESSION['install'] = array_merge($_SESSION['install'] ?? [], [
                'db_driver' => $_POST['db_driver'] ?? 'sqlite',
                'db_host' => $_POST['db_host'] ?? 'localhost',
                'db_port' => $_POST['db_port'] ?? '3306',
                'db_name' => $_POST['db_name'] ?? '',
                'db_user' => $_POST['db_user'] ?? '',
                'db_pass' => $_POST['db_pass'] ?? '',
            ]);

            // Test MariaDB connection if selected
            if ($_POST['db_driver'] === 'mysql') {
                try {
                    new PDO(
                        "mysql:host={$_POST['db_host']};port={$_POST['db_port']};dbname={$_POST['db_name']}",
                        $_POST['db_user'],
                        $_POST['db_pass']
                    );
                } catch (PDOException $e) {
                    $errors[] = 'Database connection failed: ' . $e->getMessage();
                    $step = 2;
                    break;
                }
            }
            $step = 3;
            break;

        case 3: // Site info submitted
            $_SESSION['install'] = array_merge($_SESSION['install'] ?? [], [
                'site_name' => trim($_POST['site_name'] ?? ''),
                'site_url' => rtrim(trim($_POST['site_url'] ?? ''), '/'),
                'site_tagline' => trim($_POST['site_tagline'] ?? ''),
            ]);
            if (empty($_SESSION['install']['site_name'])) {
                $errors[] = 'Site name is required.';
                break;
            }
            $step = 4;
            break;

        case 4: // Admin account submitted
            $email = trim($_POST['admin_email'] ?? '');
            $username = trim($_POST['admin_username'] ?? '');
            $password = $_POST['admin_password'] ?? '';
            $confirm = $_POST['admin_password_confirm'] ?? '';

            if (empty($email) || empty($username) || empty($password)) {
                $errors[] = 'All fields are required.';
                break;
            }
            if ($password !== $confirm) {
                $errors[] = 'Passwords do not match.';
                break;
            }
            if (strlen($password) < 8) {
                $errors[] = 'Password must be at least 8 characters.';
                break;
            }

            $_SESSION['install'] = array_merge($_SESSION['install'] ?? [], [
                'admin_email' => $email,
                'admin_username' => $username,
                'admin_password' => $password,
            ]);

            // Fall through to execute immediately — no separate step 5 POST
            $data = $_SESSION['install'];

            // Build .env content
            $secret = bin2hex(random_bytes(32));
            $env = "# Cainty CMS Configuration\n\n";
            $env .= "APP_NAME=\"" . ($data['site_name'] ?? 'Cainty') . "\"\n";
            $env .= "APP_URL=\"" . ($data['site_url'] ?? 'https://localhost') . "\"\n";
            $env .= "APP_ENV=production\n";
            $env .= "APP_DEBUG=false\n";
            $env .= "APP_SECRET={$secret}\n\n";

            if (($data['db_driver'] ?? 'sqlite') === 'sqlite') {
                $env .= "DB_DRIVER=sqlite\n";
                $env .= "DB_PATH=storage/cainty.db\n\n";
            } else {
                $env .= "DB_DRIVER=mysql\n";
                $env .= "DB_HOST=" . ($data['db_host'] ?? 'localhost') . "\n";
                $env .= "DB_PORT=" . ($data['db_port'] ?? '3306') . "\n";
                $env .= "DB_NAME=" . ($data['db_name'] ?? '') . "\n";
                $env .= "DB_USER=" . ($data['db_user'] ?? '') . "\n";
                $env .= "DB_PASS=" . ($data['db_pass'] ?? '') . "\n\n";
            }

            $env .= "THEME=default\n";
            $env .= "AUTH_METHOD=local\n";
            $env .= "OOMPH_ENABLED=false\n\n";
            $env .= "UPLOAD_MAX_SIZE=10485760\n";
            $env .= "UPLOAD_DIR=storage/uploads\n";

            try {
                // Write .env
                file_put_contents(CAINTY_ROOT . '/.env', $env);

                // Bootstrap to run migrations
                require_once CAINTY_ROOT . '/core/autoload.php';
                require_once CAINTY_ROOT . '/core/helpers/functions.php';
                require_once CAINTY_ROOT . '/core/helpers/csrf.php';

                // Parse the .env we just wrote
                $GLOBALS['cainty_config'] = [];
                foreach (explode("\n", $env) as $line) {
                    $line = trim($line);
                    if ($line === '' || $line[0] === '#') continue;
                    if (strpos($line, '=') === false) continue;
                    [$k, $v] = explode('=', $line, 2);
                    $v = trim(trim($v), '"');
                    $GLOBALS['cainty_config'][trim($k)] = $v;
                }

                // Connect and run migrations
                \Cainty\Database\Database::connect();
                $db = \Cainty\Database\Database::getInstance();
                $migration = new \Cainty\Database\Migration($db);
                $migration->run();

                // Seed data in a transaction so failures leave a clean DB
                $db->beginTransaction();

                // Create default site
                $siteUrl = $data['site_url'] ?? 'https://localhost';
                $domain = parse_url($siteUrl, PHP_URL_HOST) ?: 'localhost';

                \Cainty\Database\Database::insert('sites', [
                    'site_name' => $data['site_name'] ?? 'Cainty',
                    'site_slug' => 'default',
                    'site_domain' => $domain,
                    'site_tagline' => $data['site_tagline'] ?? '',
                    'site_locale' => 'en',
                    'is_active' => 1,
                ]);

                // Create admin user
                \Cainty\Database\Database::insert('users', [
                    'site_id' => null,
                    'email' => $data['admin_email'],
                    'username' => $data['admin_username'],
                    'display_name' => $data['admin_username'],
                    'password_hash' => password_hash($data['admin_password'], PASSWORD_BCRYPT),
                    'role' => 'admin',
                    'status' => 1,
                ]);

                // Create default categories
                \Cainty\Database\Database::insert('categories', [
                    'site_id' => 1,
                    'cat_name' => 'General',
                    'cat_slug' => 'general',
                    'cat_desc' => 'General posts',
                ]);

                // Create plugins table for the plugin system
                $db->exec("
                    CREATE TABLE IF NOT EXISTS plugins (
                        plugin_id INTEGER PRIMARY KEY AUTOINCREMENT,
                        slug VARCHAR(200) NOT NULL UNIQUE,
                        name VARCHAR(200) NOT NULL,
                        version VARCHAR(50) DEFAULT '1.0.0',
                        is_active INTEGER DEFAULT 0,
                        settings TEXT DEFAULT NULL,
                        installed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                        activated_at DATETIME DEFAULT NULL
                    )
                ");

                $db->commit();

                // Create installed lock
                file_put_contents(CAINTY_ROOT . '/storage/installed.lock', date('Y-m-d H:i:s'));

                $success = true;
                unset($_SESSION['install']);

            } catch (Exception $e) {
                // Roll back seeded data so retry starts clean
                if (isset($db) && $db->inTransaction()) {
                    $db->rollBack();
                }
                // Also remove .env and DB file so next attempt is fully fresh
                @unlink(CAINTY_ROOT . '/.env');
                @unlink(CAINTY_ROOT . '/storage/cainty.db');
                $errors[] = 'Installation failed: ' . $e->getMessage();
                $step = 4;
            }
            break;
    }
}

// System checks for step 1
$checks = [];
if ($step === 1) {
    $checks['php'] = ['label' => 'PHP 8.1+', 'ok' => version_compare(PHP_VERSION, '8.1.0', '>=')];
    $checks['pdo'] = ['label' => 'PDO Extension', 'ok' => extension_loaded('pdo')];
    $checks['pdo_sqlite'] = ['label' => 'PDO SQLite', 'ok' => extension_loaded('pdo_sqlite')];
    $checks['json'] = ['label' => 'JSON Extension', 'ok' => extension_loaded('json')];
    $checks['curl'] = ['label' => 'cURL Extension', 'ok' => extension_loaded('curl')];
    $checks['mbstring'] = ['label' => 'Mbstring Extension', 'ok' => extension_loaded('mbstring')];
    $checks['openssl'] = ['label' => 'OpenSSL Extension', 'ok' => extension_loaded('openssl')];
    $checks['fileinfo'] = ['label' => 'Fileinfo Extension', 'ok' => extension_loaded('fileinfo')];
    $checks['storage'] = ['label' => 'Storage Writable', 'ok' => is_writable(CAINTY_ROOT . '/storage')];
    $allOk = !in_array(false, array_column($checks, 'ok'));
}

$installData = $_SESSION['install'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install Cainty</title>
    <style>
        :root { --bg: #000; --surface: #111; --border: #2a2a2a; --text: #ccc; --muted: #888; --heading: #fff; --accent: #ffe454; --danger: #ff4444; --success: #44cc66; --radius: 6px; }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: var(--bg); color: var(--text); line-height: 1.6; font-size: 14px; display: flex; justify-content: center; padding: 40px 20px; }
        .installer { width: 100%; max-width: 560px; }
        .brand { text-align: center; font-size: 2rem; font-weight: 700; color: var(--accent); margin-bottom: 8px; }
        .subtitle { text-align: center; color: var(--muted); margin-bottom: 32px; }
        .steps { display: flex; justify-content: center; gap: 8px; margin-bottom: 32px; }
        .step-dot { width: 10px; height: 10px; border-radius: 50%; background: var(--border); }
        .step-dot.active { background: var(--accent); }
        .step-dot.done { background: var(--success); }
        .card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 24px; margin-bottom: 20px; }
        h2 { color: var(--heading); margin-bottom: 16px; font-size: 1.2rem; }
        .form-group { margin-bottom: 14px; }
        label { display: block; margin-bottom: 4px; font-size: 0.85rem; color: var(--muted); }
        input, select, textarea { width: 100%; padding: 8px 12px; background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius); color: var(--text); font-size: 0.9rem; font-family: inherit; }
        input:focus, select:focus { outline: none; border-color: var(--accent); }
        .btn { display: inline-block; padding: 10px 24px; border-radius: var(--radius); font-size: 0.9rem; font-weight: 600; cursor: pointer; border: none; }
        .btn-accent { background: var(--accent); color: #000; }
        .btn:hover { opacity: 0.85; }
        .btn-full { width: 100%; }
        .check { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid var(--border); }
        .check-ok { color: var(--success); }
        .check-fail { color: var(--danger); }
        .error { background: rgba(255,68,68,0.1); border: 1px solid rgba(255,68,68,0.3); color: var(--danger); padding: 12px; border-radius: var(--radius); margin-bottom: 16px; }
        .success-box { text-align: center; padding: 40px 20px; }
        .success-box h2 { color: var(--success); margin-bottom: 12px; }
        .db-fields { display: none; }
        .db-fields.active { display: block; }
        small { color: var(--muted); font-size: 0.8rem; }
        a { color: var(--accent); }
    </style>
</head>
<body>
<div class="installer">
    <div class="brand">Cainty</div>
    <div class="subtitle">AI-First CMS Installation</div>

    <div class="steps">
        <?php for ($i = 1; $i <= 4; $i++): ?>
            <div class="step-dot <?= $i < $step ? 'done' : ($i === $step ? 'active' : '') ?>"></div>
        <?php endfor; ?>
    </div>

    <?php foreach ($errors as $err): ?>
        <div class="error"><?= htmlspecialchars($err) ?></div>
    <?php endforeach; ?>

    <?php if ($success): ?>
        <div class="card success-box">
            <h2>Installation Complete!</h2>
            <p>Cainty has been installed successfully.</p>
            <br>
            <a href="/admin" class="btn btn-accent">Go to Admin Panel</a>
        </div>

    <?php elseif ($step === 1): ?>
        <div class="card">
            <h2>System Check</h2>
            <?php foreach ($checks as $check): ?>
                <div class="check">
                    <span><?= $check['label'] ?></span>
                    <span class="<?= $check['ok'] ? 'check-ok' : 'check-fail' ?>"><?= $check['ok'] ? '&#10003;' : '&#10007;' ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if ($allOk): ?>
            <a href="?step=2" class="btn btn-accent btn-full" style="text-align:center;">Continue</a>
        <?php else: ?>
            <p style="color:var(--danger);text-align:center;">Please fix the issues above before continuing.</p>
        <?php endif; ?>

    <?php elseif ($step === 2): ?>
        <form method="post" class="card">
            <input type="hidden" name="step" value="2">
            <h2>Database Configuration</h2>
            <div class="form-group">
                <label>Database Type</label>
                <select name="db_driver" id="db-driver" onchange="toggleDbFields()">
                    <option value="sqlite" <?= ($installData['db_driver'] ?? '') === 'sqlite' ? 'selected' : '' ?>>SQLite (Recommended)</option>
                    <option value="mysql" <?= ($installData['db_driver'] ?? '') === 'mysql' ? 'selected' : '' ?>>MariaDB / MySQL</option>
                </select>
                <small>SQLite requires no configuration. MariaDB is better for high-traffic sites.</small>
            </div>
            <div class="db-fields" id="mysql-fields">
                <div class="form-group"><label>Host</label><input type="text" name="db_host" value="<?= htmlspecialchars($installData['db_host'] ?? 'localhost') ?>"></div>
                <div class="form-group"><label>Port</label><input type="text" name="db_port" value="<?= htmlspecialchars($installData['db_port'] ?? '3306') ?>"></div>
                <div class="form-group"><label>Database Name</label><input type="text" name="db_name" value="<?= htmlspecialchars($installData['db_name'] ?? '') ?>"></div>
                <div class="form-group"><label>Username</label><input type="text" name="db_user" value="<?= htmlspecialchars($installData['db_user'] ?? '') ?>"></div>
                <div class="form-group"><label>Password</label><input type="password" name="db_pass" value="<?= htmlspecialchars($installData['db_pass'] ?? '') ?>"></div>
            </div>
            <button type="submit" class="btn btn-accent btn-full">Continue</button>
        </form>
        <script>
            function toggleDbFields() {
                var v = document.getElementById('db-driver').value;
                document.getElementById('mysql-fields').className = v === 'mysql' ? 'db-fields active' : 'db-fields';
            }
            toggleDbFields();
        </script>

    <?php elseif ($step === 3): ?>
        <form method="post" class="card">
            <input type="hidden" name="step" value="3">
            <h2>Site Information</h2>
            <div class="form-group"><label>Site Name</label><input type="text" name="site_name" value="<?= htmlspecialchars($installData['site_name'] ?? '') ?>" required></div>
            <div class="form-group"><label>Site URL</label><input type="url" name="site_url" value="<?= htmlspecialchars($installData['site_url'] ?? ('https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'))) ?>" required></div>
            <div class="form-group"><label>Tagline</label><input type="text" name="site_tagline" value="<?= htmlspecialchars($installData['site_tagline'] ?? '') ?>" placeholder="Optional"></div>
            <button type="submit" class="btn btn-accent btn-full">Continue</button>
        </form>

    <?php elseif ($step === 4): ?>
        <form method="post" class="card">
            <input type="hidden" name="step" value="4">
            <h2>Admin Account</h2>
            <div class="form-group"><label>Email</label><input type="email" name="admin_email" value="<?= htmlspecialchars($installData['admin_email'] ?? '') ?>" required></div>
            <div class="form-group"><label>Username</label><input type="text" name="admin_username" value="<?= htmlspecialchars($installData['admin_username'] ?? '') ?>" required></div>
            <div class="form-group"><label>Password</label><input type="password" name="admin_password" required minlength="8"></div>
            <div class="form-group"><label>Confirm Password</label><input type="password" name="admin_password_confirm" required></div>
            <button type="submit" class="btn btn-accent btn-full">Install Cainty</button>
        </form>

    <?php endif; ?>
</div>
</body>
</html>

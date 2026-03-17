<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Cainty</title>
    <link rel="stylesheet" href="<?= cainty_url('admin/assets/css/admin.css') ?>?v=<?= time() ?>">
</head>
<body class="login-body">
    <div class="login-container">
        <div class="login-brand">Cainty</div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= cainty_url('login') ?>" class="login-form">
            <?= cainty_csrf_field() ?>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" class="form-input" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" class="form-input" required>
            </div>
            <button type="submit" class="btn btn-accent btn-full">Sign In</button>
        </form>

        <?php if (\Cainty\Auth\AlexiuzSSO::isEnabled()): ?>
            <div class="login-divider"><span>or</span></div>
            <a href="<?= \Cainty\Auth\AlexiuzSSO::getLoginUrl() ?>" class="btn btn-secondary btn-full">
                Sign in with Alexiuz
            </a>
        <?php endif; ?>
    </div>
</body>
</html>

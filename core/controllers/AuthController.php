<?php

namespace Cainty\Controllers;

use Cainty\Auth\Auth;
use Cainty\Auth\AlexiuzSSO;
use Cainty\Router\Response;

class AuthController
{
    public function loginForm(array $params): void
    {
        if (Auth::check()) {
            Response::redirect(cainty_admin_url());
            return;
        }

        $error = $_SESSION['login_error'] ?? null;
        unset($_SESSION['login_error']);

        include CAINTY_ROOT . '/admin/login.php';
    }

    public function login(array $params): void
    {
        if (!cainty_verify_csrf()) {
            $_SESSION['login_error'] = 'Invalid request. Please try again.';
            Response::redirect(cainty_url('login'));
            return;
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $_SESSION['login_error'] = 'Please enter your email and password.';
            Response::redirect(cainty_url('login'));
            return;
        }

        $user = Auth::attempt($email, $password);
        if (!$user) {
            $_SESSION['login_error'] = 'Invalid email or password.';
            Response::redirect(cainty_url('login'));
            return;
        }

        Auth::loginUser($user['user_id']);
        Response::redirect(cainty_admin_url());
    }

    public function logout(array $params): void
    {
        Auth::logout();
        Response::redirect(cainty_url('login'));
    }

    public function ssoCallback(array $params): void
    {
        if (!AlexiuzSSO::isEnabled()) {
            Response::redirect(cainty_url('login'));
            return;
        }

        $ssoData = AlexiuzSSO::consumeCallback();
        if (!$ssoData) {
            $_SESSION['login_error'] = 'SSO authentication failed.';
            Response::redirect(cainty_url('login'));
            return;
        }

        $userId = AlexiuzSSO::findOrCreateUser($ssoData);
        Auth::loginUser($userId);
        Response::redirect(cainty_admin_url());
    }
}

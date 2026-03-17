<?php
/**
 * Cainty CMS — CSRF Protection
 */

/**
 * Generate or retrieve the CSRF token for the current session
 */
function cainty_csrf_token(): string
{
    if (empty($_SESSION['_cainty_csrf'])) {
        $_SESSION['_cainty_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_cainty_csrf'];
}

/**
 * Output a hidden input field with the CSRF token
 */
function cainty_csrf_field(): string
{
    $token = cainty_csrf_token();
    return '<input type="hidden" name="_csrf_token" value="' . cainty_escape($token) . '">';
}

/**
 * Verify a CSRF token against the session
 */
function cainty_verify_csrf(?string $token = null): bool
{
    if ($token === null) {
        $token = $_POST['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    }
    if (empty($token) || empty($_SESSION['_cainty_csrf'])) {
        return false;
    }
    return hash_equals($_SESSION['_cainty_csrf'], $token);
}

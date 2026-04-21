<?php

$secureCookie = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => $secureCookie,
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();
}

const SESSION_IDLE_LIMIT = 1800;

if (isset($_SESSION['last_activity']) && (time() - (int) $_SESSION['last_activity']) > SESSION_IDLE_LIMIT) {
    session_unset();
    session_destroy();
    session_start();
}
$_SESSION['last_activity'] = time();

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id'], $_SESSION['role']);
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: /DreamEvents/auth/login.php');
        exit;
    }
}

function requireRole(string $role): void
{
    requireLogin();
    if (($_SESSION['role'] ?? '') !== $role) {
        header('Location: /DreamEvents/index.php');
        exit;
    }
}

function currentUserName(): string
{
    return $_SESSION['username'] ?? 'Guest';
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') . '">';
}

function verifyCsrfOrAbort(): void
{
    $token = $_POST['csrf_token'] ?? '';
    $sessionToken = $_SESSION['csrf_token'] ?? '';

    if (!is_string($token) || $token === '' || $sessionToken === '' || !hash_equals($sessionToken, $token)) {
        http_response_code(419);
        exit('Invalid CSRF token. Please refresh and try again.');
    }
}

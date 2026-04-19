<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
    if ($_SESSION['role'] !== $role) {
        header('Location: /DreamEvents/index.php');
        exit;
    }
}

function currentUserName(): string
{
    return $_SESSION['username'] ?? 'Guest';
}

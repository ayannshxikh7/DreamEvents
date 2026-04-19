<?php
require_once __DIR__ . '/includes/auth.php';

if (!isLoggedIn()) {
    header('Location: /DreamEvents/auth/login.php');
    exit;
}

if ($_SESSION['role'] === 'admin') {
    header('Location: /DreamEvents/admin/dashboard.php');
    exit;
}

header('Location: /DreamEvents/user/dashboard.php');
exit;

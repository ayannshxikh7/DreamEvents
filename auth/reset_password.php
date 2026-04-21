<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

if (isLoggedIn()) {
    header('Location: /DreamEvents/index.php');
    exit;
}

$userId = (int) ($_SESSION['password_reset_user_id'] ?? 0);
$verified = !empty($_SESSION['password_reset_verified']);

if ($userId <= 0 || !$verified) {
    header('Location: /DreamEvents/auth/forgot_password.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrAbort();

    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $pdo->prepare('UPDATE users SET password = ? WHERE user_id = ?')->execute([$hash, $userId]);
        unset($_SESSION['password_reset_user_id'], $_SESSION['password_reset_verified'], $_SESSION['reset_email']);
        $success = 'Password reset successful. You can now login.';
    }
}

$pageTitle = 'Reset Password';
include __DIR__ . '/../includes/header.php';
?>
<div class="auth-wrapper">
    <div class="card auth-card shadow-lg">
        <div class="card-body p-4 p-md-5">
            <h1 class="h3 text-center mb-2">Reset Password</h1>
            <p class="text-secondary text-center mb-4">Set your new account password.</p>
            <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
            <?php if (!$success): ?>
            <form method="post">
                <?= csrfField() ?>
                <div class="mb-3">
                    <label class="form-label">New Password</label>
                    <input type="password" class="form-control" name="password" required>
                </div>
                <div class="mb-4">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" class="form-control" name="confirm_password" required>
                </div>
                <button class="btn btn-gradient w-100" type="submit">Update Password</button>
            </form>
            <?php else: ?>
                <p class="text-center mb-0"><a href="/DreamEvents/auth/login.php" class="link-light">Go to Login</a></p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>

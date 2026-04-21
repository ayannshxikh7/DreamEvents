<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

if (isLoggedIn()) {
    header('Location: /DreamEvents/index.php');
    exit;
}

$error = '';
$success = '';
$username = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrAbort();

    $username = trim($_POST['username'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($username === '' || $email === '' || $password === '' || $confirmPassword === '') {
        $error = 'All fields are required.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
        $error = 'Username must be 3-50 chars (letters, numbers, underscore).';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } else {
        $stmt = $pdo->prepare('SELECT user_id FROM users WHERE username = ? OR email = ? LIMIT 1');
        $stmt->execute([$username, $email]);

        if ($stmt->fetch()) {
            $error = 'Username or email already exists.';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $insert = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')");
            $insert->execute([$username, $email, $hashedPassword]);
            $success = 'Account created successfully. Please login.';
            $username = '';
            $email = '';
        }
    }
}

$pageTitle = 'Signup';
include __DIR__ . '/../includes/header.php';
?>
<div class="auth-wrapper">
    <div class="card auth-card shadow-lg">
        <div class="card-body p-4 p-md-5">
            <h1 class="h3 text-center mb-2">Create Account</h1>
            <p class="text-secondary text-center mb-4">Join DreamEvents and start booking today</p>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <form method="post">
                <?= csrfField() ?>
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-control" name="username" value="<?= htmlspecialchars($username) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($email) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" class="form-control" name="password" required>
                </div>
                <div class="mb-4">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" class="form-control" name="confirm_password" required>
                </div>
                <button class="btn btn-primary w-100" type="submit">Sign Up</button>
            </form>
            <p class="text-center mt-4 mb-0 text-secondary">Already registered? <a href="/DreamEvents/auth/login.php" class="link-light">Login</a></p>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>

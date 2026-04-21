<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

if (isLoggedIn()) {
    header('Location: /DreamEvents/index.php');
    exit;
}

$error = '';
$identity = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrAbort();

    $identity = trim($_POST['identity'] ?? '');
    $password = $_POST['password'] ?? '';
    $identityKey = strtolower($identity . '|' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

    $rateStmt = $pdo->prepare('SELECT fail_count, locked_until FROM login_attempts WHERE identity_key = ? LIMIT 1');
    $rateStmt->execute([$identityKey]);
    $rate = $rateStmt->fetch();

    if ($rate && !empty($rate['locked_until']) && strtotime($rate['locked_until']) > time()) {
        $remaining = max(1, (int) ceil((strtotime($rate['locked_until']) - time()) / 60));
        $error = 'Too many failed attempts. Try again in ' . $remaining . ' minute(s).';
    } elseif ($identity === '' || $password === '') {
        $error = 'Please fill in both login and password.';
    } else {
        $stmt = $pdo->prepare('SELECT user_id, username, email, password, role FROM users WHERE username = ? OR email = ? LIMIT 1');
        $stmt->execute([$identity, $identity]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $resetRateStmt = $pdo->prepare('DELETE FROM login_attempts WHERE identity_key = ?');
            $resetRateStmt->execute([$identityKey]);

            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            header('Location: /DreamEvents/index.php');
            exit;
        }

        $upsertRate = $pdo->prepare("INSERT INTO login_attempts (identity_key, fail_count, last_attempt, locked_until)
            VALUES (?, 1, NOW(), NULL)
            ON DUPLICATE KEY UPDATE
                fail_count = fail_count + 1,
                last_attempt = NOW(),
                locked_until = CASE WHEN fail_count + 1 >= 5 THEN DATE_ADD(NOW(), INTERVAL 15 MINUTE) ELSE NULL END");
        $upsertRate->execute([$identityKey]);

        $error = 'Invalid credentials.';
    }
}

$pageTitle = 'Login';
include __DIR__ . '/../includes/header.php';
?>
<div class="auth-wrapper">
    <div class="card auth-card shadow-lg">
        <div class="card-body p-4 p-md-5">
            <h1 class="h3 text-center mb-2">Welcome Back</h1>
            <p class="text-secondary text-center mb-4">Login to continue to DreamEvents</p>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="post">
                <?= csrfField() ?>
                <div class="mb-3">
                    <label class="form-label">Username or Email</label>
                    <input type="text" class="form-control" name="identity" value="<?= htmlspecialchars($identity) ?>" required>
                </div>
                <div class="mb-4">
                    <label class="form-label">Password</label>
                    <input type="password" class="form-control" name="password" required>
                </div>
                <button class="btn btn-primary w-100" type="submit">Login</button>
            </form>
            <p class="text-center mt-3 mb-0"><a href="/DreamEvents/auth/forgot_password.php" class="link-light">Forgot Password?</a></p>
            <p class="text-center mt-3 mb-0 text-secondary">New user? <a href="/DreamEvents/auth/signup.php" class="link-light">Create account</a></p>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>

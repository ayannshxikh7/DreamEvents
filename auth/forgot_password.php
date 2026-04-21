<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mailer.php';

if (isLoggedIn()) {
    header('Location: /DreamEvents/index.php');
    exit;
}

$message = '';
$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrAbort();

    $email = strtolower(trim($_POST['email'] ?? ''));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address.';
    } else {
        $stmt = $pdo->prepare('SELECT user_id, username, email FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $otp = (string) random_int(100000, 999999);
            $otpHash = password_hash($otp, PASSWORD_DEFAULT);

            $pdo->prepare('UPDATE password_reset_otps SET is_used = 1 WHERE user_id = ? AND is_used = 0')->execute([(int) $user['user_id']]);
            $insert = $pdo->prepare('INSERT INTO password_reset_otps (user_id, otp_hash, expires_at, is_used) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE), 0)');
            $insert->execute([(int) $user['user_id'], $otpHash]);

            $html = dreamEventsBrandTemplate('Password Reset OTP',
                '<p>Hello ' . htmlspecialchars($user['username']) . ',</p>'
                . '<p>Your DreamEvents OTP is:</p>'
                . '<p style="font-size:28px;font-weight:700;letter-spacing:3px;color:#fff;">' . $otp . '</p>'
                . '<p>This OTP expires in 10 minutes and can only be used once.</p>');
            sendSystemEmail($user['email'], $user['username'], 'DreamEvents Password Reset OTP', $html);

            $_SESSION['reset_email'] = $user['email'];
            header('Location: /DreamEvents/auth/verify_otp.php');
            exit;
        }

        $message = 'If this email is registered, an OTP has been sent.';
    }
}

$pageTitle = 'Forgot Password';
include __DIR__ . '/../includes/header.php';
?>
<div class="auth-wrapper">
    <div class="card auth-card shadow-lg">
        <div class="card-body p-4 p-md-5">
            <h1 class="h3 text-center mb-2">Forgot Password</h1>
            <p class="text-secondary text-center mb-4">Enter your registered email to receive OTP.</p>
            <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
            <form method="post">
                <?= csrfField() ?>
                <div class="mb-4">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($email) ?>" required>
                </div>
                <button class="btn btn-gradient w-100" type="submit">Send OTP</button>
            </form>
            <p class="text-center mt-4 mb-0"><a href="/DreamEvents/auth/login.php" class="link-light">Back to Login</a></p>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>

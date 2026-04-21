<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

if (isLoggedIn()) {
    header('Location: /DreamEvents/index.php');
    exit;
}

$email = $_SESSION['reset_email'] ?? '';
if (!$email) {
    header('Location: /DreamEvents/auth/forgot_password.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrAbort();

    $otp = trim($_POST['otp'] ?? '');
    if (!preg_match('/^[0-9]{6}$/', $otp)) {
        $error = 'Please enter a valid 6-digit OTP.';
    } else {
        $stmt = $pdo->prepare('SELECT o.otp_id, o.otp_hash, o.expires_at, u.user_id
            FROM password_reset_otps o
            INNER JOIN users u ON u.user_id = o.user_id
            WHERE u.email = ? AND o.is_used = 0
            ORDER BY o.otp_id DESC LIMIT 1');
        $stmt->execute([$email]);
        $otpRow = $stmt->fetch();

        if (!$otpRow || strtotime($otpRow['expires_at']) < time() || !password_verify($otp, $otpRow['otp_hash'])) {
            $error = 'Invalid or expired OTP.';
        } else {
            $pdo->prepare('UPDATE password_reset_otps SET is_used = 1 WHERE otp_id = ?')->execute([(int) $otpRow['otp_id']]);
            $_SESSION['password_reset_user_id'] = (int) $otpRow['user_id'];
            $_SESSION['password_reset_verified'] = true;
            header('Location: /DreamEvents/auth/reset_password.php');
            exit;
        }
    }
}

$pageTitle = 'Verify OTP';
include __DIR__ . '/../includes/header.php';
?>
<div class="auth-wrapper">
    <div class="card auth-card shadow-lg">
        <div class="card-body p-4 p-md-5">
            <h1 class="h3 text-center mb-2">Verify OTP</h1>
            <p class="text-secondary text-center mb-4">Enter the 6-digit OTP sent to <?= htmlspecialchars($email) ?>.</p>
            <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <form method="post">
                <?= csrfField() ?>
                <div class="mb-4">
                    <label class="form-label">OTP</label>
                    <input type="text" class="form-control" name="otp" maxlength="6" required>
                </div>
                <button class="btn btn-gradient w-100" type="submit">Verify OTP</button>
            </form>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>

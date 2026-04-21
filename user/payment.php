<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('user');

$pending = $_SESSION['pending_booking'] ?? null;
if (!$pending || !isset($pending['event_id'], $pending['amount'])) {
    header('Location: /DreamEvents/user/dashboard.php');
    exit;
}

$eventStmt = $pdo->prepare('SELECT event_id, event_name, event_date, event_time, venue, price FROM events WHERE event_id = ? LIMIT 1');
$eventStmt->execute([(int) $pending['event_id']]);
$event = $eventStmt->fetch();
if (!$event) {
    unset($_SESSION['pending_booking']);
    header('Location: /DreamEvents/user/dashboard.php');
    exit;
}

if ((float) $event['price'] <= 0) {
    header('Location: /DreamEvents/user/booking_form.php?event_id=' . (int) $event['event_id']);
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrAbort();
    $cardNumber = preg_replace('/\D+/', '', $_POST['card_number'] ?? '');
    $expiry = trim($_POST['expiry'] ?? '');
    $cvv = preg_replace('/\D+/', '', $_POST['cvv'] ?? '');

    if ($cardNumber === '' || $expiry === '' || $cvv === '' || strlen($cardNumber) < 12 || strlen($cardNumber) > 19 || !preg_match('/^(0[1-9]|1[0-2])\/[0-9]{2}$/', $expiry) || strlen($cvv) < 3 || strlen($cvv) > 4) {
        $error = 'Please enter valid payment details.';
    } else {
        $_POST['payment_status'] = 'paid';
        $_POST['amount_paid'] = (float) $event['price'];
        include __DIR__ . '/book_event.php';
        exit;
    }
}

$pageTitle = 'Payment';
include __DIR__ . '/../includes/header.php';
?>
<div class="app-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="content-area page-enter">
        <h2 class="mb-1">Payment</h2>
        <p class="text-secondary mb-4">Complete payment to confirm your booking.</p>

        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card panel-card p-4 h-100">
                    <h5><?= htmlspecialchars($event['event_name']) ?></h5>
                    <p class="mb-1 text-secondary"><?= date('D, d M Y', strtotime($event['event_date'])) ?> • <?= date('h:i A', strtotime($event['event_time'])) ?></p>
                    <p class="text-secondary">📍 <?= htmlspecialchars($event['venue']) ?></p>
                    <div class="display-6 mt-2">₹<?= number_format((float) $event['price'], 2) ?></div>
                    <small class="text-secondary">Amount to pay</small>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card panel-card p-4">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <form method="post" class="row g-3 js-processing-form">
                        <?= csrfField() ?>
                        <div class="col-12">
                            <label class="form-label">Card Number</label>
                            <input type="text" class="form-control" name="card_number" maxlength="19" placeholder="1234 5678 9012 3456" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Expiry (MM/YY)</label>
                            <input type="text" class="form-control" name="expiry" maxlength="5" placeholder="MM/YY" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">CVV</label>
                            <input type="password" class="form-control" name="cvv" maxlength="4" required>
                        </div>
                        <div class="col-12 d-flex gap-2">
                            <a href="/DreamEvents/user/booking_form.php?event_id=<?= (int) $event['event_id'] ?>" class="btn btn-outline-light">Cancel</a>
                            <button class="btn btn-gradient" type="submit" data-processing-text="Processing...">Pay &amp; Book</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>

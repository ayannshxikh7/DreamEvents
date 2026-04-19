<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('user');

$registrationId = (int) ($_GET['registration_id'] ?? 0);
if ($registrationId <= 0) {
    header('Location: /DreamEvents/user/my_bookings.php?refund_error=1');
    exit;
}

$stmt = $pdo->prepare('SELECT r.*, e.event_date
    FROM registrations r
    INNER JOIN events e ON e.event_id = r.event_id
    WHERE r.registration_id = ? AND r.user_id = ?
    LIMIT 1');
$stmt->execute([$registrationId, $_SESSION['user_id']]);
$registration = $stmt->fetch();

if (!$registration) {
    header('Location: /DreamEvents/user/my_bookings.php?refund_error=1');
    exit;
}

$eventDate = strtotime($registration['event_date']);
$isFuture = $eventDate >= strtotime(date('Y-m-d'));
$isConfirmed = in_array($registration['payment_status'], ['paid', 'free'], true);

if (!$isFuture || !$isConfirmed || $registration['refund_status'] !== 'none') {
    header('Location: /DreamEvents/user/my_bookings.php?refund_error=1');
    exit;
}

if ((float) $registration['amount_paid'] <= 0) {
    header('Location: /DreamEvents/user/my_bookings.php?refund_error=1');
    exit;
}

$commission = round((float) $registration['amount_paid'] * 0.10, 2);
$refundAmount = round((float) $registration['amount_paid'] - $commission, 2);

try {
    $pdo->beginTransaction();

    $checkStmt = $pdo->prepare('SELECT refund_id FROM refund_requests WHERE registration_id = ? LIMIT 1');
    $checkStmt->execute([$registrationId]);
    if ($checkStmt->fetch()) {
        throw new RuntimeException('Duplicate refund request.');
    }

    $insertRefund = $pdo->prepare('INSERT INTO refund_requests (registration_id, user_id, event_id, original_amount, refund_amount, commission_deducted, status) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $insertRefund->execute([
        $registrationId,
        $_SESSION['user_id'],
        $registration['event_id'],
        $registration['amount_paid'],
        $refundAmount,
        $commission,
        'requested',
    ]);

    $updateRegistration = $pdo->prepare("UPDATE registrations SET refund_status='requested', refund_amount=?, commission_deducted=? WHERE registration_id=?");
    $updateRegistration->execute([$refundAmount, $commission, $registrationId]);

    $pdo->commit();
    header('Location: /DreamEvents/user/my_bookings.php?refund_requested=1');
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    header('Location: /DreamEvents/user/my_bookings.php?refund_error=1');
    exit;
}

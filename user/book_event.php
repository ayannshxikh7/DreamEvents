<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('user');

$pending = $_SESSION['pending_booking'] ?? null;
if (!$pending || !isset($pending['event_id'], $pending['full_name'], $pending['gender'], $pending['age'], $pending['amount'])) {
    header('Location: /DreamEvents/user/dashboard.php');
    exit;
}

$eventId = (int) $pending['event_id'];
$fullName = trim($pending['full_name']);
$gender = $pending['gender'];
$age = (int) $pending['age'];
$amount = (float) $pending['amount'];
$paymentStatus = $_POST['payment_status'] ?? ($amount > 0 ? '' : 'free');
$amountPaid = isset($_POST['amount_paid']) ? (float) $_POST['amount_paid'] : ($amount > 0 ? 0 : 0.0);

$allowedGenders = ['Male', 'Female', 'Other'];
$allowedPayments = ['free', 'paid'];

if ($fullName === '' || !in_array($gender, $allowedGenders, true) || $age < 10 || $age > 100 || !in_array($paymentStatus, $allowedPayments, true)) {
    header('Location: /DreamEvents/user/booking_form.php?event_id=' . $eventId);
    exit;
}

if ($paymentStatus === 'paid' && $amountPaid <= 0) {
    header('Location: /DreamEvents/user/payment.php');
    exit;
}

$eventStmt = $pdo->prepare('SELECT e.event_id, e.price, e.capacity, COUNT(r.registration_id) AS total_bookings
    FROM events e
    LEFT JOIN registrations r ON r.event_id = e.event_id
    WHERE e.event_id = ?
    GROUP BY e.event_id
    LIMIT 1');
$eventStmt->execute([$eventId]);
$event = $eventStmt->fetch();
if (!$event) {
    unset($_SESSION['pending_booking']);
    header('Location: /DreamEvents/user/dashboard.php');
    exit;
}

if ((int) $event['total_bookings'] >= (int) $event['capacity']) {
    unset($_SESSION['pending_booking']);
    header('Location: /DreamEvents/user/dashboard.php?full=1');
    exit;
}

$insertStmt = $pdo->prepare('INSERT IGNORE INTO registrations (user_id, event_id, full_name, gender, age, payment_status, amount_paid) VALUES (?, ?, ?, ?, ?, ?, ?)');
$insertStmt->execute([
    $_SESSION['user_id'],
    $eventId,
    $fullName,
    $gender,
    $age,
    $paymentStatus,
    $paymentStatus === 'paid' ? $amountPaid : 0,
]);

unset($_SESSION['pending_booking']);
header('Location: /DreamEvents/user/my_bookings.php?booked=1');
exit;

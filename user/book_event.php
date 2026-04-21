<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mailer.php';

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
$amountPaid = isset($_POST['amount_paid']) ? (float) $_POST['amount_paid'] : 0.0;

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

$eventStmt = $pdo->prepare('SELECT e.event_id, e.event_name, e.event_date, e.event_time, e.venue, e.price, e.capacity, COUNT(r.registration_id) AS total_bookings
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

$bookingRef = 'DE-' . strtoupper(bin2hex(random_bytes(4)));

$insertStmt = $pdo->prepare('INSERT IGNORE INTO registrations (user_id, event_id, full_name, gender, age, booking_reference, payment_status, amount_paid) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
$insertStmt->execute([
    $_SESSION['user_id'],
    $eventId,
    $fullName,
    $gender,
    $age,
    $bookingRef,
    $paymentStatus,
    $paymentStatus === 'paid' ? $amountPaid : 0,
]);

if ($insertStmt->rowCount() > 0) {
    $registrationId = (int) $pdo->lastInsertId();
    $email = $_SESSION['email'] ?? '';
    if ($email) {
        $html = dreamEventsBrandTemplate('Booking Confirmation',
            '<p>Your booking is confirmed.</p>'
            . '<ul style="padding-left:18px;">'
            . '<li><strong>Event:</strong> ' . htmlspecialchars($event['event_name']) . '</li>'
            . '<li><strong>Date:</strong> ' . date('d M Y', strtotime($event['event_date'])) . '</li>'
            . '<li><strong>Venue:</strong> ' . htmlspecialchars($event['venue']) . '</li>'
            . '<li><strong>Booking ID:</strong> #' . $registrationId . '</li>'
            . '<li><strong>Amount Paid:</strong> ₹' . number_format($paymentStatus === 'paid' ? $amountPaid : 0, 2) . '</li>'
            . '</ul>'
            . '<p>We look forward to seeing you!</p>');
        sendSystemEmail($email, (string) ($_SESSION['username'] ?? 'User'), 'DreamEvents Booking Confirmation', $html);
    }
}

unset($_SESSION['pending_booking']);
header('Location: /DreamEvents/user/my_bookings.php?booked=1');
exit;

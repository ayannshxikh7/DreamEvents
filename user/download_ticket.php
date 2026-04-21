<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/pdf.php';

requireRole('user');

$registrationId = (int) ($_GET['registration_id'] ?? 0);
if ($registrationId <= 0) {
    header('Location: /DreamEvents/user/my_bookings.php');
    exit;
}

$stmt = $pdo->prepare('SELECT r.registration_id, r.registration_date, r.full_name, r.payment_status, r.amount_paid, r.booking_reference,
        e.event_name, e.event_date, e.event_time, e.venue
    FROM registrations r
    INNER JOIN events e ON e.event_id = r.event_id
    WHERE r.registration_id = ? AND r.user_id = ? LIMIT 1');
$stmt->execute([$registrationId, $_SESSION['user_id']]);
$row = $stmt->fetch();

if (!$row) {
    header('Location: /DreamEvents/user/my_bookings.php');
    exit;
}

$ref = $row['booking_reference'] ?: ('DE-' . str_pad((string) $row['registration_id'], 8, '0', STR_PAD_LEFT));
$lines = [
    'DreamEvents Ticket / Invoice',
    '----------------------------------------------',
    'Booking ID: #' . $row['registration_id'],
    'Booking Ref: ' . $ref,
    'Name: ' . $row['full_name'],
    'Event: ' . $row['event_name'],
    'Date: ' . date('d M Y', strtotime($row['event_date'])) . ' ' . date('h:i A', strtotime($row['event_time'])),
    'Venue: ' . $row['venue'],
    'Amount Paid: INR ' . number_format((float) $row['amount_paid'], 2),
    'Payment Status: ' . strtoupper($row['payment_status']),
    'Registered On: ' . date('d M Y, h:i A', strtotime($row['registration_date'])),
    'QR Ref: [' . $ref . '|DEMO-QR]',
    '----------------------------------------------',
    'This is a system generated ticket.',
];

$pdf = buildSimplePdf($lines);
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="DreamEvents-Ticket-' . (int) $row['registration_id'] . '.pdf"');
header('Content-Length: ' . strlen($pdf));
echo $pdf;
exit;

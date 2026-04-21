<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('user');

$stmt = $pdo->prepare('SELECT r.registration_id, r.registration_date, r.full_name, r.gender, r.age, r.payment_status, r.amount_paid, r.refund_status, r.refund_amount, r.commission_deducted,
        e.event_id, e.event_name, e.event_date, e.event_time, e.venue
    FROM registrations r
    INNER JOIN events e ON e.event_id = r.event_id
    WHERE r.user_id = ?
    ORDER BY e.event_date, e.event_time');
$stmt->execute([$_SESSION['user_id']]);
$bookings = $stmt->fetchAll();

$pageTitle = 'My Bookings';
include __DIR__ . '/../includes/header.php';
?>
<div class="app-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="content-area page-enter">
        <h2 class="mb-1">My Bookings</h2>
        <p class="text-secondary mb-4">Track your events, payments, and refunds in one place.</p>

        <?php if (isset($_GET['booked'])): ?><div class="alert alert-success">Booking confirmed successfully.</div><?php endif; ?>
        <?php if (isset($_GET['refund_requested'])): ?><div class="alert alert-success">Refund request submitted. Admin review is pending.</div><?php endif; ?>
        <?php if (isset($_GET['refund_error'])): ?><div class="alert alert-danger">Refund request could not be submitted for this booking.</div><?php endif; ?>

        <div class="card panel-card glass-card">
            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle mb-0">
                    <thead>
                    <tr>
                        <th>Event</th>
                        <th>Attendee</th>
                        <th>Payment</th>
                        <th>Refund</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (!$bookings): ?>
                        <tr><td colspan="5" class="text-center py-5"><div class="empty-state">No bookings yet. Explore events to get started.</div></td></tr>
                    <?php endif; ?>
                    <?php foreach ($bookings as $booking): ?>
                        <?php
                        $eventFuture = strtotime($booking['event_date']) >= strtotime(date('Y-m-d'));
                        $eligibleRefund = $booking['refund_status'] === 'none' && $eventFuture && (float) $booking['amount_paid'] > 0;
                        ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($booking['event_name']) ?></strong><br>
                                <small class="text-secondary"><?= date('d M Y, h:i A', strtotime($booking['event_date'] . ' ' . $booking['event_time'])) ?> • <?= htmlspecialchars($booking['venue']) ?></small>
                            </td>
                            <td>
                                <?= htmlspecialchars($booking['full_name']) ?><br>
                                <small class="text-secondary"><?= htmlspecialchars($booking['gender']) ?>, <?= (int) $booking['age'] ?> yrs</small>
                            </td>
                            <td>
                                <span class="badge <?= $booking['payment_status'] === 'paid' ? 'bg-warning text-dark' : 'bg-success' ?>"><?= strtoupper($booking['payment_status']) ?></span>
                                <div class="small mt-1">₹<?= number_format((float) $booking['amount_paid'], 2) ?></div>
                            </td>
                            <td>
                                <span class="badge <?= $booking['refund_status'] === 'approved' ? 'badge-approved' : ($booking['refund_status'] === 'rejected' ? 'badge-rejected' : ($booking['refund_status'] === 'requested' ? 'badge-pending' : 'bg-secondary')) ?>">
                                    Refund <?= ucfirst($booking['refund_status']) ?>
                                </span>
                                <?php if ($booking['refund_status'] === 'approved'): ?>
                                    <div class="small mt-1">Refunded: ₹<?= number_format((float) $booking['refund_amount'], 2) ?></div>
                                    <div class="small">Commission retained: ₹<?= number_format((float) $booking['commission_deducted'], 2) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-2">
                                <a href="/DreamEvents/user/download_ticket.php?registration_id=<?= (int) $booking['registration_id'] ?>" class="btn btn-sm btn-outline-info">Download Ticket</a>
                                <?php if ($eligibleRefund): ?>
                                    <form method="post" action="/DreamEvents/user/request_refund.php" onsubmit="return confirm('Request refund? 10% commission will be deducted.');">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="registration_id" value="<?= (int) $booking['registration_id'] ?>">
                                        <button class="btn btn-sm btn-primary" type="submit">Request Refund</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-secondary small">Not eligible</span>
                                <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>

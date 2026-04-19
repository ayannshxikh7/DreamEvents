<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

$stmt = $pdo->query('SELECT r.registration_date, r.full_name, r.gender, r.age, r.payment_status, r.amount_paid, r.refund_status, r.refund_amount, r.commission_deducted,
        u.username, e.event_name, e.event_date, e.event_time, e.venue
    FROM registrations r
    INNER JOIN users u ON u.user_id = r.user_id
    INNER JOIN events e ON e.event_id = r.event_id
    ORDER BY r.registration_date DESC');
$registrations = $stmt->fetchAll();

$pageTitle = 'All Registrations';
include __DIR__ . '/../includes/header.php';
?>
<div class="app-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="content-area page-enter">
        <h2 class="mb-1">All Registrations</h2>
        <p class="text-secondary mb-4">Secure in-panel view of bookings and payment/refund states.</p>

        <div class="card panel-card glass-card">
            <div class="table-responsive">
                <table class="table table-dark table-hover mb-0 align-middle">
                    <thead>
                    <tr>
                        <th>User</th>
                        <th>Attendee Details</th>
                        <th>Event</th>
                        <th>Payment</th>
                        <th>Refund</th>
                        <th>Booked On</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (!$registrations): ?>
                        <tr><td colspan="6" class="text-center py-5"><div class="empty-state">No registrations found.</div></td></tr>
                    <?php endif; ?>
                    <?php foreach ($registrations as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['username']) ?></td>
                            <td>
                                <strong><?= htmlspecialchars($row['full_name']) ?></strong><br>
                                <small class="text-secondary"><?= htmlspecialchars($row['gender']) ?>, <?= (int) $row['age'] ?> yrs</small>
                            </td>
                            <td>
                                <?= htmlspecialchars($row['event_name']) ?><br>
                                <small class="text-secondary"><?= date('d M Y, h:i A', strtotime($row['event_date'] . ' ' . $row['event_time'])) ?> • <?= htmlspecialchars($row['venue']) ?></small>
                            </td>
                            <td>
                                <span class="badge <?= $row['payment_status'] === 'paid' ? 'bg-warning text-dark' : 'bg-success' ?>"><?= strtoupper($row['payment_status']) ?></span>
                                <div class="small mt-1">₹<?= number_format((float) $row['amount_paid'], 2) ?></div>
                            </td>
                            <td>
                                <span class="badge <?= $row['refund_status'] === 'approved' ? 'badge-approved' : ($row['refund_status'] === 'rejected' ? 'badge-rejected' : ($row['refund_status'] === 'requested' ? 'badge-pending' : 'bg-secondary')) ?>">
                                    <?= ucfirst($row['refund_status']) ?>
                                </span>
                                <?php if ($row['refund_status'] === 'approved'): ?>
                                    <div class="small mt-1">Refunded ₹<?= number_format((float) $row['refund_amount'], 2) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d M Y, h:i A', strtotime($row['registration_date'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>

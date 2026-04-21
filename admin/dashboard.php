<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

$totalUsers = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$totalEvents = (int) $pdo->query('SELECT COUNT(*) FROM events')->fetchColumn();
$totalRegistrations = (int) $pdo->query('SELECT COUNT(*) FROM registrations')->fetchColumn();
$revenueAfterRefunds = (float) $pdo->query("SELECT COALESCE(SUM(amount_paid - CASE WHEN refund_status='approved' THEN refund_amount ELSE 0 END), 0) FROM registrations")->fetchColumn();
$pendingRefunds = (int) $pdo->query("SELECT COUNT(*) FROM refund_requests WHERE status='requested'")->fetchColumn();

$mostBooked = $pdo->query('SELECT e.event_name, COUNT(r.registration_id) AS total
    FROM events e
    LEFT JOIN registrations r ON r.event_id = e.event_id
    GROUP BY e.event_id
    ORDER BY total DESC, e.event_name ASC
    LIMIT 1')->fetch();

$highestRevenue = $pdo->query("SELECT e.event_name,
        COALESCE(SUM(r.amount_paid - CASE WHEN r.refund_status='approved' THEN r.refund_amount ELSE 0 END),0) AS net_revenue
    FROM events e
    LEFT JOIN registrations r ON r.event_id = e.event_id
    GROUP BY e.event_id
    ORDER BY net_revenue DESC, e.event_name ASC
    LIMIT 1")->fetch();

$topEvents = $pdo->query('SELECT e.event_name, COUNT(r.registration_id) AS total
    FROM events e
    LEFT JOIN registrations r ON r.event_id = e.event_id
    GROUP BY e.event_id
    ORDER BY total DESC, e.event_name ASC
    LIMIT 5')->fetchAll();

$refundSummary = $pdo->query("SELECT
        COALESCE(SUM(CASE WHEN refund_status='approved' THEN 1 ELSE 0 END),0) AS total_successful_refunds,
        COALESCE(SUM(CASE WHEN refund_status='approved' THEN refund_amount ELSE 0 END),0) AS refund_loss,
        COALESCE(SUM(CASE WHEN refund_status='approved' THEN commission_deducted ELSE 0 END),0) AS retained_commission
    FROM registrations")->fetch();

$recentStmt = $pdo->query('SELECT e.event_name, u.username, r.registration_date
    FROM registrations r
    INNER JOIN users u ON u.user_id = r.user_id
    INNER JOIN events e ON e.event_id = r.event_id
    ORDER BY r.registration_date DESC
    LIMIT 5');
$recentRegs = $recentStmt->fetchAll();

$pageTitle = 'Admin Dashboard';
include __DIR__ . '/../includes/header.php';
?>
<div class="app-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="content-area page-enter">
        <h2 class="mb-1">Executive Dashboard</h2>
        <p class="text-secondary mb-4">Real-time platform insights with privacy-compliant data access.</p>

        <div class="row g-3 mb-4">
            <div class="col-md-3"><div class="card stat-card glass-card"><div class="card-body"><p class="text-secondary mb-2">Users</p><h3><?= $totalUsers ?></h3></div></div></div>
            <div class="col-md-3"><div class="card stat-card glass-card"><div class="card-body"><p class="text-secondary mb-2">Events</p><h3><?= $totalEvents ?></h3></div></div></div>
            <div class="col-md-3"><div class="card stat-card glass-card"><div class="card-body"><p class="text-secondary mb-2">Bookings</p><h3><?= $totalRegistrations ?></h3></div></div></div>
            <div class="col-md-3"><div class="card stat-card glass-card"><div class="card-body"><p class="text-secondary mb-2">Revenue (Net)</p><h3>₹<?= number_format($revenueAfterRefunds, 2) ?></h3></div></div></div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-4"><div class="card panel-card p-3 h-100"><small class="text-secondary">Most Booked Event</small><h5 class="mb-0"><?= htmlspecialchars($mostBooked['event_name'] ?? 'N/A') ?></h5><div class="text-secondary small">Bookings: <?= (int) ($mostBooked['total'] ?? 0) ?></div></div></div>
            <div class="col-lg-4"><div class="card panel-card p-3 h-100"><small class="text-secondary">Highest Revenue Event</small><h5 class="mb-0"><?= htmlspecialchars($highestRevenue['event_name'] ?? 'N/A') ?></h5><div class="text-secondary small">Net: ₹<?= number_format((float) ($highestRevenue['net_revenue'] ?? 0), 2) ?></div></div></div>
            <div class="col-lg-4"><div class="card panel-card p-3 h-100"><small class="text-secondary">Refund Metrics</small><h5 class="mb-1">Successful: <?= (int) ($refundSummary['total_successful_refunds'] ?? 0) ?></h5><div class="text-secondary small">Loss: ₹<?= number_format((float) ($refundSummary['refund_loss'] ?? 0), 2) ?> | Retained: ₹<?= number_format((float) ($refundSummary['retained_commission'] ?? 0), 2) ?></div></div></div>
        </div>

        <div class="alert alert-info mb-4">Pending refund requests: <strong><?= $pendingRefunds ?></strong>. Review them in Refund Requests.</div>

        <div class="row g-3 mb-4">
            <div class="col-lg-6">
                <div class="card panel-card glass-card">
                    <div class="card-header border-0 pb-0 bg-transparent"><h5 class="mb-0">Top 5 Events by Registrations</h5></div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <?php if (!$topEvents): ?><li class="list-group-item bg-transparent text-secondary">No event data yet.</li><?php endif; ?>
                            <?php foreach ($topEvents as $idx => $row): ?>
                                <li class="list-group-item bg-transparent d-flex justify-content-between align-items-center text-light">
                                    <span>#<?= $idx + 1 ?> <?= htmlspecialchars($row['event_name']) ?></span>
                                    <span class="badge badge-approved"><?= (int) $row['total'] ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card panel-card glass-card">
                    <div class="card-header border-0 pb-0 bg-transparent"><h5 class="mb-0">Recent Registrations</h5></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-dark table-hover mb-0">
                                <thead><tr><th>User</th><th>Event</th><th>Registered On</th></tr></thead>
                                <tbody>
                                <?php if (!$recentRegs): ?><tr><td colspan="3" class="text-center py-5"><div class="empty-state">No registrations yet.</div></td></tr><?php endif; ?>
                                <?php foreach ($recentRegs as $reg): ?>
                                    <tr><td><?= htmlspecialchars($reg['username']) ?></td><td><?= htmlspecialchars($reg['event_name']) ?></td><td><?= date('d M Y, h:i A', strtotime($reg['registration_date'])) ?></td></tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>

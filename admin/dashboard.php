<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

$totalUsers = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$totalEvents = (int) $pdo->query('SELECT COUNT(*) FROM events')->fetchColumn();
$totalRegistrations = (int) $pdo->query('SELECT COUNT(*) FROM registrations')->fetchColumn();
$totalRevenue = (float) $pdo->query("SELECT COALESCE(SUM(amount_paid - CASE WHEN refund_status='approved' THEN refund_amount ELSE 0 END), 0) FROM registrations")->fetchColumn();
$pendingRefunds = (int) $pdo->query("SELECT COUNT(*) FROM refund_requests WHERE status='requested'")->fetchColumn();

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
            <div class="col-md-3">
                <div class="card stat-card glass-card">
                    <div class="card-body">
                        <p class="text-secondary mb-2">Users</p>
                        <h3><?= $totalUsers ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card glass-card">
                    <div class="card-body">
                        <p class="text-secondary mb-2">Events</p>
                        <h3><?= $totalEvents ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card glass-card">
                    <div class="card-body">
                        <p class="text-secondary mb-2">Bookings</p>
                        <h3><?= $totalRegistrations ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card glass-card">
                    <div class="card-body">
                        <p class="text-secondary mb-2">Net Revenue</p>
                        <h3>₹<?= number_format($totalRevenue, 2) ?></h3>
                        <small class="text-secondary">Includes retained commissions</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="alert alert-info mb-4">Pending refund requests: <strong><?= $pendingRefunds ?></strong>. Review them in Refund Requests.</div>

        <div class="card panel-card glass-card">
            <div class="card-header border-0 pb-0 bg-transparent">
                <h5 class="mb-0">Recent Registrations</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-dark table-hover mb-0">
                        <thead>
                        <tr>
                            <th>User</th>
                            <th>Event</th>
                            <th>Registered On</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (!$recentRegs): ?>
                            <tr><td colspan="3" class="text-center py-5"><div class="empty-state">No registrations yet.</div></td></tr>
                        <?php endif; ?>
                        <?php foreach ($recentRegs as $reg): ?>
                            <tr>
                                <td><?= htmlspecialchars($reg['username']) ?></td>
                                <td><?= htmlspecialchars($reg['event_name']) ?></td>
                                <td><?= date('d M Y, h:i A', strtotime($reg['registration_date'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>

<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

$eventsStmt = $pdo->query('SELECT e.*, COUNT(r.registration_id) AS total_bookings
    FROM events e
    LEFT JOIN registrations r ON r.event_id = e.event_id
    GROUP BY e.event_id
    ORDER BY e.event_date, e.event_time');
$events = $eventsStmt->fetchAll();

$pageTitle = 'Manage Events';
include __DIR__ . '/../includes/header.php';
?>https://github.com/ayannshxikh7/DreamEvents/pull/2/conflict?name=admin%252Fedit_event.php&base_oid=581bd6888301db0178d2b9f7bf1c2882a17355bf&head_oid=85af9ea248b16a4ab83b434f0f8520832928c260
<div class="app-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="content-area page-enter">
        <h2 class="mb-1">Manage Events</h2>
        <p class="text-secondary mb-4">View all published events and booking counts.</p>

        <?php if (isset($_GET['updated'])): ?>
            <div class="alert alert-success">Event updated successfully.</div>
        <?php endif; ?>

        <div class="card panel-card">
            <div class="table-responsive">
                <table class="table table-dark table-hover mb-0 align-middle">
                    <thead>
                    <tr>
                        <th>Event</th>
                        <th>Schedule</th>
                        <th>Price</th>
                        <th>Bookings</th>
                        <th>Capacity</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (!$events): ?>
                        <tr><td colspan="6" class="text-center py-5"><div class="empty-state">No events created yet.</div></td></tr>
                    <?php endif; ?>
                    <?php foreach ($events as $event): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($event['event_name']) ?></strong><br>
                                <small class="text-secondary"><?= htmlspecialchars($event['venue']) ?></small>
                            </td>
                            <td><?= date('d M Y, h:i A', strtotime($event['event_date'] . ' ' . $event['event_time'])) ?></td>
                            <td><?= (float) $event['price'] > 0 ? '₹' . number_format((float) $event['price'], 2) : 'Free' ?></td>
                            <td><?= (int) $event['total_bookings'] ?></td>
                            <td><?= (int) $event['capacity'] ?></td>
                            <td>
                                <div class="d-flex flex-wrap gap-2">
                                    <a href="/DreamEvents/admin/edit_event.php?event_id=<?= (int) $event['event_id'] ?>"
                                       class="btn btn-sm btn-outline-info">Edit</a>
                                    <form method="post" action="/DreamEvents/admin/delete_event.php" onsubmit="return confirm('Are you sure? This will remove the event and all related registrations.');">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="event_id" value="<?= (int) $event['event_id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                    </form>
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
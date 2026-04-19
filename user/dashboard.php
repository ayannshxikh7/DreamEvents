<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('user');

$search = trim($_GET['search'] ?? '');
$priceFilter = $_GET['price_filter'] ?? '';
$dateFilter = $_GET['date_filter'] ?? '';

$sql = 'SELECT e.*, COUNT(r.registration_id) AS total_bookings
        FROM events e
        LEFT JOIN registrations r ON r.event_id = e.event_id
        WHERE 1=1';
$params = [];

if ($search !== '') {
    $sql .= ' AND e.event_name LIKE ?';
    $params[] = '%' . $search . '%';
}

if ($priceFilter === 'free') {
    $sql .= ' AND e.price = 0';
} elseif ($priceFilter === 'paid') {
    $sql .= ' AND e.price > 0';
}

if ($dateFilter !== '') {
    $sql .= ' AND e.event_date = ?';
    $params[] = $dateFilter;
}

$sql .= ' GROUP BY e.event_id ORDER BY e.event_date, e.event_time';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll();

$bookingStmt = $pdo->prepare('SELECT event_id FROM registrations WHERE user_id = ?');
$bookingStmt->execute([$_SESSION['user_id']]);
$bookedIds = array_flip(array_column($bookingStmt->fetchAll(), 'event_id'));

$pageTitle = 'Explore Events';
include __DIR__ . '/../includes/header.php';
?>
<div class="app-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="content-area page-enter">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">Discover Events</h2>
                <p class="text-secondary mb-0">Book shows, conferences, and experiences in one place.</p>
            </div>
        </div>

        <?php if (isset($_GET['full'])): ?>
            <div class="alert alert-danger">This event is full and can no longer accept bookings.</div>
        <?php endif; ?>

        <div class="card panel-card p-3 mb-4">
            <form method="get" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label">Search Event</label>
                    <input type="text" name="search" class="form-control" value="<?= htmlspecialchars($search) ?>" placeholder="Search by event name">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Price Filter</label>
                    <select name="price_filter" class="form-select">
                        <option value="">All</option>
                        <option value="free" <?= $priceFilter === 'free' ? 'selected' : '' ?>>Free</option>
                        <option value="paid" <?= $priceFilter === 'paid' ? 'selected' : '' ?>>Paid</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date</label>
                    <input type="date" name="date_filter" class="form-control" value="<?= htmlspecialchars($dateFilter) ?>">
                </div>
                <div class="col-md-1 d-grid">
                    <button class="btn btn-primary" type="submit">Go</button>
                </div>
            </form>
        </div>

        <div class="row g-4">
            <?php if (!$events): ?>
                <div class="col-12">
                    <div class="empty-state glass-card p-4">No events matched your filters. Try broadening your search.</div>
                </div>
            <?php endif; ?>

            <?php foreach ($events as $event): ?>
                <?php
                $imagePath = '/DreamEvents/assets/images/' . ($event['image'] ?: 'default.jpg');
                $price = (float) $event['price'];
                $isFull = (int) $event['total_bookings'] >= (int) $event['capacity'];
                $isPast = strtotime($event['event_date']) < strtotime(date('Y-m-d'));
                ?>
                <div class="col-md-6 col-xl-4">
                    <div class="card event-card h-100 overflow-hidden">
                        <img src="<?= htmlspecialchars($imagePath) ?>" class="event-image" alt="<?= htmlspecialchars($event['event_name']) ?>" onerror="this.src='/DreamEvents/assets/images/default.jpg'">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-1">
                                <span class="badge <?= $isPast ? 'badge-rejected' : 'badge-approved' ?>"><?= $isPast ? 'Past' : 'Upcoming' ?></span>
                                <span class="badge <?= $price > 0 ? 'bg-warning text-dark' : 'bg-success' ?>">
                                    <?= $price > 0 ? '₹' . number_format($price, 2) : 'Free' ?>
                                </span>
                                <?php if ($isFull): ?>
                                    <span class="badge badge-pending">Full</span>
                                <?php endif; ?>
                            </div>
                            <h5 class="card-title"><?= htmlspecialchars($event['event_name']) ?></h5>
                            <p class="small text-secondary mb-2"><?= date('D, d M Y', strtotime($event['event_date'])) ?> • <?= date('h:i A', strtotime($event['event_time'])) ?></p>
                            <p class="small text-secondary mb-2">📍 <?= htmlspecialchars($event['venue']) ?></p>
                            <p class="small text-secondary mb-3">Seats: <?= (int) $event['total_bookings'] ?>/<?= (int) $event['capacity'] ?></p>
                            <p class="card-text text-light-emphasis flex-grow-1"><?= htmlspecialchars($event['description']) ?></p>
                            <?php if ($isPast): ?>
                                <button class="btn btn-secondary mt-3" disabled>Event Ended</button>
                            <?php elseif ($isFull): ?>
                                <button class="btn btn-secondary mt-3" disabled>Event Full</button>
                            <?php elseif (isset($bookedIds[$event['event_id']])): ?>
                                <button class="btn btn-outline-success mt-3" disabled>Already Booked</button>
                            <?php else: ?>
                                <a class="btn btn-primary mt-3" href="/DreamEvents/user/booking_form.php?event_id=<?= (int) $event['event_id'] ?>">Book Event</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>

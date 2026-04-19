<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('user');

$eventId = (int) ($_GET['event_id'] ?? $_POST['event_id'] ?? 0);
if ($eventId <= 0) {
    header('Location: /DreamEvents/user/dashboard.php');
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
    header('Location: /DreamEvents/user/dashboard.php');
    exit;
}

if ((int) $event['total_bookings'] >= (int) $event['capacity']) {
    header('Location: /DreamEvents/user/dashboard.php?full=1');
    exit;
}

$alreadyBooked = $pdo->prepare('SELECT registration_id FROM registrations WHERE user_id = ? AND event_id = ? LIMIT 1');
$alreadyBooked->execute([$_SESSION['user_id'], $eventId]);
if ($alreadyBooked->fetch()) {
    header('Location: /DreamEvents/user/my_bookings.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $age = (int) ($_POST['age'] ?? 0);
    $allowedGenders = ['Male', 'Female', 'Other'];

    if ($fullName === '' || !preg_match('/^[a-zA-Z\\s]{2,120}$/', $fullName) || !in_array($gender, $allowedGenders, true) || $age <= 0 || $age > 100) {
        $error = 'Please enter valid booking details.';
    } else {
        $_SESSION['pending_booking'] = [
            'event_id' => (int) $event['event_id'],
            'full_name' => $fullName,
            'gender' => $gender,
            'age' => $age,
            'amount' => (float) $event['price'],
        ];

        if ((float) $event['price'] > 0) {
            header('Location: /DreamEvents/user/payment.php');
            exit;
        }

        $_POST['payment_status'] = 'free';
        $_POST['amount_paid'] = 0;
        include __DIR__ . '/book_event.php';
        exit;
    }
}

$pageTitle = 'Booking Form';
include __DIR__ . '/../includes/header.php';
?>
<div class="app-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="content-area page-enter">
        <h2 class="mb-1">Booking Details</h2>
        <p class="text-secondary mb-4">Please provide attendee details to continue.</p>

        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card panel-card p-4 h-100">
                    <h5><?= htmlspecialchars($event['event_name']) ?></h5>
                    <p class="mb-1 text-secondary"><?= date('D, d M Y', strtotime($event['event_date'])) ?> • <?= date('h:i A', strtotime($event['event_time'])) ?></p>
                    <p class="text-secondary">📍 <?= htmlspecialchars($event['venue']) ?></p>
                    <span class="badge <?= (float)$event['price'] > 0 ? 'bg-warning text-dark' : 'bg-success' ?> w-fit-content">
                        <?= (float)$event['price'] > 0 ? 'Price: ₹' . number_format((float)$event['price'], 2) : 'Free Event' ?>
                    </span>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card panel-card p-4">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <form method="post" class="row g-3 js-processing-form">
                        <input type="hidden" name="event_id" value="<?= (int) $event['event_id'] ?>">
                        <div class="col-12">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="full_name" maxlength="120" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Gender</label>
                            <select class="form-select" name="gender" required>
                                <option value="">Select gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Age</label>
                            <input type="number" class="form-control" name="age" min="10" max="100" required>
                        </div>
                        <div class="col-12 d-flex gap-2">
                            <a href="/DreamEvents/user/dashboard.php" class="btn btn-outline-light">Cancel</a>
                            <button class="btn btn-gradient" type="submit" data-processing-text="Processing...">
                                <?= (float) $event['price'] > 0 ? 'Continue to Payment' : 'Confirm Booking (Free)' ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>

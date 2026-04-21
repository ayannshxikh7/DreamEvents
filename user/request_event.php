<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('user');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrAbort();
    $name = trim($_POST['event_name'] ?? '');
    $eventDate = $_POST['event_date'] ?? '';
    $eventTime = $_POST['event_time'] ?? '';
    $venue = trim($_POST['venue'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = is_numeric($_POST['price'] ?? null) ? (float) $_POST['price'] : -1;

    if ($name === '' || !preg_match('/^[a-zA-Z0-9\\s\\-\\&]{2,120}$/', $name) || $eventDate === '' || $eventTime === '' || $venue === '' || $price < 0) {
        $error = 'Please fill all required fields correctly.';
    } else {
        $imageName = null;
        if (!empty($_FILES['image']['name'])) {
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
            $uploadError = $_FILES['image']['error'] ?? UPLOAD_ERR_OK;
            $tmpPath = $_FILES['image']['tmp_name'] ?? '';
            $mime = ($uploadError === UPLOAD_ERR_OK && is_uploaded_file($tmpPath)) ? mime_content_type($tmpPath) : '';

            if ($uploadError !== UPLOAD_ERR_OK) {
                $error = 'Image upload failed. Please try again.';
            } elseif (!isset($allowed[$mime])) {
                $error = 'Only JPG or PNG images are allowed.';
            } elseif ($_FILES['image']['size'] > 2 * 1024 * 1024) {
                $error = 'Image size must be less than 2MB.';
            } else {
                $imageName = 'request_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
                $targetPath = __DIR__ . '/../assets/images/' . $imageName;
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                    $error = 'Image upload failed. Please try again.';
                }
            }
        }

        if ($error === '') {
            $stmt = $pdo->prepare('INSERT INTO event_requests (user_id, event_name, event_date, event_time, venue, description, price, image, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                $_SESSION['user_id'],
                $name,
                $eventDate,
                $eventTime,
                $venue,
                $description,
                $price,
                $imageName,
                'pending',
            ]);
            $success = 'Event request submitted for admin approval.';
        }
    }
}

$requestsStmt = $pdo->prepare('SELECT * FROM event_requests WHERE user_id = ? ORDER BY created_at DESC');
$requestsStmt->execute([$_SESSION['user_id']]);
$requests = $requestsStmt->fetchAll();

$pageTitle = 'Request Event';
include __DIR__ . '/../includes/header.php';
?>
<div class="app-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="content-area page-enter">
        <h2 class="mb-1">Request a New Event</h2>
        <p class="text-secondary mb-4">Submit your event idea. Admin will review and approve/reject it.</p>

        <div class="card panel-card p-4 mb-4">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data" class="row g-3">
                <?= csrfField() ?>
                <div class="col-md-6">
                    <label class="form-label">Event Name</label>
                    <input type="text" class="form-control" name="event_name" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date</label>
                    <input type="date" class="form-control" name="event_date" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Time</label>
                    <input type="time" class="form-control" name="event_time" required>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Venue</label>
                    <input type="text" class="form-control" name="venue" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Price (₹)</label>
                    <input type="number" class="form-control" name="price" min="0" step="0.01" value="0" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Event Image (Optional)</label>
                    <input type="file" class="form-control" name="image" accept="image/png,image/jpeg">
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="description" rows="4"></textarea>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary" type="submit">Submit Request</button>
                </div>
            </form>
        </div>

        <div class="card panel-card">
            <div class="card-header bg-transparent border-0 pb-0">
                <h5 class="mb-0">My Event Requests</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-dark table-hover mb-0 align-middle">
                    <thead>
                    <tr>
                        <th>Event</th>
                        <th>Date</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Requested On</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (!$requests): ?>
                        <tr><td colspan="5" class="text-center text-secondary py-4">No requests submitted yet.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($requests as $request): ?>
                        <tr>
                            <td><?= htmlspecialchars($request['event_name']) ?></td>
                            <td><?= date('d M Y, h:i A', strtotime($request['event_date'] . ' ' . $request['event_time'])) ?></td>
                            <td><?= (float) $request['price'] > 0 ? '₹' . number_format((float) $request['price'], 2) : 'Free' ?></td>
                            <td>
                                <span class="badge <?= $request['status'] === 'approved' ? 'badge-approved' : ($request['status'] === 'rejected' ? 'badge-rejected' : 'badge-pending') ?>">
                                    <?= ucfirst($request['status']) ?>
                                </span>
                            </td>
                            <td><?= date('d M Y, h:i A', strtotime($request['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>

<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['event_name'] ?? '');
    $eventDate = $_POST['event_date'] ?? '';
    $eventTime = $_POST['event_time'] ?? '';
    $venue = trim($_POST['venue'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = is_numeric($_POST['price'] ?? null) ? (float) $_POST['price'] : -1;
    $capacity = (int) ($_POST['capacity'] ?? 0);

    if ($name === '' || $eventDate === '' || $eventTime === '' || $venue === '' || $price < 0 || $capacity <= 0) {
        $error = 'Please fill all required fields correctly.';
    } else {
        $imageName = null;
        if (!empty($_FILES['image']['name'])) {
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            $uploadError = $_FILES['image']['error'] ?? UPLOAD_ERR_OK;
            $tmpPath = $_FILES['image']['tmp_name'] ?? '';
            $mime = ($uploadError === UPLOAD_ERR_OK && is_uploaded_file($tmpPath)) ? mime_content_type($tmpPath) : '';

            if ($uploadError !== UPLOAD_ERR_OK) {
                $error = 'Image upload failed. Please try again.';
            } elseif (!isset($allowed[$mime])) {
                $error = 'Only JPG, PNG, or WEBP images are allowed.';
            } elseif ($_FILES['image']['size'] > 2 * 1024 * 1024) {
                $error = 'Image size must be less than 2MB.';
            } else {
                $imageName = 'event_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
                $targetPath = __DIR__ . '/../assets/images/' . $imageName;
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                    $error = 'Image upload failed. Please try again.';
                }
            }
        }

        if ($error === '') {
            $stmt = $pdo->prepare('INSERT INTO events (event_name, event_date, event_time, venue, description, price, image, capacity, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$name, $eventDate, $eventTime, $venue, $description, $price, $imageName, $capacity, $_SESSION['user_id']]);
            $success = 'Event added successfully.';
        }
    }
}

$pageTitle = 'Add Event';
include __DIR__ . '/../includes/header.php';
?>
<div class="app-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="content-area">
        <h2 class="mb-1">Add New Event</h2>
        <p class="text-secondary mb-4">Create and publish an event for users.</p>

        <div class="card panel-card p-4">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data" class="row g-3">
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
                <div class="col-md-4">
                    <label class="form-label">Capacity</label>
                    <input type="number" class="form-control" name="capacity" min="1" value="100" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Event Image (Optional)</label>
                    <input type="file" class="form-control" name="image" accept="image/png,image/jpeg,image/webp">
                    <small class="text-secondary">Max 2MB. Supported: JPG, PNG, WEBP.</small>
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="description" rows="4"></textarea>
                </div>
                <div class="col-12">
                    <button class="btn btn-gradient" type="submit">Publish Event</button>
                </div>
            </form>
        </div>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>

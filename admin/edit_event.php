<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

$eventId = (int) ($_GET['event_id'] ?? $_POST['event_id'] ?? 0);
if ($eventId <= 0) {
    header('Location: /DreamEvents/admin/manage_events.php');
    exit;
}

$fetchStmt = $pdo->prepare('SELECT * FROM events WHERE event_id = ? LIMIT 1');
$fetchStmt->execute([$eventId]);
$event = $fetchStmt->fetch();

if (!$event) {
    header('Location: /DreamEvents/admin/manage_events.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrAbort();
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
        $imageName = $event['image'];

        if (!empty($_FILES['image']['name'])) {
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
            $uploadError = $_FILES['image']['error'] ?? UPLOAD_ERR_OK;
            $tmpPath = $_FILES['image']['tmp_name'] ?? '';
            $mime = ($uploadError === UPLOAD_ERR_OK && is_uploaded_file($tmpPath)) ? mime_content_type($tmpPath) : '';

            if ($uploadError !== UPLOAD_ERR_OK) {
                $error = 'Image upload failed. Please try again.';
            } elseif (!isset($allowed[$mime])) {
                $error = 'Only JPG or PNG images are allowed.';
            } elseif (($_FILES['image']['size'] ?? 0) > 2 * 1024 * 1024) {
                $error = 'Image size must be less than 2MB.';
            } else {
                $newImageName = 'event_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
                $targetPath = __DIR__ . '/../assets/images/' . $newImageName;

                if (!move_uploaded_file($tmpPath, $targetPath)) {
                    $error = 'Image upload failed. Please try again.';
                } else {
                    $oldImage = $event['image'] ?? null;
                    if ($oldImage && $oldImage !== 'default.jpg') {
                        $oldPath = __DIR__ . '/../assets/images/' . $oldImage;
                        if (is_file($oldPath)) {
                            @unlink($oldPath);
                        }
                    }
                    $imageName = $newImageName;
                }
            }
        }

        if ($error === '') {
            $updateStmt = $pdo->prepare('UPDATE events
                SET event_name = ?, event_date = ?, event_time = ?, venue = ?, description = ?, price = ?, capacity = ?, image = ?
                WHERE event_id = ?');
            $updateStmt->execute([$name, $eventDate, $eventTime, $venue, $description, $price, $capacity, $imageName, $eventId]);

            header('Location: /DreamEvents/admin/manage_events.php?updated=1');
            exit;
        }
    }

    $event['event_name'] = $name;
    $event['event_date'] = $eventDate;
    $event['event_time'] = $eventTime;
    $event['venue'] = $venue;
    $event['description'] = $description;
    $event['price'] = $price >= 0 ? $price : $event['price'];
    $event['capacity'] = $capacity > 0 ? $capacity : $event['capacity'];
    if (isset($imageName)) {
        $event['image'] = $imageName;
    }
}

$pageTitle = 'Edit Event';
include __DIR__ . '/../includes/header.php';
?>
<div class="app-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="content-area page-enter">
        <h2 class="mb-1">Edit Event</h2>
        <p class="text-secondary mb-4">Update event details while preserving existing bookings and workflows.</p>

        <div class="card panel-card p-4">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data" class="row g-3">
                <?= csrfField() ?>
                <input type="hidden" name="event_id" value="<?= (int) $eventId ?>">

                <div class="col-md-6">
                    <label class="form-label">Event Name</label>
                    <input type="text" class="form-control" name="event_name" value="<?= htmlspecialchars($event['event_name']) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date</label>
                    <input type="date" class="form-control" name="event_date" value="<?= htmlspecialchars($event['event_date']) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Time</label>
                    <input type="time" class="form-control" name="event_time" value="<?= htmlspecialchars(substr((string) $event['event_time'], 0, 5)) ?>" required>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Venue</label>
                    <input type="text" class="form-control" name="venue" value="<?= htmlspecialchars($event['venue']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Ticket Price (₹)</label>
                    <input type="number" class="form-control" name="price" min="0" step="0.01" value="<?= htmlspecialchars((string) $event['price']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Capacity</label>
                    <input type="number" class="form-control" name="capacity" min="1" value="<?= (int) $event['capacity'] ?>" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Event Image (Optional Replacement)</label>
                    <input type="file" class="form-control" name="image" accept="image/png,image/jpeg">
                    <small class="text-secondary">Leave empty to keep current image. Max 2MB. Supported: JPG, PNG.</small>
                </div>
                <div class="col-12">
                    <div class="d-flex align-items-center gap-3 p-2 rounded" style="background: rgba(15, 23, 42, 0.5); border: 1px solid rgba(148, 163, 184, 0.2);">
                        <img src="/DreamEvents/assets/images/<?= htmlspecialchars($event['image'] ?: 'default.jpg') ?>"
                             alt="Current image"
                             style="width: 120px; height: 80px; object-fit: cover; border-radius: 10px;"
                             onerror="this.src='/DreamEvents/assets/images/default.jpg'">
                        <div>
                            <div class="fw-semibold">Current Event Image</div>
                            <small class="text-secondary"><?= htmlspecialchars($event['image'] ?: 'default.jpg') ?></small>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="description" rows="4"><?= htmlspecialchars($event['description'] ?? '') ?></textarea>
                </div>
                <div class="col-12 d-flex gap-2">
                    <a href="/DreamEvents/admin/manage_events.php" class="btn btn-outline-light">Cancel</a>
                    <button class="btn btn-gradient" type="submit">Save Changes</button>
                </div>
            </form>
        </div>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>

<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mailer.php';

requireRole('admin');

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrAbort();

    $requestId = (int) ($_POST['request_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($requestId <= 0 || !in_array($action, ['approve', 'reject'], true)) {
        $error = 'Invalid request action.';
    } else {
        $requestStmt = $pdo->prepare('SELECT er.*, u.username, u.email FROM event_requests er INNER JOIN users u ON u.user_id = er.user_id WHERE request_id = ? LIMIT 1');
        $requestStmt->execute([$requestId]);
        $request = $requestStmt->fetch();

        if (!$request) {
            $error = 'Event request not found.';
        } elseif ($request['status'] !== 'pending') {
            $error = 'This request was already processed.';
        } elseif ($action === 'reject') {
            $rejectStmt = $pdo->prepare("UPDATE event_requests SET status = 'rejected' WHERE request_id = ?");
            $rejectStmt->execute([$requestId]);
            $message = 'Event request rejected.';

            if (!empty($request['email'])) {
                $html = dreamEventsBrandTemplate('Event Request Update', '<p>Your event request has been <strong>rejected</strong>.</p><p>You can submit another request anytime.</p>');
                sendSystemEmail($request['email'], $request['username'], 'Your event request has been rejected', $html);
            }
        } else {
            try {
                $pdo->beginTransaction();

                $insertEvent = $pdo->prepare('INSERT INTO events (event_name, event_date, event_time, venue, description, price, image, capacity, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $insertEvent->execute([
                    $request['event_name'],
                    $request['event_date'],
                    $request['event_time'],
                    $request['venue'],
                    $request['description'],
                    $request['price'],
                    $request['image'],
                    100,
                    $_SESSION['user_id'],
                ]);

                $approveStmt = $pdo->prepare("UPDATE event_requests SET status = 'approved' WHERE request_id = ?");
                $approveStmt->execute([$requestId]);

                $pdo->commit();
                $message = 'Event request approved and published.';

                if (!empty($request['email'])) {
                    $html = dreamEventsBrandTemplate('Event Request Update', '<p>Your event request has been <strong>approved</strong> and is now live on DreamEvents.</p>');
                    sendSystemEmail($request['email'], $request['username'], 'Your event request has been approved', $html);
                }
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = 'Could not approve request. Please try again.';
            }
        }
    }
}

$requestsStmt = $pdo->query('SELECT er.*, u.username
    FROM event_requests er
    INNER JOIN users u ON u.user_id = er.user_id
    ORDER BY er.created_at DESC');
$requests = $requestsStmt->fetchAll();

$pageTitle = 'Event Requests';
include __DIR__ . '/../includes/header.php';
?>
<div class="app-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="content-area page-enter">
        <h2 class="mb-1">Event Requests</h2>
        <p class="text-secondary mb-4">Review user-submitted events and approve or reject them.</p>

        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card panel-card">
            <div class="table-responsive">
                <table class="table table-dark table-hover mb-0 align-middle">
                    <thead>
                    <tr>
                        <th>User</th>
                        <th>Event</th>
                        <th>Date</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (!$requests): ?>
                        <tr><td colspan="6" class="text-center text-secondary py-4">No event requests available.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($requests as $request): ?>
                        <tr>
                            <td><?= htmlspecialchars($request['username']) ?></td>
                            <td>
                                <strong><?= htmlspecialchars($request['event_name']) ?></strong><br>
                                <small class="text-secondary"><?= htmlspecialchars($request['venue']) ?></small>
                            </td>
                            <td><?= date('d M Y, h:i A', strtotime($request['event_date'] . ' ' . $request['event_time'])) ?></td>
                            <td><?= (float) $request['price'] > 0 ? '₹' . number_format((float) $request['price'], 2) : 'Free' ?></td>
                            <td>
                                <span class="badge <?= $request['status'] === 'approved' ? 'badge-approved' : ($request['status'] === 'rejected' ? 'badge-rejected' : 'badge-pending') ?>">
                                    <?= ucfirst($request['status']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($request['status'] === 'pending'): ?>
                                    <form method="post" class="d-flex gap-2">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="request_id" value="<?= (int) $request['request_id'] ?>">
                                        <button class="btn btn-sm btn-success" name="action" value="approve" type="submit">Approve</button>
                                        <button class="btn btn-sm btn-danger" name="action" value="reject" type="submit">Reject</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-secondary">Processed</span>
                                <?php endif; ?>
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

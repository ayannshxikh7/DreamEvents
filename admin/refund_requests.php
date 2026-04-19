<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $refundId = (int) ($_POST['refund_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($refundId <= 0 || !in_array($action, ['approve', 'reject'], true)) {
        $error = 'Invalid refund action.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM refund_requests WHERE refund_id = ? LIMIT 1');
        $stmt->execute([$refundId]);
        $refund = $stmt->fetch();

        if (!$refund || $refund['status'] !== 'requested') {
            $error = 'Refund request already processed or missing.';
        } else {
            try {
                $pdo->beginTransaction();

                $newStatus = $action === 'approve' ? 'approved' : 'rejected';

                $updateRefund = $pdo->prepare('UPDATE refund_requests SET status = ?, processed_date = NOW() WHERE refund_id = ?');
                $updateRefund->execute([$newStatus, $refundId]);

                if ($newStatus === 'approved') {
                    $updateReg = $pdo->prepare("UPDATE registrations SET refund_status='approved' WHERE registration_id = ?");
                    $updateReg->execute([$refund['registration_id']]);
                    $message = 'Refund approved successfully.';
                } else {
                    $updateReg = $pdo->prepare("UPDATE registrations SET refund_status='rejected', refund_amount=0, commission_deducted=0 WHERE registration_id = ?");
                    $updateReg->execute([$refund['registration_id']]);
                    $message = 'Refund rejected.';
                }

                $pdo->commit();
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = 'Failed to process refund request.';
            }
        }
    }
}

$requestsStmt = $pdo->query('SELECT rr.*, u.username, e.event_name, e.event_date, r.registration_date
    FROM refund_requests rr
    INNER JOIN users u ON u.user_id = rr.user_id
    INNER JOIN events e ON e.event_id = rr.event_id
    INNER JOIN registrations r ON r.registration_id = rr.registration_id
    ORDER BY rr.request_date DESC');
$requests = $requestsStmt->fetchAll();

$pageTitle = 'Refund Requests';
include __DIR__ . '/../includes/header.php';
?>
<div class="app-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="content-area page-enter">
        <h2 class="mb-1">Refund Requests</h2>
        <p class="text-secondary mb-4">Review and process customer refund requests with commission retention.</p>

        <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <div class="card panel-card glass-card">
            <div class="table-responsive">
                <table class="table table-dark table-hover mb-0 align-middle">
                    <thead>
                    <tr>
                        <th>User</th>
                        <th>Event</th>
                        <th>Original</th>
                        <th>Commission</th>
                        <th>Refund</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (!$requests): ?>
                        <tr><td colspan="7" class="text-center py-5"><div class="empty-state">No refund requests available.</div></td></tr>
                    <?php endif; ?>
                    <?php foreach ($requests as $req): ?>
                        <tr>
                            <td><?= htmlspecialchars($req['username']) ?></td>
                            <td>
                                <strong><?= htmlspecialchars($req['event_name']) ?></strong><br>
                                <small class="text-secondary">Event date: <?= date('d M Y', strtotime($req['event_date'])) ?></small>
                            </td>
                            <td>₹<?= number_format((float) $req['original_amount'], 2) ?></td>
                            <td>₹<?= number_format((float) $req['commission_deducted'], 2) ?></td>
                            <td>₹<?= number_format((float) $req['refund_amount'], 2) ?></td>
                            <td>
                                <span class="badge <?= $req['status'] === 'approved' ? 'badge-approved' : ($req['status'] === 'rejected' ? 'badge-rejected' : 'badge-pending') ?>">
                                    <?= ucfirst($req['status']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($req['status'] === 'requested'): ?>
                                    <form method="post" class="d-flex gap-2">
                                        <input type="hidden" name="refund_id" value="<?= (int) $req['refund_id'] ?>">
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

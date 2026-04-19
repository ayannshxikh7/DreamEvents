<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

$eventId = (int) ($_GET['event_id'] ?? 0);
if ($eventId > 0) {
    $stmt = $pdo->prepare('DELETE FROM events WHERE event_id = ?');
    $stmt->execute([$eventId]);
}

header('Location: /DreamEvents/admin/manage_events.php');
exit;

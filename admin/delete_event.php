<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /DreamEvents/admin/manage_events.php');
    exit;
}

verifyCsrfOrAbort();

$eventId = (int) ($_POST['event_id'] ?? 0);
if ($eventId > 0) {
    $stmt = $pdo->prepare('DELETE FROM events WHERE event_id = ?');
    $stmt->execute([$eventId]);
}

header('Location: /DreamEvents/admin/manage_events.php');
exit;

<?php
$currentPath = $_SERVER['PHP_SELF'];
$role = $_SESSION['role'] ?? 'user';
?>
<div class="sidebar d-flex flex-column p-3">
    <a href="/DreamEvents/index.php" class="brand mb-4 text-decoration-none">DreamEvents</a>
    <ul class="nav nav-pills flex-column gap-2">
        <?php if ($role === 'admin'): ?>
            <li><a class="nav-link <?= str_contains($currentPath, '/admin/dashboard.php') ? 'active' : '' ?>" href="/DreamEvents/admin/dashboard.php">Dashboard</a></li>
            <li><a class="nav-link <?= str_contains($currentPath, '/admin/add_event.php') ? 'active' : '' ?>" href="/DreamEvents/admin/add_event.php">Add Event</a></li>
            <li><a class="nav-link <?= str_contains($currentPath, '/admin/manage_events.php') ? 'active' : '' ?>" href="/DreamEvents/admin/manage_events.php">Manage Events</a></li>
            <li><a class="nav-link <?= str_contains($currentPath, '/admin/event_requests.php') ? 'active' : '' ?>" href="/DreamEvents/admin/event_requests.php">Event Requests</a></li>
            <li><a class="nav-link <?= str_contains($currentPath, '/admin/refund_requests.php') ? 'active' : '' ?>" href="/DreamEvents/admin/refund_requests.php">Refund Requests</a></li>
            <li><a class="nav-link <?= str_contains($currentPath, '/admin/registrations.php') ? 'active' : '' ?>" href="/DreamEvents/admin/registrations.php">Registrations</a></li>
        <?php else: ?>
            <li><a class="nav-link <?= str_contains($currentPath, '/user/dashboard.php') ? 'active' : '' ?>" href="/DreamEvents/user/dashboard.php">Explore Events</a></li>
            <li><a class="nav-link <?= str_contains($currentPath, '/user/request_event.php') ? 'active' : '' ?>" href="/DreamEvents/user/request_event.php">Request Event</a></li>
            <li><a class="nav-link <?= str_contains($currentPath, '/user/my_bookings.php') ? 'active' : '' ?>" href="/DreamEvents/user/my_bookings.php">My Bookings</a></li>
        <?php endif; ?>
    </ul>
    <div class="mt-auto pt-3 border-top border-secondary-subtle">
        <small class="d-block text-secondary">Signed in as</small>
        <strong><?= htmlspecialchars(currentUserName()) ?></strong>
        <a href="/DreamEvents/auth/logout.php" class="btn btn-outline-light btn-sm w-100 mt-3">Logout</a>
    </div>
</div>

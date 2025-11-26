<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) session_start();
$account_ID = $_SESSION['account_ID'] ?? null;

$touristNotification = [];
$unread_count = 0;
$badge_display = 'd-none';

if ($account_ID && is_numeric($account_ID)) {
    require_once "../../classes/booking.php";
    require_once "../../classes/activity-log.php";

    try {
        $bookingObj  = new Booking();
        $activityObj = new ActivityLogs();
        $bookingObj->updateBookings();
        $touristNotification = $activityObj->touristNotification((int)$account_ID);

        foreach ($touristNotification as $n) {
            if ((int)$n['is_viewed'] === 0) $unread_count++;
        }
        $badge_display = $unread_count > 0 ? '' : 'd-none';
    } catch (Throwable $e) {
        error_log("Header notification error: " . $e->getMessage());
    }
}
?>

<header class="header">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">Tourismo Zamboanga</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">
                    <li class="nav-item"><a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'booking.php' ? 'active' : '' ?>" href="booking.php">My Booking</a></li>

                    <!-- Notification Dropdown -->
                    <li class="nav-item dropdown position-relative">
                        <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" 
                           href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-bell-fill"></i>
                            <span class="d-lg-none">Notifications</span>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger <?= $badge_display ?>"
                                  style="font-size: 0.65rem; z-index: 10;">
                                <?= $unread_count ?>
                                <span class="visually-hidden">unread</span>
                            </span>
                        </a>

                        <ul id="notification-dropdown" class="dropdown-menu dropdown-menu-end mt-2 shadow-lg border-0" style="width: 380px; max-width: 95vw;">
                            <li class="dropdown-header bg-primary text-white py-3 rounded-top">
                                <div class="d-flex justify-content-between align-items-center px-3">
                                    <strong>Notifications</strong>
                                    <span class="badge bg-light text-primary"><?= count($touristNotification) ?></span>
                                </div>
                            </li>

                            <!-- Scrollable Area -->
                            <div id="notification-list-container" class="px-2" style="max-height: 70vh; overflow-y: auto; overflow-x: hidden;">
                                <?php include __DIR__ . '/components/notification-list.php'; ?>
                            </div>

                            <li><hr class="dropdown-divider my-0"></li>
                            <li><a class="dropdown-item text-center py-3 text-primary fw-bold" href="notifications.php">View All Notifications</a></li>
                        </ul>
                    </li>
                </ul>

                <?php if ($account_ID): ?>
                    <a href="logout.php" class="btn btn-outline-info ms-lg-3">Log out</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline-light ms-lg-3">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
</header>

<style>
    #notification-dropdown .dropdown-header { position: sticky; top: 0; z-index: 10; }
    #notification-list-container { scrollbar-width: thin; scrollbar-color: #0d6efd #f1f1f1; }
    #notification-list-container::-webkit-scrollbar { width: 6px; }
    #notification-list-container::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
    #notification-list-container::-webkit-scrollbar-thumb { background: #0d6efd; border-radius: 10px; }
    #notification-list-container::-webkit-scrollbar-thumb:hover { background: #0b5ed7; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.querySelector('.dropdown-toggle[aria-expanded]');
    const container = document.getElementById('notification-list-container');
    if (!toggle || !container) return;

    let marked = false;

    toggle.addEventListener('click', function () {
        // Always refresh list
        fetch('includes/ajax/get-notifications.php', {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.text())
        .then(html => container.innerHTML = html);

        // Mark all as read only once
        if (!marked && <?= $unread_count ?> > 0) {
            fetch('includes/ajax/mark-notifications-read.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(() => {
                document.querySelectorAll('.badge.rounded-pill.bg-danger').forEach(b => {
                    if (b.textContent.trim() !== 'New') {
                        b.textContent = '0';
                        b.classList.add('d-none');
                    }
                });
            });
            marked = true;
        }
    });
});
</script>
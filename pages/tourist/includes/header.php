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
                                <?php require_once "components/notification-list.php";  ?>
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
    console.log('Notification script loaded. Toggle:', toggle, 'Container:', container);
    
    if (!toggle || !container) {
        console.error('ERROR: toggle or container not found');
        return;
    }

    let marked = false;

    toggle.addEventListener('click', function () {
        console.log('Dropdown toggle clicked');
        
        // Always refresh list
        console.log('Fetching notifications from includes/ajax/get-notifications.php');
        fetch('includes/ajax/get-notifications.php', {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => {
            console.log('Refresh response status:', r.status);
            return r.text();
        })
        .then(html => {
            console.log('Refresh HTML received, updating container');
            container.innerHTML = html;
        })
        .catch(err => console.error('Refresh error:', err));

        // Mark all as read only once
        if (!marked && <?= $unread_count ?> > 0) {
            console.log('Marking all notifications as read. Unread count:', <?= $unread_count ?>);
            
            fetch('includes/ajax/mark-notifications-read.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => {
                console.log('Mark-all response status:', r.status);
                return r.json();
            })
            .then(data => {
                console.log('Mark-all response data:', data);
                if (data.success) {
                    // Hide the badge
                    document.querySelectorAll('.badge.rounded-pill.bg-danger').forEach(b => {
                        if (b.textContent.trim() !== 'New') {
                            console.log('Hiding badge, setting to 0');
                            b.textContent = '0';
                            b.classList.add('d-none');
                        }
                    });
                    
                    // Refresh the notification list to show updated status
                    console.log('Refreshing notification list after mark-all');
                    fetch('includes/ajax/get-notifications.php', {
                        credentials: 'same-origin',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(r => r.text())
                    .then(html => {
                        console.log('Notification list refreshed');
                        container.innerHTML = html;
                    })
                    .catch(err => console.error('Refresh after mark-all error:', err));
                } else {
                    console.error('Mark-all failed:', data);
                }
            })
            .catch(err => console.error('Mark-all error:', err));
            
            marked = true;
        }
    });

    // Handle individual notification clicks
    document.addEventListener('click', function(e) {
        const notifEl = e.target.closest('.mark-as-read');
        if (!notifEl) return;
        
        e.preventDefault();
        
        const activityId = notifEl.getAttribute('data-activity-id');
        const accountId = notifEl.getAttribute('data-account-id');
        
        console.log('Notification clicked - Activity ID:', activityId, 'Account ID:', accountId);

        if (!activityId || !accountId) {
            console.error('ERROR: Missing activity_id or account_id');
            return;
        }

        // Remove unread styling
        notifEl.classList.remove('bg-primary', 'bg-opacity-10', 'fw-semibold', 'border-start', 'border-primary', 'border-4');
        notifEl.classList.add('text-muted');
        
        // Remove "New" badge
        const newBadge = notifEl.querySelector('.badge.bg-danger');
        if (newBadge) newBadge.remove();

        console.log('Sending AJAX to mark-single-notification-read.php');
        
        fetch('includes/ajax/mark-single-notification-read.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                activity_id: activityId,
                account_id: accountId
            })
        })
        .then(r => {
            console.log('Mark-single response status:', r.status);
            return r.json();
        })
        .then(data => {
            console.log('Mark-single response data:', data);
            if (data.success) {
                console.log('Notification marked as read successfully');
            } else {
                console.error('Failed to mark notification as read:', data);
            }
        })
        .catch(err => console.error('Mark-single AJAX error:', err));
    });
});

</script>
<?php
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

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<head>
    <link rel="stylesheet" href="../../assets/node_modules/@fortawesome/fontawesome-free/css/all.min.css">
</head>
<style>
    
    :root {
        --primary-color: #ffffff;
        --secondary-color: #213638;
        --accent: #E5A13E; 
        --secondary-accent: #CFE7E5;
        --cancelled: #F44336; 
    }

    
    .navbar {
        background-color: var(--secondary-color) !important;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .navbar-brand {
        color: var(--primary-color) !important;
        font-weight: bold;
        font-size: 1.5rem;
    }

    
    .navbar-brand i {
        color: var(--accent);
    }

    
    .nav-link {
        color: var(--secondary-accent) !important;
        margin: 0 10px;
        transition: color 0.3s;
    }

    .nav-link:hover, .nav-item .active.nav-link {
        color: var(--accent) !important;
    }
    
    
    .notification-badge-custom {
        position: absolute;
        top: -5px;
        right: -5px;
        background-color: var(--cancelled); 
        color: white;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        font-size: 0.7rem;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10;
    }
    
    
    .btn-custom-accent-outline {
        color: var(--accent);
        border-color: var(--accent);
        font-weight: 600;
        transition: all 0.3s;
    }

    .btn-custom-accent-outline:hover {
        background-color: var(--accent);
        border-color: var(--accent);
        color: var(--secondary-color) !important;
    }

    
    #notification-dropdown .dropdown-header { position: sticky; top: 0; z-index: 10; }
    #notification-list-container { scrollbar-width: thin; scrollbar-color: var(--accent) var(--primary-color); }
    #notification-list-container::-webkit-scrollbar { width: 6px; }
    #notification-list-container::-webkit-scrollbar-track { background: var(--primary-color); border-radius: 10px; }
    #notification-list-container::-webkit-scrollbar-thumb { background: var(--accent); border-radius: 10px; }
    #notification-list-container::-webkit-scrollbar-thumb:hover { background: #d89435; } 

    
    .dropdown-menu {
        background-color: var(--primary-color);
    }
    .dropdown-item {
        color: var(--secondary-color);
    }
    .dropdown-item:hover {
        background-color: var(--secondary-accent);
        color: var(--secondary-color);
    }
</style>

<header class="header">
    <nav class="navbar navbar-expand-lg fixed-top shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-map-marked-alt"></i> Tourismo Zamboanga
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>" href="index.php">
                            <i class="fas fa-home me-1"></i> Home
                        </a>
                    </li>
                    
                    <li class="nav-item dropdown">
                        <?php  $travelPages = ['find-tour-packages.php', 'find-guide.php', 'tour-packages-view.php']; ?>
                        <a class="nav-link dropdown-toggle <?= in_array($currentPage, $travelPages) ? 'active' : '' ?>" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-search me-1"></i> Travel
                        </a>
                        <ul class="dropdown-menu shadow-sm">
                            <li><a class="dropdown-item" href="search-guide.php">Find Guide</a></li>
                            <li><a class="dropdown-item" href="search-tour-packages.php">Find Tour Packages</a></li>
                        </ul>
                    </li>

                    <?php  $bookingPages = ['booking.php', 'booking-view.php', 'booking-history.php', 'booking-add.php', 'booking-again.php']; ?>
                    <li class="nav-item">
                        <a class="nav-link <?= in_array($currentPage, $bookingPages) ? 'active' : '' ?>" href="booking.php">
                            <i class="fas fa-calendar-alt me-1"></i> My Booking
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'inbox.php' ? 'active' : '' ?>" href="inbox.php">
                            <i class="fas fa-message me-1"></i> Message
                        </a>
                    </li>

                    <li class="nav-item dropdown position-relative">
                        <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" 
                            href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"
                            id="notification-toggle">
                            <i class="fas fa-bell"></i>
                            <span class="notification-badge-custom <?= $badge_display ?>">
                                <?= $unread_count ?>
                                <span class="visually-hidden">unread</span>
                            </span>
                        </a>

                        <ul id="notification-dropdown" class="dropdown-menu dropdown-menu-end mt-2 shadow-lg border-0" style="width: 380px; max-width: 95vw;">
                            <li class="dropdown-header bg-primary text-white py-3 rounded-top" style="background-color: var(--secondary-color) !important;">
                                <div class="d-flex justify-content-between align-items-center px-3">
                                    <strong>Notifications</strong>
                                    <span class="badge bg-light text-primary" style="color: var(--secondary-color) !important; background-color: var(--secondary-accent) !important;"><?= count($touristNotification) ?></span>
                                </div>
                            </li>

                            <div id="notification-list-container" class="px-2" style="max-height: 70vh; overflow-y: auto; overflow-x: hidden;">
                                <?php require_once "components/notification-list.php";  ?>
                            </div>

                            <li><hr class="dropdown-divider my-0"></li>
                            <li><a class="dropdown-item text-center py-3 fw-bold" href="notifications.php" style="color: var(--secondary-color) !important;">View All Notifications</a></li>
                        </ul>
                    </li>
                    
                    <?php if ($account_ID): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $currentPage === 'profile.php' ? 'active' : '' ?>" href="dashboard-profile.php">
                                <i class="fas fa-user-circle me-1"></i> Profile
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>

                <?php if ($account_ID): ?>
                    <a href="logout.php" class="btn btn-custom-accent-outline ms-lg-3">Log out</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-custom-accent-outline ms-lg-3">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
</header>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.getElementById('notification-toggle');
    const container = document.getElementById('notification-list-container');
    
    if (!toggle || !container) {
        console.error('ERROR: toggle or container not found');
        return;
    }

    let marked = false;

    toggle.addEventListener('click', function () {
        // 1. Always refresh list
        fetch('includes/ajax/get-notifications.php', {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.text())
        .then(html => container.innerHTML = html)
        .catch(err => console.error('Refresh error:', err));

        // 2. Mark all as read only once
        if (!marked && <?= $unread_count ?> > 0) {
            fetch('includes/ajax/mark-notifications-read.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    // Hide the custom badge (using the new custom class)
                    document.querySelectorAll('.notification-badge-custom').forEach(b => {
                        b.textContent = '0';
                        b.classList.add('d-none');
                    });
                    
                    // Refresh the notification list to show updated status
                    fetch('includes/ajax/get-notifications.php', {
                        credentials: 'same-origin',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(r => r.text())
                    .then(html => container.innerHTML = html)
                    .catch(err => console.error('Refresh after mark-all error:', err));
                } else {
                    console.error('Mark-all failed:', data);
                }
            })
            .catch(err => console.error('Mark-all error:', err));
            
            marked = true;
        }
    });

    // Handle individual notification clicks (KEPT INTACT)
    document.addEventListener('click', function(e) {
        const notifEl = e.target.closest('.mark-as-read');
        if (!notifEl) return;
        
        e.preventDefault();
        
        const activityId = notifEl.getAttribute('data-activity-id');
        const accountId = notifEl.getAttribute('data-account-id');
        
        if (!activityId || !accountId) return;

        // Remove unread styling
        notifEl.classList.remove('bg-primary', 'bg-opacity-10', 'fw-semibold', 'border-start', 'border-primary', 'border-4');
        notifEl.classList.add('text-muted');
        
        // Remove "New" badge
        const newBadge = notifEl.querySelector('.badge.bg-danger');
        if (newBadge) newBadge.remove();

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
        .then(r => r.json())
        .then(data => {
            if (!data.success) console.error('Failed to mark notification as read:', data);
        })
        .catch(err => console.error('Mark-single AJAX error:', err));
    });
});
</script>
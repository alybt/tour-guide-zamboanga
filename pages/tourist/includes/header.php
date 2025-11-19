<?php
    // === Start session safely ===
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $account_ID = $_SESSION['account_ID'] ?? null;

    $touristNotification = [];
    $unread_count = 0;
    $badge_display = 'd-none';

    if ($account_ID && is_numeric($account_ID)) {
        require_once __DIR__ . "/../../../classes/booking.php";
        require_once __DIR__ . "/../../../classes/activity-log.php";

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
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>" 
                           href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'booking.php' ? 'active' : '' ?>" 
                           href="booking.php">My Booking</a>
                    </li>

                    <!-- Notification Dropdown with Scroll -->
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

                        <!-- Scrollable Dropdown Menu -->
                        <ul id="notification-dropdown" 
                            class="dropdown-menu dropdown-menu-end mt-2 shadow-lg border-0" 
                            style="width: 380px; max-width: 95vw;">

                            <li class="dropdown-header bg-primary text-white py-3 rounded-top">
                                <div class="d-flex justify-content-between align-items-center px-3">
                                    <strong>Notifications</strong>
                                    <span class="badge bg-light text-primary"><?= count($touristNotification) ?></span>
                                </div>
                            </li>

                            <!-- Scrollable Notification List -->
                            <div class="px-2" style="max-height: 70vh; overflow-y: auto; overflow-x: hidden;">
                                <?php if (empty($touristNotification)): ?>
                                    <li>
                                        <div class="text-center text-muted py-5">
                                            <i class="bi bi-bell-slash fs-2 mb-3 text-muted"></i>
                                            <div>No notifications yet</div>
                                        </div>
                                    </li>
                                <?php else: ?>
                                    <?php foreach ($touristNotification as $notif): 
                                        $isUnread = (int)$notif['is_viewed'] === 0;
                                    ?>
                                        <li>
                                            <a class="dropdown-item py-3 rounded-3 mb-1 <?= $isUnread ? 'bg-primary bg-opacity-10 fw-semibold border-start border-primary border-4' : 'text-muted' ?> mark-as-read"
                                               href="javascript:void(0)"
                                               data-activity-id="<?= $notif['activity_ID'] ?>"
                                               data-account-id="<?= $account_ID ?>">
                                                <div class="d-flex">
                                                    <div class="me-3 mt-1">
                                                        <?php 
                                                        $action = strtolower($notif['action_name']);
                                                        if (str_contains($action, 'booking')): ?>
                                                            <i class="bi bi-calendar-check-fill text-primary fs-5"></i>
                                                        <?php elseif (str_contains($action, 'payment')): ?>
                                                            <i class="bi bi-credit-card-fill text-success fs-5"></i>
                                                        <?php elseif (str_contains($action, 'message')): ?>
                                                            <i class="bi bi-chat-dots-fill text-info fs-5"></i>
                                                        <?php else: ?>
                                                            <i class="bi bi-bell-fill text-secondary fs-5"></i>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <div class="small <?= $isUnread ? 'text-dark' : '' ?>"> 
                                                            <?= htmlspecialchars($notif['action_name']) ?> · 
                                                            <?= date('M j, Y · g:i A', strtotime($notif['activity_timestamp'])) ?>
                                                        </div>
                                                        <div class="text-muted xsmall mt-1">
                                                            <?php 
                                                            $pattern = '/^(\d+)\s+(.+?)\s+(\d+)$/i';
                                                            $m =[];
                                                            $desc   = trim($n['activity_description'] ?? '');
                                                            preg_match('/^(\d+)\s+(.+?)\s+(\d+)$/i', $desc, $m);
                                                            $booking_ID = (int)$m[3];
                                                            $message = ''; 
                                                            $action = $notif['action_name']; 
                                                            $tourName = $bookingObj->getTourPackageDetailsByBookingID($booking_ID);

                                                            switch ($action) {
                                                                case 'Books':
                                                                case 'Booking':
                                                                    $message = "You booked <strong>". $tourName['tourpackage_name'] ."</strong>";
                                                                    break;

                                                                case 'Cancel Booking':
                                                                    $message = "You cancelled your booking for <strong>". $tourName['tourpackage_name'] ."</strong>";
                                                                    break;

                                                                case 'Payment':
                                                                    $message = "You paid for <strong>". $tourName['tourpackage_name'] ."</strong>";
                                                                    break;

                                                                case 'Refund Booking':
                                                                    $message = "Your booking for <strong>". $tourName['tourpackage_name'] ."</strong> has been refunded";
                                                                    break;

                                                                default:
                                                                   $message = 'Hmmm';
                                                                   break;
                                                            }
                                                            ?>
                                                            <?= $message ?> 
                                                        </div>
                                                    </div>
                                                    <?php if ($isUnread): ?>
                                                        <div class="ms-2">
                                                            <span class="badge bg-danger rounded-pill">New</span>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <li><hr class="dropdown-divider my-0"></li>
                            <li>
                                <a class="dropdown-item text-center py-3 text-primary fw-bold" href="notifications.php">
                                    View All Notifications
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>

                <?php if ($account_ID): ?>
                    <a href="logout.php" class="btn btn-outline-info ms-lg-3">Log out</a>
                <?php else: ?>
                    <a href="../../login.php" class="btn btn-outline-light ms-lg-3">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
</header>

<!-- Custom CSS for smooth scrollbar -->
<style>
    #notification-dropdown .dropdown-header {
        position: sticky;
        top: 0;
        z-index: 10;
    }
    #notification-dropdown > div {
        /* Custom scrollbar */
        scrollbar-width: thin;
        scrollbar-color: #0d6efd #f1f1f1;
    }
    #notification-dropdown > div::-webkit-scrollbar {
        width: 6px;
    }
    #notification-dropdown > div::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }
    #notification-dropdown > div::-webkit-scrollbar-thumb {
        background: #0d6efd;
        border-radius: 10px;
    }
    #notification-dropdown > div::-webkit-scrollbar-thumb:hover {
        background: #0b5ed7;
    }
</style>

<script>
    // Mark all notifications as read when dropdown is opened
document.addEventListener('DOMContentLoaded', function () {
    const dropdownToggle = document.querySelector('.dropdown-toggle[aria-expanded]');
    const dropdownMenu = document.querySelector('#notification-dropdown');

    if (!dropdownToggle || !dropdownMenu) return;

    let hasMarkedAsRead = false;

    dropdownToggle.addEventListener('click', function () {
        // Only mark as read once per page load (when dropdown first opens)
        if (hasMarkedAsRead) return;
        
        const unreadCount = <?= $unread_count ?>;

        if (unreadCount > 0) {
            fetch('includes/ajax/mark-notifications-read.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    hasMarkedAsRead = true;
                    
                    // Remove all "New" badges and bg-light
                    document.querySelectorAll('.mark-as-read').forEach(item => {
                        item.classList.remove('bg-light', 'fw-semibold');
                    });
                    document.querySelectorAll('.badge.bg-danger').forEach(badge => {
                        if (badge.textContent.trim() === 'New') {
                            badge.remove();
                        }
                    });

                    // Hide the red counter badge
                    const counterBadge = document.querySelector('.badge.rounded-pill.bg-danger');
                    if (counterBadge) {
                        counterBadge.classList.add('d-none');
                        counterBadge.textContent = '0';
                    }
                }
            })
            .catch(err => {
                console.error('Failed to mark notifications as read:', err);
            });
        }
    });
});
</script>
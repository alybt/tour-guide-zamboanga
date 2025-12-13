<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$account_ID = $_SESSION['account_ID'] ?? null;
if (!$account_ID || !is_numeric($account_ID)) {
    echo '<div class="text-center text-muted py-5"><div>No notifications yet</div></div>';
    exit;
}

require_once "../../../../classes/booking.php";
require_once "../../../../classes/activity-log.php";

try {
    $bookingObj  = new Booking();
    $activityObj = new ActivityLogs();
    $touristNotification = $activityObj->touristNotification((int)$account_ID);

 
    include __DIR__ . '/../components/notification-list.php';
} catch (Throwable $e) {
    error_log("AJAX notification error: " . $e->getMessage());
    echo '<div class="text-danger text-center py-5">Error loading notifications</div>';
}
exit;
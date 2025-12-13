<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role_name'] !== 'Tour Guide') {
    header('Location: ../../../../index.php');
    exit;
} else if ($_SESSION['user']['account_status'] == 'Suspended'){
    header('Location: ../../account-suspension.php');
    exit;
} else if ($_SESSION['user']['account_status'] == 'Pending'){
    header('Location: ../../account-pending.php');
}
require_once "../../../../classes/guide.php";
require_once "../../../../classes/booking.php";
require_once "../../../../classes/activity-log.php";

if (isset($_GET['id']) && isset($_SESSION['user'])) {
    $booking_ID = $_GET['id'];
    $bookingObj = new Booking();
    $guideObj = new Guide();
    $activityLogObj = new ActivityLogs();
    $guide_ID = $guideObj->getGuide_ID($_SESSION['user']['account_ID']);
    $results = $bookingObj->updateBookingStatus_Complete($booking_ID);
    $activity =$activityLogObj->guideMarkedCompleted($booking_ID, $guide_ID);
    if ($results) {
        $_SESSION['success'] = "Booking marked as completed.";
        
    } else {
        $_SESSION['error'] = "Failed to completing the booking.";
    }

    header("Location: ../../dashboard.php");
    exit;
}
?>

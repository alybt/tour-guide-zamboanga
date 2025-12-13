<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role_name'] !== 'Tourist') {
    header('Location: ../../index.php');
    exit;
}

require_once "../../classes/booking.php";
require_once "../../classes/activity-log.php";

$activityObj = new ActivityLogs();

if (isset($_GET['id']) && isset($_GET['type'])) {
    $booking_ID = intval($_GET['id']);
    $type = $_GET['type'];
    $account_ID = $_SESSION['user']['account_ID'];

    $bookingObj = new Booking();

    if ($type === 'norefund') {
        $result = $bookingObj->cancelBookingNoRefund($booking_ID, $account_ID);
    } else {
        $_SESSION['error'] = "Invalid cancel type.";
        header("Location: booking.php");
        exit;
    }

    if ($result) {
        $action = $activityObj->touristCancelBooking($booking_ID, $account_ID);
        $_SESSION['success'] = "Booking cancelled without refund.";
    } else {
        $_SESSION['error'] = "Failed to cancel booking.";
    }

    header("Location: booking.php");
    exit;
}
?>

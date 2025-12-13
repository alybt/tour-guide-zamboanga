<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role_name'] !== 'Tourist') {
    header('Location: ../../index.php');
    exit;
}

require_once "../../classes/booking.php";
require_once "../../classes/activity-log.php";

$activityObj = new ActivityLogs();

if (isset($_GET['id']) && isset($_SESSION['user'])) {
    $booking_ID = intval($_GET['id']);
    $account_ID = $_SESSION['user']['account_ID'];

    $bookingObj = new Booking();
    $booking = $bookingObj->getBookingDetailsByBooking($booking_ID);

    if (!$booking) {
        $_SESSION['error'] = "Booking not found.";
        header("Location: booking.php");
        exit;
    }

    $status = $booking['booking_status'];
    $startDate = new DateTime($booking['booking_start_date']);
    $now = new DateTime();
    $interval = $now->diff($startDate);
    $hoursToStart = ($interval->days * 24) + $interval->h;

    $results = false;

    // ✅ 1. Pending for Payment → direct cancel
    if ($status === 'Pending for Payment') {
        $results = $bookingObj->cancelBookingIfPendingForPayment($booking_ID, $account_ID);
    }

    // ✅ 2. Pending for Approval / Approved / Waiting for the Schedule Date (>1 day)
    else if (in_array($status, ['Pending for Approval', 'Approved', 'Waiting for the Schedule Date']) && $hoursToStart >= 24) {
        echo "
        <script>
            if (confirm('Are you going to request a refund?')) {
                window.location.href = 'refund-form.php?id={$booking_ID}';
            } else {
                window.location.href = 'booking-cancel-action.php?id={$booking_ID}&type=norefund';
            }
        </script>
        ";
        exit;
    }

    // ✅ 3. Waiting for the Schedule Date (less than 1 day) → auto cancel, no refund
    else if ($status === 'Waiting for the Schedule Date' && $hoursToStart < 24) {
        $results = $bookingObj->cancelBookingNoRefund($booking_ID, $account_ID);
    }

    if ($results) {
        $action = $activityObj->touristCancelBooking($booking_ID, $account_ID);
        $_SESSION['success'] = "Booking successfully cancelled.";
    } else {
        $_SESSION['error'] = "Failed to cancel booking.";
    }

    header("Location: booking.php");
    exit;
}
?>

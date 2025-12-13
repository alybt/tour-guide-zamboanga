<?php
require_once "../../classes/booking.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $guide_ID = intval($_POST['guide_ID']);
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';

    if (empty($guide_ID) || empty($start_date) || empty($end_date)) {
        echo json_encode(['error' => 'Missing required data.']);
        exit;
    }

    $bookingObj = new Booking();
    $bookings = $bookingObj->existingBookingsInGuide($guide_ID);
    $hasOverlap = false;

    foreach ($bookings as $b) {
        $existingStart = strtotime($b['booking_start_date']);
        $existingEnd = strtotime($b['booking_end_date']);
        $newStart = strtotime($start_date);
        $newEnd = strtotime($end_date);

        // Overlap logic
        if ($newStart <= $existingEnd && $existingStart <= $newEnd) {
            $hasOverlap = true;
            break;
        }
    }

    echo json_encode(['overlap' => $hasOverlap]);
    exit;
}
?>

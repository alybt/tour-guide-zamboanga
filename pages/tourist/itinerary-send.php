<?php
require_once "../../classes/tour-manager.php";
require_once "../../classes/guide.php";
require_once "../../classes/tourist.php";
require_once "../../classes/booking.php";
require_once "../../classes/mailer.php";  // Your Mailer class

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid booking ID");
}

$booking_ID = intval($_GET['id']);
$bookingObj = new Booking();
$tourManager = new TourManager();
$guideObj = new Guide();
$touristObj = new Tourist();

// Fetch all data (same as in itinerary-view.php)
$booking = $bookingObj->getBookingWithDetails($booking_ID);
if (!$booking) {
    die("Booking not found");
}

$tourist_ID = $booking['tourist_ID'];
$tourist = $touristObj->getTouristByID($tourist_ID);

$package = $tourManager->getTourPackageDetailsByID($booking['tourpackage_ID']);
$guide = $booking['guide_ID'] ? $guideObj->getGuideByID($booking['guide_ID']) : null;
$companions = $bookingObj->getCompanionsByBookingID($booking_ID);
$spots = $tourManager->getSpotsByPackage($booking['tourpackage_ID']);
$payment = $bookingObj->getPaymentInfoByBookingID($booking_ID);

// Calculate duration
$start = new DateTime($booking['booking_start_date']);
$end = new DateTime($booking['booking_end_date']);
$duration = $start->diff($end)->days + 1;

// Capture the full HTML output into a variable
ob_start();
include "itinerary-view.php"; // This uses all variables above and outputs full HTML
$itineraryHTML = ob_get_clean();

// Now send the email
$mailer = new Mailer();

$toEmail = $tourist['tourist_email'] ?? 'tourist@example.com';
$toName  = $tourist['tourist_name'] ?? 'Valued Traveler';

$subject = "Your Travel Itinerary - Booking #{$booking_ID}";

$result = $mailer->send($toEmail, $toName, $subject, $itineraryHTML);

if ($result['success']) {
    $_SESSION['success'] = "Itinerary sent successfully to your email!";
} else {
    $_SESSION['error'] = "Failed to send email: " . $result['message'];
}

header("Location: itinerary-view.php?id={$booking_ID}");
exit;
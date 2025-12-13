<?php
require_once "../../classes/mailer.php";  // Your Mailer class

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid booking ID");
}

$username   = $_SESSION['new_username']   ?? 'Tourist';
$emailSent  = $_SESSION['email_sent']    ?? false;
$emailError = $_SESSION['email_error']   ?? '';

// Clean session
unset($_SESSION['new_username'], $_SESSION['email_sent'], $_SESSION['email_error']);



$tourist_ID = $booking['tourist_ID'];
$tourist = $touristObj->getTouristByID($tourist_ID);

ob_start();
include "registration-sucess.php"; // This uses all variables above and outputs full HTML
$registrationHTML = ob_get_clean();

// Now send the email
$mailer = new Mailer();

$toEmail = $tourist['tourist_email'] ?? 'tourist@example.com';
$toName  = $tourist['tourist_name'] ?? 'Valued Traveler';

$subject = "Registration Successful";

$result = $mailer->send($toEmail, $toName, $subject, $registrationHTML);

if ($result['success']) {
    $_SESSION['success'] = "Registration sent successfully to your email!";
} else {
    $_SESSION['error'] = "Failed to send email: " . $result['message'];
}

header("Location: itinerary-view.php?id={$booking_ID}");
exit;
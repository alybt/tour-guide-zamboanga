<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role_name'] !== 'Tourist') {
    container-header('Location: ../../index.php');
    exit;
}

require_once "../../classes/tour-manager.php";
require_once "../../classes/guide.php";
require_once "../../classes/tourist.php";
require_once "../../classes/booking.php";

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "Invalid booking ID.";
    container-header("Location: my-bookings.php");
    exit();
}

$booking_ID = intval($_GET['id']);
$tourist_ID = $_SESSION['user']['account_ID'];

$bookingObj = new Booking();
$tourManager = new TourManager();
$guideObj = new Guide();
$touristObj = new Tourist();

// Get booking details
$booking = $bookingObj->getBookingWithDetails($booking_ID);

// if (!$booking || $booking['tourist_ID'] != $tourist_ID) {
//     $_SESSION['error'] = "Booking not found or unauthorized.";
//     container-header("Location: my-bookings.php");
//     exit();
// }

// Get tour package details
$package = $tourManager->getTourPackageDetailsByID($booking['tourpackage_ID']);

// Get guide details
$guide = $guideObj->getGuideByID($booking['guide_ID']);

// Get companions
$companions = $bookingObj->getCompanionsByBookingID($booking_ID);

// Get tour spots
$spots = $tourManager->getSpotsByPackage($booking['tourpackage_ID']);

// Get tourist details
$tourist = $touristObj->getTouristByID($tourist_ID);

// Calculate duration
$start = new DateTime($booking['booking_start_date']);
$end = new DateTime($booking['booking_end_date']);
$duration = $start->diff($end)->days + 1;

// Get payment info if exists
$payment = $bookingObj->getPaymentInfoByBookingID($booking_ID);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Itinerary - #<?= $booking_ID ?></title>
    <link rel="stylesheet" href="../../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/vendor/bootstrap-icons/bootstrap-icons.css" >
    <link rel="stylesheet" href="../../assets/css/tourist/itinerary-view.css">
    <link rel="stylesheet" href="../../assets/css/tourist/header.css">
    
</head>
<body>
<?php 
    require_once "includes/header.php"; 
    include_once "includes/header.php";
?>
<main>
    <div class="container-main">
        <div class="container-header">
            <h1>🌴 Your Travel Itinerary</h1>
            <p>Everything you need for your upcoming adventure</p>
            <div class="booking-ref">
                Booking #<?= str_pad($booking_ID, 6, '0', STR_PAD_LEFT) ?>
            </div>
        </div>

        <div class="content">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    ✓ <?= htmlspecialchars($_SESSION['success']) ?>
                    <?php unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <!-- Booking Status -->
            <div style="text-align: center;">
                <span class="status-badge status-<?= strtolower(str_replace(' ', '-', $booking['booking_status'])) ?>">
                    <?= htmlspecialchars($booking['booking_status']) ?>
                </span>
            </div>

            <!-- Tour Package Information -->
            <div class="section">
                <div class="section-title">📦 Tour Package Details</div>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Package Name</div>
                        <div class="info-value"><?= htmlspecialchars($package['tourpackage_name']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Duration</div>
                        <div class="info-value"><?= $duration ?> Day<?= $duration > 1 ? 's' : '' ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Total Amount</div>
                        <div class="info-value">
                            <?= htmlspecialchars($package['pricing_currency']) ?> 
                            <?= number_format($payment['paymentinfo_total_amount'] ?? $package['pricing_foradult'], 2) ?>
                        </div>
                    </div>
                </div>
                <div style="margin-top: 15px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                    <div class="info-label">Description</div>
                    <p style="margin-top: 5px; color: #333;"><?= htmlspecialchars($package['tourpackage_desc']) ?></p>
                </div>
            </div>

            <!-- Travel Dates -->
            <div class="section">
                <div class="section-title">📅 Travel Schedule</div>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Departure Date</div>
                        <div class="info-value">
                            <?= date('F d, Y', strtotime($booking['booking_start_date'])) ?>
                        </div>
                        <div style="margin-top: 5px; font-size: 14px; color: #6c757d;">
                            <?= date('l', strtotime($booking['booking_start_date'])) ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Return Date</div>
                        <div class="info-value">
                            <?= date('F d, Y', strtotime($booking['booking_end_date'])) ?>
                        </div>
                        <div style="margin-top: 5px; font-size: 14px; color: #6c757d;">
                            <?= date('l', strtotime($booking['booking_end_date'])) ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Guide Information -->
            <div class="section">
                <div class="section-title">👤 Your Tour Guide</div>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Guide Name</div>
                        <div class="info-value"><?= htmlspecialchars($guide['guide_name'] ?? '') ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Contact Number</div>
                        <div class="info-value"><?= htmlspecialchars($guide['guide_phonenumber'] ?? '') ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Email</div>
                        <div class="info-value"><?= htmlspecialchars($guide['guide_email'] ?? '')  ?></div>
                    </div>
                </div>
            </div>

            <!-- Travelers -->
            <div class="section">
                <div class="section-title">👥 Travelers</div>
                <div class="companion-list">
                    <?php if ($booking['booking_isselfincluded']): ?>
                        <div class="companion-card">
                            <div class="companion-name">
                                <?= htmlspecialchars($tourist['tourist_name']) ?> (You)
                            </div>
                            <div class="companion-category">Primary Traveler</div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($companions)): ?>
                        <?php foreach ($companions as $companion): ?>
                            <div class="companion-card">
                                <div class="companion-name">
                                    <?= htmlspecialchars($companion['companion_name']) ?>
                                </div>
                                <div class="companion-category">
                                    <?= htmlspecialchars($companion['companion_category_name']) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div style="margin-top: 15px; padding: 10px; background: #e7f3ff; border-radius: 8px; font-size: 14px;">
                    <strong>Total Travelers:</strong> 
                    <?= ($booking['booking_isselfincluded'] ? 1 : 0) + count($companions) ?> person(s)
                </div>
            </div>

            <!-- Tour Spots -->
            <?php if (!empty($spots)): ?>
            <div class="section">
                <div class="section-title">🗺️ Places You'll Visit</div>
                <div class="spot-list">
                    <?php foreach ($spots as $index => $spot): ?>
                        <div class="spot-item">
                            <div class="spot-name">
                                Day <?= $index + 1 ?>: <?= htmlspecialchars($spot['spots_name']) ?>
                            </div>
                            <div class="spot-desc">
                                <?= htmlspecialchars($spot['spots_description']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Payment Information -->
            <?php if ($payment): ?>
            <div class="section">
                <div class="section-title">💳 Payment Information</div>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Payment Status</div>
                        <div class="info-value"><?= htmlspecialchars($payment['payment_status'] ?? 'Pending') ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Total Paid</div>
                        <div class="info-value">
                            <?= htmlspecialchars($package['pricing_currency']) ?> 
                            <?= number_format($payment['total_amount'] ?? 0, 2) ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Payment Date</div>
                        <div class="info-value">
                            <?= date('M d, Y', strtotime($payment['payment_date'] ?? 'now')) ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Important Notes -->
            <div class="section">
                <div class="section-title">📌 Important Reminders</div>
                <div class="timeline">
                    <div class="timeline-item">
                        <div class="timeline-content">
                            <div class="timeline-title">Before Departure</div>
                            <div class="timeline-desc">
                                Please arrive at the meeting point 30 minutes before departure time.
                            </div>
                        </div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-content">
                            <div class="timeline-title">What to Bring</div>
                            <div class="timeline-desc">
                                Valid ID, comfortable clothing, sunscreen, and personal medications.
                            </div>
                        </div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-content">
                            <div class="timeline-title">Contact Information</div>
                            <div class="timeline-desc">
                                Keep your guide's contact number handy for emergencies.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
            <button onclick="window.print()" class="btn btn-primary">
            Print Itinerary
            </button>

            <!-- New Button -->
            <a href="itinerary-send.php?id=<?= $booking_ID ?>" 
            class="btn btn-primary" 
            onclick="this.innerHTML='Sending...'; this.style.opacity='0.7';">
            Send to Email
            </a>

            <a href="my-bookings.php" class="btn btn-secondary">
                Back to My Bookings
            </a>
                </div>
        </div>
    </div>
</main>
<script src="../../assets/vendor/twbs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/vendor/components/jquery/jquery.min.js"></script> 
<script src="../../assets/node_modules/@fullcalendar/core/index.global.min.js"></script> 
<script src="../../assets/node_modules/@fullcalendar/daygrid/index.global.min.js"></script>
</body>
</html>
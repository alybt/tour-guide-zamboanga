<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role_name'] !== 'Tourist') {
    header('Location: ../../index.php');
    exit;
}

require_once "../../classes/tour-manager.php";
require_once "../../classes/guide.php";
require_once "../../classes/tourist.php";
require_once "../../classes/booking.php";

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "Invalid booking ID.";
    header("Location: my-bookings.php");
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
//     header("Location: my-bookings.php");
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
    <style>
        :root{
            --primary-color: #ffffff;
            --secondary-color: #213638;
            --accent: #E5A13E;
            --secondary-accent: #CFE7E5;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--secondary-color) 0%, var(--secondary-color) 100%);
            padding: 20px;
            min-height: 100vh;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, var(--secondary-accent) 0%, var(--accent) 100%);
            color: var(--secondary-color);
            padding: 40px 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 16px;
            opacity: 0.9;
        }

        .booking-ref {
            color: white;
            background: var(--secondary-color);
            display: inline-block;
            padding: 8px 20px;
            border-radius: 20px;
            margin-top: 15px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .content {
            padding: 30px;
        }

        .status-badge {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-approved {
            background: #d4edda;
            color: #155724;
        }

        .status-completed {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .section {
            margin-bottom: 30px;
            padding-bottom: 30px;
            border-bottom: 2px solid #f0f0f0;
        }

        .section:last-child {
            border-bottom: none;
        }

        .section-title {
            font-size: 20px;
            color: #667eea;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title::before {
            content: '';
            width: 4px;
            height: 24px;
            background: #667eea;
            border-radius: 2px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 15px;
        }

        .info-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .info-label {
            font-size: 12px;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 16px;
            color: #333;
            font-weight: 600;
        }

        .timeline {
            position: relative;
            padding-left: 30px;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 8px;
            top: 10px;
            bottom: 10px;
            width: 2px;
            background: #e0e0e0;
        }

        .timeline-item {
            position: relative;
            padding-bottom: 20px;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -25px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #667eea;
            border: 3px solid white;
            box-shadow: 0 0 0 2px #667eea;
        }

        .timeline-content {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
        }

        .timeline-title {
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .timeline-desc {
            color: #6c757d;
            font-size: 14px;
        }

        .companion-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .companion-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }

        .companion-name {
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .companion-category {
            font-size: 14px;
            color: #6c757d;
        }

        .spot-list {
            margin-top: 15px;
        }

        .spot-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            border-left: 4px solid #764ba2;
        }

        .spot-name {
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .spot-desc {
            color: #6c757d;
            font-size: 14px;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .container {
                box-shadow: none;
            }

            .action-buttons {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
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
</body>
</html>
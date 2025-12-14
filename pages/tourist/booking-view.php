<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role_name'] !== 'Tourist') {
    header('Location: ../../index.php');
    exit;
}

require_once "../../classes/tour-manager.php";
require_once "../../classes/guide.php";
require_once "../../classes/booking.php";
require_once "../../classes/tourist.php";



if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid booking ID.";
    header("Location: my-bookings.php");
    exit;
}

$booking_ID = (int)$_GET['id'];
$tourist_ID = $_SESSION['account_ID'];
$bookingObj = new Booking();
$tourManager = new TourManager();
$guideObj = new Guide();


$booking = $bookingObj->getBookingByIDAndTourist($booking_ID, $tourist_ID);


$package = $tourManager->getTourPackageDetailsByID($booking['tourpackage_ID']);
$guide = $guideObj->getGuideByID($package['guide_ID']);
$spots = $tourManager->getSpotsByPackage($package['tourpackage_ID']);
$companions = $bookingObj->getCompanionsByBooking($booking_ID);

$tourdetails = $bookingObj->getTourDetails($booking_ID);

$statusColor = match($booking['booking_status']) {
    'Pending for Payment' => 'bg-pending-for-payment',
    'Pending for Approval' => 'bg-pending-for-approval',
    'Approved' => 'bg-approved',
    'In Progress' => 'bg-in-progress',
    'Completed' => 'bg-completed',
    'Cancelled' => 'bg-cancelled',
    'Cancelled - No Refund' => 'bg-cancelled-no-refund',
    'Refunded' => 'bg-refunded',
    'Failed' => 'bg-failed',
    'Rejected by the Guide' => 'bg-rejected-by-guide',
    'Booking Expired — Payment Not Completed' => 'bg-booking-expired-payment-not-completed',
    'Booking Expired — Guide Did Not Confirm in Time' => 'bg-booking-expired-guide-did-not-confirm-in-time',
    default => 'bg-secondary'
};

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Details - Tourismo Zamboanga</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/vendor/bootstrap-icons/bootstrap-icons.css" >
    <link rel="stylesheet" href="../../assets/css/tourist/booking-view.css">
    <style>
        :root {
            --primary-color: #ffffff;
            --secondary-color: #213638;
            --accent: #E5A13E;
            --secondary-accent: #CFE7E5;
            --approved: #3A8E5C;
            --in-progress: #009688;
        }

        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .navbar {
            background-color: var(--secondary-color) !important;
        }

        .navbar-brand {
            color: white !important;
            font-weight: bold;
        }

        .booking-header {
            background: linear-gradient(135deg, var(--secondary-color) 0%, #2d4a4d 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
        }

        .status-badge {
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: 600;
        }

        .status-approved {
            background-color: var(--approved);
            color: white;
        }

        .detail-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .detail-card h5 {
            color: var(--secondary-color);
            font-weight: bold;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--accent);
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #6c757d;
            font-weight: 600;
        }

        .info-value {
            color: var(--secondary-color);
            font-weight: 600;
        }

        .guide-info {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 20px;
            background: var(--secondary-accent);
            border-radius: 12px;
            margin-bottom: 20px;
        }

        .guide-img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
        }

        .btn-primary {
            background-color: var(--accent);
            border: none;
            color: var(--secondary-color);
            font-weight: 600;
        }

        .btn-primary:hover {
            background-color: #d89435;
        }

        .timeline {
            position: relative;
            padding-left: 40px;
        }

        .timeline-item {
            position: relative;
            padding-bottom: 30px;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -32px;
            top: 0;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background-color: var(--accent);
            border: 3px solid white;
            box-shadow: 0 0 0 3px var(--accent);
        }

        .timeline-item::after {
            content: '';
            position: absolute;
            left: -25px;
            top: 16px;
            width: 2px;
            height: calc(100% - 16px);
            background-color: var(--accent);
        }

        .timeline-item:last-child::after {
            display: none;
        }

        .map-container {
            height: 300px;
            background: #e0e0e0;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
        }
    </style>
</head>
<body>
    <?php require_once "includes/header.php"; ?>
    <?php include_once "includes/header.php"; ?>

    <div class="booking-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-2">Booking #<?= str_pad($booking_ID, 5, '0', STR_PAD_LEFT) ?></h6>
                    <h1><?= htmlspecialchars($package['tourpackage_name']) ?></h1>
                </div>
                <span class="status-badge status-approved <?= $statusColor ?>">
                    <i class="fas fa-check-circle me-2"></i> <?= htmlspecialchars($booking['booking_status']) ?>
                </span>
            </div>
        </div>
    </div>

    <div class="container mb-5">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <div class="row">
            <div class="col-md-8">
                <!-- Tour Details -->
                <div class="detail-card">
                    <h5><i class="fas fa-info-circle me-2"></i> Tour Details</h5>
                    <div class="info-row">
                        <span class="info-label">Date</span>
                        <span class="info-value">
                            <?= date('F j, Y', strtotime($booking['booking_start_date'])) ?> - <?= date('F j, Y', strtotime($booking['booking_end_date'])) ?>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Time</span>
                        <span class="info-value"><?= date('h:i A', strtotime($tourdetails['time_start'])) ?> - <?= date('h:i A', strtotime($tourdetails['time_end'])) ?> (<?= number_format($tourdetails['total_hours'], 1) ?> hour(s))
                        </span>
                    </div>
                    <?php 
                        $selfCount = 0;
                        if (isset($booking['is_selfIncluded']) && $booking['is_selfIncluded']) {
                            $selfCount = 1;
                        } elseif (isset($booking['booking_isselfIncluded']) && $booking['booking_isselfIncluded']) {
                            $selfCount = 1;
                        }
                        $totalPeople = $selfCount + count($companions);
                    ?>
                    <div class="info-row">
                        <span class="info-label">Number of People</span>
                        <span class="info-value"><?= $totalPeople ?> Person(s)</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Language</span>
                        <span class="info-value"><?= $tourdetails['guide_languages']?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Meeting Point</span>
                        <span class="info-value"><?= $tourdetails['meeting_name']?></span>
                    </div>
                </div>

                <!-- Guide Information -->
                <div class="detail-card">
                    <h5><i class="fas fa-user-tie me-2"></i> Your Guide</h5>
                    <div class="guide-info">
                        <img src="https://i.pravatar.cc/150?img=33" alt="Guide" class="guide-img">
                        <div class="flex-grow-1">
                            <h5 class="mb-1"><?= htmlspecialchars($guide['guide_name'] ?? 'Not Assigned') ?></h5>
                            <div class="text-warning mb-2">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <span class="text-muted">(156 reviews)</span>
                            </div>
                            <p class="mb-0 text-muted">
                                <i class="fas fa-language me-2"></i>English, Italian, Spanish
                            </p>
                        </div>
                        <div>
                            <button class="btn btn-primary btn-sm mb-2">
                                <i class="fas fa-comment"></i> Message
                            </button>
                            <br>
                            <button class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-phone"></i> Call
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Itinerary -->
                <div class="detail-card">
                    <h5><i class="fas fa-route me-2"></i> Itinerary</h5>
                    <div class="timeline">
                        <?php if ($spots): ?>
                            <?php foreach ($spots as $spot): ?>
                                <div class="timeline-item">
                                    <strong><?= htmlspecialchars($spot['spots_name']) ?></strong>
                                    <p class="text-muted mb-0"><?= htmlspecialchars($spot['spots_description']) ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted">No spots listed.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Map -->
                <div class="detail-card">
                    <h5><i class="fas fa-map-marked-alt me-2"></i> Meeting Location</h5>
                    <div class="map-container">
                        <div class="text-center">
                            <i class="fas fa-map-marker-alt fa-3x mb-3"></i>
                            <p>Map: Colosseum Main Entrance<br>Piazza del Colosseo, Rome</p>
                            <button class="btn btn-primary">
                                <i class="fas fa-directions me-2"></i> Get Directions
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Booking Summary -->
                <div class="detail-card">
                    <h5><i class="fas fa-receipt me-2"></i> Booking Summary</h5>
                    <div class="info-row">
                        <span class="info-label">Tour Price</span>
                        <span class="info-value"></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Service Fee</span>
                        <span class="info-value"></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Discount</span>
                        <span class="info-value text-success"></span>
                    </div>
                    <div class="info-row" style="border-top: 2px solid var(--accent); margin-top: 15px; padding-top: 15px;">
                        <span class="info-label"><strong>Total Paid</strong></span>
                        <span class="info-value" style="font-size: 1.3rem; color: var(--accent);"></span>
                    </div>
                </div>

                <!-- Actions -->
                <div class="detail-card">
                    <h5><i class="fas fa-tools me-2"></i> Actions</h5>
                    <button class="btn btn-primary w-100 mb-2">
                        <i class="fas fa-download me-2"></i> Download Ticket
                    </button>
                    <button class="btn btn-outline-primary w-100 mb-2">
                        <i class="fas fa-calendar me-2"></i> Add to Calendar
                    </button>
                    <button class="btn btn-outline-primary w-100 mb-2">
                        <i class="fas fa-edit me-2"></i> Modify Booking
                    </button>
                    <button class="btn btn-outline-danger w-100">
                        <i class="fas fa-times me-2"></i> Cancel Booking
                    </button>
                    <?php if ($booking['booking_status'] === 'Pending'): ?>
                        <a href="payment-form.php?id=<?= $booking_ID ?>" class="btn btn-success btn-lg w-100 mb-2">
                            <i class="bi bi-credit-card"></i> Pay Now
                        </a>
                    <?php elseif ($booking['booking_status'] === 'Paid'): ?>
                        <button class="btn btn-primary btn-lg w-100 mb-2" disabled>
                            <i class="bi bi-check-circle"></i> Paid - Awaiting Confirmation
                        </button>
                    <?php elseif ($booking['booking_status'] === 'Confirmed'): ?>
                        <button class="btn btn-success btn-lg w-100 mb-2" disabled>
                            <i class="bi bi-trophy"></i> Confirmed! Get Ready!
                        </button>
                    <?php endif; ?>

                    <a href="booking.php" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-arrow-left"></i> Back to My Bookings
                    </a>

                    <?php if ($booking['booking_status'] == 'Pending for Payment'){ ?>
                    <a href="payment-form.php?id=<?= $booking['booking_ID'] ?>" class="btn btn btn-outline-secondary btn-sm w-100"> Pay </a> 
                    <a href="booking-cancel.php?id=<?= $booking['booking_ID'] ?>" class="btn btn-danger btn-sm cancel-booking w-100" data-name="<?= htmlspecialchars($booking['tourpackage_name']) ?>"> Cancel </a>
                    <?php } else if (in_array($booking['booking_status'], [ 'Completed', 'Cancelled', 'Refunded','Failed', 'Rejected by the Guide', 'Booking Expired — Payment Not Completed', 'Booking Expired — Guide Did Not Confirm in Time' ], true)){ ?>
                    <a href="booking-again.php?id=<?= $booking['booking_ID'] ?>" class="btn btn-success btn-sm w-100"> Book Again </a>
                    <?php } ?>
                </div>

                <!-- Important Info -->
                <div class="detail-card" style="background: #fff3cd; border-left: 4px solid #ffc107;">
                    <h6 style="color: #856404;"><i class="fas fa-exclamation-triangle me-2"></i> Important</h6>
                    <ul class="mb-0" style="color: #856404; font-size: 0.9rem;">
                        <li>Please arrive 10 minutes early</li>
                        <li>Bring comfortable walking shoes</li>
                        <li>Water and snacks recommended</li>
                        <li>Valid ID required for Colosseum entry</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.btn-primary, .btn-outline-primary, .btn-outline-danger').on('click', function() {
                const action = $(this).text().trim();
                alert('Action: ' + action);
            });
        });
    </script>
</body>
</html>
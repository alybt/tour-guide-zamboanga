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
    header("Location: bookings.php");
    exit;
}

$booking_ID = (int)$_GET['id'];
 

$tourist_ID = $_SESSION['account_ID'];
$bookingObj = new Booking();
$tourManagerObj = new TourManager();
$guideObj = new Guide();


$booking = $bookingObj->getBookingByIDAndTourist($booking_ID, $tourist_ID);  
$packages = $tourManagerObj->getTourPackageDetailsByID($booking['tourpackage_ID']); 
$guide = $guideObj->getGuideByBooking($booking['booking_ID']);
$guide_name = $guide['guide_name'] ?? '';
$spots =  $tourManagerObj->getSpotsByPackage($booking['tourpackage_ID']);
$companions = $bookingObj->getCompanionsByBooking($booking_ID);


$tourdetails = $bookingObj->getTourDetails($booking_ID);
$transactionDetails = $bookingObj->getTransactionSummary($booking_ID);

$statusColor = match ($booking['booking_status']) {
    'Pending for Payment' => 'status-pending-for-payment',
    'Pending for Approval' => 'status-pending-for-approval',
    'Approved' => 'status-approved',
    'In Progress' => 'status-in-progress',
    'Completed' => 'status-completed',
    'Cancelled' => 'status-cancelled',
    'Cancelled - No Refund' => 'status-cancelled-no-refund',
    'Refunded' => 'status-refunded',
    'Failed' => 'status-failed',
    'Rejected by the Guide' => 'status-rejected-by-guide',
    'Booking Expired — Payment Not Completed' => 'status-booking-expired-payment-not-completed',
    'Booking Expired — Guide Did Not Confirm in Time' => 'status-booking-expired-guide-did-not-confirm-in-time',
    default => 'status-secondary'
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
            --muted-color: gainsboro;

            /*Booking Status Color*/
            --pending-for-payment: #F9A825 ;
            --pending-for-approval: #EF6C00 ;
            --approved: #3A8E5C;
            --in-progress: #009688;
            --completed: #1A6338;
            --cancelled: #F44336;
            --cancelled-no-refund: #BC2E2A;
            --refunded: #42325D;    
            --failed: #820000;
            --rejected-by-guide: #B71C1C;
            --booking-expired-payment-not-completed: #695985;
            --booking-expired-guide-did-not-confirm-in-time: #695985;
        }

        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin-top: 3rem;
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
        
        .status-pending-for-payment {
            background-color: var(--pending-for-payment);
            color: white;
        }

        .status-pending-for-approval {
            background-color: var(--pending-for-approval);
            color: white;
        }

        .status-approved {
            background-color: var(--approved);
            color: white;
        }

        .status-in-progress {
            background-color: var(--in-progress);
            color: white;
        }

        .status-completed {
            background-color: var(--completed);
            color: white;
        }

        .status-cancelled {
            background-color: var(--cancelled);
            color: var(--secondary-color);
        }

        .status-cancelled-no-refund {
            background-color: var(--cancelled-no-refund);
            color: white;
        }

        .status-refunded {
            background-color: var(--refunded);
            color: white;
        }

        .status-failed {
            background-color: var(--failed);
            color: white;
        }

        .status-rejected-by-guide {
            background-color: var(--rejected-by-guide);
            color: white;
        }

        .status-booking-expired-payment-not-completed {
            background-color: var(--booking-expired-payment-not-completed);
            color: white;
        }

        .status-booking-expired-guide-did-not-confirm-in-time {
            background-color: var(--booking-expired-guide-did-not-confirm-in-time);
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
                    <h6 class="mb-2">Booking #<?= str_pad($booking['booking_ID'], 5, '0', STR_PAD_LEFT) ?></h6>
                    <h1><?= htmlspecialchars($booking['tourpackage_name']) ?></h1>
                </div>
                <span class="status-badge <?= $statusColor ?>"> 
                    <?= htmlspecialchars($booking['booking_status']) ?>
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
                    <?php $rating = $guideObj->guideRatingAndCount($guide['account_ID'] ?? '');
                    $average_rating = $rating['average_rating'] ?? 0;
                    $rating_count = $rating['rating_count'] ?? 0;
                    $display_rating = round($average_rating * 2) / 2; ?>
                    <div class="guide-info">
                        <img src="https://i.pravatar.cc/150?img=33" alt="Guide" class="guide-img">
                        <div class="flex-grow-1">
                            <h5 class="mb-1"><?= htmlspecialchars($guide['guide_name'] ?? 'Not Assigned') ?></h5>
                            <div class="text-warning mb-2">
                                <?php 
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= $display_rating) { 
                                        echo '<i class="fas fa-star text-warning"></i>';
                                    } elseif ($i - 0.5 == $display_rating) { 
                                        echo '<i class="fas fa-star-half-alt text-warning"></i>';
                                    } else { 
                                        echo '<i class="far fa-star text-muted"></i>';  
                                    }
                                }
                                ?>
                                <span class="text-muted">(<?= $rating_count ?>)</span>
                            </div>
                            <?php $spoken = $guideObj->getguideLanguages($booking['guide_ID']); ?>
                            <p class="mb-0 text-muted">
                                <i class="fas fa-language me-2"></i> <?= $spoken['Spoken_Languages'] ?? 'N/A' ?>
                            </p>
                        </div>
                        <div>
                            <a href="inbox.php?guide_id=<?= htmlspecialchars($guide['account_ID']) ?>" class="btn btn-primary btn-sm mb-2">
                                <i class="fas fa-comment"></i> Message
                            </a>
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
                <?php if (in_array($booking['booking_status'],['Pending for Payment','Pending for Approval','Approved'])) {?>
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
                <?php }?>
            </div>

            <div class="col-md-4">
                <!-- Booking Summary -->
                <?php if($transactionDetails != null){ ?>
                    <div class="detail-card">
                        <h5><i class="fas fa-receipt me-2"></i> Booking Summary</h5>
                        <div class="info-row">
                            <span class="info-label">Tour Price</span>
                            <span class="info-value"><?= $transactionDetails['Total_WO_PF'] ?? '' ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Processing Fee</span>
                            <span class="info-value"><?= $transactionDetails['Processing_Fee_Rate'] ?? '' ?></span>
                        </div>
                        <div class="info-row" style="border-top: 2px solid var(--accent); margin-top: 15px; padding-top: 15px;">
                            <span class="info-label"><strong>Total Paid</strong></span>
                            <span class="info-value" style="font-size: 1.3rem; color: var(--accent);"> <?= $transactionDetails['Total_Amount_Paid'] ?? '' ?></span>
                        </div>
                    </div>
                <?php }?>

                <!-- Actions -->
                <div class="detail-card">
                    <h5><i class="fas fa-tools me-2"></i> Actions</h5>
                    <!-- <button class="btn btn-primary w-100 mb-2">
                        <i class="fas fa-download me-2"></i> Download Ticket
                    </button> -->
                   
                    <?php if (in_array($booking['booking_status'],['Pending for Payment','Pending for Approval'])){?>
                        <a href="booking-cancel.php?id=<?= $booking['booking_ID'] ?>" class="btn btn-danger w-100 mb-2 cancel-booking" data-name="<?= htmlspecialchars($booking['tourpackage_name']) ?>">
                                <i class="fas fa-times me-2"></i> Cancel Booking
                        </a>
                    <?php } if (in_array($booking['booking_status'],['Pending for Payment'])) {?>
                        <a href="payment-form.php?id=<?= $booking_ID ?>" class="btn btn-success w-100 mb-2">
                            <i class="bi bi-credit-card"></i> Pay Now
                        </a>
                    <?php } if (in_array($booking['booking_status'],['Completed','Cancelled','Cancelled - No Refund','Refunded','Failed','Rejected by the Guide','Booking Expired — Payment Not Completed','Booking Expired — Guide Did Not Confirm in Time'])) {?>
                        <a href="booking-again.php?id=<?= $booking['booking_ID'] ?>" class="btn btn-success w-100 mb-2">
                            <i class="fas fa-redo me-2"></i> Book Again
                        </a>
                    <?php } ?>   
                    <a href="booking.php" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-arrow-left"></i> Back to My Bookings
                    </a>
                </div>

                <!-- Important Info -->
                 <?php if (in_array($booking['booking_status'],['Pending for Payment','Pending for Approval', 'Approved', 'In Progress'])){ ?>
                    <div class="detail-card" style="background: #fff3cd; border-left: 4px solid #ffc107;">
                        <h6 style="color: #856404;"><i class="fas fa-exclamation-triangle me-2"></i> Important</h6>
                        <ul class="mb-0" style="color: #856404; font-size: 0.9rem;">
                            <li>Please arrive 10 minutes early</li>
                            <li>Bring comfortable walking shoes</li>
                            <li>Water and snacks recommended</li>
                            <li>Valid ID required for Colosseum entry</li>
                        </ul>
                    </div>
                <?php }?>
                
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // $(document).ready(function() {
        //     $('.btn-primary, .btn-outline-primary, .btn-outline-danger').on('click', function() {
        //         const action = $(this).text().trim();
        //         alert('Action: ' + action);
        //     });
        // });
    </script>
</body>
</html>
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

// Fetch booking with all related data
$booking = $bookingObj->getBookingByIDAndTourist($booking_ID, $tourist_ID);


$package = $tourManager->getTourPackageDetailsByID($booking['tourpackage_ID']);
$guide = $guideObj->getGuideByID($package['guide_ID']);
$spots = $tourManager->getSpotsByPackage($package['tourpackage_ID']);
$companions = $bookingObj->getCompanionsByBooking($booking_ID);

// Status badge color
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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Booking #<?= $booking_ID ?> - Tourismo Zamboanga</title>
    <link rel="stylesheet" href="../../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/vendor/bootstrap-icons/bootstrap-icons.css" >
    <link rel="stylesheet" href="../../assets/css/tourist/booking-view.css">

</head>
<body class=" ">
     <?php require_once "includes/header.php"; 
    include_once "includes/header.php";?>
<main>
    
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <!-- Header -->
                <div class="header-booking d-flex justify-content-between align-items-center mb-4">
                    <h2 class="fw-bold text-primary">
                        <i class="bi bi-receipt"></i> Booking #<?= str_pad($booking_ID, 5, '0', STR_PAD_LEFT) ?>
                    </h2>
                    <div>
                        <span class="badge <?= $statusColor ?> status-badge fs-6">
                            <?= htmlspecialchars($booking['booking_status']) ?>
                        </span>
                    </div>
                </div>

                <!-- Success/Error Messages -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="package-details row g-4">
                    <!-- Package Details -->
                    <div class="col-md-8">
                        <div class="card shadow-sm booking-card h-100">
                            <div class="card-body">
                                <h4 class="card-title fw-bold text-primary">
                                    <?= htmlspecialchars($package['tourpackage_name']) ?>
                                </h4>
                                <p class="text-muted"><?= htmlspecialchars($package['tourpackage_desc']) ?></p>

                                <hr>

                                <div class="row">
                                    <div class="col-sm-6">
                                        <p><strong><i class="bi bi-calendar-event"></i> Booking Dates</strong></p>
                                        <p class="ms-3">
                                            <?= date('F j, Y', strtotime($booking['booking_start_date'])) ?>
                                            <i class="bi bi-arrow-right"></i>
                                            <?= date('F j, Y', strtotime($booking['booking_end_date'])) ?>
                                            <br>
                                            <small class="text-muted">
                                                (<?= round((strtotime($booking['booking_end_date']) - strtotime($booking['booking_start_date'])) / 86400) + 1 ?> days)
                                            </small>
                                        </p>
                                    </div>
                                    <div class="col-sm-6">
                                        <p><strong><i class="bi bi-person-standing"></i> Tour Guide</strong></p>
                                        <p class="ms-3"><?= htmlspecialchars($guide['guide_name'] ?? 'Not Assigned') ?></p>
                                    </div>
                                </div>

                                <hr>

                                <!-- Tour Spots -->
                                <p><strong><i class="bi bi-geo-alt-fill text-danger"></i> Tour Spots Included</strong></p>
                                <?php if ($spots): ?>
                                    <div class="row row-cols-1 row-cols-md-2 g-3">
                                        <?php foreach ($spots as $spot): ?>
                                            <div class="col">
                                                <div class="d-flex align-items-center gap-3 border rounded p-3 bg-white">
                                                    <div class="bg-light rounded flex-shrink-0">
                                                        <i class="bi bi-camera-fill fs-1 text-primary opacity-75"></i>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-1"><?= htmlspecialchars($spot['spots_name']) ?></h6>
                                                        <small class="text-muted"><?= htmlspecialchars($spot['spots_description']) ?></small>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted ms-3">No spots listed.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Booking Summary Sidebar -->
                    <div class="booking-summary col-md-4">
                        <div class="card shadow-sm sticky-top" style="top: 100px;">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="bi bi-people"></i> Travelers</h5>
                            </div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush">
                                    <?php if ($booking['booking_isselfIncluded'] ?? ''): ?>
                                        <li class="list-group-item d-flex justify-content-between">
                                            <div>
                                                <strong>You (Lead Traveler)</strong><br>
                                                <small class="text-muted"><?= $_SESSION['user']['fullname'] ?? 'Tourist' ?></small>
                                            </div>
                                            <span class="badge bg-success rounded-pill">Included</span>
                                        </li>
                                    <?php endif; ?>

                                    <?php foreach ($companions as $c): ?>
                                        <li class="list-group-item d-flex justify-content-between">
                                            <div>
                                                <strong><?= htmlspecialchars($c['companion_name']) ?></strong><br>
                                                <small class="text-muted"><?= htmlspecialchars($c['companion_category_name']) ?></small>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>

                                    <?php 
                                    $selfCount = 0;
                                    if (isset($booking['is_selfIncluded']) && $booking['is_selfIncluded']) {
                                        $selfCount = 1;
                                    } elseif (isset($booking['booking_isselfIncluded']) && $booking['booking_isselfIncluded']) {
                                        $selfCount = 1;
                                    }
                                    $totalPeople = $selfCount + count($companions);
                                    ?>
                                    <li class="list-group-item text-center fw-bold pt-3 border-top">
                                        Total: <?= $totalPeople ?> Person(s)
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="action-btn mt-4">
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
                    </div>
                </div>
            </div>
        </div>
    </div>

</main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
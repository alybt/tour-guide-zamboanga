<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role_name'] !== 'Tour Guide') {
    header('Location: ../../index.php');
    exit;
}

if ($_SESSION['user']['account_status'] === 'Suspended') {
    header('Location: account-suspension.php');
    exit;
}
if ($_SESSION['user']['account_status'] === 'Pending') {
    header('Location: account-pending.php');
    exit;
}

require_once "../../classes/tour-manager.php";
require_once "../../classes/guide.php";
require_once "../../classes/tourist.php";
require_once "../../classes/booking.php";

$tourManager = new TourManager();
$guideObj = new Guide();
$bookingObj = new Booking();

$booking_ID = isset($_GET['booking_ID']) ? intval($_GET['booking_ID']) : 0;
$tourist_ID = isset($_GET['tourist_ID']) ? intval($_GET['tourist_ID']) : 0;

$package = $bookingObj->viewBookingByBookingIDForGuide($booking_ID);
$spots = $tourManager->getSpotsByPackage($package['tourpackage_ID'] ?? 0);
$touristDetails = $bookingObj->getCompanions($booking_ID);
$bookingDates = $bookingObj->getDates($booking_ID);

if (!$package) {
    $_SESSION['error'] = "Tour package not found.";
    header("Location: booking.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>View Booking | TourGuide PH</title>

    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="../../assets/vendor/bootstrap/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="../../assets/vendor/bootstrap-icons/bootstrap-icons.css" >
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="../../assets/css/guide/booking-view.css">
    <link rel="stylesheet" href="../../assets/css/guide/dashboard.css">
    
</head>
<body>

    <?php 
        require_once "includes/aside-dashboard.php"; 
        include_once "includes/aside-dashboard.php";
    ?>

    <!-- Main Content -->
    <main class="main-content">

        <!-- Header -->
        <div class="header-card d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <div>
                <h3 class="mb-1 fw-bold">Booking Details</h3>
                <p class="text-muted mb-0">View complete information about this booking.</p>
            </div>
            <div class="text-md-end">
                <div class="d-flex align-items-center gap-3 flex-wrap justify-content-md-end">
                    <span class="badge bg-success status-badge">
                        <i class="bi bi-check-circle"></i> <?= ucfirst($_SESSION['user']['account_status']) ?>
                    </span>
                    <div class="clock" id="liveClock"></div>
                </div>
                <small class="text-muted d-block mt-1">Philippine Standard Time (PST)</small>
            </div>
        </div>

        <!-- Alerts -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert-custom alert-success p-3">
                <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert-custom alert-error p-3">
                <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Back Button -->
        <div class="mb-4">
            <a href="booking.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Back to Bookings
            </a>
        </div>

        <!-- Package Details Card -->
        <div class="card-custom mb-4">
            <div class="card-header">
                <i class="bi bi-box-seam"></i> Package Information
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="d-flex">
                            <span class="detail-label">Package Name:</span>
                            <span class="detail-value ms-2"><?= htmlspecialchars($package['tourpackage_name'] ?? '—') ?></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex">
                            <span class="detail-label">Schedule Days:</span>
                            <span class="detail-value ms-2"><?= htmlspecialchars($package['schedule_days'] ?? '—') ?> days</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex">
                            <span class="detail-label">Max People:</span>
                            <span class="detail-value ms-2"><?= htmlspecialchars($package['numberofpeople_maximum'] ?? '—') ?></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex">
                            <span class="detail-label">Min People:</span>
                            <span class="detail-value ms-2"><?= htmlspecialchars($package['numberofpeople_based'] ?? '—') ?></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex">
                            <span class="detail-label">Base Amount:</span>
                            <span class="detail-value ms-2">₱<?= number_format($package['pricing_foradult'] ?? 0, 2) ?></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex">
                            <span class="detail-label">Discount:</span>
                            <span class="detail-value ms-2"><?= htmlspecialchars($package['pricing_discount'] ?? '0') ?>%</span>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex align-items-start">
                            <span class="detail-label">Description:</span>
                            <p class="detail-value ms-2 mb-0"><?= nl2br(htmlspecialchars($package['tourpackage_desc'] ?? '—')) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tour Spots -->
        <?php if (!empty($spots)): ?>
        <div class="card-custom mb-4">
            <div class="card-header">
                <i class="bi bi-geo-alt"></i> Tour Spots
            </div>
            <div class="card-body">
                <ul class="spots-list">
                    <?php foreach ($spots as $spot): ?>
                        <li>
                            <strong><?= htmlspecialchars($spot['spots_name']); ?></strong><br>
                            <small class="text-muted"><?= htmlspecialchars($spot['spots_description']); ?></small>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <!-- Tourist & Booking Dates -->
        <div class="card-custom">
            <div class="card-header">
                <i class="bi bi-person-check"></i> Tourist & Booking Details
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="d-flex">
                            <span class="detail-label">Tourist Name:</span>
                            <span class="detail-value ms-2"><?= htmlspecialchars($package['tourist_name'] ?? '—') ?></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex">
                            <span class="detail-label">Start Date:</span>
                            <span class="detail-value ms-2"><?= $bookingDates['booking_start_date'] ? date('M d, Y', strtotime($bookingDates['booking_start_date'])) : '—' ?></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex">
                            <span class="detail-label">End Date:</span>
                            <span class="detail-value ms-2"><?= $bookingDates['booking_end_date'] ? date('M d, Y', strtotime($bookingDates['booking_end_date'])) : '—' ?></span>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex align-items-start">
                            <span class="detail-label">Companions:</span>
                            <div class="ms-2">
                                <?php if (empty($touristDetails)): ?>
                                    <em class="text-muted">No companions</em>
                                <?php else: ?>
                                    <?php foreach ($touristDetails as $t): ?>
                                        <span class="companion-item">
                                            <?= htmlspecialchars($t['companion_name']) ?>
                                            <small class="text-muted">(<?= htmlspecialchars($t['companion_category_name']) ?>)</small>
                                        </span>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </main>

    <!-- Bootstrap JS -->
    <script src="../../assets/vendor/twbs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Live Clock (PH Time) -->
    <script>
        function updateClock() {
            const now = new Date();
            const options = {
                timeZone: 'Asia/Manila',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            };
            document.getElementById('liveClock').textContent = now.toLocaleTimeString('en-US', options);
        }
        updateClock();
        setInterval(updateClock, 1000);
    </script>
</body>
</html>
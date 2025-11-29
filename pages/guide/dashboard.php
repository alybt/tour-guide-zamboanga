<?php
session_start();

// Redirect if not logged in or not a Guide
if (!isset($_SESSION['user']) || $_SESSION['user']['role_name'] !== 'Tour Guide') {
    header('Location: ../../index.php');
    exit;
}

// Status-based redirects
if ($_SESSION['user']['account_status'] === 'Suspended') {
    header('Location: account-suspension.php');
    exit;
}
if ($_SESSION['user']['account_status'] === 'Pending') {
    header('Location: account-pending.php');
    exit;
}

require_once "../../classes/guide.php";
require_once "../../classes/booking.php";
require_once "../../classes/tour-manager.php";


$bookingObj = new Booking();
$updateBookings = $bookingObj->updateBookings();

$guideObj = new Guide();
$tourManagerObj = new TourManager();

$guide_ID = $guideObj->getGuide_ID($_SESSION['user']['account_ID']);
$activebookings = $bookingObj->getActiveBookingCount($guide_ID);
$totalofActivePackages = $tourManagerObj->getTourPackagesCountByGuide($guide_ID);
$totalEarnings = $guideObj->getTotalEarnings($guide_ID);
$totalRatings = $guideObj->guideRating($guide_ID);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Guide Dashboard | TourGuide PH</title>

     
    <link rel="stylesheet" href="../../assets/vendor/bootstrap/css/bootstrap.min.css"> 
    <link rel="stylesheet" href="../../assets/vendor/bootstrap-icons/bootstrap-icons.css"> 
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet"/>

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
                <h3 class="mb-1 fw-bold">Welcome back, <span class="text-accent"><?= htmlspecialchars($_SESSION['full_name'] ?? 'Guide') ?>!</span></h3>
                <p class="text-muted mb-0">Manage your tours and connect with travelers.</p>
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

        <!-- Stats Grid -->
        <div class="row g-4">
            <div class="col-md-6 col-lg-3">
                <div class="card stat-card h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #E5A13E, #f39c12);">
                            <i class="bi bi-calendar-check"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 text-muted">Active Bookings</h6>
                            <h3 class="mb-0 fw-bold text-accent"><?= $activebookings ?? ''?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card stat-card h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #213638, #2e8b57);">
                            <i class="bi bi-box-seam"></i>
                        Brenda>
                        </div>
                        <div>
                            <h6 class="mb-0 text-muted">Tour Packages</h6>
                            <h3 class="mb-0 fw-bold" style="color: var(--secondary-color);"><?= $totalofActivePackages ?? '' ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card stat-card h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #E5A13E, #f1c40f);">
                            <i class="bi bi-currency-dollar"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 text-muted">Earnings</h6>
                            <h3 class="mb-0 fw-bold text-accent">₱ <?= number_format($totalEarnings, 2) ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card stat-card h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #CFE7E5, #a8e6cf);">
                            <i class="bi bi-star-fill"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 text-muted">Rating</h6>
                            <h3 class="mb-0 fw-bold" style="color: #27ae60;"><?= number_format($totalRatings, 1) ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="mt-5">
            <h5 class="fw-bold mb-3">Recent Activity</h5>
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <!-- <li class="list-group-item d-flex justify-content-between align-items-center">
                            New booking from <strong>Maria Santos</strong>
                            <small class="text-muted">2 hours ago</small>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Tour package <strong>Boracay Sunset</strong> updated
                            <small class="text-muted">5 hours ago</small>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Payment received: <strong>₱8,500</strong>
                            <small class="text-muted">1 day ago</small>
                        </li> -->
                    </ul>
                </div>
            </div>
        </div>
    </main> 
    <script src="../../assets/vendor/twbs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
 
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
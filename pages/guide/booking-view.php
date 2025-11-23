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

    <style>
        :root {
            --primary-color: #ffffff;
            --secondary-color: #213638;
            --accent: #E5A13E;
            --secondary-accent: #CFE7E5;
            --text-dark: #2d3436;
            --text-light: #636e72;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            color: var(--text-dark);
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            width: 260px;
            background: var(--secondary-color);
            color: var(--primary-color);
            padding-top: 1.5rem;
            transition: all 0.3s ease;
            z-index: 1000;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }

        .sidebar .logo {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--accent);
            text-align: center;
            margin-bottom: 2rem;
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.85);
            padding: 0.85rem 1.5rem;
            border-radius: 0;
            transition: all 0.2s;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(229, 161, 62, 0.15);
            color: var(--accent);
        }

        .sidebar .nav-link i {
            font-size: 1.2rem;
            width: 24px;
            text-align: center;
        }

        .sidebar .nav-text {
            white-space: nowrap;
        }

        /* Main Content */
        .main-content {
            margin-left: 260px;
            padding: 2rem;
            transition: all 0.3s ease;
        }

        .header-card {
            background: var(--primary-color);
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(33, 54, 56, 0.08);
            padding: 1.75rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(207, 231, 229, 0.3);
        }

        .status-badge {
            font-size: 0.8rem;
            padding: 0.35rem 0.75rem;
            border-radius: 50px;
            font-weight: 600;
        }

        .clock {
            font-family: 'Courier New', monospace;
            font-weight: 600;
            color: var(--secondary-color);
            font-size: 1.1rem;
        }

        .card-custom {
            background: var(--primary-color);
            border-radius: 14px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            border: 1px solid rgba(207, 231, 229, 0.4);
            overflow: hidden;
        }

        .card-custom .card-header {
            background-color: rgba(207, 231, 229, 0.3);
            color: var(--text-dark);
            font-weight: 600;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid rgba(207, 231, 229, 0.5);
        }

        .card-custom .card-body {
            padding: 1.5rem;
        }

        .detail-label {
            font-weight: 600;
            color: var(--text-dark);
            min-width: 180px;
        }

        .detail-value {
            color: var(--text-light);
        }

        .spots-list {
            list-style: none;
            padding-left: 0;
        }

        .spots-list li {
            padding: 0.75rem 1rem;
            background: rgba(207, 231, 229, 0.15);
            border-radius: 8px;
            margin-bottom: 0.75rem;
            border-left: 4px solid var(--accent);
        }

        .companion-item {
            display: inline-block;
            background: rgba(33, 54, 56, 0.08);
            color: var(--text-dark);
            padding: 0.35rem 0.75rem;
            border-radius: 50px;
            font-size: 0.875rem;
            margin: 0.25rem;
            font-weight: 500;
        }

        .alert-custom {
            border-radius: 12px;
            font-weight: 500;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .sidebar {
                width: 80px;
            }
            .sidebar .nav-text,
            .sidebar .logo span {
                display: none;
            }
            .main-content {
                margin-left: 80px;
            }
        }

        @media (max-width: 576px) {
            .main-content {
                padding: 1rem;
            }
            .header-card {
                padding: 1.25rem;
            }
            .detail-label {
                min-width: 140px;
            }
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="logo px-3">
            <span>TourGuide PH</span>
        </div>
        <nav class="nav flex-column px-2">
            <a class="nav-link" href="dashboard.php">
                <i class="bi bi-house-door"></i>
                <span class="nav-text">Dashboard</span>
            </a>
            <a class="nav-link active" href="booking.php">
                <i class="bi bi-calendar-check"></i>
                <span class="nav-text">Bookings</span>
            </a>
            <a class="nav-link" href="tour-packages.php">
                <i class="bi bi-box-seam"></i>
                <span class="nav-text">Tour Packages</span>
            </a>
            <a class="nav-link" href="schedules.php">
                <i class="bi bi-clock-history"></i>
                <span class="nav-text">Schedules</span>
            </a>
            <a class="nav-link" href="payments.php">
                <i class="bi bi-credit-card"></i>
                <span class="nav-text">Payments</span>
            </a>
            <hr class="bg-white opacity-25 my-3">
            <a class="nav-link text-warning" href="account-change.php">
                <i class="bi bi-person-walking"></i>
                <span class="nav-text">Switch to Tourist</span>
            </a>
            <a class="nav-link text-danger" href="logout.php"
               onclick="return confirm('Logout now? Your last activity will be recorded.');">
                <i class="bi bi-box-arrow-right"></i>
                <span class="nav-text">Logout</span>
            </a>
        </nav>
    </aside>

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
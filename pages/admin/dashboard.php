<?php 
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role_name'] !== 'Admin' || $_SESSION['user']['account_status'] == 'Suspended') {
    header('Location: ../../index.php');
    exit;
}

require_once "../../classes/tour-manager.php";
require_once "../../classes/guide.php";
require_once "../../classes/admin.php";
require_once "../../classes/booking.php";

$tourPackageObj = new TourManager();
$guideObj = new Guide();
$adminObj = new Admin();
$bookingObj = new Booking();

$totalAccount = $adminObj->countAccount();
$countspots = $tourPackageObj->countSpots();
$countPackage = $tourPackageObj->countPackages();
$countBookings = $bookingObj->countBookings();
$updateBookings = $bookingObj->updateBookings();


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Tourismo Zamboanga</title>

    <link rel="stylesheet" href="../../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/vendor/bootstrap-icons/bootstrap-icons.css">
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

        .stats-card {
            background: var(--primary-color);
            border-radius: 14px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            border: 1px solid rgba(207, 231, 229, 0.4);
            padding: 1.5rem;
            text-align: center;
            transition: transform 0.2s;
        }

        .stats-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }

        .stats-icon {
            font-size: 2.2rem;
            color: var(--accent);
            margin-bottom: 0.75rem;
        }

        .stats-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text-dark);
        }

        .stats-label {
            color: var(--text-light);
            font-size: 0.95rem;
            margin-top: 0.25rem;
        }

        .quick-links .btn {
            border-radius: 12px;
            font-weight: 500;
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .quick-links .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
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
            .stats-card {
                padding: 1.25rem;
            }
        }
    </style>
</head>
<body>

    <?php include 'includes/dashboard.php';?>

    <!-- Main Content -->
    <main class="main-content">

        <!-- Header -->
        <div class="header-card d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <div>
                <h3 class="mb-1 fw-bold">Admin Dashboard</h3>
                <p class="text-muted mb-0">Welcome back, <strong><?= htmlspecialchars($_SESSION['user']['firstname'] ?? 'Admin') ?></strong>. Manage the platform with full control.</p>
            </div>
            <div class="text-md-end">
                <div class="d-flex align-items-center gap-3 flex-wrap justify-content-md-end">
                    <span class="badge bg-success status-badge">
                        <i class="bi bi-shield-check"></i> Active
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

        <!-- Stats Row -->
        <div class="row g-4 mb-5">
            <div class="col-md-3 col-6">
                <div class="stats-card">
                    <div class="stats-icon"><i class="bi bi-geo-alt"></i></div>
                    <div class="stats-value"><?= $countspots['countspots']; ?></div>
                    <div class="stats-label">Total Spots</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stats-card">
                    <div class="stats-icon"><i class="bi bi-people"></i></div>
                    <div class="stats-value"><?= $totalAccount['accounts']; ?></div>
                    <div class="stats-label">Active Users</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stats-card">
                    <div class="stats-icon"><i class="bi bi-box-seam"></i></div>
                    <div class="stats-value"><?=$countPackage['packages']?></div>
                    <div class="stats-label">Tour Packages</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stats-card">
                    <div class="stats-icon"><i class="bi bi-calendar-check"></i></div>
                    <div class="stats-value"><?= $countBookings['countbookings'];?></div>
                    <div class="stats-label">Pending Bookings</div>
                </div>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="quick-links">
            <h5 class="mb-3">Quick Actions</h5>
            <div class="d-flex flex-wrap gap-3">
                <a href="tour-spots-add.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Add New Spot
                </a>
                <a href="manage-users.php" class="btn btn-outline-info">
                    <i class="bi bi-person-check"></i> View All Users
                </a>
                <a href="reports.php" class="btn btn-outline-success">
                    <i class="bi bi-download"></i> Export Reports
                </a>
                <!-- <a href="settings.php" class="btn btn-outline-secondary">
                    <i class="bi bi-sliders"></i> System Settings
                </a> -->
            </div>
        </div>

        <!-- Recent Activity (Placeholder) -->
        <div class="mt-5">
            <div class="card-custom p-4">
                <h5 class="mb-3">Recent Activity</h5>
                <ul class="list-group list-group-flush">
                    <!-- <li class="list-group-item d-flex justify-content-between align-items-center">
                        New spot added: <strong>Boracay Beach</strong>
                        <small class="text-muted">2 hours ago</small>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        User <strong>Juan Dela Cruz</strong> registered as Guide
                        <small class="text-muted">5 hours ago</small>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Booking #1001 approved
                        <small class="text-muted">1 day ago</small>
                    </li> -->
                </ul>
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
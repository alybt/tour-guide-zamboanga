<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role_name'] !== 'Admin' || $_SESSION['user']['account_status'] == 'Suspended') {
    header('Location: ../../index.php');
    exit;
}
// Print ng booking and itinerary/
// Email and notification process 
// email template
// WMSU 18: Mission Vision Quality THE BSCS Program Objective
// 

require_once "../../config/database.php";
require_once "../../classes/auth.php";
require_once "../../classes/tour-manager.php";
require_once "../../classes/guide.php";
require_once "../../classes/admin.php";
require_once "../../classes/booking.php";

$tourPackageObj = new TourManager();
$guideObj = new Guide();
$adminObj = new Admin();
$bookingObj = new Booking();

$db = new Database();
$pdo = $db->connect();

// === Sample Data (Replace with real queries later) ===
$total_users      = $pdo->query("SELECT COUNT(*) FROM account_info WHERE role_ID != 1")->fetchColumn();
$total_guides     = $pdo->query("SELECT COUNT(*) FROM account_info WHERE role_ID = 2")->fetchColumn();
$total_tourists   = $pdo->query("SELECT COUNT(*) FROM account_info WHERE role_ID = 3")->fetchColumn();
$total_packages   = $tourPackageObj->countPackages();
$total_bookings   = $pdo->query("SELECT COUNT(*) FROM booking")->fetchColumn();
$pending_bookings = $bookingObj->countBookings();
$revenue_this_month = $pdo->query("SELECT COALESCE(SUM(paymentinfo_total_amount), 0) FROM payment_info WHERE MONTH(paymentinfo_date) = MONTH(CURRENT_DATE()) AND YEAR(paymentinfo_date) = YEAR(CURRENT_DATE())")->fetchColumn();


$countPackage = $tourPackageObj->countPackages();
$countAllBookings = $bookingObj->countAllBookings();

$countBookings = $bookingObj->countBookings();


// Monthly Bookings (Last 6 months)
$monthly_bookings = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $count = $pdo->query("SELECT COUNT(*) FROM booking WHERE DATE_FORMAT(booking_start_date, '%Y-%m') = '$month'")->fetchColumn();
    $monthly_bookings[] = ['month' => date('M Y', strtotime("-$i months")), 'count' => $count];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports | Tourismo Zamboanga Admin</title>

    <link rel="stylesheet" href="../../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/vendor/bootstrap-icons/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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

        .chart-card {
            background: var(--primary-color);
            border-radius: 14px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            border: 1px solid rgba(207, 231, 229, 0.4);
            padding: 1.5rem;
        }

        .export-btn {
            border-radius: 12px;
            font-weight: 500;
            padding: 0.6rem 1rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
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
            .header-card, .stats-card, .chart-card {
                padding: 1.25rem;
            }
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="logo px-3">
            <span>Tourismo Zamboanga</span>
        </div>
        <nav class="nav flex-column px-2">
            <a class="nav-link" href="dashboard.php">
                <i class="bi bi-speedometer2"></i>
                <span class="nav-text">Dashboard</span>
            </a>
            <a class="nav-link" href="tour-spots.php">
                <i class="bi bi-geo-alt"></i>
                <span class="nav-text">Manage Spots</span>
            </a>
            <a class="nav-link" href="manage-users.php">
                <i class="bi bi-people"></i>
                <span class="nav-text">Manage Users</span>
            </a>
            <a class="nav-link active" href="reports.php">
                <i class="bi bi-graph-up"></i>
                <span class="nav-text">Reports</span>
            </a>
            <a class="nav-link" href="settings.php">
                <i class="bi bi-gear"></i>
                <span class="nav-text">Settings</span>
            </a>
            <hr class="bg-white opacity-25 my-3">
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
                <h3 class="mb-1 fw-bold">Platform Reports</h3>
                <p class="text-muted mb-0">Real-time analytics and performance insights.</p>
            </div>
            <div class="text-md-end">
                <div class="d-flex align-items-center gap-3 flex-wrap justify-content-md-end">
                    <span class="badge bg-success status-badge">
                        <i class="bi bi-shield-check"></i> Admin
                    </span>
                    <div class="clock" id="liveClock"></div>
                </div>
                <small class="text-muted d-block mt-1">Philippine Standard Time (PST)</small>
            </div>
        </div>

        <!-- Export Buttons -->
        <div class="mb-4 d-flex gap-2 flex-wrap">
            <button class="btn btn-outline-success export-btn" onclick="exportCSV()">
                <i class="bi bi-file-earmark-spreadsheet"></i> Export CSV
            </button>
            <button class="btn btn-outline-danger export-btn" onclick="window.print()">
                <i class="bi bi-printer"></i> Print Report
            </button>
        </div>

        <!-- Stats Grid -->
        <div class="row g-4 mb-5">
            <div class="col-md-3 col-6">
                <div class="stats-card">
                    <div class="stats-icon"><i class="bi bi-people"></i></div>
                    <div class="stats-value"><?= $total_users ?></div>
                    <div class="stats-label">Total Users</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stats-card">
                    <div class="stats-icon"><i class="bi bi-person-badge"></i></div>
                    <div class="stats-value"><?= $total_guides ?></div>
                    <div class="stats-label">Tour Guides</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stats-card">
                    <div class="stats-icon"><i class="bi bi-person-heart"></i></div>
                    <div class="stats-value"><?= $total_tourists ?></div>
                    <div class="stats-label">Tourists</div>
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
                    <div class="stats-value"><?= $countAllBookings['countallbookings']?></div>
                    <div class="stats-label">Total Bookings</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stats-card">
                    <div class="stats-icon"><i class="bi bi-hourglass-split"></i></div>
                    <div class="stats-value"><?= $countBookings['countbookings'];?></div>
                    <div class="stats-label">Pending Bookings</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stats-card">
                    <div class="stats-icon"><i class="bi bi-currency-bitcoin"></i></div>
                    <div class="stats-value">₱<?= number_format($revenue_this_month, 2) ?></div>
                    <div class="stats-label">Revenue (This Month)</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stats-card">
                    <div class="stats-icon"><i class="bi bi-graph-up-arrow"></i></div>
                    <div class="stats-value">+12%</div>
                    <div class="stats-label">Growth (vs last month)</div>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="chart-card">
                    <h5 class="mb-3">Monthly Bookings (Last 6 Months)</h5>
                    <canvas id="bookingsChart"></canvas>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="chart-card">
                    <h5 class="mb-3">User Distribution</h5>
                    <canvas id="userRoleChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="mt-5">
            <div class="chart-card p-4">
                <h5 class="mb-3">Recent Platform Activity</h5>
                <ul class="list-group list-group-flush">
                    <!-- <li class="list-group-item d-flex justify-content-between align-items-center">
                        New booking: <strong>Boracay Sunset Tour</strong>
                        <small class="text-muted">2 mins ago</small>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Guide <strong>Maria Santos</strong> updated profile
                        <small class="text-muted">15 mins ago</small>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Payment received: <strong>₱2,500.00</strong>
                        <small class="text-muted">1 hour ago</small>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        New user registered: <strong>juan.tourist</strong>
                        <small class="text-muted">3 hours ago</small>
                    </li> -->
                </ul>
            </div>
        </div>

    </main>

    <!-- Bootstrap JS -->
    <script src="../../assets/vendor/twbs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Live Clock -->
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

    <!-- Charts -->
    <script>
        // Bookings Chart
        const ctx1 = document.getElementById('bookingsChart').getContext('2d');
        new Chart(ctx1, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($monthly_bookings, 'month')) ?>,
                datasets: [{
                    label: 'Bookings',
                    data: <?= json_encode(array_column($monthly_bookings, 'count')) ?>,
                    borderColor: '#E5A13E',
                    backgroundColor: 'rgba(229, 161, 62, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'top' } },
                scales: { y: { beginAtZero: true } }
            }
        });

        // User Role Pie Chart
        const ctx2 = document.getElementById('userRoleChart').getContext('2d');
        new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: ['Admin', 'Guides', 'Tourists'],
                datasets: [{
                    data: [1, <?= $total_guides ?>, <?= $total_tourists ?>],
                    backgroundColor: ['#dc3545', '#ffc107', '#0dcaf0']
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom' } }
            }
        });

        // Export to CSV
        function exportCSV() {
            const data = [
                ['Metric', 'Value'],
                ['Total Users', <?= $total_users ?>],
                ['Tour Guides', <?= $total_guides ?>],
                ['Tourists', <?= $total_tourists ?>],
                ['Tour Packages', <?= $total_packages ?>],
                ['Total Bookings', <?= $total_bookings ?>],
                ['Pending Bookings', <?= $pending_bookings ?>],
                ['Revenue (This Month)', '₱<?= $revenue_this_month ?>']
            ];
            let csv = data.map(row => row.join(',')).join('\n');
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'tourguide-ph-report-<?= date('Y-m-d') ?>.csv';
            a.click();
        }
    </script>
</body>
</html>
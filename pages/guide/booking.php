<?php
session_start();
$current_page = basename($_SERVER['PHP_SELF']);

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

$bookingObj = new Booking();
$guideObj = new Guide();

$guide_ID = $guideObj->getGuide_ID($_SESSION['user']['account_ID']);
$bookings = $bookingObj->getBookingByGuideID($guide_ID);
function isActive($page) {
    global $current_page;
    return ($current_page === $page) ? 'active' : '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Bookings | TourGuide PH</title>

    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="../../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/vendor/bootstrap-icons/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="../../assets/css/guide/dashboard.css">

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

        .table-container {
            background: var(--primary-color);
            border-radius: 14px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            overflow: hidden;
            border: 1px solid rgba(207, 231, 229, 0.4);
        }

        .table th {
            background-color: rgba(207, 231, 229, 0.3);
            color: var(--text-dark);
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table td {
            vertical-align: middle;
            font-size: 0.95rem;
        }

        .btn-sm {
            font-size: 0.8rem;
            padding: 0.25rem 0.6rem;
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
            .table-responsive {
                font-size: 0.85rem;
            }
        }
    </style>
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
                <h3 class="mb-1 fw-bold">My Bookings</h3>
                <p class="text-muted mb-0">Manage and track all your tour bookings.</p>
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

        <!-- Quick Links -->
        <div class="d-flex gap-2 mb-4 flex-wrap">
            <a href="tour-packages-browse.php" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-search"></i> Browse Packages
            </a>
            <a href="booking-history.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-clock-history"></i> Booking History
            </a>
        </div>

        <!-- Bookings Table -->
        <div class="table-container">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Package</th>
                            <th>Description</th>
                            <th>Days</th>
                            <th>Tourist</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Status</th>
                            <th>Spots</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($bookings)): ?>
                            <?php $no = 1; foreach ($bookings as $booking): ?>
                                <?php 
                                $status = $booking['booking_status'];
                                $isPending = in_array($status, ['Pending for Payment', 'Pending for Approval', 'Approved']);
                                if (!$isPending) continue;
                                ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><strong><?= htmlspecialchars($booking['tourpackage_name']) ?></strong></td>
                                    <td class="text-truncate" style="max-width: 180px;">
                                        <?= htmlspecialchars($booking['tourpackage_desc']) ?>
                                    </td>
                                    <td><?= htmlspecialchars($booking['schedule_days']) ?> days</td>
                                    <td><?= htmlspecialchars($booking['tourist_name']) ?></td>
                                    <td><?= date('M d, Y', strtotime($booking['booking_start_date'])) ?></td>
                                    <td><?= date('M d, Y', strtotime($booking['booking_end_date'])) ?></td>
                                    <td>
                                        <?php
                                        $badgeClass = match($status) {
                                            'Pending for Payment' => 'bg-warning text-dark',
                                            'Pending for Approval' => 'bg-info text-white',
                                            'Approved' => 'bg-success text-white',
                                            default => 'bg-secondary'
                                        };
                                        ?>
                                        <span class="badge <?= $badgeClass ?> status-badge">
                                            <?= htmlspecialchars($status) ?>
                                        </span>
                                    </td>
                                    <td class="text-truncate" style="max-width: 120px;">
                                        <?= htmlspecialchars($booking['tour_spots'] ?? '—') ?>
                                    </td>
                                    <td>
                                        <?php if ($status === 'Pending for Payment'): ?>
                                            <a href="booking-view.php?booking_ID=<?= $booking['booking_ID'] ?? ''; ?>&tourist_ID=<?= $booking['tourist_ID'] ?? ''; ?>" class="btn btn-sm btn-outline-primary">View</a>

                                        <?php elseif ($status === 'Pending for Approval'): ?>
                                            <a href="booking-approve.php?id=<?= $booking['booking_ID'] ?>" 
                                               class="btn btn-sm btn-success"
                                               onclick="return confirm('Approve this booking?')">
                                                Approve
                                            </a>
                                            <a href="booking-reject.php?id=<?= $booking['booking_ID'] ?>" 
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirm('Reject this booking?')">
                                                Reject
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                    You currently have no active bookings.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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
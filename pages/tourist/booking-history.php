<?php
session_start();

// Redirect if not logged in or not a Tourist
if (!isset($_SESSION['user']) || $_SESSION['user']['role_name'] !== 'Tourist') {
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

require_once "../../classes/tourist.php";
require_once "../../classes/tour-manager.php";

$touristObj = new Tourist();
$TourManagerObj = new TourManager();

// Get the booking history for the logged-in tourist
$touristBookingHistory = $touristObj->getBookingHistory($_SESSION['user']['account_ID']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>My Booking History | Tourismo Zamboanga</title>

    <link rel="stylesheet" href="../../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/vendor/bootstrap-icons/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    
    <link rel="stylesheet" href="../../assets/css/guide/booking-history.css">
    <link rel="stylesheet" href="../../assets/css/guide/dashboard.css">
    
    <style>
        /* Custom status colors if not defined in your CSS */
        .bg-pending-for-payment { background-color: #ffc107; color: #000; }
        .bg-approved { background-color: #198754; color: #fff; }
        .bg-completed { background-color: #0dcaf0; color: #000; }
        .bg-cancelled { background-color: #dc3545; color: #fff; }
        .status-badge { padding: 0.5em 0.8em; border-radius: 50px; font-size: 0.75rem; font-weight: 600; }
    </style>
</head>
<body>

    <?php require_once "includes/header.php"; ?>

    <main class="container py-5" style="margin-top: 50px;">
        
        <div class="header-card d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 p-4 mb-4 bg-white rounded shadow-sm">
            <div>
                <h3 class="mb-1 fw-bold">My Booking History</h3>
                <p class="text-muted mb-0">Track all your past and current tour adventures.</p>
            </div>
            <div class="text-md-end">
                <div class="d-flex align-items-center gap-3 flex-wrap justify-content-md-end">
                    <span class="badge bg-primary status-badge">
                        <i class="bi bi-person-circle"></i> Tourist
                    </span>
                    <div class="clock fw-bold" id="liveClock"></div>
                </div>
                <small class="text-muted d-block mt-1">Philippine Standard Time (PST)</small>
            </div>
        </div>

        <div class="table-container bg-white p-4 rounded shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold mb-0">History List</h5>
                <a href="packages.php" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-lg"></i> New Booking
                </a>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Booking Date</th>
                            <th>Tour Package</th>
                            <th>Tour Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($touristBookingHistory)): ?>
                            <?php $no = 1; foreach ($touristBookingHistory as $booking): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td>
                                        <div class="fw-medium"><?= date('M d, Y', strtotime($booking['booking_date'] ?? 'now')) ?></div>
                                        <small class="text-muted">ID: #<?= $booking['booking_ID'] ?></small>
                                    </td>
                                    <td>
                                        <span class="text-truncate d-inline-block" style="max-width: 200px;">
                                            <?= htmlspecialchars($booking['tourpackage_name'] ?? 'Unknown Package') ?>
                                        </span>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($booking['tour_date'] ?? 'now')) ?></td>
                                    <td class="fw-bold text-success">
                                        ₱ <?= number_format($booking['total_amount'] ?? 0, 2) ?>
                                    </td>
                                    <td>
                                        <?php
                                        $status = $booking['booking_status'] ?? 'Pending';
                                        $badgeClass = match($status) {
                                            'Pending for Payment' => 'bg-pending-for-payment',
                                            'Approved' => 'bg-approved',
                                            'Completed' => 'bg-completed',
                                            'Cancelled' => 'bg-cancelled',
                                            default => 'bg-secondary'
                                        };
                                        ?>
                                        <span class="badge <?= $badgeClass ?> status-badge">
                                            <?= htmlspecialchars($status) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <a href="booking-view.php?id=<?= $booking['booking_ID'] ?>" class="btn btn-sm btn-outline-primary px-3">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-calendar-x fs-1 d-block mb-2"></i>
                                    You haven't made any bookings yet.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script src="../../assets/vendor/twbs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateClock() {
            const now = new Date();
            const options = {
                timeZone: 'Asia/Manila', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true
            };
            const clockEl = document.getElementById('liveClock');
            if(clockEl) clockEl.textContent = now.toLocaleTimeString('en-US', options);
        }
        setInterval(updateClock, 1000);
        updateClock();
    </script>
</body>
</html>
<?php
session_start(); 
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
require_once "../../classes/payment-manager.php";

$bookingObj = new Booking();
$updateBookings = $bookingObj->updateBookings();

$guideObj = new Guide();
$paymentManagerObj = new PaymentManager();

$guide_ID = $guideObj->getGuide_ID($_SESSION['user']['account_ID']);
$balance = $guideObj->getGuideBalanace($guide_ID);
$recentEarning = $paymentManagerObj->viewAllTransactionbyGuide($guide_ID);
$pendingRelease = $paymentManagerObj->viewSumofPendingbyGuide($guide_ID); 
$overAllPayout = $guideObj->getAllPayoutofGuide($guide_ID); 

// Extract scalar values safely
$currentBalance = $balance['guide_balance'] ?? 0;
$pendingAmount = $pendingRelease['total_earning_amount'] ?? 0;
$totalPayout = $overAllPayout['total_payout'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'request_payout') {
    $amount = isset($_POST['payout_amount']) ? floatval($_POST['payout_amount']) : 0.0;
    if ($amount <= 0 || $amount > $currentBalance) {
        $_SESSION['error'] = "Enter a valid payout amount.";
    } else {
        $canPayout = $paymentManagerObj->processGuidePayout($guide_ID, $amount);
        if ($canPayout) {
            $_SESSION['success'] = "Payout requested successfully.";
            header("Location: payout-request.php");
            exit;
        } else {
            $_SESSION['error'] = "Failed to process payout request. Insufficient balance or other issue.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Guide Payout</title>

    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="../../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/vendor/bootstrap-icons/bootstrap-icons.css">
    <link rel="stylesheet" href="../../assets/node_modules/@fortawesome/fontawesome-free/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="../../assets/css/guide/dashboard.css">
    <style>
        :root {
            --accent-color: #E5A13E;
            --secondary-color: #2e8b57;
        }
        .text-accent { color: var(--accent-color); }
        .stat-icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.4rem; }
        .status-badge { font-size: 0.85rem; padding: 0.35em 0.75em; }
        .earning-item { display: flex; justify-content: space-between; align-items: center; padding: 1rem; border-bottom: 1px solid #eee; }
        .earning-item:last-child { border-bottom: none; }
        .earning-amount { font-size: 1.1rem; font-weight: 600; }
        .alert-custom { border-radius: 8px; margin-bottom: 1.5rem; }
    </style>
</head>
<body class="d-flex">

    <?php require_once "includes/aside-dashboard.php"; ?>

    <!-- Main Content -->
    <main class="main-content flex-grow-1 p-4">
        <!-- Header -->
        <div class="header-card d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4 p-4 bg-white rounded shadow-sm">
            <div>
                <h3 class="mb-1 fw-bold">Payout Dashboard</h3>
                <p class="text-muted mb-0">View your earnings and request withdrawals.</p>
            </div>
            <div class="text-md-end">
                <div class="d-flex align-items-center gap-3 flex-wrap justify-content-md-end">
                    <span class="badge bg-success status-badge">
                        <i class="bi bi-check-circle"></i> <?= ucfirst($_SESSION['user']['account_status']) ?>
                    </span>
                    <div class="clock fw-semibold" id="liveClock"></div>
                </div>
                <small class="text-muted d-block mt-1">Philippine Standard Time (PST)</small>
            </div>
        </div>

        <!-- Alerts -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert-custom alert-success p-3 rounded mb-4">
                <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert-custom alert-danger p-3 rounded mb-4">
                <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Balance Stats -->
        <div class="row g-4 mb-5">
            <div class="col-md-6 col-lg-3">
                <div class="card stat-card h-100 shadow-sm border-0">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #E5A13E, #f39c12);">
                            <i class="bi bi-wallet2"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 text-muted">Available Balance</h6>
                            <h3 class="mb-0 fw-bold text-accent">₱ <?= number_format($currentBalance, 2) ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card stat-card h-100 shadow-sm border-0">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #FFE08A, #f1c40f);">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 text-muted">Pending Release</h6>
                            <h3 class="mb-0 fw-bold" style="color: #f39c12;">₱ <?= number_format($pendingAmount, 2) ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card stat-card h-100 shadow-sm border-0">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #213638, #2e8b57);">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 text-muted">Total Payouts</h6>
                            <h3 class="mb-0 fw-bold" style="color: var(--secondary-color);">₱ <?= number_format($totalPayout, 2) ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card stat-card h-100 shadow-sm border-0">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #CFE7E5, #a8e6cf);">
                            <i class="bi bi-piggy-bank"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 text-muted">Total Earnings</h6>
                            <h3 class="mb-0 fw-bold text-success">₱ <?= number_format($currentBalance + $pendingAmount + $totalPayout, 2) ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payout Request Card -->
        <div class="row g-4 mb-5">
            <div class="col-lg-5">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3">Request Payout</h5>
                        <form method="POST">
                            <input type="hidden" name="action" value="request_payout">
                            <div class="mb-3">
                                <label class="form-label fw-medium">Amount to Withdraw</label>
                                <input type="number" name="payout_amount" class="form-control form-control-lg" 
                                       min="100" step="0.01" max="<?= $currentBalance ?>" 
                                       placeholder="0.00" required>
                                <small class="text-muted">Maximum: ₱<?= number_format($currentBalance, 2) ?></small>
                            </div>
                            <button type="submit" class="btn btn-primary btn-lg w-100">
                                <i class="bi bi-box-arrow-in-right me-2"></i> Submit Payout Request
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Earnings -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="fw-bold mb-0">Recent Earnings</h5>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($recentEarning)): ?>
                    <?php foreach ($recentEarning as $earning): ?>
                        <div class="earning-item">
                            <div>
                                <div class="fw-semibold">Booking #<?= $earning['booking_ID'] ?></div>
                                <div class="text-muted small">
                                    <?= date('M d, Y', strtotime($earning['created_at'])) ?>
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="earning-amount text-accent">₱<?= number_format($earning['earning_amount'], 2) ?></div>
                                <span class="badge <?= $earning['earning_status'] === 'Released' ? 'bg-success' : 'bg-warning text-dark' ?>">
                                    <?= htmlspecialchars($earning['earning_status']) ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-receipt fs-1 d-block mb-3"></i>
                        No earnings recorded yet.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Pending Earnings Approval -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0">Earnings Needing Approval</h5>
        <span class="badge bg-warning text-dark">
            <?php 
            $pendingCount = array_filter($recentEarning, fn($e) => $e['earning_status'] === 'Pending');
            echo count($pendingCount) . ' Pending';
            ?>
        </span>
    </div>
    <div class="card-body p-0">
            <?php 
            $hasPending = false;
            foreach ($recentEarning as $earning): 
                if ($earning['earning_status'] !== 'Pending') continue;
                $hasPending = true;
            ?>
                <div class="earning-item border-bottom">
                    <div class="d-flex justify-content-between align-items-center w-100 px-4 py-3">
                        <div>
                            <div class="fw-semibold">Booking #<?= htmlspecialchars($earning['booking_ID']) ?></div>
                            <div class="text-muted small">
                                Earned on <?= date('M d, Y', strtotime($earning['created_at'])) ?>
                                &middot; ₱<?= number_format($earning['earning_amount'], 2) ?>
                            </div>
                        </div>
                        <div class="text-end">
                            <a href="payout-accepted.php?id=<?= htmlspecialchars($earning['earning_ID']) ?>"
                            class="btn btn-success btn-sm"
                            onclick="return confirm('Accept this earning and release funds to your balance?')">
                                <i class="bi bi-check-lg me-1"></i>
                                Accept & Release
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (!$hasPending): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-check-circle fs-1 d-block mb-3 text-success"></i>
                    <p class="mb-0">No pending earnings to approve at this time.</p>
                    <small>All completed bookings have been processed.</small>
                </div>
            <?php endif; ?>
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
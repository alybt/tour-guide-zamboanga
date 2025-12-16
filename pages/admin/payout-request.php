<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role_name'] !== 'Admin' || $_SESSION['user']['account_status'] == 'Suspended') {
    header('Location: ../../index.php');
    exit;
}

require_once "../../classes/payment-manager.php";
require_once "../../classes/booking.php";
require_once "../../classes/guide.php";
require_once "../../classes/account.php";

$paymentManagerObj = new PaymentManager();
$bookingObj = new Booking();
$guideObj = new Guide();
$accountObj = new Account();

// Handle session messages from payout-approve.php
$successMessage = $_SESSION['success'] ?? '';
$errorMessage = $_SESSION['error'] ?? '';

// Clear session messages after displaying them
if (!empty($successMessage)) {
    unset($_SESSION['success']);
}
if (!empty($errorMessage)) {
    unset($_SESSION['error']);
}

$transactions = $paymentManagerObj->viewAllTransaction();

// Get statistics
$totalRevenue = 0;
$totalPlatformFees = 0;
$pendingPayouts = $paymentManagerObj->countAllTransaction();
$completedTransactions = 0;

foreach ($transactions as $t) {
    $totalRevenue += (float)$t['transaction_total_amount'];
    if ($t['transaction_status'] === 'succeeded' || $t['transaction_status'] === 'paid') {
        $completedTransactions++;
    }
}

// Filter handling
$statusFilter = $_GET['status'] ?? 'all';
$dateFilter = $_GET['date'] ?? 'all';
$searchQuery = $_GET['search'] ?? '';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Transactions | Admin - Tourismo Zamboanga</title>

    <link rel="stylesheet" href="../../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/vendor/bootstrap-icons/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet"/>

    <style>
        :root {
            --primary-color: #ffffff;
            --secondary-color: #213638;
            --accent: #E5A13E;
            --secondary-accent: #CFE7E5;
            --text-dark: #2d3436;
            --text-light: #636e72;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --info: #17a2b8;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: gainsboro !important;
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
            overflow-y: auto;
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

        .stats-card {
            background: var(--primary-color);
            border-radius: 14px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            border: 1px solid rgba(207, 231, 229, 0.4);
            padding: 1.5rem;
            text-align: center;
            transition: transform 0.2s;
            height: 100%;
        }

        .stats-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }

        .stats-icon {
            font-size: 2.2rem;
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

        .filter-card {
            background: var(--primary-color);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
        }

        .table-card {
            background: var(--primary-color);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
        }

        .transaction-table {
            margin-top: 1rem;
        }

        .transaction-table th {
            background-color: var(--secondary-accent);
            color: var(--secondary-color);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            padding: 1rem;
            border: none;
        }

        .transaction-table td {
            padding: 1rem;
            vertical-align: middle;
        }

        .transaction-table tbody tr {
            transition: background-color 0.2s;
        }

        .transaction-table tbody tr:hover {
            background-color: rgba(207, 231, 229, 0.2);
        }

        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-succeeded,
        .status-paid {
            background-color: rgba(40, 167, 69, 0.15);
            color: var(--success);
        }

        .status-pending,
        .status-processing {
            background-color: rgba(255, 193, 7, 0.15);
            color: #d39e00;
        }

        .status-failed,
        .status-cancelled {
            background-color: rgba(220, 53, 69, 0.15);
            color: var(--danger);
        }

        .status-refunded {
            background-color: rgba(23, 162, 184, 0.15);
            color: var(--info);
        }

        .btn-action {
            padding: 0.4rem 0.8rem;
            font-size: 0.85rem;
            border-radius: 6px;
        }

        .search-box {
            border-radius: 8px;
            border: 1px solid #dee2e6;
            padding: 0.6rem 1rem;
        }

        .search-box:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 0.2rem rgba(229, 161, 62, 0.25);
        }

        .pagination {
            margin-top: 1.5rem;
        }

        .page-link {
            color: var(--secondary-color);
            border-radius: 6px;
            margin: 0 2px;
        }

        .page-link:hover {
            background-color: var(--accent);
            border-color: var(--accent);
            color: white;
        }

        .page-item.active .page-link {
            background-color: var(--accent);
            border-color: var(--accent);
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
            .header-card,
            .filter-card,
            .table-card {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>

    <?php include 'includes/dashboard.php'; ?>

    <!-- Main Content -->
    <main class="main-content">

        <!-- Success/Error Messages -->
        <?php if (!empty($successMessage)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($successMessage) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle-fill"></i> <?= htmlspecialchars($errorMessage) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="header-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="mb-1 fw-bold"><i class="bi bi-credit-card-2-front"></i> Payment Transactions</h3>
                    <p class="text-muted mb-0">Monitor and manage all payment transactions</p>
                </div>
                <button class="btn btn-primary" onclick="window.location.reload()">
                    <i class="bi bi-arrow-clockwise"></i> Refresh
                </button>
            </div>
        </div>

        <!-- Stats Row -->
        <div class="row g-4 mb-4">
            <div class="col-md-3 col-6">
                <div class="stats-card">
                    <div class="stats-icon text-success"><i class="bi bi-currency-dollar"></i></div>
                    <div class="stats-value">₱<?= number_format($totalRevenue, 2) ?></div>
                    <div class="stats-label">Total Revenue</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stats-card">
                    <div class="stats-icon text-primary" style="color: var(--accent) !important;">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div class="stats-value"><?= $completedTransactions ?></div>
                    <div class="stats-label">Completed</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stats-card">
                    <div class="stats-icon text-warning"><i class="bi bi-clock-history"></i></div>
                    <div class="stats-value"><?= $pendingPayouts['pending'] ?></div>
                    <div class="stats-label">Pending</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stats-card">
                    <div class="stats-icon text-info"><i class="bi bi-receipt"></i></div>
                    <div class="stats-value"><?= count($transactions) ?></div>
                    <div class="stats-label">Total Transactions</div>
                </div>
            </div>
        </div>


        <!-- Transactions Table -->
        <div class="table-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0 fw-bold">Completed Bookings</h5>
                <a href="export-transactions.php" class="btn btn-outline-success btn-sm">
                    <i class="bi bi-file-earmark-excel"></i> Export to Excel
                </a>
            </div>

            <div class="table-responsive">
                <table class="table transaction-table">
                    <thead>
                        <tr>
                            <th>Transaction ID</th>
                            <th>Booking ID</th>
                            <th>Guide</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($transactions)): ?>
                            <?php foreach ($transactions as $t): 
                                $bookingDetails = $bookingObj->getTourPackageDetailsByBookingID($t['booking_ID']);
                                $guide_ID = $bookingDetails['guide_ID'] ?? null;
                                
                                // Get guide info
                                $guideName = 'N/A';
                                if ($guide_ID) {
                                    $guideAccount = $guideObj->getGuideAccountID($guide_ID);
                                    if ($guideAccount) {
                                        $guideInfo = $accountObj->getInfobyAccountID($guideAccount['account_ID']);
                                        if (!empty($guideInfo)) {
                                            $guideName = $guideInfo[0]['name_first'] . ' ' . $guideInfo[0]['name_last'];
                                        }
                                    }
                                }
                                
                                $status = strtolower($t['transaction_status'] ?? 'pending');
                                $statusClass = 'status-' . $status;
                            ?>
                                <tr>
                                    <td>
                                        <span class="fw-semibold">#<?= htmlspecialchars($t['transaction_ID']) ?></span>
                                        <?php if (!empty($t['transaction_reference'])): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars($t['transaction_reference']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="booking-view.php?booking_id=<?= $t['booking_ID'] ?>" 
                                           class="text-decoration-none fw-semibold">
                                            #<?= htmlspecialchars($t['booking_ID']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php if ($guide_ID): ?>
                                            <a href="guide-view.php?guide_id=<?= $guide_ID ?>" 
                                               class="text-decoration-none">
                                                <?= htmlspecialchars($guideName) ?>
                                            </a>
                                            <br><small class="text-muted">ID: <?= $guide_ID ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fw-bold">
                                        ₱<?= number_format($t['transaction_total_amount'], 2) ?>
                                    </td>
                                    <td>
                                        <span class="status-badge <?= $statusClass ?>">
                                            <?= htmlspecialchars($t['transaction_status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= date('M d, Y', strtotime($t['transaction_created_date'])) ?>
                                        <br><small class="text-muted"><?= date('h:i A', strtotime($t['transaction_created_date'])) ?></small>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            
                                            <a href="transaction-details.php?id=<?= $t['transaction_ID'] ?>" 
                                               class="btn btn-outline-secondary btn-action btn-sm">
                                                <i class="bi bi-receipt"></i>
                                            </a>
                                            <?php if ($status === 'pending'): ?>
                                                <a href="payout-approve.php?id=<?= $t['transaction_ID'] ?>" 
                                                   class="btn btn-outline-success btn-action btn-sm"
                                                   onclick="return confirm('Are you sure you want to approve this transaction?');">
                                                    <i class="bi bi-check-circle"></i>
                                                </a>
                                                <form method="POST" style="display: inline;" 
                                                      onsubmit="return confirm('Are you sure you want to cancel/reject this transaction?');">
                                                    <input type="hidden" name="action" value="refund">
                                                    <input type="hidden" name="transaction_id" value="<?= $t['transaction_ID'] ?>">
                                                    <button type="submit" class="btn btn-outline-danger btn-action btn-sm">
                                                        <i class="bi bi-x-circle"></i>
                                                    </button>
                                                </form>
                                            <?php elseif ($status === 'succeeded' || $status === 'paid'): ?>
                                                <form method="POST" style="display: inline;" 
                                                      onsubmit="return confirm('Are you sure you want to refund this transaction?');">
                                                    <input type="hidden" name="action" value="refund">
                                                    <input type="hidden" name="transaction_id" value="<?= $t['transaction_ID'] ?>">
                                                    <button type="submit" class="btn btn-outline-warning btn-action btn-sm">
                                                        <i class="bi bi-arrow-counterclockwise"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>

                                
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                                    <p class="text-muted mt-3">No transactions found</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            
        </div>

    </main>

    <script src="../../assets/vendor/twbs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
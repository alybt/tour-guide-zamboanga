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
require_once "../../classes/rating.php";

$paymentManagerObj = new PaymentManager();
$bookingObj = new Booking();
$guideObj = new Guide();
$accountObj = new Account();
$ratingObj = new Rating();

// Get transaction ID from URL
$transaction_ID = $_GET['id'];

if (!$transaction_ID) {
    header('Location: payment-transactions.php');
    exit;
}

// Get transaction details
$transaction = $paymentManagerObj->getTransactionDetailsByID($transaction_ID);
if (!$transaction) {
    $_SESSION['error'] = 'Transaction not found';
    header('Location: payment-transactions.php');
    exit;
}

// Convert array to single item if needed
if (is_array($transaction) && isset($transaction[0])) {
    $transaction = $transaction[0];
}

// Validate booking_ID exists
if (empty($transaction['booking_ID'])) {
    $_SESSION['error'] = 'Transaction has no associated booking';
    header('Location: payment-transactions.php');
    exit;
}

$booking = $bookingObj->getBookingDetailsByBookingID($transaction['booking_ID']);
$bookingDetails = $bookingObj->getTourPackageDetailsByBookingID($transaction['booking_ID']);

// Get guide information
$guide_ID = $bookingDetails['guide_ID'] ?? null;
$guideName = 'N/A';
$guideEmail = 'N/A';
$guidePhone = 'N/A';
$guideAccount_ID = null;

if ($guide_ID) {
    $guideAccount = $guideObj->getGuideAccountID($guide_ID);
    if ($guideAccount) {
        $guideAccount_ID = $guideAccount['account_ID'];
        $guideInfo = $accountObj->getInfobyAccountID($guideAccount_ID);
        if (!empty($guideInfo)) {
            $guideName = $guideInfo[0]['name_first'] . ' ' . $guideInfo[0]['name_last'];
            $guideEmail = $guideInfo[0]['email'] ?? 'N/A';
            $guidePhone = $guideInfo[0]['phone_number'] ?? 'N/A';
        }
    }
}

// Get tourist information
$tourist_ID = $booking['tourist_ID'] ?? null;
$touristName = 'N/A';
$touristEmail = 'N/A';
$touristPhone = 'N/A';

if ($tourist_ID) {
    $touristInfo = $accountObj->getInfobyAccountID($tourist_ID);
    if (!empty($touristInfo)) {
        $touristName = $touristInfo[0]['name_first'] . ' ' . $touristInfo[0]['name_last'];
        $touristEmail = $touristInfo[0]['email'] ?? 'N/A';
        $touristPhone = $touristInfo[0]['phone_number'] ?? 'N/A';
    }
}

// Get payment method details

// Get reviews/ratings
$touristToGuideReview = $ratingObj->getReviewByBooking($transaction['booking_ID'] ?? '', 'Tourist to Guide');
$guideToTouristReview = $ratingObj->getReviewByBooking($transaction['booking_ID'] ?? '', 'Guide to Tourist');
$tourPackageReview = $ratingObj->getReviewByBooking($transaction['booking_ID'] ?? '', 'Tourist To Tour Packages');

// Get earnings breakdown

$status = strtolower($transaction['transaction_status'] ?? 'pending');
$statusClass = 'status-' . $status;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Details #<?= $transaction_ID ?> | Admin - Tourismo Zamboanga</title>

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
            background-color: #f8f9fa;
            color: var(--text-dark);
            min-height: 100vh;
        }

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

        .detail-card {
            background: var(--primary-color);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
            border: 1px solid rgba(207, 231, 229, 0.3);
        }

        .detail-card h5 {
            color: var(--secondary-color);
            font-weight: 700;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid var(--secondary-accent);
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: var(--text-light);
            font-weight: 500;
        }

        .info-value {
            color: var(--text-dark);
            font-weight: 600;
            text-align: right;
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

        .review-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border: 1px solid rgba(207, 231, 229, 0.5);
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .reviewer-name {
            font-weight: 600;
            color: var(--secondary-color);
        }

        .rating-stars {
            color: #ffc107;
            font-size: 1.2rem;
        }

        .review-text {
            color: var(--text-dark);
            line-height: 1.6;
            margin-top: 0.75rem;
        }

        .no-review {
            text-align: center;
            padding: 2rem;
            color: var(--text-light);
        }

        .no-review i {
            font-size: 3rem;
            opacity: 0.3;
            margin-bottom: 1rem;
        }

        .timeline {
            position: relative;
            padding-left: 30px;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 8px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: var(--secondary-accent);
        }

        .timeline-item {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -26px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--accent);
            border: 3px solid var(--primary-color);
        }

        .timeline-time {
            color: var(--text-light);
            font-size: 0.85rem;
        }

        .timeline-content {
            color: var(--text-dark);
            font-weight: 500;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        @media print {
            .sidebar,
            .action-buttons,
            .header-card button {
                display: none !important;
            }
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>

    <?php include 'includes/dashboard.php'; ?>

    <main class="main-content">
        <!-- Header -->
        <div class="header-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="mb-1 fw-bold">
                        <i class="bi bi-receipt"></i> Transaction #<?= $transaction_ID ?>
                    </h3>
                    <p class="text-muted mb-0">Complete transaction details and reviews</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-secondary" onclick="window.print()">
                        <i class="bi bi-printer"></i> Print
                    </button>
                    <a href="payment-transactions.php" class="btn btn-primary">
                        <i class="bi bi-arrow-left"></i> Back to Transactions
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Left Column -->
            <div class="col-md-6">
                <!-- Transaction Information -->
                <div class="detail-card">
                    <h5><i class="bi bi-info-circle"></i> Transaction Information</h5>
                    <div class="info-row">
                        <span class="info-label">Transaction ID</span>
                        <span class="info-value">#<?= htmlspecialchars($transaction['transaction_ID']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Reference Number</span>
                        <span class="info-value"><?= htmlspecialchars($transaction['transaction_reference'] ?? 'N/A') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Status</span>
                        <span class="info-value">
                            <span class="status-badge <?= $statusClass ?>">
                                <?= htmlspecialchars($transaction['transaction_status']) ?>
                            </span>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Amount</span>
                        <span class="info-value">₱<?= number_format($transaction['transaction_total_amount'], 2) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Created Date</span>
                        <span class="info-value"><?= date('M d, Y h:i A', strtotime($transaction['transaction_created_date'])) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Updated Date</span>
                        <span class="info-value"><?= date('M d, Y h:i A', strtotime($transaction['transaction_updated_date'])) ?></span>
                    </div>
                    <?php if (!empty($transaction['paymongo_intent_id'])): ?>
                    <div class="info-row">
                        <span class="info-label">PayMongo Intent ID</span>
                        <span class="info-value" style="font-size: 0.85rem;"><?= htmlspecialchars($transaction['paymongo_intent_id']) ?></span>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Booking Information -->
                <div class="detail-card">
                    <h5><i class="bi bi-calendar-check"></i> Booking Information</h5>
                    <div class="info-row">
                        <span class="info-label">Booking ID</span>
                        <span class="info-value">
                            <a href="booking-view.php?booking_id=<?= $transaction['booking_ID'] ?>" class="text-decoration-none">
                                #<?= htmlspecialchars($transaction['booking_ID']) ?? '' ?>
                            </a>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Tour Package</span>
                        <span class="info-value"><?= htmlspecialchars($booking['tourpackage_name'] ?? 'N/A') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Booking Status</span>
                        <span class="info-value"><?= htmlspecialchars($booking['booking_status']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Start Date</span>
                        <span class="info-value"><?= date('M d, Y', strtotime($booking['booking_start_date'])) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">End Date</span>
                        <span class="info-value"><?= date('M d, Y', strtotime($booking['booking_end_date'])) ?></span>
                    </div>
                </div>

                <!-- Payment Method -->
                 
            </div>

            <!-- Right Column -->
            <div class="col-md-6">
                <!-- Tourist Information -->
                <div class="detail-card">
                    <h5><i class="bi bi-person"></i> Tourist Information</h5>
                    <div class="info-row">
                        <span class="info-label">Name</span>
                        <span class="info-value"><?= htmlspecialchars($touristName) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email</span>
                        <span class="info-value"><?= htmlspecialchars($touristEmail) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Phone</span>
                        <span class="info-value"><?= htmlspecialchars($touristPhone) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Account ID</span>
                        <span class="info-value">
                            <a href="user-view.php?account_id=<?= $tourist_ID ?>" class="text-decoration-none">
                                #<?= $tourist_ID ?>
                            </a>
                        </span>
                    </div>
                </div>

                <!-- Guide Information -->
                <div class="detail-card">
                    <h5><i class="bi bi-person-badge"></i> Guide Information</h5>
                    <div class="info-row">
                        <span class="info-label">Name</span>
                        <span class="info-value"><?= htmlspecialchars($guideName) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email</span>
                        <span class="info-value"><?= htmlspecialchars($guideEmail) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Phone</span>
                        <span class="info-value"><?= htmlspecialchars($guidePhone) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Guide ID</span>
                        <span class="info-value">
                            <?php if ($guide_ID): ?>
                                <a href="guide-view.php?guide_id=<?= $guide_ID ?>" class="text-decoration-none">
                                    #<?= $guide_ID ?>
                                </a>
                            <?php else: ?>
                                N/A
                            <?php endif; ?>
                        </span>
                    </div>
                </div>

                <!-- Earnings Breakdown -->
                
            </div>
        </div>

        <!-- Reviews Section -->
        <div class="row mt-3">
            <div class="col-12">
                <div class="detail-card">
                    <h5><i class="bi bi-star-fill"></i> Reviews & Ratings</h5>

                    <!-- Tourist to Guide Review -->
                    <h6 class="mt-4 mb-3 text-muted"><i class="bi bi-arrow-right-circle"></i> Tourist Review for Guide</h6>
                    <?php if ($touristToGuideReview): ?>
                        <div class="review-card">
                            <div class="review-header">
                                <div>
                                    <div class="reviewer-name"><?= htmlspecialchars($touristName) ?></div>
                                    <small class="text-muted">Tourist</small>
                                </div>
                                <div class="rating-stars">
                                    <?php 
                                    $rating = (float)$touristToGuideReview['rating_value'];
                                    for ($i = 1; $i <= 5; $i++): 
                                        if ($i <= $rating): ?>
                                            <i class="bi bi-star-fill"></i>
                                        <?php elseif ($i - 0.5 <= $rating): ?>
                                            <i class="bi bi-star-half"></i>
                                        <?php else: ?>
                                            <i class="bi bi-star"></i>
                                        <?php endif;
                                    endfor; ?>
                                    <span class="ms-2 text-dark fw-bold"><?= number_format($rating, 1) ?></span>
                                </div>
                            </div>
                            <?php if (!empty($touristToGuideReview['rating_description'])): ?>
                                <div class="review-text">
                                    <i class="bi bi-quote text-muted"></i>
                                    <?= nl2br(htmlspecialchars($touristToGuideReview['rating_description'])) ?>
                                </div>
                            <?php endif; ?>
                            <small class="text-muted d-block mt-2">
                                <i class="bi bi-calendar3"></i> 
                                <?= date('M d, Y h:i A', strtotime($touristToGuideReview['rating_date'])) ?>
                            </small>
                        </div>
                    <?php else: ?>
                        <div class="no-review">
                            <i class="bi bi-chat-right-text"></i>
                            <p>No review submitted yet</p>
                        </div>
                    <?php endif; ?>

                    <!-- Guide to Tourist Review -->
                    <h6 class="mt-4 mb-3 text-muted"><i class="bi bi-arrow-left-circle"></i> Guide Review for Tourist</h6>
                    <?php if ($guideToTouristReview): ?>
                        <div class="review-card">
                            <div class="review-header">
                                <div>
                                    <div class="reviewer-name"><?= htmlspecialchars($guideName) ?></div>
                                    <small class="text-muted">Tour Guide</small>
                                </div>
                                <div class="rating-stars">
                                    <?php 
                                    $rating = (float)$guideToTouristReview['rating_value'];
                                    for ($i = 1; $i <= 5; $i++): 
                                        if ($i <= $rating): ?>
                                            <i class="bi bi-star-fill"></i>
                                        <?php elseif ($i - 0.5 <= $rating): ?>
                                            <i class="bi bi-star-half"></i>
                                        <?php else: ?>
                                            <i class="bi bi-star"></i>
                                        <?php endif;
                                    endfor; ?>
                                    <span class="ms-2 text-dark fw-bold"><?= number_format($rating, 1) ?></span>
                                </div>
                            </div>
                            <?php if (!empty($guideToTouristReview['rating_description'])): ?>
                                <div class="review-text">
                                    <i class="bi bi-quote text-muted"></i>
                                    <?= nl2br(htmlspecialchars($guideToTouristReview['rating_description'])) ?>
                                </div>
                            <?php endif; ?>
                            <small class="text-muted d-block mt-2">
                                <i class="bi bi-calendar3"></i> 
                                <?= date('M d, Y h:i A', strtotime($guideToTouristReview['rating_date'])) ?>
                            </small>
                        </div>
                    <?php else: ?>
                        <div class="no-review">
                            <i class="bi bi-chat-right-text"></i>
                            <p>No review submitted yet</p>
                        </div>
                    <?php endif; ?>

                    <!-- Tour Package Review -->
                    <h6 class="mt-4 mb-3 text-muted"><i class="bi bi-box-seam"></i> Tour Package Review</h6>
                    <?php if ($tourPackageReview): ?>
                        <div class="review-card">
                            <div class="review-header">
                                <div>
                                    <div class="reviewer-name"><?= htmlspecialchars($touristName) ?></div>
                                    <small class="text-muted">Reviewed: <?= htmlspecialchars($bookingDetails['tourPackage_name'] ?? 'Tour Package') ?></small>
                                </div>
                                <div class="rating-stars">
                                    <?php 
                                    $rating = (float)$tourPackageReview['rating_value'];
                                    for ($i = 1; $i <= 5; $i++): 
                                        if ($i <= $rating): ?>
                                            <i class="bi bi-star-fill"></i>
                                        <?php elseif ($i - 0.5 <= $rating): ?>
                                            <i class="bi bi-star-half"></i>
                                        <?php else: ?>
                                            <i class="bi bi-star"></i>
                                        <?php endif;
                                    endfor; ?>
                                    <span class="ms-2 text-dark fw-bold"><?= number_format($rating, 1) ?></span>
                                </div>
                            </div>
                            <?php if (!empty($tourPackageReview['rating_description'])): ?>
                                <div class="review-text">
                                    <i class="bi bi-quote text-muted"></i>
                                    <?= nl2br(htmlspecialchars($tourPackageReview['rating_description'])) ?>
                                </div>
                            <?php endif; ?>
                            <small class="text-muted d-block mt-2">
                                <i class="bi bi-calendar3"></i> 
                                <?= date('M d, Y h:i A', strtotime($tourPackageReview['rating_date'])) ?>
                            </small>
                        </div>
                    <?php else: ?>
                        <div class="no-review">
                            <i class="bi bi-chat-right-text"></i>
                            <p>No review submitted yet</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="payment-transactions.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Transactions
            </a>
            <button class="btn btn-outline-primary" onclick="window.print()">
                <i class="bi bi-printer"></i> Print Receipt
            </button>
            <?php if ($status === 'pending'): ?>
                <a href="payout-approve.php?id=<?= $transaction_ID ?>" 
                   class="btn btn-success"
                   onclick="return confirm('Are you sure you want to approve this transaction?');">
                    <i class="bi bi-check-circle"></i> Approve Transaction
                </a>
            <?php endif; ?>
        </div>

    </main>

    <script src="../../assets/vendor/twbs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
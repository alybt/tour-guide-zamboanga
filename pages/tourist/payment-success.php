<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role_name'] !== 'Tourist') {
    header('Location: ../../index.php');
    exit;
}

require_once "../../classes/payment-manager.php";
require_once "../../classes/booking.php";

$tourist_ID = $_SESSION['user']['account_ID'];
$booking_ID = $_GET['id'] ?? null;
$payment_intent_id = $_GET['payment_intent'] ?? null;

if (!$booking_ID || !is_numeric($booking_ID)) {
    die("Invalid booking ID.");
}

$paymentObj = new PaymentManager();
$bookingObj = new Booking();

// Get booking details
$booking = $bookingObj->viewBookingByTouristANDBookingID($booking_ID);

if (!$booking) {
    die("Booking not found.");
}

// Get payment details
$paymentData = $paymentObj->getPaymentByBooking($booking_ID);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful - Tourismo Zamboanga</title>
    <link rel="stylesheet" href="../../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/vendor/bootstrap-icons/bootstrap-icons.css">
    <link rel="stylesheet" href="../../assets/css/tourist/header.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .success-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
            max-width: 600px;
            text-align: center;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #1a5d1a, #2d8a2d);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 40px;
            color: white;
            animation: scaleIn 0.5s ease-out 0.2s both;
        }

        @keyframes scaleIn {
            from {
                transform: scale(0);
            }
            to {
                transform: scale(1);
            }
        }

        h1 {
            color: #1a5d1a;
            font-weight: 700;
            margin: 20px 0;
            font-size: 2rem;
        }

        .success-message {
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .booking-details {
            background: #f9f9f9;
            border-radius: 12px;
            padding: 20px;
            margin: 30px 0;
            text-align: left;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 600;
            color: #2c3e50;
        }

        .detail-value {
            color: #666;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            justify-content: center;
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, #1a5d1a, #2d8a2d);
            color: white;
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-block;
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(26, 93, 26, 0.3);
            color: white;
        }

        .btn-secondary-custom {
            background: #f0f0f0;
            color: #333;
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-block;
        }

        .btn-secondary-custom:hover {
            background: #e0e0e0;
            color: #333;
        }

        .reference-number {
            background: #e8f5e9;
            border-left: 4px solid #1a5d1a;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            word-break: break-all;
        }
    </style>
</head>
<body>

<div class="success-container">
    <div class="success-icon">✓</div>
    
    <h1>Payment Successful!</h1>
    
    <p class="success-message">
        Your payment has been processed successfully. Your booking is now confirmed and you can proceed with your tour.
    </p>

    <?php if ($paymentData): ?>
    <div class="booking-details">
        <div class="detail-row">
            <span class="detail-label">Booking ID:</span>
            <span class="detail-value">#<?= htmlspecialchars($booking_ID) ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Package:</span>
            <span class="detail-value"><?= htmlspecialchars($booking['tourpackage_name'] ?? 'N/A') ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Tour Guide:</span>
            <span class="detail-value"><?= htmlspecialchars($booking['guide_name'] ?? 'Not Assigned') ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Travel Dates:</span>
            <span class="detail-value">
                <?= date('M j, Y', strtotime($booking['booking_start_date'])) ?> → 
                <?= date('M j, Y', strtotime($booking['booking_end_date'])) ?>
            </span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Amount Paid:</span>
            <span class="detail-value" style="font-weight: 700; color: #1a5d1a;">
                ₱<?= number_format($paymentData['paymentinfo_total_amount'] ?? 0, 2) ?>
            </span>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($payment_intent_id): ?>
    <div class="reference-number">
        <strong>Transaction Reference:</strong><br>
        <?= htmlspecialchars($payment_intent_id) ?>
    </div>
    <?php endif; ?>

    <div class="action-buttons">
        <a href="itinerary-view.php?id=<?= urlencode($booking_ID) ?>" class="btn-primary-custom">
            View Itinerary
        </a>
        <a href="booking.php" class="btn-secondary-custom">
            Back to Bookings
        </a>
    </div>
</div>

</body>
</html>

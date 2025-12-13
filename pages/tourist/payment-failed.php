<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role_name'] !== 'Tourist') {
    header('Location: ../../index.php');
    exit;
}

$booking_ID = $_GET['id'] ?? null;
$error_message = $_GET['error'] ?? 'Your payment could not be processed. Please try again.';

if (!$booking_ID || !is_numeric($booking_ID)) {
    die("Invalid booking ID.");
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Failed - Tourismo Zamboanga</title>
    <link rel="stylesheet" href="../../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/vendor/bootstrap-icons/bootstrap-icons.css">
    <link rel="stylesheet" href="../../assets/css/tourist/header.css">
    <style>
        body {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .error-container {
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

        .error-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #f5576c, #f093fb);
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
            color: #f5576c;
            font-weight: 700;
            margin: 20px 0;
            font-size: 2rem;
        }

        .error-message {
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 30px;
            line-height: 1.6;
            background: #fff3f3;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #f5576c;
        }

        .error-details {
            background: #f9f9f9;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
            text-align: left;
        }

        .error-details h3 {
            color: #f5576c;
            margin-bottom: 15px;
            font-size: 1rem;
        }

        .error-details ul {
            margin: 0;
            padding-left: 20px;
            color: #666;
        }

        .error-details li {
            margin: 8px 0;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, #f5576c, #f093fb);
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
            box-shadow: 0 8px 20px rgba(245, 87, 108, 0.3);
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
    </style>
</head>
<body>

<div class="error-container">
    <div class="error-icon">✕</div>
    
    <h1>Payment Failed</h1>
    
    <div class="error-message">
        <?= htmlspecialchars($error_message) ?>
    </div>

    <div class="error-details">
        <h3>What you can do:</h3>
        <ul>
            <li>Check your card details and try again</li>
            <li>Ensure you have sufficient funds</li>
            <li>Try a different payment method</li>
            <li>Contact your bank if the issue persists</li>
        </ul>
    </div>

    <div class="action-buttons">
        <a href="payment-form.php?id=<?= urlencode($booking_ID) ?>" class="btn-primary-custom">
            Try Again
        </a>
        <a href="booking.php" class="btn-secondary-custom">
            Back to Bookings
        </a>
    </div>
</div>

</body>
</html>

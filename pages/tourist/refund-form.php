<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role_name'] !== 'Tourist') {
    header('Location: ../../index.php');
    exit;
} else if ($_SESSION['user']['account_status'] == 'Suspended') {
    header('Location: account-suspension.php');
    exit;
} else if ($_SESSION['user']['account_status'] == 'Pending') {
    header('Location: account-pending.php');
    exit;
}

require_once "../../classes/tourist.php";
require_once "../../classes/payment-manager.php";
require_once "../../classes/booking.php";
require_once "../../classes/activity-log.php";

$activityObj = new ActivityLogs();

$tourist_ID = $_SESSION['user']['account_ID'];
$booking_ID = $_GET['id'] ?? null;

if (!$booking_ID || !is_numeric($booking_ID)) {
    die("Invalid booking ID.");
} 

$touristObj = new Tourist();
$paymentObj = new PaymentManager();
$bookingObj = new Booking();

// Booking details
$booking = $bookingObj->viewBookingByTouristANDBookingID($booking_ID);
$companions = $bookingObj->getCompanionsByBooking($booking_ID);

// Payment & Method details
$payment = $paymentObj->getPaymentByBooking($booking_ID); // implement in PaymentManager
$method = $paymentObj->getMethodByPayment($payment['paymentinfo_ID'] ?? 0); // implement in PaymentManager
$refundCategories = $paymentObj->getAllRefundCategories($_SESSION['role_ID']);

// Calculate days difference
$today = new DateTime();
$booking_start = new DateTime($booking['booking_start_date'] ?? $today->format('Y-m-d'));
$diffDays = (int)$today->diff($booking_start)->format("%r%a");

// Determine refund percentage
if ($diffDays >= 4) {
    $refundPercentage = 1.0; // 100%
} elseif ($diffDays >= 2) {
    $refundPercentage = 0.5; // 50%
} else {
    $refundPercentage = 0; // No refund
}

// Base amount
$totalAmount = $payment['paymentinfo_total_amount'] ?? 0;

// Fees
$processingFeeRate = 0.02; // 2%
$refundFeeRate = 0.05; // 5%

$refundableAmount = $totalAmount * $refundPercentage;
$processingFee = $refundableAmount * $processingFeeRate;
$refundFee = $refundableAmount * $refundFeeRate;
$totalRefund = $refundableAmount - $processingFee - $refundFee;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Basic validation
    $categoryrefund_ID = $_POST['categoryrefund_ID'] ?? null;
    $refund_reason = trim($_POST['refund_reason'] ?? '');
    $refund_processingfee = $_POST['refund_processingfee'] ?? 0;
    $refund_refundfee = $_POST['refund_refundfee'] ?? 0;
    $refund_total_amount = $_POST['refund_total_amount'] ?? 0;

    // Ensure required fields exist
    if (!$categoryrefund_ID || empty($refund_reason)) {
        $_SESSION['error'] = "Please complete all required fields.";
        header("Location: refund-form.php?id=" . $booking_ID);
        exit;
    }

    // Check payment existence and transaction ID
    if (empty($payment) || empty($payment['transaction_ID'])) {
        $_SESSION['error'] = "No valid payment record found for this booking.";
        header("Location: refund-form.php?id=" . $booking_ID);
        exit;
    }

    $transaction_ID = $payment['transaction_ID'];
    $refund_status = "Pending";

    // Insert refund
    $result = $paymentObj->refundABooking($booking_ID, $transaction_ID, $categoryrefund_ID,$refund_reason, $refund_status, $refund_processingfee, $refund_refundfee, $refund_total_amount);
    

    if ($result) {
        $_SESSION['success'] = "Refund request submitted successfully.";
        $action = $activityObj->touristRefundBooking($booking_ID, $tourist_ID);
        header("Location: booking.php");
        exit;
    } else {
        $_SESSION['error'] = "Failed to submit refund request.";
        header("Location: refund-form.php?id=" . $booking_ID);
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Booking Details</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
:root {
    --primary-color: #ffffff;
    --secondary-color: #213638;
    --accent: #E5A13E;
    --secondary-accent: #CFE7E5;
    --muted-color: gainsboro;
    
}

body {
    background-color: var(--muted-color);
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    margin: 0;
    padding: 0;
}

main{
    margin-top: 6rem;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

h1, h2 {
    color: var(--secondary-color);
}

.card {
    border: none;
    box-shadow: 0 0 15px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.card-header {
    background: var(--secondary-color);
    color: var(--primary-color);
    font-weight: bold;
}

.table {
    margin-bottom: 0;
}

.table th {
    background: var(--secondary-accent);
    color: var(--secondary-color);
}

.form-label {
    color: var(--secondary-color);
    font-weight: 600;
}

.form-control, .form-select {
    border: 2px solid #e0e0e0;
    border-radius: 5px;
}

.form-control:focus, .form-select:focus {
    border-color: var(--accent);
    box-shadow: none;
}

.btn-primary {
    background: var(--accent);
    border: none;
    color: var(--secondary-color);
}

.btn-primary:hover {
    background: #d89435;
}

.btn-secondary {
    background: var(--secondary-color);
    color: var(--primary-color);
}

.btn-secondary:hover {
    background: #1a2b2d;
}

.alert {
    margin-bottom: 20px;
}
</style>
</head>
<body>
<?php include 'includes/header.php'; ?>

<main>
    <div class="container">
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?= $_SESSION['error'] ?>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>


    <div class="card mb-4">
        <div class="card-header">Booking Information</div>
        <div class="card-body">
            <table class="table table-striped">
                <tbody>
                    <tr><th>Booking ID</th><td><?= htmlspecialchars($booking['booking_ID'] ?? '-') ?></td></tr>
                    <tr><th>Tourist ID</th><td><?= htmlspecialchars($booking['tourist_ID'] ?? '-') ?></td></tr>
                    <tr><th>Self Included</th><td><?= $booking['booking_isselfincluded'] ? 'Yes' : 'No' ?></td></tr>
                    <tr><th>Status</th><td><?= htmlspecialchars($booking['booking_status'] ?? '-') ?></td></tr>
                    <tr><th>Created At</th><td><?= htmlspecialchars($booking['booking_created_at'] ?? '-') ?></td></tr>
                    <tr><th>Tour Package ID</th><td><?= htmlspecialchars($booking['tourpackage_ID'] ?? '-') ?></td></tr>
                    <tr><th>Booking Date</th><td><?= htmlspecialchars($booking['booking_start_date'] ?? '-') ?> - <?= htmlspecialchars($booking['booking_end_date'] ?? '-') ?></td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">Companions</div>
        <div class="card-body">
            <?php if (!empty($companions)): ?>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Category</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($companions as $c): ?>
                            <tr>
                                <td><?= htmlspecialchars($c['companion_name'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($c['companion_category_name'] ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No companions added.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">Payment Information</div>
        <div class="card-body">
            <?php if ($payment): ?>
                <table class="table table-striped">
                    <tbody>
                        <tr><th>Payment Info ID</th><td><?= htmlspecialchars($payment['paymentinfo_ID'] ?? '-') ?></td></tr>
                        <tr><th>Total Amount</th><td>₱<?= number_format($payment['paymentinfo_total_amount'] ?? 0, 2) ?></td></tr>
                        <tr><th>Payment Date</th><td><?= htmlspecialchars($payment['paymentinfo_date'] ?? '-') ?></td></tr>
                        <tr><th>Transaction ID</th><td><?= htmlspecialchars($payment['transaction_ID'] ?? '-') ?></td></tr>
                        <tr><th>Status</th><td><?= htmlspecialchars($payment['transaction_status'] ?? '-') ?></td></tr>
                        <tr><th>Reference</th><td><?= htmlspecialchars($payment['transaction_reference'] ?? '-') ?></td></tr>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No payment recorded yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">Payment Method Details</div>
        <div class="card-body">
            <?php if ($method): ?>
                <table class="table table-striped">
                    <tbody>
                        <tr><th>Amount</th><td>₱<?= number_format($method['method_amount'] ?? 0, 2) ?></td></tr>
                        <tr><th>Currency</th><td><?= htmlspecialchars($method['method_currency'] ?? '-') ?></td></tr>
                        <tr><th>Name</th><td><?= htmlspecialchars($method['method_name'] ?? '-') ?></td></tr>
                        <tr><th>Email</th><td><?= htmlspecialchars($method['method_email'] ?? '-') ?></td></tr>
                        <tr><th>Phone</th><td><?= htmlspecialchars($method['phone_ID'] ?? '-') ?></td></tr>
                        <tr><th>Address</th><td>
                            <?= htmlspecialchars($method['method_line1'] ?? '-') ?>,
                            <?= htmlspecialchars($method['method_city'] ?? '-') ?>,
                            <?= htmlspecialchars($method['method_postalcode'] ?? '-') ?>,
                            <?= htmlspecialchars($method['method_country'] ?? '-') ?>
                        </td></tr>
                        <tr><th>Card Number</th><td><?= htmlspecialchars($method['method_cardnumber'] ?? '-') ?></td></tr>
                        <tr><th>Expiry</th><td><?= htmlspecialchars($method['method_expmonth'] ?? '-') ?>/<?= htmlspecialchars($method['method_expyear'] ?? '-') ?></td></tr>
                        <tr><th>CVC</th><td><?= htmlspecialchars($method['method_cvc'] ?? '-') ?></td></tr>
                        <tr><th>Status</th><td><?= htmlspecialchars($method['method_status'] ?? '-') ?></td></tr>
                        <tr><th>Created At</th><td><?= htmlspecialchars($method['method_created_at'] ?? '-') ?></td></tr>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No payment method recorded.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Refund Request Form</div>
        <div class="card-body">
            <form action="" method="POST">
                <div class="mb-3">
                    <label for="categoryrefund_ID" class="form-label">Refund Category</label>
                    <select id="categoryrefund_ID" name="categoryrefund_ID" class="form-select" required>
                        <option value="">-- Select Refund Category --</option>
                        <?php foreach ($refundCategories as $c): ?>
                            <option value="<?= $c['categoryrefund_ID'] ?>"><?= htmlspecialchars($c['categoryrefundname_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="refund_reason" class="form-label">Reason for Refund</label>
                    <textarea id="refund_reason" name="refund_reason" class="form-control" placeholder="Please explain your reason..." required rows="4"></textarea>
                </div>

                <div class="mb-3">
                    <label for="refund_processingfee" class="form-label">Processing Fee</label>
                    <input type="number" id="refund_processingfee" name="refund_processingfee" class="form-control" step="0.01" min="0" readonly>
                </div>

                <div class="mb-3">
                    <label for="refund_refundfee" class="form-label">Refund Fee</label>
                    <input type="number" id="refund_refundfee" name="refund_refundfee" class="form-control" step="0.01" min="0" readonly>
                </div>

                <div class="mb-3">
                    <label for="refund_total_amount" class="form-label">Total Refund Amount</label>
                    <input type="number" id="refund_total_amount" name="refund_total_amount" class="form-control" step="0.01" min="0" readonly>
                </div>

                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary">Submit Refund Request</button>
                    <a href="booking.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <div class="mt-3">
        <a href="booking.php" class="btn btn-link">← Back to My Bookings</a>
    </div>
</div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const processingFeeInput = document.getElementById('refund_processingfee');
    const refundFeeInput = document.getElementById('refund_refundfee');
    const totalRefundInput = document.getElementById('refund_total_amount');

    // Values from PHP
    const processingFee = <?= json_encode($processingFee) ?>;
    const refundFee = <?= json_encode($refundFee) ?>;
    const totalRefund = <?= json_encode($totalRefund) ?>;

    // Populate the fields (without negative signs, as fees are deductions but displayed positive)
    processingFeeInput.value = processingFee.toFixed(2);
    refundFeeInput.value = refundFee.toFixed(2);
    totalRefundInput.value = totalRefund.toFixed(2);

    // If you want to show deductions, perhaps add text or style
});
</script>
</body>
</html>
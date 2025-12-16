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




?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Guide Payout </title>

     
    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="../../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/vendor/bootstrap-icons/bootstrap-icons.css">
    <link href="../../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/node_modules/@fortawesome/fontawesome-free/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="../../assets/css/guide/dashboard.css">
</head>
<body class="d-flex">

    <?php 
    require_once "includes/aside-dashboard.php"; 
    ?>

    <!-- Main Content -->
    <main class="main-content flex-grow-1">

        <div>
            Balance: <?= $balance['guide_balance'] ?>
            <br>
            Pending Release: <?= $pendingRelease['total_earning_amount']?>
            <br>
            Total Payout: <?= $overAllPayout ['total_payout'] ?>
        </div>

        <div>
            <p> Recent Earning</p>
            <?php foreach ($recentEarning as $earning) {?>
                <div class="earning-item">
                    <div>
                        <div class="fw-semibold">Booking #<?php echo $earning['booking_ID']; ?></div>
                        <div class="earning-date">
                            <?php echo date('M d, Y', strtotime($earning['created_at'])); ?>
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="earning-amount">
                            ₱<?php echo number_format($earning['earning_amount'], 2); ?>
                        </div>
                        <span class="earning-status bg-<?php echo $earning['earning_status'] === 'Released' ? 'success' : 'warning'; ?> text-white">
                            <?php echo $earning['earning_status']; ?>
                        </span>
                    </div>
                </div>
            <?php }?> 
        </div> 

        <div>
            <p> Earning need to Approve</p>
            <?php foreach ($recentEarning as $earning) {
                if ($earning['earning_status'] == 'Pending'){
                ?>
                    <div class="earning-item">
                        <div>
                            <div class="fw-semibold">Booking #<?php echo $earning['booking_ID']; ?></div>
                            <div class="earning-date">
                                <?php echo date('M d, Y', strtotime($earning['created_at'])); ?>
                            </div>
                        </div>
                        <div class="text-end">
                            <a href="payout-accepted.php?id=<?= $earning['earning_ID']?>">
                                <button type="submit" class="btn btn-outline-danger btn-action btn-sm">
                                <i class="bi approved"></i>
                                Accept
                            </button>
                            </a>
                        </div>
                    </div>
            <?php }
                }?> 
        </div>



    
    </main> 


    <script src="../../assets/vendor/twbs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
 
    <script>
    </script>
</body>
</html>
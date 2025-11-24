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
require_once "../../classes/tour-manager.php";

$touristObj = new Tourist();
$TourManagerObj = new TourManager();
$packages = $TourManagerObj->viewAllPackages();
$packageCategory = $TourManagerObj->getTourSpotsCategory();

$touristBookingHistory = $touristObj->getBookingHistory($_SESSION['user']['account_ID']);



?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking History</title>

    <link rel="stylesheet" href="../../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/vendor/bootstrap-icons/bootstrap-icons.css" >
    <link rel="stylesheet" href="../../assets/css/tourist/header.css">
</head>
<body>
    <?php require_once "includes/header.php";  ?>

    
        
        
    
        <div class="mt-5">
            <div class="card-custom p-4">
                <h5 class="mb-3">Recent Activity</h5>
                <ul class="list-group list-group-flush">
                    
                <?php $no = 1;
                foreach ($touristBookingHistory as $booking) { ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <p><?= $no++ ?></p>
                        <p><?= $booking['tourpackage_name'] ?? ''?></p>
                        
                        <small class="text-muted"><a href="booking-view.php?id=<?= $booking['booking_ID'] ?>" class="btn btn-primary btn-sm flex-fill"> View </a></small>
                    </li>
                <?php } ?>    
                    
                </ul>
            </div>
        </div>



<main id="packagesContainer" class="main-contents ">
    
</main>


<script>
   
</script>

<script src="../../assets/vendor/twbs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
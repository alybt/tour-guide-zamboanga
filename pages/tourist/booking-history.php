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

$TourManagerObj = new TourManager();
$packages = $TourManagerObj->viewAllPackages();
$packageCategory = $TourManagerObj->getTourSpotsCategory();



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
 



<main id="packagesContainer" class="main-contents ">
    
</main>


<script>
   
</script>

<script src="../../assets/vendor/twbs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
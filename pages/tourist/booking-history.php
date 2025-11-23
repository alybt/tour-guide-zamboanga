<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role_name'] !== 'Tourist') {
    header('Location: ../../index.php');
    exit;
}

require_once "../../classes/booking.php";
require_once "../../classes/tourist.php";


?>
<!DOCTYPE html>
<html>
<head>
    <title>Booking History/title>
    <link rel="stylesheet" href="/../../assets/css/tourist/header.css">
    <link rel="stylesheet" href="../../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/vendor/components/font-awesome/css/all.min.css">
    <link rel="stylesheet" href="../../assets/vendor/bootstrap-icons/bootstrap-icons.css" >
    
</head>
<body>
     <?php require_once "includes/header.php"; 
    include_once "includes/header.php";?>

    <h2>My Bookings</h2>
    
    
</body>
</html>

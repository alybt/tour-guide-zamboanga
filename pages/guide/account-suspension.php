<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role_name'] !== 'Tour Guide') {
    header('Location: ../../index.php');
    exit;
} else if ($_SESSION['user']['account_status'] == 'Pending'){
    header('Location: account-pending.php');
}
require_once "../../classes/guide.php";

$guideObj = new Guide();

$guide_ID = $guideObj->getGuide_ID($_SESSION['user']['account_ID']);


?>
<!DOCTYPE html>
<html>
<head>
    <title>Suspended Account</title>
</head>
<body>
    
    
    <h2>Account Suspended</h2>
    
    
</body>
</html>
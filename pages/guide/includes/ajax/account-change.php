<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role_name'] !== 'Tour Guide') {
    header('Location: ../../../../index.php');
    exit;
} else if ($_SESSION['user']['account_status'] == 'Suspended'){
    header('Location: ../../account-suspension.php');
    exit;
} else if ($_SESSION['user']['account_status'] == 'Pending'){
    header('Location: ../../account-pending.php');
}
require_once "../../../../classes/guide.php";
require_once "../../../../classes/activity-log.php";

$activityObj = new ActivityLogs();
$guideObj = new Guide();


$guide_ID = $guideObj->getGuide_ID($_SESSION['user']['account_ID']);

if (isset($_SESSION['user']) || $_SESSION['user']['role_name'] == 'Tour Guide'){
    $result = $guideObj->changeAccountToTourist($_SESSION['user']['user_ID']);
    $account_ID = $result['account_ID'];

    if ($result){
        $_SESSION["account_ID"] = $account_ID ; 
        $_SESSION["role_ID"] = 3;
        $_SESSION['user']['role_name'] = 'Tourist';
        $activity = $activityObj->guideChangeToTourist($guide_ID, $account_ID);
        header('Location: ../tourist/index.php');
    }



}

?>
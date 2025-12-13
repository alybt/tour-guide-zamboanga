<?php
session_start();
require_once "../../classes/activity-log.php";

$activityObj = new ActivityLogs();

$activity = $activityObj->logoutActivity($_SESSION["account_ID"]);

$_SESSION = array();
session_unset();
session_destroy();
header('Location: ../../index.php');
exit;

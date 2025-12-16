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

$paymentManagerObj = new PaymentManager();  
$guideObj = new Guide(); 

$guide_ID = $guideObj->getGuide_ID($_SESSION['user']['account_ID']);


$response = ['success' => false];

if (isset($_GET['id'])) {
    $earning_ID = intval($_GET['id']); 
    $earning = $paymentManagerObj->getEarningByID($earning_ID);
    $transaction_ID = $earning['transaction_ID'] ?? null;
    $balanceRow = $guideObj->getGuideBalanace($guide_ID);
    $guide_balance = $balanceRow['guide_balance'] ?? 0;
    $earning_amount = $earning['earning_amount'] ?? null;
    
    if ($earning_ID && $transaction_ID && $earning_amount !== null) {
        if ($paymentManagerObj->addingToBalanceMoney($guide_ID, $guide_balance, $earning_amount, $transaction_ID, $earning_ID)) {
            $response['success'] = true;
            $response['message'] = "adding To Balance Succesfully";
        } else {
            $response['error'] = "Failed to add balance #$earning_ID.";
        }
    } else {
        $response['error'] = "Invalid earning or transaction reference.";
    }
} else {
    $response['error'] = "Invalid transaction ID.";
}

if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode($response);
} else {
    if ($response['success']) {
        $_SESSION['success'] = $response['message'];
    } else {
        $_SESSION['error'] = $response['error'] ?? "Failed to process request.";
    }
    header("Location: payout-request.php");
}
exit;
?>

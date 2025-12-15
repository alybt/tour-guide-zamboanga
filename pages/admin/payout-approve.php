<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role_name'] !== 'Admin' || $_SESSION['user']['account_status'] == 'Suspended') {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    } else {
        header('Location: ../../index.php');
    }
    exit;
}

require_once "../../classes/payment-manager.php"; 

$paymentManagerObj = new PaymentManager();

$response = ['success' => false];

if (isset($_GET['id'])) {
    $transaction_ID = intval($_GET['id']);
    
    $update = $paymentManagerObj->transactionApproved($transaction_ID);
    
    if ($update) {
        $response['success'] = true;
        $response['message'] = "Transaction approved successfully.";
    } else {
        $response['error'] = "Failed to approve transaction.";
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
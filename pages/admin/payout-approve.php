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
    
    // Get transaction details to get the amount
    $sql = "SELECT transaction_total_amount FROM Payment_Transaction WHERE transaction_ID = :transaction_id";
    $db = $paymentManagerObj->connect();
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':transaction_id', $transaction_ID, PDO::PARAM_INT);
    $stmt->execute();
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($transaction) {
        if ($paymentManagerObj->transactionApproved($transaction_ID, $transaction['transaction_total_amount'])) {
            $response['success'] = true;
            $response['message'] = "Transaction #$transaction_ID succeeded successfully.";
        } else {
            $response['error'] = "Failed to approve transaction #$transaction_ID.";
        }
    } else {
        $response['error'] = "Transaction #$transaction_ID not found.";
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
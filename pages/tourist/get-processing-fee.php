<?php
require_once "../../classes/payment-manager.php";

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid ID']);
    exit;
}

$methodcategory_ID = (int)$_GET['id'];

$paymentObj = new PaymentManager();
$fee = $paymentObj->getProcessingFeeByID($methodcategory_ID);

if ($fee !== null) {
    echo json_encode(['success' => true, 'fee' => $fee]);
} else {
    echo json_encode(['success' => false, 'error' => 'Fee not found']);
}
?>

<?php 
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role_name'] !== 'Admin' || $_SESSION['user']['account_status'] == 'Suspended') {
    header('Location: ../../index.php');
    exit;
}
 
require_once "../../classes/payment-manager.php";
require_once "../../classes/booking.php";
require_once "../../classes/guide.php";
require_once "../../classes/account.php";

$paymentManagerObj = new PaymentManager();
$bookingObj = new Booking();
$guideObj = new Guide();
$accountObj = new Account();
 

$transactions = $paymentManagerObj->viewAllTransaction();
 
$filename = "Tourismo_Transactions_" . date('Ymd_His') . ".csv";

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');
 
$output = fopen('php://output', 'w');
 
$delimiter = ','; 
 
$headers = [
    'Transaction ID',
    'Booking ID',
    'Guide Name',
    'Guide ID',
    'Amount (PHP)',
    'Platform Fee (PHP)',  
    'Total Amount (PHP)',
    'Status',
    'Payment Reference',
    'PayMongo Intent ID',
    'Created Date',
    'Created Time'
];
fputcsv($output, $headers, $delimiter); 

if (!empty($transactions)) {
    foreach ($transactions as $t) { 
        $bookingDetails = $bookingObj->getTourPackageDetailsByBookingID($t['booking_ID']);
        $guide_ID = $bookingDetails['guide_ID'] ?? null;
        $guideName = 'N/A';
        $guideAccountId = null;

        if ($guide_ID) {
            $guideAccount = $guideObj->getGuideAccountID($guide_ID);
            if ($guideAccount) {
                $guideAccountId = $guideAccount['account_ID'];
                $guideInfo = $accountObj->getInfobyAccountID($guideAccountId);
                if (!empty($guideInfo)) {
                    $guideName = $guideInfo[0]['name_first'] . ' ' . $guideInfo[0]['name_last'];
                }
            }
        }
         
        $row = [
            $t['transaction_ID'],
            $t['booking_ID'],
            $guideName,
            $guide_ID, 
            $t['transaction_amount'] ?? 'N/A',  
            $t['transaction_platform_fee'] ?? 'N/A',  
            $t['transaction_total_amount'],
            ucwords($t['transaction_status'] ?? 'pending'),
            $t['transaction_reference'],
            $t['paymongo_intent_id'],
            date('Y-m-d', strtotime($t['transaction_created_date'])),
            date('H:i:s', strtotime($t['transaction_created_date']))
        ]; 
        fputcsv($output, $row, $delimiter);
    }
} 
fclose($output);
exit;  
?>
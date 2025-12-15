<?php
/**
 * Paymongo Webhook Handler
 * Handles payment status updates from Paymongo
 */

require_once "../../config/database.php";
require_once "../../classes/payment-manager.php";
require_once "../../classes/activity-log.php";

// Get raw POST data
$input = file_get_contents('php://input');
$event = json_decode($input, true);

// Log webhook for debugging
error_log("[Paymongo Webhook] " . json_encode($event));

if (!$event || !isset($event['data']['attributes']['type'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid webhook']);
    exit;
}

try {
    $eventType = $event['data']['attributes']['type'];
    $eventData = $event['data']['attributes']['data'] ?? [];
    
    $paymentObj = new PaymentManager();
    $activityObj = new ActivityLogs();
    
    // Handle payment.paid event
    if ($eventType === 'payment.paid') {
        $paymentIntentId = $eventData['id'] ?? null;
        $status = $eventData['attributes']['status'] ?? null;
        
        if ($paymentIntentId && $status === 'succeeded') {
            // Update transaction status to Paid
            $sql = "UPDATE Payment_Transaction 
                    SET transaction_status = 'Paid',
                        paymongo_intent_id = :intent_id,
                        transaction_updated_date = NOW()
                    WHERE paymongo_intent_id = :intent_id OR transaction_reference = :intent_id";
            
            $db = $paymentObj->connect();
            $stmt = $db->prepare($sql);
            $stmt->execute([':intent_id' => $paymentIntentId]);
            
            // Get booking ID from payment transaction
            $getSql = "SELECT pt.booking_ID, pt.transaction_ID 
                       FROM Payment_Transaction pt
                       WHERE pt.paymongo_intent_id = :intent_id";
            
            $getStmt = $db->prepare($getSql);
            $getStmt->execute([':intent_id' => $paymentIntentId]);
            $result = $getStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $booking_ID = $result['booking_ID'];
                
                // Update booking status to Paid
                $updateBookingSql = "UPDATE Booking 
                                     SET booking_status = 'Confirmed'
                                     WHERE booking_ID = :booking_ID";
                
                $updateStmt = $db->prepare($updateBookingSql);
                $updateStmt->execute([':booking_ID' => $booking_ID]);
                
                error_log("[Paymongo Webhook] Payment confirmed for Booking #$booking_ID");
            }
        }
    }
    
    // Handle payment.failed event
    if ($eventType === 'payment.failed') {
        $paymentIntentId = $eventData['id'] ?? null;
        
        if ($paymentIntentId) {
            $sql = "UPDATE Payment_Transaction 
                    SET transaction_status = 'Failed',
                        transaction_updated_date = NOW()
                    WHERE paymongo_intent_id = :intent_id";
            
            $db = $paymentObj->connect();
            $stmt = $db->prepare($sql);
            $stmt->execute([':intent_id' => $paymentIntentId]);
            
            error_log("[Paymongo Webhook] Payment failed for Intent #$paymentIntentId");
        }
    }
    
    http_response_code(200);
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    error_log("[Paymongo Webhook Error] " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>

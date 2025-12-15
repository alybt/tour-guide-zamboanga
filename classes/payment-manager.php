<?php


require_once __DIR__ . "/../config/database.php";
require_once "trait/payment-info/method.php";
require_once "trait/payment-info/transaction-reference.php";
require_once "trait/payment-info/payment-info.php";
require_once "trait/payment-info/payment-transaction.php";
require_once "trait/payment-info/refund.php";
require_once "trait/payment-info/paymongo.php";
require_once "trait/payment-info/payout.php";
require_once "trait/person/trait-phone.php";
require_once __DIR__ . '/../assets/vendor/autoload.php';
 use Paymongo\Paymongo;

class PaymentManager extends Database{
    use MethodTrait, TransactionReferenceTrait, PaymentInfo, PaymentTransaction, PhoneTrait, Refund, PayMongoTrait,PayoutTrait;
   
    
 // Use Sandbox Secret Key

    public function addAllPaymentInfo( $booking_ID, $paymentinfo_total_amount, $method_ID, $methodcategory_ID, $method_amount, $method_currency, $method_cardnumber, $method_expmonth, $method_expyear, $method_cvc, $method_name, $method_email, $method_line1, $method_city, $method_postalcode, $method_country, $country_ID, $phone_number ) {
        $db = $this->connect();
        $db->beginTransaction();

        try {
            // Step 1: Add payment info
            // $paymentinfo_ID = $this->addPaymentInfo($booking_ID, $paymentinfo_total_amount, $db);
            // if (!$paymentinfo_ID) {
            //     throw new Exception("Failed to prepare payment information.");
            // }

            // Step 2: Add payment transaction
            $transaction_ID = $this->addPaymentTransaction( 
                $method_ID,
                $methodcategory_ID,
                $method_amount,
                $method_currency,
                $method_cardnumber,
                $method_expmonth,
                $method_expyear,
                $method_cvc,
                $method_name,
                $method_email,
                $method_line1,
                $method_city,
                $method_postalcode,
                $method_country,
                $country_ID,
                $phone_number,
                $booking_ID, $paymentinfo_total_amount,
                $db
            );

            if (!$transaction_ID) {
                throw new Exception("Failed to insert into Payment_Transaction table.");
            }
            

            // Step 3: Update booking status
            $sql = "UPDATE Booking 
                    SET booking_status = 'Pending for Approval' 
                    WHERE booking_ID = :booking_ID";
            $query = $db->prepare($sql);
            $query->bindParam(':booking_ID', $booking_ID);

            if (!$query->execute()) {
                throw new Exception("Failed to update booking status.");
            }

            // ✅ If all steps succeeded, commit
            $db->commit();
            return true;

        } catch (Exception $e) {
            // 🔴 Roll back everything on failure
            $db->rollBack();
            error_log("[addAllPaymentInfo] " . $e->getMessage());
            return false;
        }
    }

    public function getPaymentByBooking($booking_ID){
        $sql = "SELECT * FROM booking b 
                JOIN Payment_Transaction pt ON b.booking_ID = pt.booking_ID
                JOIN method m ON m.method_ID = pt.method_ID
                WHERE b.booking_ID = :booking_ID";
        $db = $this->connect();
        $query = $db->prepare($sql);
        $query->bindParam(':booking_ID', $booking_ID);
        $query->execute();
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function refundABooking($booking_ID, $transaction_ID, $categoryrefund_ID,$refund_reason, $refund_status, $refund_processingfee, $refund_refundfee, $refund_total_amount){
        $db = $this->connect();
        $db->beginTransaction();

        try {
            $refund_ID = $this->addRefund($transaction_ID, $categoryrefund_ID,$refund_reason, $refund_status, $refund_processingfee, $refund_refundfee, $refund_total_amount, $db);
                if (!$refund_ID) {
                    throw new Exception("Failed to insert into Refund table.");
                }

            $sql = "UPDATE Booking 
                SET booking_status = 'Refunded' 
                WHERE booking_ID = :booking_ID";
            $query = $db->prepare($sql);
            $query->bindParam(':booking_ID', $booking_ID);

            if (!$query->execute()) {
                throw new Exception("Failed to update booking status.");
            }
            $sql = "UPDATE Payment_Transaction pt
                SET pt.transaction_status = 'Refunded'
                WHERE pt.booking_ID = :booking_ID";
            $query = $db->prepare($sql);
            $query->bindParam(':booking_ID', $booking_ID);

            if (!$query->execute()) {
                throw new Exception("Failed to update Transaction status.");
            }


 
            $db->commit();
            return true;
 
        }catch (Exception $e) { 

        $db->rollBack();
        error_log("[addAllPaymentInfo] " . $e->getMessage());
        return false;
    }
    }

    public function requestRefund( int $booking_ID, int $tourist_ID,  int $categoryrefund_ID,  string $refund_reason, ?float $custom_refund_amount = null ): array {
        $db = $this->connect();

        try {
            $db->beginTransaction();

            // 1. Get booking + transaction details
            $sql = "SELECT 
                        b.booking_ID,
                        pt.transaction_ID,
                        pt.transaction_total_amount,
                        pt.paymongo_intent_id
                    FROM booking b
                    JOIN Payment_Transaction pt ON b.booking_ID = pt.booking_ID
                    WHERE b.booking_ID = :booking_ID 
                    AND b.tourist_ID = :tourist_ID
                    AND pt.transaction_status = 'Paid'
                    AND pt.paymongo_intent_id IS NOT NULL";

            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':booking_ID' => $booking_ID,
                ':tourist_ID' => $tourist_ID
            ]);
            $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$transaction) {
                throw new Exception("No paid PayMongo transaction found for this booking.");
            }

            $originalAmount = (float)$transaction['transaction_total_amount'];
            $refundAmount = $custom_refund_amount ?? $originalAmount;

            if ($refundAmount > $originalAmount) {
                throw new Exception("Refund amount cannot exceed original payment of ₱" . number_format($originalAmount, 2));
            }

            // 2. Get refund category details (for processing fee)
            $catSql = "SELECT cr.*, crn.categoryrefundname_name 
                    FROM Category_Refund cr
                    JOIN CategoryRefund_Name crn ON cr.categoryrefundname_ID = crn.categoryrefundname_ID
                    WHERE cr.categoryrefund_ID = :categoryrefund_ID";

            $catStmt = $db->prepare($catSql);
            $catStmt->execute([':categoryrefund_ID' => $categoryrefund_ID]);
            $category = $catStmt->fetch(PDO::FETCH_ASSOC);

            if (!$category) {
                throw new Exception("Invalid refund category.");
            }

            $processingFee = (float)($category['processing_fee'] ?? 0);
            $finalRefund = $refundAmount - $processingFee;

            if ($finalRefund < 0) {
                throw new Exception("Processing fee exceeds refundable amount.");
            }

            // 3. Create Refund Request Record
            $insertSql = "INSERT INTO Refund (
                            transaction_ID,
                            categoryrefund_ID,
                            refund_reason,
                            refund_status,
                            refund_processingfee,
                            refund_refundfee,
                            refund_total_amount
                        ) VALUES (
                            :transaction_ID,
                            :categoryrefund_ID,
                            :refund_reason,
                            'Pending',
                            :processing_fee,
                            :refund_amount,
                            :total_refunded
                        )";

            $insertStmt = $db->prepare($insertSql);
            $insertStmt->execute([
                ':transaction_ID' => $transaction['transaction_ID'],
                ':categoryrefund_ID' => $categoryrefund_ID,
                ':refund_reason' => $refund_reason,
                ':processing_fee' => $processingFee,
                ':refund_amount' => $refundAmount,
                ':total_refunded' => $finalRefund
            ]);

            $refund_ID = $db->lastInsertId();

            // 4. Log Activity
            $this->activity->touristRequestedRefund(
                $tourist_ID,
                $booking_ID,
                $refund_ID,
                $category['categoryrefundname_name'],
                $refund_reason
            );

            $db->commit();

            return [
                'success' => true,
                'message' => 'Refund request submitted successfully. Awaiting approval.',
                'refund_ID' => $refund_ID,
                'amount_requested' => $refundAmount,
                'processing_fee' => $processingFee,
                'net_refund' => $finalRefund,
                'status' => 'Pending'
            ];

        } catch (Exception $e) {
            $db->rollBack();
            error_log("REFUND REQUEST FAILED [Booking #$booking_ID]: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

}

?>
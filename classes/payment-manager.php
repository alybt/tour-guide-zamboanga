<?php


require_once __DIR__ . "/../config/database.php";
require_once "trait/payment-info/method.php";
require_once "trait/payment-info/transaction-reference.php";
require_once "trait/payment-info/payment-info.php";
require_once "trait/payment-info/payment-transaction.php";
require_once "trait/payment-info/refund.php";
require_once "trait/person/trait-phone.php";
require_once __DIR__ . '/../assets/vendor/autoload.php';
 use Paymongo\Paymongo;

class PaymentManager extends Database{
    use MethodTrait, TransactionReferenceTrait, PaymentInfo, PaymentTransaction, PhoneTrait, Refund;
   
    
 // Use Sandbox Secret Key

    public function addAllPaymentInfo( $booking_ID, $paymentinfo_total_amount, $method_ID, $methodcategory_ID, $method_amount, $method_currency, $method_cardnumber, $method_expmonth, $method_expyear, $method_cvc, $method_name, $method_email, $method_line1, $method_city, $method_postalcode, $method_country, $country_ID, $phone_number ) {
        $db = $this->connect();
        $db->beginTransaction();

        try {
            // Step 1: Add payment info
            $paymentinfo_ID = $this->addPaymentInfo($booking_ID, $paymentinfo_total_amount, $db);
            if (!$paymentinfo_ID) {
                throw new Exception("Failed to insert into Payment_Info table.");
            }

            // Step 2: Add payment transaction
            $transaction_ID = $this->addPaymentTransaction(
                $paymentinfo_ID,
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
                JOIN payment_info pi ON b.booking_ID = pi.booking_ID
                JOIN payment_transaction pt ON pi.paymentinfo_ID = pt.paymentinfo_ID
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
            // SELECT * FROM booking b JOIN payment_info pi ON b.booking_ID = pi.booking_ID JOIN payment_transaction pt ON pi.paymentinfo_ID = pt.paymentinfo_ID
            // SELECT b.booking_ID, b.booking_status, pt.transaction_status FROM booking b JOIN payment_info pi ON b.booking_ID = pi.booking_ID JOIN payment_transaction pt ON pi.paymentinfo_ID = pt.paymentinfo_ID
            $sql = "UPDATE Payment_Transaction pt
                JOIN Payment_Info pi ON pt.paymentinfo_ID = pi.paymentinfo_ID
                SET pt.transaction_status = 'Refunded'
                WHERE pi.booking_ID = :booking_ID;";
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

}

?>
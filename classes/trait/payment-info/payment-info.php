<?php 

trait PaymentInfo {

    public function addPaymentInfo($booking_ID, $paymentinfo_total_amount, $db){
        
        $sql = "INSERT INTO Payment_Info (booking_ID, paymentinfo_total_amount) VALUES ( :booking_ID, :paymentinfo_total_amount)";
        $query = $db->prepare($sql);
        $query->bindParam(':booking_ID',$booking_ID);
        $query->bindParam(':paymentinfo_total_amount',$paymentinfo_total_amount);
        $result = $query->execute();
        
        if ($result) {
            return $db->lastInsertId();
        } else {
            return false;
        }

    }

    public function hasPaymentTransaction($booking_ID) {
        $sql = "SELECT paymentinfo_ID 
                FROM Payment_Info
                WHERE booking_ID = :booking_ID";
        
        $db = $this->connect();
        $query = $db->prepare($sql);
        $query->bindParam(':booking_ID', $booking_ID);
        $query->execute();

        // Fetch one row as an associative array
        $row = $query->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            // Return the paymentinfo_ID value
            return $row['paymentinfo_ID'];
        } else {
            // No record found
            return false;
        }
    }





}
<?php 

trait PaymentTransaction{
    public function addPaymentTransaction($method_ID, $methodcategory_ID, $method_amount, $method_currency, $method_cardnumber, $method_expmonth, $method_expyear, $method_cvc, $method_name, $method_email, $method_line1, $method_city, $method_postalcode, $method_country, $country_ID, $phone_number, $booking_ID, $paymentinfo_total_amount, $db ){
        $method_ID = $this->addMethod($methodcategory_ID, $method_amount, $method_currency, $method_cardnumber, $method_expmonth, $method_expyear, $method_cvc, $method_name, $method_email, $method_line1, $method_city, $method_postalcode, $method_country, $country_ID, $phone_number, $db);

        if (!$method_ID){
            throw new Exception("Failed to insert into method ID.");
            return false;
        }

        $sql = "INSERT INTO Payment_Transaction (
                booking_ID,
                method_ID,
                transaction_total_amount,
                transaction_status 
            ) VALUES ( :booking_ID,
                :method_ID,
                :transaction_total_amount,
                'Pending')";
        $query = $db->prepare($sql); 
        $query->bindParam(':booking_ID',$booking_ID); 
        $query->bindParam(':method_ID',$method_ID);
        $query->bindParam(':transaction_total_amount',$transaction_total_amount);  

        $result = $query->execute();

        if ($result) {
            return $db->lastInsertId();
        } else {
            return false;
        }



    }

    public function hasPaymentTransaction($booking_ID) {
        $sql = "SELECT transaction_ID 
                FROM Payment_Transaction
                WHERE booking_ID = :booking_ID
                LIMIT 1";
        
        $db = $this->connect();
        $query = $db->prepare($sql);
        $query->bindParam(':booking_ID', $booking_ID);
        $query->execute();

        // Fetch one row as an associative array
        $row = $query->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            // Return the transaction_ID value
            return $row['transaction_ID'];
        } else {
            // No record found
            return false;
        }
    }



}

?>
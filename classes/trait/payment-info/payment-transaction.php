<?php 

trait PaymentTransaction{
    public function addPaymentTransaction($paymentinfo_ID, $method_ID, $methodcategory_ID, $method_amount, $method_currency, $method_cardnumber, $method_expmonth, $method_expyear, $method_cvc, $method_name, $method_email, $method_line1, $method_city, $method_postalcode, $method_country, $country_ID, $phone_number, $db){
        $method_ID = $this->addMethod($methodcategory_ID, $method_amount, $method_currency, $method_cardnumber, $method_expmonth, $method_expyear, $method_cvc, $method_name, $method_email, $method_line1, $method_city, $method_postalcode, $method_country, $country_ID, $phone_number, $db);

        if (!$method_ID){
            throw new Exception("Failed to insert into method ID.");
            return false;
        }

        $sql = "INSERT INTO Payment_Transaction (paymentinfo_ID, method_ID, transaction_status) VALUES (:paymentinfo_ID, :method_ID, 'Pending')";
        $query = $db->prepare($sql);
        $query->bindParam(':paymentinfo_ID',$paymentinfo_ID);
        $query->bindParam(':method_ID',$method_ID);

        $result = $query->execute();

        if ($result) {
            return $db->lastInsertId();
        } else {
            return false;
        }



    }



}

?>
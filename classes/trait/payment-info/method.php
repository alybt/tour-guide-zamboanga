<?php

trait MethodTrait{

    public function viewAllPaymentMethodCategory(){
        $sql = "SELECT * FROM Method_Category";
        $db = $this->connect();
        $query = $db->prepare($sql);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProcessingFeeByID($methodcategory_ID){
        $sql = "SELECT methodcategory_processing_fee FROM Method_Category";
        $db = $this->connect();
        $query = $db->prepare($sql);
        $query->execute();
        $result =$query->fetch(PDO::FETCH_ASSOC);
        return $result ? (float)$result['methodcategory_processing_fee'] : null;
    }



    public function addMethod($methodcategory_ID, $method_amount, $method_currency, $method_cardnumber, $method_expmonth, $method_expyear, $method_cvc, $method_name, $method_email, $method_line1, $method_city, $method_postalcode, $method_country, $country_ID, $phone_number, $db){
        
        $phone_ID = $this->addgetPhoneNumber($country_ID, $phone_number, $db);

        if(!$phone_ID){
            throw new Exception("Failed to insert into method ID.");
            return false;
        }

        $sql = "INSERT INTO Method(methodcategory_ID, method_amount, method_currency, method_cardnumber, method_expmonth, method_expyear, method_cvc, method_name, method_email, method_line1, method_city, method_postalcode, method_country, phone_ID) VALUES (:methodcategory_ID, :method_amount, :method_currency, :method_cardnumber, :method_expmonth, :method_expyear, :method_cvc, :method_name, :method_email, :method_line1, :method_city, :method_postalcode, :method_country, :phone_ID)";
        $query = $db->prepare($sql);
        $query->bindParam(':methodcategory_ID', $methodcategory_ID);
        $query->bindParam(':method_amount', $method_amount);
        $query->bindParam(':method_currency', $method_currency);
        $query->bindParam(':method_cardnumber', $method_cardnumber);
        $query->bindParam(':method_expmonth', $method_expmonth);
        $query->bindParam(':method_expyear', $method_expyear);
        $query->bindParam(':method_cvc', $method_cvc);
        $query->bindParam(':method_name', $method_name);
        $query->bindParam(':method_email', $method_email);
        $query->bindParam(':method_line1', $method_line1);
        $query->bindParam(':method_city', $method_city);
        $query->bindParam(':method_postalcode', $method_postalcode);
        $query->bindParam(':method_country', $method_country);
        $query->bindParam(':phone_ID', $phone_ID);
        $result = $query->execute();

        if ($result) {
            return $db->lastInsertId();
        } else {
            return false;
        }

    }

    public function getMethodByPayment($transaction_ID){
        $sql = "SELECT * FROM Payment_Transaction pt
                JOIN method m ON m.method_ID = pt.method_ID 
                WHERE pt.transaction_ID = :transaction_ID";
        $db = $this->connect();
        $query = $db->prepare($sql);
        $query->bindParam(':transaction_ID', $transaction_ID);
        $query->execute();
        return $query->fetch(PDO::FETCH_ASSOC);
    }

}


?>
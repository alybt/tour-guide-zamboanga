<?php 

trait PayoutTrait{

    public function transactionApproved($transaction_ID, $transaction_total_amount) { 
        $result = $this->updateTransaction($transaction_ID);

        if (!$result) {
            error_log("CRITICAL: Failed to update status for transaction #{$transaction_ID}");
            return false;
        } 

        $total_amount_float = floatval($transaction_total_amount);  
        $platform_fee = $total_amount_float * 0.20;  
        $earning_amount = $total_amount_float - $platform_fee;  
        $sql = "INSERT INTO Guide_Earnings (
                    transaction_ID, 
                    platform_fee, 
                    earning_amount, 
                    earning_status
                ) VALUES (
                    :transaction_ID, 
                    :platform_fee, 
                    :earning_amount, 
                    'Pending'
                )";
        
        $db = $this->connect(); 
        $query = $db->prepare($sql);
         
        $query->bindParam(':transaction_ID', $transaction_ID, PDO::PARAM_INT);
        $query->bindParam(':platform_fee', $platform_fee); 
        $query->bindParam(':earning_amount', $earning_amount);
        
        if ($query->execute()) {
            return true;
        } else { 
            error_log("Failed to insert Guide_Earnings for #{$transaction_ID}: " . implode(", ", $query->errorInfo()));
            return false;
        }
    }

    public function updateTransaction($transaction_ID){  
        $db = $this->connect(); 
        
        $sql = "UPDATE Payment_Transaction 
                SET transaction_status = 'Approved',  
                    transaction_updated_date = NOW()
                WHERE transaction_ID = :transaction_ID";
                
        $query = $db->prepare($sql);
        $query->bindParam(':transaction_ID', $transaction_ID, PDO::PARAM_INT);
        
        if ($query->execute()) {
            return true;
        } else {
            error_log("Failed to update Payment_Transaction status for #{$transaction_ID}: " . implode(", ", $query->errorInfo()));
            return false;
        }
    }



}


?>
<?php 

trait PayoutTrait{

    public function transactionApproved($transaction_ID){
        $sql = "UPDATE Payment_Transaction 
                SET transaction_status = 'Approved',
                    transaction_updated_date = NOW()
                WHERE transaction_ID = :transaction_ID";
        
        $db = $this->connect();
        $query = $db->prepare($sql);
        $query->bindParam(':transaction_ID', $transaction_ID, PDO::PARAM_INT);
        
        if ($query->execute()) {
            return true;
        } else {
            error_log("Failed to approve transaction: " . implode(", ", $query->errorInfo()));
            return false;
        }
    }



}


?>
<?php

trait Refund{


    public function getAllRefundCategories($role_ID){
        $sql = "SELECT cr.categoryrefund_ID, cr.categoryrefundname_ID, cn.categoryrefundname_name
                FROM category_refund cr 
                JOIN categoryrefund_name cn ON cr.categoryrefundname_ID = cn.categoryrefundname_ID
                WHERE cr.role_ID = :role_ID";
        $db = $this->connect();
        $query = $db->prepare($sql);
        $query->bindParam(':role_ID', $role_ID);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);

    }

    
//    transaction_ID	categoryrefund_ID	refund_reason	refund_status	refund_requested_date	refund_approval_date	refund_processingfee	refund_refundfee	refund_total_amount	

    public function addRefund($transaction_ID, $categoryrefund_ID,$refund_reason, $refund_status, $refund_processingfee, $refund_refundfee, $refund_total_amount, $db){
       try {
            $sql = "INSERT INTO Refund (transaction_ID,	categoryrefund_ID,	refund_reason,	refund_status,	refund_processingfee,	refund_refundfee,	refund_total_amount) 
            VALUES (:transaction_ID, :categoryrefund_ID,:refund_reason, :refund_status, :refund_processingfee,	:refund_refundfee, :refund_total_amount)";
            $db = $this->connect();
            $query = $db->prepare($sql);
            $query->bindParam(':transaction_ID', $transaction_ID);
            $query->bindParam(':categoryrefund_ID', $categoryrefund_ID);
            $query->bindParam(':refund_reason', $refund_reason);
            $query->bindParam(':refund_status', $refund_status);
            $query->bindParam(':refund_processingfee', $refund_processingfee);
            $query->bindParam(':refund_refundfee', $refund_refundfee);
            $query->bindParam(':refund_total_amount', $refund_total_amount);
            
            $result = $query->execute();

            if ($result) {
                return $db->lastInsertId();
            } else {
                return false;
            }
            return true;
       } catch (Exception $e) {
            $db->rollBack();
            error_log("[addAllPaymentInfo] " . $e->getMessage());
            return false;
        }
    }



}

?>
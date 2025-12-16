<?php

trait GuideEarningTrait{

    public function addingToBalanceMoney($guide_ID, $guide_balance, $earning_amount, $transaction_ID, $earning_ID){
        $db = $this->connect();
        $db->beginTransaction();

        try {
            
            $earningRelease = $this->earningUpdateRelease($earning_ID, $db);

            if (!$earningRelease) {
                throw new Exception("Earning Release Failed");
            }

            $balance_after = floatval($guide_balance) + floatval($earning_amount);

            $updateBalance = $this->addearnings($guide_ID, $balance_after, $db);

            if (!$updateBalance) {
                throw new Exception("Update Balance Failed");
            }

            $sql = "INSERT INTO guide_money_history (guide_ID, balance_before, amount, balance_after, reference_name) VALUES (:guide_ID, :balance_before, :amount, :balance_after, 'Earning') ";
            $query = $db->prepare($sql);
            $query->bindParam(':guide_ID', $guide_ID);
            $query->bindParam(':balance_before', $guide_balance);
            $query->bindParam(':amount', $earning_amount);
            $query->bindParam(':balance_after', $balance_after);
            $query->execute();

 
            $db->commit();
            return true;
 
        }catch (Exception $e) { 

            $db->rollBack();
            error_log("addingToBalanceMoney:  " . $e->getMessage());
            return false;
        }
    }


    public function earningUpdateRelease($earning_ID, $db){
        $sql = "UPDATE Guide_Earnings 
            SET earning_status ='Released' 
            WHERE earning_ID = :earning_ID";
        $query = $db->prepare($sql);
        $query->bindParam(':earning_ID', $earning_ID);
        $query->execute();
        return $query->rowCount() > 0;
        
    }


    public function addearnings($guide_ID, $balance_after, $db){
        $sql = "UPDATE Guide
            SET guide_balance = :balance_after 
            WHERE guide_ID = :guide_ID";
        $query = $db->prepare($sql);
        $query->bindParam(':guide_ID', $guide_ID); 
        $query->bindParam(':balance_after', $balance_after);
        $query->execute();
        return $query->rowCount() > 0;
    }


    public function getTransactionIDByEarning($earnings_ID){
        $sql = "SELECT transaction_ID FROM Guide_Earnings 
            WHERE earning_ID = :earning_ID";
        $db = $this->connect();
        $query = $db->prepare($sql);
        $query->bindParam(':earning_ID', $earnings_ID);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);

        return $result;
    }

    public function getEarningByID($earning_ID){
        $sql = "SELECT earning_amount, transaction_ID FROM guide_earnings 
            WHERE earning_ID = :earning_ID";
        $db = $this->connect();
        $query = $db->prepare($sql);
        $query->bindParam(':earning_ID', $earning_ID);
        $query->execute();
        return $query->fetch(PDO::FETCH_ASSOC);
    }

}

?>

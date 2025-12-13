<?php 

trait ActivityViewed{

    public function addActivityView($account_ID, $activity_ID) {
        $db = $this->connect();
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
        $db->beginTransaction();

        try {
            // 1. Check if the record already exists
            $sql = "SELECT activity_ID FROM Activity_View WHERE account_ID = :account_ID AND activity_ID = :activity_ID";
            $query = $db->prepare($sql);
            $query->bindParam(':account_ID', $account_ID);
            $query->bindParam(':activity_ID', $activity_ID);

            $query->execute();
            
            // 2. Check the row count. If a row is found (count > 0), return true and commit the read.
            if ($query->rowCount() > 0) {
                $db->commit();
                return true;
            }

            // 3. If no row was found, perform the INSERT
            // Note: activity_isViewed is set to 1 here, though default 0 is fine too.
            $sql = "INSERT INTO Activity_View (account_ID, activity_ID, activity_isViewed) VALUES (:account_ID, :activity_ID, 1)";
            $query = $db->prepare($sql);
            $query->bindParam(':account_ID', $account_ID);
            $query->bindParam(':activity_ID', $activity_ID);

            $query->execute();

            $db->commit();
            return true;
            
        } catch (Exception $e) {
            $db->rollBack();
            error_log("FATAL addActivityView error: Transaction failed. Details: " . $e->getMessage());
            return false;
        }
    }




}


?>
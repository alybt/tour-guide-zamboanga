<?php 

trait BookingLogs{


    public function systemUpdateBooking() {
    
        $db = $this->connect();
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
        $db->beginTransaction();

        try {
            $action_desc = 'system updates booking';
            $action_name = 'System Updates';
            $action_ID = $this->addgetActionID($action_name, $db);

            // --- 1. Check if Action ID was retrieved/created ---
            if (!$action_ID){
                // addgetActionID already logged the DB error, just log the fatal failure here
                error_log("FATAL: Cannot continue. Failed to retrieve or create action_ID for action_name: " . $action_name); 
                $db->rollBack();
                return false; 
            }

            $sql= "INSERT INTO Activity_Log (account_ID, action_ID, activity_description) VALUES (:account_ID, :action_ID, :activity_description)";
            $query = $db->prepare($sql);
            $query->bindParam(':action_ID', $action_ID);
            $query->bindParam(':activity_description', $action_desc);

            // This execute is covered by the catch block because of ERRMODE_EXCEPTION
            $query->execute();

            $db->commit();
            return true;
        } 
        // --- 3. Catch ALL Exceptions (Includes database errors in the try block) ---
        catch (Exception $e) {
            $db->rollBack();
            // Log general system or database errors (e.g., failed connection, INSERT failure)
            error_log("FATAL SystemUpdate error: Transaction failed. Details: " . $e->getMessage());
            return false;
        }
    }

    public function bookingExpired() {
    
        $db = $this->connect();
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
        $db->beginTransaction();

        try {
            $action_desc = 'booking expired';
            $action_name = 'Booking Expired';
            $action_ID = $this->addgetActionID($action_name, $db);

            // --- 1. Check if Action ID was retrieved/created ---
            if (!$action_ID){
                // addgetActionID already logged the DB error, just log the fatal failure here
                error_log("FATAL: Cannot continue. Failed to retrieve or create action_ID for action_name: " . $action_name); 
                $db->rollBack();
                return false; 
            }

            $sql= "INSERT INTO Activity_Log (account_ID, action_ID, activity_description) VALUES (:account_ID, :action_ID, :activity_description)";
            $query = $db->prepare($sql);
            $query->bindParam(':action_ID', $action_ID);
            $query->bindParam(':activity_description', $action_desc);

            // This execute is covered by the catch block because of ERRMODE_EXCEPTION
            $query->execute();

            $db->commit();
            return true;
        } 
        // --- 3. Catch ALL Exceptions (Includes database errors in the try block) ---
        catch (Exception $e) {
            $db->rollBack();
            // Log general system or database errors (e.g., failed connection, INSERT failure)
            error_log("FATAL SystemUpdate error: Transaction failed. Details: " . $e->getMessage());
            return false;
        }
    } 

    
    public function guideMarkedCompleted($booking_ID, $account_ID) {
    
        $db = $this->connect();
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
        $db->beginTransaction();

        try {
            $action_desc = $account_ID . ' completed ' . $booking_ID;
            $action_name = 'Complete Booking';
            $action_ID = $this->addgetActionID($action_name, $db);

            // --- 1. Check if Action ID was retrieved/created ---
            if (!$action_ID){
                // addgetActionID already logged the DB error, just log the fatal failure here
                error_log("FATAL: Cannot continue. Failed to retrieve or create action_ID for action_name: " . $action_name); 
                $db->rollBack();
                return false; 
            }

            $sql= "INSERT INTO Activity_Log (account_ID, action_ID, activity_description) VALUES (:account_ID, :action_ID, :activity_description)";
            $query = $db->prepare($sql);
            $query->bindParam(':account_ID', $account_ID);
            $query->bindParam(':action_ID', $action_ID);
            $query->bindParam(':activity_description', $action_desc);

            // This execute is covered by the catch block because of ERRMODE_EXCEPTION
            $query->execute();

            $db->commit();
            return true;
        } 
        // --- 3. Catch ALL Exceptions (Includes database errors in the try block) ---
        catch (Exception $e) {
            $db->rollBack();
            // Log general system or database errors (e.g., failed connection, INSERT failure)
            error_log("FATAL guideMarkedCompleted error: Transaction failed. Details: " . $e->getMessage());
            return false;
        }
    }

}

?>
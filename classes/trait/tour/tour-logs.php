<?php


trait TourLogs{


    public function touristCancelBooking($booking_ID, $tourist_ID) {
    
        $db = $this->connect();
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
        $db->beginTransaction();

        try {
            $action_desc = $tourist_ID . ' cancel ' . $booking_ID;
            $action_name = 'Cancel Booking';
            $action_ID = $this->addgetActionID($action_name, $db);
            $account_ID = $tourist_ID;

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
            error_log("FATAL touristCancelBooking error: Transaction failed. Details: " . $e->getMessage());
            return false;
        }
    }

    public function touristRefundBooking($booking_ID, $tourist_ID) {
    
        $db = $this->connect();
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
        $db->beginTransaction();

        try {
            $action_desc = $tourist_ID . ' refund ' . $booking_ID;
            $action_name = 'Refund Booking';
            $action_ID = $this->addgetActionID($action_name, $db);
            $account_ID = $tourist_ID;

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
            error_log("FATAL touristRefundlBooking error: Transaction failed. Details: " . $e->getMessage());
            return false;
        }
    }

    public function touristBook($booking_ID, $tourist_ID) {
    
        $db = $this->connect();
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
        $db->beginTransaction();

        try {
            $action_desc = $tourist_ID . ' book ' . $booking_ID;
            $action_name = 'Books';
            $action_ID = $this->addgetActionID($action_name, $db);
            $account_ID = $tourist_ID;

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
            error_log("FATAL touristBook error: Transaction failed. Details: " . $e->getMessage());
            return false;
        }
    }

}
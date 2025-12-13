<?php

trait AccountLogs {

    public function loginActivity($account_ID) {
    
        $db = $this->connect();
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
        $db->beginTransaction();

        try {
            $action_desc = 'logins';
            $action_name = 'Login';
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
            error_log("FATAL loginActivity error: Transaction failed. Details: " . $e->getMessage());
            return false;
        }
    }

    public function logoutActivity($account_ID) {
    
        $db = $this->connect();
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
        
        $db->beginTransaction();

        try {
            $action_desc = 'logout';
            $action_name = 'Logout';
            $action_ID = $this->addgetActionID($action_name, $db);

            if (!$action_ID){
                error_log("FATAL: Cannot continue. Failed to retrieve or create action_ID for action_name: " . $action_name); 
                $db->rollBack();
                return false; 
            }

            $sql= "INSERT INTO Activity_Log (account_ID, action_ID, activity_description) VALUES (:account_ID, :action_ID, :activity_description)";
            $query = $db->prepare($sql);
            $query->bindParam(':account_ID', $account_ID);
            $query->bindParam(':action_ID', $action_ID);
            $query->bindParam(':activity_description', $action_desc);

            $query->execute();

            $db->commit();
            return true;
        } 
        catch (Exception $e) {
            $db->rollBack();
            error_log("FATAL logoutActivity error: Transaction failed. Details: " . $e->getMessage());
            return false;
        }
    }

    public function guideChangeToTourist($guide_ID, $account_ID) {
    
        $db = $this->connect();
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
        
        $db->beginTransaction();

        try {
            $action_desc = $guide_ID . ' change into ' . $account_ID;
            $action_name = 'Change Account Into Tourist';
            $action_ID = $this->addgetActionID($action_name, $db);

            if (!$action_ID){
                error_log("FATAL: Cannot continue. Failed to retrieve or create action_ID for action_name: " . $action_name); 
                $db->rollBack();
                return false; 
            }

            $sql= "INSERT INTO Activity_Log (account_ID, action_ID, activity_description) VALUES (:account_ID, :action_ID, :activity_description)";
            $query = $db->prepare($sql);
            $query->bindParam(':account_ID', $account_ID);
            $query->bindParam(':action_ID', $action_ID);
            $query->bindParam(':activity_description', $action_desc);

            $query->execute();

            $db->commit();
            return true;
        } 
        catch (Exception $e) {
            $db->rollBack();
            error_log("FATAL logoutActivity error: Transaction failed. Details: " . $e->getMessage());
            return false;
        }
    }

    public function touristRegister($account_ID) {
    
        $db = $this->connect();
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
        
        $db->beginTransaction();

        try {
            $action_desc = 'registers';
            $action_name = 'Register';
            $action_ID = $this->addgetActionID($action_name, $db);

            if (!$action_ID){
                error_log("FATAL: Cannot continue. Failed to retrieve or create action_ID for action_name: " . $action_name); 
                $db->rollBack();
                return false; 
            }

            $sql= "INSERT INTO Activity_Log (account_ID, action_ID, activity_description) VALUES (:account_ID, :action_ID, :activity_description)";
            $query = $db->prepare($sql);
            $query->bindParam(':account_ID', $account_ID);
            $query->bindParam(':action_ID', $action_ID);
            $query->bindParam(':activity_description', $action_desc);

            $query->execute();
            
            $db->commit();
            return true;
        } 
        catch (Exception $e) {
            $db->rollBack();
            error_log("FATAL logoutActivity error: Transaction failed. Details: " . $e->getMessage());
            return false;
        }
    }

}



?>
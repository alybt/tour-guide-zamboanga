<?php 

trait NotificationTrait{

    public function touristNotification(int $tourist_ID): array {
        $db = $this->connect();
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
        $sql = "SELECT 
                    al.activity_ID,
                    al.account_ID,
                    al.action_ID,
                    al.activity_description,
                    al.activity_timestamp,
                    a.action_name,
                    COALESCE(av.activity_isViewed, 0) AS is_viewed 
                FROM 
                    `activity_log` al 
                INNER JOIN 
                    `action` a ON al.action_ID = a.action_ID
                LEFT JOIN
                    `Activity_View` av 
                    ON al.activity_ID = av.activity_ID AND al.account_ID = av.account_ID
                WHERE 
                    al.account_ID = :touristID 
                    AND a.action_name NOT IN ('Logout', 'Login', 'Change Account Into Tourist')
                ORDER BY 
                    al.activity_timestamp DESC";

        try {
            $query = $db->prepare($sql);
            
            // Correctly bind the parameter with the expected name
            $query->bindParam(':touristID', $tourist_ID, PDO::PARAM_INT);

            $query->execute();
            
            // Fetch all results as an associative array
            return $query->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("FATAL touristNotification error: Failed to fetch notifications for ID {$tourist_ID}. Details: " . $e->getMessage());
            return []; // Return an empty array on failure
        }
    }
 
    public function markTouristNotificationsAsViewed(int $tourist_ID): bool {
        $db = $this->connect();
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        try {
            // Step 1: Insert missing Activity_View records for activities that don't have them yet
            $insertSql = "INSERT INTO Activity_View (activity_ID, account_ID, activity_isViewed)
                          SELECT al.activity_ID, al.account_ID, 1
                          FROM activity_log al
                          INNER JOIN action a ON al.action_ID = a.action_ID
                          WHERE al.account_ID = :touristID
                          AND a.action_name NOT IN ('Logout', 'Login', 'Change Account Into Tourist')
                          AND NOT EXISTS (
                              SELECT 1 FROM Activity_View av 
                              WHERE av.activity_ID = al.activity_ID 
                              AND av.account_ID = al.account_ID
                          )
                          ON DUPLICATE KEY UPDATE activity_isViewed = 1";
            
            $insertQuery = $db->prepare($insertSql);
            $insertQuery->bindParam(':touristID', $tourist_ID, PDO::PARAM_INT);
            $insertQuery->execute();
            error_log("INSERT missing Activity_View records executed");

            // Step 2: Update existing Activity_View records to mark as viewed
            $updateSql = "UPDATE Activity_View av
                          INNER JOIN activity_log al 
                              ON al.activity_ID = av.activity_ID 
                              AND al.account_ID = av.account_ID
                          INNER JOIN action a 
                              ON al.action_ID = a.action_ID
                          SET av.activity_isViewed = 1
                          WHERE al.account_ID = :touristID
                          AND a.action_name NOT IN ('Logout', 'Login', 'Change Account Into Tourist')";

            $updateQuery = $db->prepare($updateSql);
            $updateQuery->bindParam(':touristID', $tourist_ID, PDO::PARAM_INT);
            $updateQuery->execute();
            error_log("UPDATE Activity_View records executed");
            
            return true;
        } catch (Exception $e) {
            error_log("FATAL markTouristNotificationsAsViewed error: " . $e->getMessage());
            return false;
        }
    }

    public function markSingleNotificationAsViewed(int $activity_ID, int $account_ID): bool {
        $db = $this->connect();
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        error_log("markSingleNotificationAsViewed - Activity ID: $activity_ID, Account ID: $account_ID");

        // First, check if the activity_view record exists
        $checkSql = "SELECT activity_ID FROM Activity_View 
                     WHERE activity_ID = :activity_ID 
                     AND account_ID = :account_ID";
        
        try {
            $checkQuery = $db->prepare($checkSql);
            $checkQuery->bindParam(':activity_ID', $activity_ID, PDO::PARAM_INT);
            $checkQuery->bindParam(':account_ID', $account_ID, PDO::PARAM_INT);
            $checkQuery->execute();
            
            $exists = $checkQuery->fetch(PDO::FETCH_ASSOC);
            error_log("Activity_View record exists: " . ($exists ? 'YES' : 'NO'));

            if ($exists) {
                // Update existing record
                $updateSql = "UPDATE Activity_View 
                              SET activity_isViewed = 1
                              WHERE activity_ID = :activity_ID 
                              AND account_ID = :account_ID";
                
                $updateQuery = $db->prepare($updateSql);
                $updateQuery->bindParam(':activity_ID', $activity_ID, PDO::PARAM_INT);
                $updateQuery->bindParam(':account_ID', $account_ID, PDO::PARAM_INT);
                $result = $updateQuery->execute();
                error_log("UPDATE result: " . ($result ? 'TRUE' : 'FALSE'));
            } else {
                // Insert new record
                $insertSql = "INSERT INTO Activity_View (activity_ID, account_ID, activity_isViewed)
                              VALUES (:activity_ID, :account_ID, 1)";
                
                $insertQuery = $db->prepare($insertSql);
                $insertQuery->bindParam(':activity_ID', $activity_ID, PDO::PARAM_INT);
                $insertQuery->bindParam(':account_ID', $account_ID, PDO::PARAM_INT);
                $result = $insertQuery->execute();
                error_log("INSERT result: " . ($result ? 'TRUE' : 'FALSE'));
            }
            
            return true;
        } catch (Exception $e) {
            error_log("FATAL markSingleNotificationAsViewed error: " . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            return false;
        }
    }
}

?>
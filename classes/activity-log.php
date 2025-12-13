<?php 

require_once __DIR__ . "/../config/database.php";
require_once "trait/account/account-logs.php";
require_once "trait/tour/tour-logs.php";
require_once "trait/booking/booking-logs.php";
require_once "trait/activity-logs/notification.php";
require_once "trait/payment-info/payment-log.php";

class ActivityLogs extends Database {

    use AccountLogs, TourLogs, BookingLogs, NotificationTrait, PaymentLogs;

    public function addgetActionID($action_name, $db){
    
        // --- 1. SELECT Action ID (Check if action_name exists) ---
        $sql = "SELECT action_ID FROM Action WHERE action_name = :action_name";
        $query = $db->prepare($sql);
        $query->bindParam(':action_name', $action_name);

        // Check if the SELECT query executed successfully
        if ($query->execute()){
            $result = $query->fetch(PDO::FETCH_ASSOC); 
            if ($result) {
                return $result['action_ID'];
            }
        } else {
            // Log SELECT failure details
            $error_info = $query->errorInfo();
            error_log("DB Error in addgetActionID (SELECT): SQLSTATE " . $error_info[0] . ", Code " . $error_info[1] . ", Message " . $error_info[2]);
            // If SELECT fails, we must return false to prevent trying the INSERT
            return false;
        }

        // --- 2. INSERT Action ID (If action_name does not exist) ---
        $sql = "INSERT INTO Action (action_name) VALUES (:action_name)";
        $query = $db->prepare($sql);
        $query->bindParam(':action_name', $action_name);

        // Check if the INSERT query executed successfully
        if($query->execute()){
            return $db->lastInsertId();
        } else {
            // Log INSERT failure details
            $error_info = $query->errorInfo();
            error_log("DB Error in addgetActionID (INSERT): SQLSTATE " . $error_info[0] . ", Code " . $error_info[1] . ", Message " . $error_info[2]);
            return false;
        }
    }

}

?>
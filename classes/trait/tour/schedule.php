<?php

trait ScheduleTrait {

    public function addGetSchedule($days, $numberofpeople_maximum, $numberofpeople_based, $currency, $forAdult, $forChild, $forYoungAdult, $forSenior, $forPWD, $includeMeal, $mealFee, $transportFee, $discount, $db) {
        
            $numberofpeople_ID = $this->addPeople($numberofpeople_maximum, $numberofpeople_based, $currency, $forAdult, $forChild, $forYoungAdult, $forSenior, $forPWD, $includeMeal, $mealFee, $transportFee, $discount, $db);
            
            if (!$numberofpeople_ID) {
                return false;
            }

            $sql = "INSERT INTO Schedule (numberofpeople_ID, schedule_days)
                    VALUES (:numberofpeople_ID, :days)";
            $query = $db->prepare($sql);
            $query->bindParam(':numberofpeople_ID', $numberofpeople_ID);
            $query->bindParam(':days', $days);
            $query->execute();
            return $db->lastInsertId();
        
    }

    public function updateSchedule($schedule_ID, $days, $numberofpeople_ID, $numberofpeople_maximum, $numberofpeople_based, $pricing_ID, $currency, $forAdult, $forChild, $forYoungAdult, $forSenior, $forPWD, $includeMeal, $mealFee, $transportFee, $discount, $db) {
        
            $result = $this->updatePeople($numberofpeople_ID, $numberofpeople_maximum, $numberofpeople_based, $pricing_ID, $currency, $forAdult, $forChild, $forYoungAdult, $forSenior, $forPWD, $includeMeal, $mealFee, $transportFee, $discount, $db);
            
            if (!$result) {
                return false;
            }

            $sql = "UPDATE Schedule SET numberofpeople_ID = :numberofpeople_ID, schedule_days = :days 
            WHERE schedule_ID = :schedule_ID";
            $query = $db->prepare($sql);
            $query->bindParam(':schedule_ID', $schedule_ID);
            $query->bindParam(':numberofpeople_ID', $numberofpeople_ID);
            $query->bindParam(':days', $days);
            $query->execute();
            return $query->execute();
        
    }

    public function getScheduleByID($scheduleID) {
        $db = $this->connect();
        $sql = "SELECT * FROM Schedule WHERE schedule_ID = :scheduleID";
        $query = $db->prepare($sql);
        $query->bindParam(':scheduleID', $scheduleID);
        $query->execute();
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function deleteScheduleByID($schedule_ID, $db){
        $sql = "DELETE FROM Schedule WHERE schedule_ID = :schedule_ID";
        
        try {
            $query = $db->prepare($sql);
            $query->bindParam(":schedule_ID ", $schedule_ID );
            
            if ($query->execute()) {
                return true;
            }
            error_log("Pricing Delete Error: " . print_r($query->errorInfo(), true));
            return false;
        } catch (PDOException $e) {
            error_log("Pricing Delete Exception: " . $e->getMessage());
            return false;
        }
    }

    // public function  getPeopleIDInScheduleByScheduleID($schedule_ID){
    //     $db = $this->connect();
    //     $sql = "SELECT numberofpeople_ID FROM Schedule WHERE schedule_ID = :scheduleID";
    //     $query = $db->prepare($sql);
    //     $query->bindParam(':scheduleID', $scheduleID);
    //     $query->execute();
    //     return $query->fetch(PDO::FETCH_ASSOC);
    // }

}

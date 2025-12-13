<?php

trait PeopleTrait {

    public function addPeople($numberofpeople_maximum, $numberofpeople_based, $currency, $forAdult, $forChild, $forYoungAdult, $forSenior, $forPWD, $includeMeal, $mealFee, $transportFee, $discount, $db) {
        try {

            $pricing_ID = $this->addPricing($currency, $forAdult, $forChild, $forYoungAdult, $forSenior, $forPWD, $includeMeal, $mealFee, $transportFee, $discount, $db);

                if (!$pricing_ID){
                    return false;
                }


            $sql = "INSERT INTO Number_Of_People (pricing_ID, numberofpeople_maximum, numberofpeople_based)
                    VALUES (:pricing_ID, :max, :based)";

            $query = $db->prepare($sql);
            $query->bindParam(':based', $numberofpeople_based );
            $query->bindParam(':max', $numberofpeople_maximum );
            $query->bindParam(':pricing_ID', $pricing_ID);
            $query->execute();
            return $db->lastInsertId();


            
        } catch (PDOException $e) {
            error_log("Error in addGetPeople: " . $e->getMessage());
            return false;
        }
    }

    public function updatePeople($numberofpeople_ID, $numberofpeople_maximum, $numberofpeople_based, $pricing_ID, $currency, $forAdult, $forChild, $forYoungAdult, $forSenior, $forPWD, $includeMeal, $mealFee, $transportFee, $discount, $db) {
        try {

            $result = $this->updatePricing($pricing_ID, $currency, $forAdult, $forChild, $forYoungAdult, $forSenior, $forPWD, $includeMeal, $mealFee, $transportFee, $discount, $db);

                if (!$result){
                    return false;
                }

            $sql = "UPDATE Number_Of_People SET
                        pricing_ID = :pricing_ID,
                        numberofpeople_maximum = :max,
                        numberofpeople_based = :based
                    WHERE numberofpeople_ID = :numberofpeople_ID";
            $query = $db->prepare($sql);
            $query->bindParam(':based', $numberofpeople_based );
            $query->bindParam(':max', $numberofpeople_maximum );
            $query->bindParam(':pricing_ID', $pricing_ID);
            $query->bindParam(':numberofpeople_ID', $numberofpeople_ID);
            
            return $query->execute();


            
        } catch (PDOException $e) {
            error_log("Error in addGetPeople: " . $e->getMessage());
            return false;
        }
    }

    // updatePricing($pricing_ID ,$currency, $forAdult, $forChild, $forYoungAdult, $forSenior, $forPWD, $includeMeal, $mealFee, $transportFee, $discount, $db)

    public function getPeopleByID($peopleID) {
        $db = $this->connect();
        $sql = "SELECT * FROM Number_Of_People WHERE numberofpeople_ID = :peopleID";
        $query = $db->prepare($sql);
        $query->bindParam(':peopleID', $peopleID);
        $query->execute();
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function deletePeopleByID($numberofpeople_ID, $db){
        $sql = "DELETE FROM Number_Of_People WHERE numberofpeople_ID = :numberofpeople_ID";
        
        try {
            $query = $db->prepare($sql);
            $query->bindParam(":numberofpeople_ID", $numberofpeople_ID);
            
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

    // public function getPricingIDInNumberOfPeopleByPeopleID($people_ID){
    //     $db = $this->connect();
    //     $sql = "SELECT pricing_ID FROM Number_Of_People WHERE numberofpeople_ID = :people_ID";
    //     $query = $db->prepare($sql);
    //     $query->bindParam(':people_ID', $people_ID);
    //     $query->execute();
    //     return $query->fetch(PDO::FETCH_ASSOC);
    // }

    // public function addGetPeople($numberofpeople_maximum, $numberofpeople_based, $currency, $forAdult, $forChild, $forYoungAdult, $forSenior, $forPWD, $includeMeal, $mealFee, $transportFee, $discount, $db) {
    //     try {

    //         $pricing_ID = $this->addPricing($currency, $forAdult, $forChild, $forYoungAdult, $forSenior, $forPWD, $includeMeal, $mealFee, $transportFee, $discount, $db);

    //             if (!$pricing_ID){
    //                 return false;
    //             }

    //         $sql = "SELECT numberofpeople_ID FROM Number_Of_People WHERE numberofpeople_maximum = :max
    //                 AND numberofpeople_based = :based AND pricing_ID = :pricing_ID";
    //         $query = $db->prepare($sql);
    //         $query->bindParam(':based', $numberofpeople_based );
    //         $query->bindParam(':max', $numberofpeople_maximum );
    //         $query->bindParam(':pricing_ID', $pricing_ID);
    //         $query->execute();
    //         $result = $query->fetch();
            
    //             if ($result) {
    //                 return $result['numberofpeople_ID'];
    //             }

    //         $sql = "INSERT INTO Number_Of_People (pricing_ID, numberofpeople_maximum, numberofpeople_based)
    //                 VALUES (:pricing_ID, :max, :based)";

    //         $query = $db->prepare($sql);
    //         $query->bindParam(':based', $numberofpeople_based );
    //         $query->bindParam(':max', $numberofpeople_maximum );
    //         $query->bindParam(':pricing_ID', $pricing_ID);
    //         $query->execute();
    //         $result = $query->fetch();

    //             if ($result) {
    //                 return $db->lastInsertId();
    //             }

            
    //     } catch (PDOException $e) {
    //         error_log("Error in addGetPeople: " . $e->getMessage());
    //         return false;
    //     }
    // }
}

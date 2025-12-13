<?php

trait PricingTrait {

    public function addPricing($currency, $forAdult, $forChild, $forYoungAdult, $forSenior, $forPWD, $includeMeal, $mealFee, $transportFee, $discount, $db){
        $sql = "INSERT INTO Pricing (
                    pricing_currency, pricing_foradult, pricing_forchild,
                    pricing_foryoungadult, pricing_forsenior, pricing_forpwd,
                    include_meal, pricing_mealfee, transport_fee, pricing_discount
                ) VALUES (
                    :currency, :forAdult, :forChild, :forYoungAdult,
                    :forSenior, :forPWD, :includeMeal, :mealFee,
                    :transportFee, :discount
                )";

        $query = $db->prepare($sql);

        $query->bindParam(':currency',      $currency);
        $query->bindParam(':forAdult',      $forAdult);
        $query->bindParam(':forChild',      $forChild);       // ← fixed
        $query->bindParam(':forYoungAdult', $forYoungAdult);
        $query->bindParam(':forSenior',     $forSenior);
        $query->bindParam(':forPWD',        $forPWD);
        $query->bindParam(':includeMeal',   $includeMeal);
        $query->bindParam(':mealFee',       $mealFee);
        $query->bindParam(':transportFee',  $transportFee);
        $query->bindParam(':discount',      $discount);

        // optional debug (remove in production)
        // error_log("SQL: $sql");
        // error_log("Params: " . print_r(compact(
        //     'currency','forAdult','forChild','forYoungAdult',
        //     'forSenior','forPWD','includeMeal','mealFee',
        //     'transportFee','discount'), true));

        return $query->execute() ? $db->lastInsertId() : false;
    }
    
    public function updatePricing($pricing_ID ,$currency, $forAdult, $forChild, $forYoungAdult, $forSenior, $forPWD, $includeMeal, $mealFee, $transportFee, $discount, $db){
        $sql = "UPDATE Pricing SET
            pricing_currency       = :currency,
            pricing_foradult       = :forAdult,
            pricing_forchild       = :forChild,
            pricing_foryoungadult  = :forYoungAdult,
            pricing_forsenior      = :forSenior,
            pricing_forpwd         = :forPWD,
            include_meal           = :includeMeal,
            pricing_mealfee        = :mealFee,
            transport_fee          = :transportFee,
            pricing_discount       = :discount
        WHERE pricing_ID = :pricing_ID";

        $query = $db->prepare($sql);
        $query->bindParam(':pricing_ID',      $pricing_ID);
        $query->bindParam(':currency',      $currency);
        $query->bindParam(':forAdult',      $forAdult);
        $query->bindParam(':forChild',      $forChild);       // ← fixed
        $query->bindParam(':forYoungAdult', $forYoungAdult);
        $query->bindParam(':forSenior',     $forSenior);
        $query->bindParam(':forPWD',        $forPWD);
        $query->bindParam(':includeMeal',   $includeMeal);
        $query->bindParam(':mealFee',       $mealFee);
        $query->bindParam(':transportFee',  $transportFee);
        $query->bindParam(':discount',      $discount);


        return $query->execute();
    }

    public function getPricingByID($pricingID) {
        $db = $this->connect();
        $sql = "SELECT * FROM Pricing WHERE pricing_ID = :pricingID";
        $query = $db->prepare($sql);
        $query->bindParam(':pricingID', $pricingID);
        $query->execute();
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function deletePricingByID($pricing_ID,$db){
        $sql = "DELETE FROM Pricing WHERE pricing_ID = :pricing_ID";
        
        try {
            $query = $db->prepare($sql);
            $query->bindParam(":pricing_ID", $pricing_ID);
            
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




    // public function addGetPricing($currency, $forAdult, $forChild, $forYoungAdult, $forSenior, $forPWD, $includeMeal, $mealFee, $transportFee, $discount, $db) {
    //     try {
    //         $sql = "SELECT pricing_ID FROM Pricing 
    //                 WHERE pricing_currency = :currency
    //                 AND pricing_foradult = :forAdult
    //                 AND pricing_forchild = :forChild
    //                 AND pricing_foryoungadult = :forYoungAdult
    //                 AND pricing_forsenior = :forSenior
    //                 AND pricing_forpwd = :forPWD
    //                 AND include_meal = :includeMeal
    //                 AND pricing_mealfee = :mealFee
    //                 AND transport_fee = :transportFee
    //                 AND pricing_discount = :discount";
    //         $query = $db->prepare($sql);
    //         $query->bindParam(':currency', $currency );
    //         $query->bindParam(':forAdult', $forAdult );
    //         $query->bindParam(':forChild', $currency );
    //         $query->bindParam(':forYoungAdult', $forYoungAdult );
    //         $query->bindParam(':forSenior', $forSenior );
    //         $query->bindParam(':forPWD', $forPWD );
    //         $query->bindParam(':includeMeal', $includeMeal );
    //         $query->bindParam(':mealFee',$mealFee );
    //         $query->bindParam(':mealFee',$currency );
    //         $query->bindParam(':transportFee',$transportFee );
    //         $query->bindParam(':discount',$discount );


    //         $result = $query->execute();
    //         if ($result) {
    //             return $result['pricing_ID'];
    //         }
    //         $sql = "INSERT INTO Pricing (
    //                     pricing_currency, pricing_foradult, pricing_forchild,
    //                     pricing_foryoungadult, pricing_forsenior, pricing_forpwd,
    //                     include_meal, pricing_mealfee, transport_fee, pricing_discount
    //                 ) VALUES (
    //                     :currency, :forAdult, :forChild, :forYoungAdult,
    //                     :forSenior, :forPWD, :includeMeal, :mealFee :transportFee, :discount
    //                 )";

    //         $query = $db->prepare($sql);
    //         $query->bindParam(':currency', $currency );
    //         $query->bindParam(':forAdult', $forAdult );
    //         $query->bindParam(':forChild', $currency );
    //         $query->bindParam(':forYoungAdult', $forYoungAdult );
    //         $query->bindParam(':forSenior', $forSenior );
    //         $query->bindParam(':forPWD', $forPWD );
    //         $query->bindParam(':includeMeal', $includeMeal );
    //         $query->bindParam(':mealFee',$mealFee );
    //         $query->bindParam(':mealFee',$currency );
    //         $query->bindParam(':transportFee',$transportFee );
    //         $query->bindParam(':discount',$discount );

    //        $result = $query->execute();
    //         if ($result) {
    //            return $db->lastInsertId();
    //         } else {
    //             return false;
    //         }
    //     } catch (PDOException $e) {
    //         error_log("Error in addGetPricing: " . $e->getMessage());
    //         return false;
    //     }
    // }
}

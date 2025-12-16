<?php

trait TourPackagesTrait {

    //addGetSchedule($days, $numberofpeople_maximum, $numberofpeople_based, $currency, $forAdult, $forChild, $forYoungAdult, $forSenior, $forPWD, $includeMeal, $mealFee, $transportFee, $discount, $db)

    public function addTourPackage($guide_ID, $name, $desc, $days, $numberofpeople_maximum, $numberofpeople_based, $currency, $forAdult, $forChild, $forYoungAdult, $forSenior, $forPWD, $includeMeal, $mealFee, $transportFee, $discount, $db) {
        $sql = "INSERT INTO Tour_Package(
                    guide_ID, tourpackage_name, tourpackage_desc,
                    schedule_days, numberofpeople_maximum, numberofpeople_based,
                    pricing_currency, pricing_foradult, pricing_forchild, pricing_foryoungadult,
                    pricing_forsenior, pricing_forpwd, include_meal, pricing_mealfee,
                    transport_fee, pricing_discount
                ) VALUES (
                    :guide_ID, :tourpackage_name, :tourpackage_desc,
                    :schedule_days, :numberofpeople_maximum, :numberofpeople_based,
                    :pricing_currency, :pricing_foradult, :pricing_forchild, :pricing_foryoungadult,
                    :pricing_forsenior, :pricing_forpwd, :include_meal, :pricing_mealfee,
                    :transport_fee, :pricing_discount
                )";
        $query = $db->prepare($sql);
        $query->execute([
            ':guide_ID'               => $guide_ID,
            ':tourpackage_name'       => $name,
            ':tourpackage_desc'       => $desc,
            ':schedule_days'          => $days,
            ':numberofpeople_maximum' => $numberofpeople_maximum,
            ':numberofpeople_based'   => $numberofpeople_based,
            ':pricing_currency'       => $currency,
            ':pricing_foradult'       => $forAdult,
            ':pricing_forchild'       => $forChild,
            ':pricing_foryoungadult'  => $forYoungAdult,
            ':pricing_forsenior'      => $forSenior,
            ':pricing_forpwd'         => $forPWD,
            ':include_meal'           => $includeMeal,
            ':pricing_mealfee'        => $mealFee,
            ':transport_fee'          => $transportFee,
            ':pricing_discount'       => $discount,
        ]);
        return $db->lastInsertId();
    }

    public function updateTourPackages($tourpackage_ID, $guide_ID, $name, $desc, $schedule_ID, $days, $numberofpeople_ID, $numberofpeople_maximum, $numberofpeople_based, $pricing_ID, $currency, $forAdult, $forChild, $forYoungAdult, $forSenior, $forPWD, $includeMeal, $mealFee, $transportFee, $discount, $db) {
        try {
            $sql = "UPDATE Tour_Package SET 
                        guide_ID = :guide_ID,
                        tourpackage_name = :name,
                        tourpackage_desc = :desc,
                        schedule_days = :days,
                        numberofpeople_maximum = :max,
                        numberofpeople_based = :based,
                        pricing_currency = :currency,
                        pricing_foradult = :adult,
                        pricing_forchild = :child,
                        pricing_foryoungadult = :young,
                        pricing_forsenior = :senior,
                        pricing_forpwd = :pwd,
                        include_meal = :meal,
                        pricing_mealfee = :meal_fee,
                        transport_fee = :transport,
                        pricing_discount = :discount
                    WHERE tourpackage_ID = :tourpackage_ID";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':guide_ID'       => $guide_ID,
                ':name'           => $name,
                ':desc'           => $desc,
                ':days'           => $days,
                ':max'            => $numberofpeople_maximum,
                ':based'          => $numberofpeople_based,
                ':currency'       => $currency,
                ':adult'          => $forAdult,
                ':child'          => $forChild,
                ':young'          => $forYoungAdult,
                ':senior'         => $forSenior,
                ':pwd'            => $forPWD,
                ':meal'           => $includeMeal,
                ':meal_fee'       => $mealFee,
                ':transport'      => $transportFee,
                ':discount'       => $discount,
                ':tourpackage_ID' => $tourpackage_ID,
            ]);
            return true;

        } catch (Exception $e) {
            error_log("[updateTourPackages] Error: " . $e->getMessage());
            return false;
        }
    }

    public function getTourPackageByID($tourpackage_ID){
        $sql = "SELECT * FROM Tour_Package WHERE tourpackage_ID = :tourpackage_ID";
        $db = $this->connect();
        $query = $db->prepare($sql);
        $query->bindParam(':tourpackage_ID', $tourpackage_ID);
        $query->execute();

        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function viewAllPackages(){
        $sql = "SELECT * FROM Tour_Package";
        $db = $this->connect();
        $query = $db->prepare($sql);
        $query->execute();

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function viewAllPackagesInfo(){
        $sql = "SELECT 
                tp.guide_ID, tp.tourpackage_desc, tp.tourpackage_ID, tp.tourpackage_name,
                tp.schedule_days, tp.numberofpeople_maximum, tp.numberofpeople_based,
                tp.pricing_currency, tp.pricing_foradult
            FROM Tour_Package tp";
        $db = $this->connect();
        $query = $db->prepare($sql);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getTourPackageDetailsByID($tourpackage_ID){
        $sql = "SELECT
            tp.tourpackage_ID,
            tp.tourpackage_name,
            tp.tourpackage_desc,
            CONCAT(ul.name_first, ' ', ul.name_last) AS guide_name,
            tp.schedule_days,
            g.guide_ID as guide_ID,
            tp.numberofpeople_maximum,
            tp.numberofpeople_based,
            tp.pricing_currency,
            tp.pricing_foradult,
            tp.pricing_forchild,
            tp.pricing_foryoungadult,
            tp.pricing_forsenior,
            tp.pricing_forpwd,
            tp.include_meal,
            tp.pricing_mealfee,
            tp.transport_fee,
            tp.pricing_discount,
            GROUP_CONCAT(ts.spots_name SEPARATOR ', ') AS tour_spots
        FROM tour_package tp
        JOIN Guide g ON tp.guide_ID = g.guide_ID
        JOIN account_info ai ON g.account_ID = ai.account_ID
        JOIN User_Login ul ON ai.user_ID = ul.user_ID 
        JOIN tour_package_spots tps ON tp.tourpackage_ID = tps.tourpackage_ID
        JOIN tour_spots ts ON tps.spots_ID = ts.spots_ID    
        WHERE tp.tourpackage_ID = :tourpackage_ID";
        $db = $this->connect();
        $query = $db->prepare($sql);
        $query->bindParam(':tourpackage_ID', $tourpackage_ID);
        $query->execute();
        $results =$query->fetchAll(PDO::FETCH_ASSOC);

        return $results;
    }

    public function getPackageById($id) {
        $db = $this->connect();
        $query = "SELECT tourpackage_status FROM tour_package  WHERE tourpackage_ID = :i";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":i", $id);
        $stmt->execute();
        $results = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $results;
    }

    public function getTourPackagesRating($tourpackage_ID): ?array{
        // 1. Make sure table name is correct (you wrote "rating" — is it "ratings"?)
        $sql = "SELECT 
                AVG(rating_value) AS avg_rating,
                COUNT(rating_value) AS total_ratings
                FROM rating
                WHERE rating_tourpackage_ID = :tourpackage_ID";

        try {
            $db = $this->connect(); // make sure this returns a valid PDO instance

            // 2. Enable exceptions for easier debugging
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $query = $db->prepare($sql);
            $query->bindParam(':tourpackage_ID', $tourpackage_ID, PDO::PARAM_INT);
            $query->execute();

            $row = $query->fetch(PDO::FETCH_ASSOC);

            // 3. If no ratings → AVG() returns NULL, COUNT() = 0
            if (!$row || $row['total_ratings'] == 0) {
                return null;
            }

            return [
                'avg'   => round((float)$row['avg_rating'], 1),
                'count' => (int)$row['total_ratings']
            ];

        } catch (PDOException $e) {
            // 4. Log error (never show raw error to user)
            error_log("Rating Query Failed: " . $e->getMessage());
            return null;
        }
    }

    public function getTourPackagesCountByGuide($guide_ID){
        $sql = "SELECT COUNT(*) AS total_packages
            FROM tour_package 
            WHERE guide_ID = :guide_ID 
            AND tourpackage_status = 'Active'";

        try {
            $db = $this->connect();
            $query = $db->prepare($sql);
            $query->bindParam(':guide_ID', $guide_ID, PDO::PARAM_INT);
            $query->execute();

            $result = $query->fetch(PDO::FETCH_ASSOC);
            return $result['total_packages'] ?? 0;
        } catch (Exception $e) {
            error_log("getTourPackagesCountByGuide Error: " . $e->getMessage());
            return 0;
        }

    }

    public function countPackages(){
        $sql = "SELECT COUNT(*) AS packages FROM tour_package WHERE tourpackage_status = 'Active'";
        $db = $this->connect();
        $query = $db->prepare($sql);
        $query->execute();

        return $query->fetch(PDO::FETCH_ASSOC);
    }

    // TourManager.php  (add inside the class)
    public function filterPackages(array $filters = []): array {
        $sql = "SELECT DISTINCT ul.* 
                FROM Tour_Package p";
        
        $params = [];
        $conditions = [];

        // ----- CATEGORIES -----
        // Filter packages that have at least one spot in the selected categories
        if (!empty($filters['categories']) && is_array($filters['categories'])) {
            $placeholders = implode(',', array_fill(0, count($filters['categories']), '?'));
            
            $sql .= " JOIN Tour_Package_Spots ps ON ul.tourpackage_ID = ps.tourpackage_ID
                    JOIN Tour_Spots s ON ps.spots_ID = s.spots_ID";
            
            // Use LOWER() and TRIM() for case-insensitive matching
            $conditions[] = "LOWER(TRIM(s.spots_category)) IN ($placeholders)";
            
            // Add lowercase trimmed categories to params
            foreach ($filters['categories'] as $cat) {
                $params[] = strtolower(trim($cat));
            }
        }

        // ----- PRICE RANGE -----
        if (!empty($filters['price_min'])) {
            $conditions[] = "ul.pricing_foradult >= ?";
            $params[] = $filters['price_min'];
        }

        if (!empty($filters['price_max'])) {
            $conditions[] = "ul.pricing_foradult <= ?";
            $params[] = $filters['price_max'];
        }

        // ----- PAX -----
        if (!empty($filters['minPax'])) {
            $conditions[] = "ul.numberofpeople_based >= ?";
            $params[] = $filters['minPax'];
        }

        if (!empty($filters['maxPax'])) {
            $conditions[] = "(ul.numberofpeople_maximum <= ? OR ul.numberofpeople_maximum IS NULL)";
            $params[] = $filters['maxPax'];
        }

        // Add WHERE clause if there are conditions
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        // Add ORDER BY for consistent results
        $sql .= " ORDER BY ul.tourpackage_ID";

        try {
            $db = $this->connect();
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Filter packages error: " . $e->getMessage());
            return [];
        }
    }

    public function updatePackageStatus($id, $status) {
        $query = "UPDATE tour_package SET tourpackage_status = :s WHERE tourpackage_ID = :i";
        $db = $this->connect();
        $stmt = $db->prepare($query);
        $stmt->bindParam(":s", $status);
        $stmt->bindParam(":i", $id);
        $stmt->execute();
        $results = $stmt->fetch(PDO::FETCH_ASSOC);

        return $results;
    }

    // public function getScheduleIDInTourPackageByTourPackageID($tourpackage_ID){
    //     $sql = "SELECT schedule_ID FROM Tour_Package WHERE tourpackage_ID = :tourpackage_ID";
    //     $db = $this->connect();
    //     $query = $db->prepare($sql);
    //     $query->bindParam(':tourpackage_ID', $tourpackage_ID);
    //     $query->execute();

    //     return $query->fetch(PDO::FETCH_ASSOC);

    // }

    // public function addTourPackage($guide_ID, $name, $desc, $days, $max, $min, $currency, $adult, $child, $young, $senior, $pwd, $meal, $meal_fee, $transport, $discount) {
    //     $sql = "INSERT INTO Tour_Packages 
    //             (guide_ID, tourpackage_name, tourpackage_desc, schedule_days,
    //              numberofpeople_maximum, numberofpeople_based, currency,
    //              pricing_foradult, pricing_forchild, pricing_foryoungadult,
    //              pricing_forsenior, pricing_forpwd,
    //              include_meal, meal_fee, transport_fee, discount)
    //             VALUES
    //             (:guide_ID, :name, :desc, :days,
    //              :max, :min, :currency,
    //              :adult, :child, :young,
    //              :senior, :pwd,
    //              :meal, :meal_fee, :transport, :discount)";

    //     try {
    //         $db = $this->connect();
    //         $q = $db->prepare($sql);
    //         $q->execute([
    //             ':guide_ID'   => $guide_ID,
    //             ':name'       => $name,
    //             ':desc'       => $desc,
    //             ':days'       => $days,
    //             ':max'        => $max,
    //             ':min'        => $min,
    //             ':currency'   => $currency,
    //             ':adult'      => $adult,
    //             ':child'      => $child,
    //             ':young'      => $young,
    //             ':senior'     => $senior,
    //             ':pwd'        => $pwd,
    //             ':meal'       => $meal,
    //             ':meal_fee'   => $meal_fee,
    //             ':transport'  => $transport,
    //             ':discount'   => $discount,
    //         ]);

    //         $id = (int)$db->lastInsertId();
    //         return $id > 0 ? $id : false;
    //     } catch (Exception $e) {
    //         error_log("addTourPackage error: " . $e->getMessage());
    //         return false;
    //     }
    // }
}

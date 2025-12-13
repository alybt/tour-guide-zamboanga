<?php

trait TourPackagesTrait {

    //addGetSchedule($days, $numberofpeople_maximum, $numberofpeople_based, $currency, $forAdult, $forChild, $forYoungAdult, $forSenior, $forPWD, $includeMeal, $mealFee, $transportFee, $discount, $db)

    public function addTourPackage($guide_ID, $name, $desc, $days, $numberofpeople_maximum, $numberofpeople_based, $currency, $forAdult, $forChild, $forYoungAdult, $forSenior, $forPWD, $includeMeal, $mealFee, $transportFee, $discount, $db) {
        $schedule_ID = $this->addGetSchedule($days, $numberofpeople_maximum, $numberofpeople_based, $currency, $forAdult, $forChild, $forYoungAdult, $forSenior, $forPWD, $includeMeal, $mealFee, $transportFee, $discount, $db);

        if (!$schedule_ID) {
                return false;
        }

        $sql = "INSERT INTO Tour_Package(guide_ID, tourpackage_name, tourpackage_desc, schedule_ID) VALUES (:guide_ID, :tourpackage_name, :tourpackage_desc, :schedule_ID)";
            $query = $db->prepare($sql);
            $query->bindParam(':guide_ID', $guide_ID);
            $query->bindParam(':tourpackage_name', $name);
            $query->bindParam(':tourpackage_desc', $desc);
            $query->bindParam(':schedule_ID', $schedule_ID);
            $query->execute();
            return $db->lastInsertId();


        
    }

    public function updateTourPackages($tourpackage_ID, $guide_ID, $name, $desc, $schedule_ID, $days, $numberofpeople_ID, $numberofpeople_maximum, $numberofpeople_based, $pricing_ID, $currency, $forAdult, $forChild, $forYoungAdult, $forSenior, $forPWD, $includeMeal, $mealFee, $transportFee, $discount, $db) {
        try {
            // ✅ STEP 1: Update Schedule
            $result = $this->updateSchedule(
                $schedule_ID, $days, $numberofpeople_ID, $numberofpeople_maximum, $numberofpeople_based,
                $pricing_ID, $currency, $forAdult, $forChild, $forYoungAdult,
                $forSenior, $forPWD, $includeMeal, $mealFee, $transportFee, $discount, $db
            );

            if (!$result) {
                throw new Exception("Failed to update schedule for package ID: $tourpackage_ID");
            }

            // ✅ STEP 2: Update Number of People
            $sqlPeople = "UPDATE Number_Of_People
                        SET pricing_ID = :pricing_ID,
                            numberofpeople_maximum = :max,
                            numberofpeople_based = :based
                        WHERE numberofpeople_ID = :numberofpeople_ID";

            $stmtPeople = $db->prepare($sqlPeople);
            $stmtPeople->execute([
                ':pricing_ID' => $pricing_ID,
                ':max'        => $numberofpeople_maximum,
                ':based'      => $numberofpeople_based,
                ':numberofpeople_ID' => $numberofpeople_ID
            ]);

            // ✅ STEP 3: Update Main Tour_Packages Table
            $sqlPackage = "UPDATE Tour_Package
                        SET guide_ID = :guide_ID,
                            tourpackage_name = :name,
                            tourpackage_desc = :desc,
                            schedule_ID = :schedule_ID
                        WHERE tourpackage_ID = :tourpackage_ID";

            $stmtPackage = $db->prepare($sqlPackage);
            $stmtPackage->execute([
                ':guide_ID'           => $guide_ID,
                ':name'               => $name,
                ':desc'               => $desc,
                ':schedule_ID'        => $schedule_ID,
                ':tourpackage_ID'     => $tourpackage_ID
            ]);

            // ✅ All succeeded
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
        $sql = "SELECT tp.guide_ID, tp.tourpackage_desc, tp.tourpackage_ID, tp.tourpackage_name,
            s.schedule_days, nop.numberofpeople_maximum, nop.numberofpeople_based,
            p.pricing_currency, p.pricing_foradult
            FROM Tour_Package tp 
            JOIN schedule s ON s.schedule_ID = tp.schedule_ID
            JOIN number_of_people nop ON s.numberofpeople_ID = nop.numberofpeople_ID
            JOIN pricing p ON nop.pricing_ID = p.pricing_ID";
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
            CONCAT(n.name_first, ' ', n.name_last) AS guide_name,
            s.schedule_days,
            g.guide_ID,
            np.numberofpeople_maximum,
            np.numberofpeople_based,
            pc.pricing_currency,
            pc.pricing_foradult,
            pc.pricing_forchild,
            pc.pricing_foryoungadult,
            pc.pricing_forsenior,
            pc.pricing_forpwd,
            pc.include_meal,
            pc.pricing_mealfee,
            pc.transport_fee,
            pc.pricing_discount,
            GROUP_CONCAT(ts.spots_name SEPARATOR ', ') AS tour_spots
        FROM tour_package tp
        JOIN schedule s ON tp.schedule_ID = s.schedule_ID
        JOIN Number_Of_People np ON np.numberofpeople_ID = s.numberofpeople_ID
        JOIN pricing pc ON pc.pricing_ID = np.pricing_ID
        JOIN guide g ON tp.guide_ID = g.guide_ID
        JOIN account_info ai ON g.account_ID = ai.account_ID
        JOIN user_login ul ON ai.user_ID = ul.user_ID
        JOIN person p ON ul.person_ID = p.person_ID
        JOIN name_info n ON p.name_ID = n.name_ID
        JOIN tour_package_spots tps ON tp.tourpackage_ID = tps.tourpackage_ID
        JOIN tour_spots ts ON tps.spots_ID = ts.spots_ID    
        WHERE tp.tourpackage_ID = :tourpackage_ID";
        $db = $this->connect();
        $query = $db->prepare($sql);
        $query->bindParam(':tourpackage_ID', $tourpackage_ID);
        $query->execute();
        return $query->fetch(PDO::FETCH_ASSOC);
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
        $sql = "SELECT DISTINCT p.* 
                FROM Tour_Package p
                JOIN Schedule sch ON p.schedule_ID = sch.schedule_ID
                JOIN Number_Of_People nop ON sch.numberofpeople_ID = nop.numberofpeople_ID
                JOIN Pricing pr ON nop.pricing_ID = pr.pricing_ID";
        
        $params = [];
        $conditions = [];

        // ----- CATEGORIES -----
        // Filter packages that have at least one spot in the selected categories
        if (!empty($filters['categories']) && is_array($filters['categories'])) {
            $placeholders = implode(',', array_fill(0, count($filters['categories']), '?'));
            
            $sql .= " JOIN Tour_Package_Spots ps ON p.tourpackage_ID = ps.tourpackage_ID
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
            $conditions[] = "pr.pricing_foradult >= ?";
            $params[] = $filters['price_min'];
        }

        if (!empty($filters['price_max'])) {
            $conditions[] = "pr.pricing_foradult <= ?";
            $params[] = $filters['price_max'];
        }

        // ----- PAX -----
        if (!empty($filters['minPax'])) {
            $conditions[] = "nop.numberofpeople_based >= ?";
            $params[] = $filters['minPax'];
        }

        if (!empty($filters['maxPax'])) {
            $conditions[] = "(nop.numberofpeople_maximum <= ? OR nop.numberofpeople_maximum IS NULL)";
            $params[] = $filters['maxPax'];
        }

        // Add WHERE clause if there are conditions
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        // Add ORDER BY for consistent results
        $sql .= " ORDER BY p.tourpackage_ID";

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

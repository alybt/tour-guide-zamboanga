<?php

require_once __DIR__ . "/../config/database.php";
require_once "trait/account/account-login.php";
require_once "trait/tour/tour-packages.php";
require_once "trait/tour/tour-spots.php";
require_once "trait/tour/tour-packagespots.php";
// schedule, pricing, and people tables are flattened into Tour_Package


class Guide extends Database {
    use AccountLoginTrait;
    use TourPackagesTrait;
    use TourSpotsTrait;
    use TourPackageSpot;


    public function viewAllGuide(){
        $sql = "SELECT 
                    g.guide_ID,
                    CONCAT(
                        ul.name_first, 
                        IF(ul.name_middle IS NOT NULL AND ul.name_middle != '', CONCAT(' ', ul.name_middle), ''),
                        ' ', 
                        ul.name_last,
                        IF(ul.name_suffix IS NOT NULL AND ul.name_suffix != '', CONCAT(' ', ul.name_suffix), '')
                    ) AS guide_name
                FROM Guide g
                JOIN Account_Info ai ON g.account_ID = ai.account_ID
                JOIN User_Login ul ON ai.user_ID = ul.user_ID
                
                
                ORDER BY ul.name_last, ul.name_first";
        $db = $this->connect();
        $query = $db->prepare($sql);

        if ($query->execute()){
            return $query->fetchAll(PDO::FETCH_ASSOC);
        } else {
            return false;
        }
    }

    public function viewAllGuideInfo(){
        $sql = "SELECT 
                    g.guide_ID,
                    CONCAT(
                        ul.name_first, 
                        IF(ul.name_middle IS NOT NULL AND ul.name_middle != '', CONCAT(' ', ul.name_middle), ''),
                        ' ', 
                        ul.name_last,
                        IF(ul.name_suffix IS NOT NULL AND ul.name_suffix != '', CONCAT(' ', ul.name_suffix), '')
                    ) AS guide_name,
                    ci.contactinfo_email AS guide_email,
                    gl.license_number AS guide_license,
                    ai.*
                FROM Guide g
                JOIN guide_license gl ON g.license_ID = gl.license_ID
                JOIN Account_Info ai ON g.account_ID = ai.account_ID
                JOIN User_Login ul ON ai.user_ID = ul.user_ID 
                LEFT JOIN Contact_Info ci ON ci.contactinfo_ID = ul.contactinfo_ID 
                ORDER BY ul.name_last, ul.name_first";
        $db = $this->connect();
        $query = $db->prepare($sql);

        if ($query->execute()){
            return $query->fetchAll(PDO::FETCH_ASSOC);
        } else {
            return false;
        }
    }


    public function viewPackageByGuideID($guide_ID){
        $sql = "SELECT * FROM Tour_Package WHERE guide_ID = :guide_ID";
        $db = $this->connect();
        $query = $db->prepare($sql);
        $query->bindParam(':guide_ID', $guide_ID);
        $query->execute();

        return $query->fetchAll(PDO::FETCH_ASSOC);
    

    }

    public function getGuide_ID($account_ID){
        $sql = "SELECT g.guide_ID FROM Guide AS g WHERE g.account_ID = :account_ID";
        $db = $this->connect();
        $query = $db->prepare($sql);
        $query->bindParam(":account_ID", $account_ID);
        $query->execute();

        $result = $query->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['guide_ID'] : null;
    }

    public function getScheduleByID($scheduleID) {
        $db = $this->connect();
        $sql = "SELECT * FROM Schedule WHERE schedule_ID = :scheduleID";
        $query = $db->prepare($sql);
        $query->bindParam(':scheduleID', $scheduleID);
        $query->execute();
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function getPricingByID($pricingID) {
        $db = $this->connect();
        $sql = "SELECT * FROM Pricing WHERE pricing_ID = :pricingID";
        $query = $db->prepare($sql);
        $query->bindParam(':pricingID', $pricingID);
        $query->execute();
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function getPeopleByID($peopleID) {
        $db = $this->connect();
        $sql = "SELECT * FROM Number_Of_People WHERE numberofpeople_ID = :peopleID";
        $query = $db->prepare($sql);
        $query->bindParam(':peopleID', $peopleID);
        $query->execute();
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function getSpotsByPackage($packageID) {
        $sql = "SELECT ts.* 
                FROM Tour_Package_Spots tps
                JOIN Tour_Spots ts ON tps.spots_ID = ts.spots_ID
                WHERE tps.tourpackage_ID = ?";
        $query = $this->conn->prepare($sql);
        $query->execute([$packageID]);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateTourPackage($tourpackage_ID, $guide_ID, $tourpackage_name, $tourpackage_desc, 
                                    $schedule_days, $numberofpeople_maximum, $numberofpeople_based, 
                                    $currency, $basedAmount, $discount, $spots) {
        $db = $this->connect();
        $db->beginTransaction();
        
        try {
            // Get current package data to find related records
            $currentPackage = $this->getTourPackageById($tourpackage_ID);
            if (!$currentPackage) {
                throw new Exception("Package not found");
            }

            // Update tour package (flattened)
            $sql = "UPDATE Tour_Package SET 
                    guide_ID = :guide_ID,
                    tourpackage_name = :tourpackage_name,
                    tourpackage_desc = :tourpackage_desc,
                    schedule_days = :schedule_days,
                    numberofpeople_maximum = :numberofpeople_maximum,
                    numberofpeople_based = :numberofpeople_based,
                    pricing_currency = :pricing_currency,
                    pricing_foradult = :pricing_foradult,
                    pricing_discount = :pricing_discount
                    WHERE tourpackage_ID = :tourpackage_ID";
            
            $query = $db->prepare($sql);
            $query->bindParam(':guide_ID', $guide_ID);
            $query->bindParam(':tourpackage_name', $tourpackage_name);
            $query->bindParam(':tourpackage_desc', $tourpackage_desc);
            $query->bindParam(':schedule_days', $schedule_days);
            $query->bindParam(':numberofpeople_maximum', $numberofpeople_maximum);
            $query->bindParam(':numberofpeople_based', $numberofpeople_based);
            $query->bindParam(':pricing_currency', $currency);
            $query->bindParam(':pricing_foradult', $basedAmount);
            $query->bindParam(':pricing_discount', $discount);
            $query->bindParam(':tourpackage_ID', $tourpackage_ID);
            
            if (!$query->execute()) {
                throw new Exception("Failed to update tour package");
            }

            // Delete existing spots
            $sql = "DELETE FROM Tour_Package_Spots WHERE tourpackage_ID = :tourpackage_ID";
            $query = $db->prepare($sql);
            $query->bindParam(':tourpackage_ID', $tourpackage_ID);
            $query->execute();

            // Add new spots
            if (!empty($spots)) {
                foreach ($spots as $spot_ID) {
                    $sql = "INSERT INTO Tour_Package_Spots (tourpackage_ID, spots_ID) VALUES (:tourpackage_ID, :spots_ID)";
                    $query = $db->prepare($sql);
                    $query->bindParam(':tourpackage_ID', $tourpackage_ID);
                    $query->bindParam(':spots_ID', $spot_ID);
                    $query->execute();
                }
            }

            $db->commit();
            return true;

        } catch (Exception $e) {
            $db->rollBack();
            error_log("Error updating tour package: " . $e->getMessage());
            return false;
        }
    }

     public function getTourPackageById($tourpackage_ID) {
        $db = $this->connect();
        try {
            // Get tour package information
            $sql = "SELECT tp.*
                    FROM Tour_Package tp
                    WHERE tp.tourpackage_ID = :tourpackage_ID";
            
            $query = $db->prepare($sql);
            $query->bindParam(':tourpackage_ID', $tourpackage_ID);
            $query->execute();
            
            $package = $query->fetch(PDO::FETCH_ASSOC);
            if (!$package) {
                return null;
            }

            // Get associated spots
            $sql = "SELECT spots_ID FROM Tour_Package_Spots WHERE tourpackage_ID = :tourpackage_ID";
            $query = $db->prepare($sql);
            $query->bindParam(':tourpackage_ID', $tourpackage_ID);
            $query->execute();
            $package['spots'] = array_column($query->fetchAll(PDO::FETCH_ASSOC), 'spots_ID');

            return $package;
        } catch (PDOException $e) {
            error_log("Error getting tour package: " . $e->getMessage());
            return null;
        }
    }

    public function getAllSpots(){
        $sql = "SELECT * FROM tour_spots";
        $db = $this->connect();
        $query = $db->prepare($sql);
        $query->execute();

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addgetSchedule($schedule_days, $numberofpeople_maximum, $numberofpeople_based, $currency, $basedAmount, $discount, $db){
        // First check if a matching schedule exists
        $sql = "SELECT s.schedule_ID
                FROM Schedule s
                JOIN Number_Of_People nop ON s.numberofpeople_ID = nop.numberofpeople_ID
                JOIN Pricing p ON nop.pricing_ID = ul.pricing_ID
                WHERE 
                    s.schedule_days = :schedule_days
                    AND nop.numberofpeople_maximum = :max
                    AND nop.numberofpeople_based = :based
                    AND ul.pricing_currency = :currency
                    AND ul.pricing_based = :basedAmount
                    AND ul.pricing_discount = :discount";
                    
        $query = $db->prepare($sql);
        $query->bindParam(':schedule_days', $schedule_days);
        $query->bindParam(':max', $numberofpeople_maximum);
        $query->bindParam(':based', $numberofpeople_based);
        $query->bindParam(':currency', $currency);
        $query->bindParam(':basedAmount', $basedAmount);
        $query->bindParam(':discount', $discount);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            return $result["schedule_ID"];
        }

        // If no matching schedule exists, create a new one
        $numberofpeople_ID = $this->addgetPeople($numberofpeople_maximum, $numberofpeople_based, $currency, $basedAmount, $discount, $db);
        if(!$numberofpeople_ID){
            return false;
        }

        $sql = "INSERT INTO Schedule(numberofpeople_ID, schedule_days) VALUES (:numberofpeople_ID, :schedule_days)";
        $query = $db->prepare($sql);
        $query->bindParam(':numberofpeople_ID', $numberofpeople_ID);
        $query->bindParam(':schedule_days', $schedule_days);

        if ($query->execute()){
            return $db->lastInsertId();
        } else {
            return false;
        }
    }

    
    public function addgetTouristByUserID($user_ID){
        $sql = "SELECT account_ID FROM Account_Info WHERE user_ID = :user_ID AND role_ID = 3";
        $db = $this->connect();
        $query_select = $db->prepare($sql);
        $query_select->bindParam(':user_ID', $user_ID);
        $query_select->execute();
        $result = $query_select->fetch();

        if($result){
            return $result["account_ID"];
        }

        $sql = "INSERT INTO Account_Info(user_ID, role_ID) VALUES (:user_ID, 3)";
        $query_insert = $db->prepare($sql);
        $query_insert->bindParam(':user_ID', $user_ID);

        if ($query_insert->execute()) {
            return $db->lastInsertId();
        } else {
            return false;
        }
    }

    public function changeAccountToTourist($user_ID){
        $db = $this->connect();
        $db->beginTransaction();
        try {
            $sql = "SELECT account_ID FROM Account_Info WHERE user_ID = :user_ID AND role_ID = 3";
            $qs = $db->prepare($sql);
            $qs->bindParam(':user_ID', $user_ID);
            $qs->execute();
            $existing = $qs->fetch(PDO::FETCH_ASSOC);
            if ($existing && isset($existing['account_ID'])) {
                $db->commit();
                return (int)$existing['account_ID'];
            }
            $sql = "INSERT INTO Account_Info(user_ID, role_ID, account_status) VALUES (:user_ID, 3, 'Active')";
            $qi = $db->prepare($sql);
            $qi->bindParam(':user_ID', $user_ID);
            if (!$qi->execute()) {
                $db->rollBack();
                return false;
            }
            $newId = (int)$db->lastInsertId();
            $db->commit();
            return $newId;
        } catch (PDOException $e) {
            $db->rollBack();
            error_log("Change Account Error: " . $e->getMessage());
            return false;
        }
    }

    public function getTotalEarnings($guide_ID) {
        $sql = "SELECT COALESCE(SUM(pt.transaction_total_amount), 0) AS total_earnings
            FROM booking b
            JOIN Payment_Transaction pt ON pt.booking_ID = b.booking_ID
            JOIN tour_package tp ON b.tourpackage_ID = tp.tourpackage_ID
            WHERE tp.guide_ID = :guide_ID
            AND b.booking_status IN ('Completed')
            AND pt.transaction_status = 'Paid'";

        try {
            $db = $this->connect();
            $query = $db->prepare($sql);
            $query->execute([':guide_ID' => $guide_ID]);
            return (float) $query->fetchColumn();
        } catch (Exception $e) {
            error_log("getTotalEarnings Error: " . $e->getMessage());
            return 0.0;
        }
    }

    public function guideRating($guide_ID){
        $sql = "SELECT account_rating_score FROM guide g JOIN account_info ai ON g.account_ID = ai.account_ID";
        try {
            $db = $this->connect();
            $query = $db->prepare($sql);
            $query->execute([':guide_ID' => $guide_ID]);
            return $query->fetchAll();
        } catch (Exception $e) {
            error_log("guideRating Error: " . $e->getMessage());
            return 0.0;
        }
    }

    // classes/guide.php
    public function getGuideByID(int $guide_ID): ?array {
        $sql = " SELECT 
                -- Guide & Account
                g.guide_ID,
                ai.*,
                
                -- Login
                ul.user_username,
                
                -- Personal Info
                ul.person_Nationality,
                ul.person_Gender,
                ul.person_DateOfBirth,
                
                -- Name
                ul.name_first,
                ul.name_second,
                ul.name_middle,
                ul.name_last,
                ul.name_suffix,
                
                -- Full Name (Clean Concatenation)
                TRIM(
                    CONCAT(
                        ul.name_first, ' ',
                        COALESCE(ul.name_second, ''), ' ',
                        COALESCE(ul.name_middle, ''), ' ',
                        ul.name_last,
                        IF(ul.name_suffix IS NOT NULL AND ul.name_suffix != '', CONCAT(' ', ul.name_suffix), '')
                    )
                ) AS guide_name,
                
                -- Contact
                ci.contactinfo_email AS guide_email,
                
                -- Primary Phone Number (with country code)
                CONCAT(COALESCE(c.country_codenumber, ''), pn.phone_number) AS guide_phonenumber,
                pn.phone_number AS phone_number_only,
                c.country_name,
                 
                CONCAT(
                    TRIM(CONCAT(ci.address_street, ' ', ci.address_houseno)),
                    IF(ci.barangay_ID IS NOT NULL, CONCAT(', ', b.barangay_name), ''),
                    IF(city.city_ID IS NOT NULL, CONCAT(', ', city.city_name), ''),
                    IF(prov.province_ID IS NOT NULL, CONCAT(', ', prov.province_name), ''),
                    IF(reg.region_ID IS NOT NULL, CONCAT(', ', reg.region_name), '')
                ) AS guide_address
                
            FROM guide g
            JOIN Account_Info ai ON ai.account_ID = g.account_ID
            JOIN User_Login ul ON ul.user_ID = ai.user_ID 
            LEFT JOIN Contact_Info ci ON ci.contactinfo_ID = ul.contactinfo_ID
            
            -- Primary Phone (assume one primary or get the first)
            LEFT JOIN Phone_Number pn ON pn.phone_ID = ci.phone_ID
            LEFT JOIN Country c ON c.country_ID = pn.country_ID
            
            -- Address 
            LEFT JOIN Barangay b ON b.barangay_ID = ci.barangay_ID
            LEFT JOIN City city ON city.city_ID = b.city_ID
            LEFT JOIN Province prov ON prov.province_ID = city.province_ID
            LEFT JOIN Region reg ON reg.region_ID = prov.region_ID
            
            WHERE g.guide_ID = :guide_ID
            LIMIT 1
        ";

        try {
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([':guide_ID' => $guide_ID]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ?: null; // Return null if no guide found

        } catch (PDOException $e) {
            error_log("getGuideByID Error: " . $e->getMessage());
            return null;
        }
    }

    public function viewTop5GuideInfoByRate(){
        $sql = "SELECT g.guide_ID, 
                CONCAT(
                    ul.name_first, 
                    IF(ul.name_middle IS NOT NULL AND ul.name_middle != '', CONCAT(' ', ul.name_middle), ''),
                    ' ',
                    ul.name_last,
                    IF(ul.name_suffix IS NOT NULL AND ul.name_suffix != '', CONCAT(' ', ul.name_suffix), '')
                ) AS guide_name, 
                ci.contactinfo_email AS guide_email, 
                gl.license_number,
                gl.license_verification_status,
                gl.license_issued_date,
                gl.license_expiry_date, 
                ai.*,
                g.guide_ID, 
                GROUP_CONCAT(l.languages_name ORDER BY l.languages_name SEPARATOR ', ') AS guide_languages 
            FROM Guide g 
            JOIN Guide_License gl ON g.license_ID = gl.license_ID 
            JOIN Account_Info ai ON g.account_ID = ai.account_ID 
            JOIN User_Login ul ON ai.user_ID = ul.user_ID 
            
            LEFT JOIN Contact_Info ci ON ul.contactinfo_ID = ci.contactinfo_ID 
            
            LEFT JOIN Guide_Languages glang ON g.guide_ID = glang.guide_ID 
            LEFT JOIN Languages l ON glang.languages_ID = l.languages_ID 
            GROUP BY 
                g.guide_ID,
                gl.license_ID,
                ai.account_ID,
                ci.contactinfo_email,
                ul.name_first,
                ul.name_middle,
                ul.name_last,
                ul.name_suffix 
            ORDER BY 
                ai.account_rating_score DESC,
                ul.name_last,
                ul.name_first
            LIMIT 5";  

        $db = $this->connect();
        $query = $db->prepare($sql);

        if ($query->execute()){
            return $query->fetchAll(PDO::FETCH_ASSOC);
        } else { 
            error_log("SQL Error in viewTop5GuideInfoByRate: " . implode(" ", $query->errorInfo()));
            return false;
        }
    }

    public function guideRatingAndCount($account_ID){
        $sql = "SELECT 
                AVG(r.rating_value) AS average_rating,
                COUNT(r.rating_ID) AS rating_count
            FROM 
                Rating r
            WHERE 
                r.rating_account_ID = :account_ID";
        try {
            $db = $this->connect();
            $query = $db->prepare($sql);
            $query->execute([':account_ID' => $account_ID]);
            $result = $query->fetch(PDO::FETCH_ASSOC);
            return $result ?: ['average_rating' => 0, 'rating_count' => 0];
        } catch (Exception $e) {
            error_log("guideRatingAndCount Error: " . $e->getMessage());
            return ['average_rating' => 0, 'rating_count' => 0];
        }

    }

    public function getguideLanguages($guide_ID){
        $sql = "SELECT
                     G.guide_ID,
                     AI.account_nickname AS Guide_Nickname,
                     CONCAT(ul.name_first, ' ', ul.name_last) AS Full_Name,
                     GROUP_CONCAT(L.languages_name SEPARATOR ', ') AS Spoken_Languages
                FROM
                    Guide G
                JOIN
                    Account_Info AI ON G.account_ID = AI.account_ID
                JOIN
                    User_Login UL ON AI.user_ID = UL.user_ID
                JOIN
                    Person P ON UL.person_ID = ul.person_ID
                JOIN
                    Name_Info NI ON ul.name_ID = ul.name_ID
                LEFT JOIN
                    Guide_Languages GL ON G.guide_ID = GL.guide_ID
                LEFT JOIN
                    Languages L ON GL.languages_ID = L.languages_ID 
                WHERE G.guide_ID = :guide_ID";
        try {
            $db = $this->connect();
            $query = $db->prepare($sql);
            $query->execute([':guide_ID' => $guide_ID]);
            return $query->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("getguideLanguages Error: " . $e->getMessage());
            return 0.0;
        }

    }

    public function viewAllGuideForSearch(){
        $sql = "SELECT g.guide_ID, 
                CONCAT(
                    ul.name_first, 
                    IF(ul.name_middle IS NOT NULL AND ul.name_middle != '', CONCAT(' ', ul.name_middle), ''),
                    ' ',
                    ul.name_last,
                    IF(ul.name_suffix IS NOT NULL AND ul.name_suffix != '', CONCAT(' ', ul.name_suffix), '')
                ) AS guide_name, 
                ci.contactinfo_email AS guide_email, 
                gl.license_number,
                gl.license_verification_status,
                gl.license_issued_date,
                gl.license_expiry_date, 
                ai.*,
                g.guide_ID, 
                GROUP_CONCAT(l.languages_name ORDER BY l.languages_name SEPARATOR ', ') AS guide_languages 
            FROM Guide g 
            JOIN Guide_License gl ON g.license_ID = gl.license_ID 
            JOIN Account_Info ai ON g.account_ID = ai.account_ID 
            JOIN User_Login ul ON ai.user_ID = ul.user_ID 
            
            LEFT JOIN Contact_Info ci ON ul.contactinfo_ID = ci.contactinfo_ID 
            
            LEFT JOIN Guide_Languages glang ON g.guide_ID = glang.guide_ID 
            LEFT JOIN Languages l ON glang.languages_ID = l.languages_ID 
            GROUP BY 
                g.guide_ID,
                gl.license_ID,
                ai.account_ID,
                ci.contactinfo_email,
                ul.name_first,
                ul.name_middle,
                ul.name_last,
                ul.name_suffix 
            ORDER BY 
                ai.account_rating_score DESC,
                ul.name_last,
                ul.name_first
            LIMIT 5";  

        $db = $this->connect();
        $query = $db->prepare($sql);

        if ($query->execute()){
            return $query->fetchAll(PDO::FETCH_ASSOC);
        } else { 
            error_log("SQL Error in viewAllGuideForSearch: " . implode(" ", $query->errorInfo()));
            return false;
        }
    }

    public function getGuideAccountID($guide_ID){
        $sql = "SELECT account_ID FROM Guide
        WHERE guide_ID = :guide_ID";
        
        $db = $this->connect();
        $query = $db->prepare($sql);
        $query->bindParam(':guide_ID', $guide_ID); 
        $query->execute();
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function getGuideDetails($guide_ID){
        $sql = "SELECT g.* FROM Guide g
        WHERE g.guide_ID = :guide_ID";
        
        $db = $this->connect();
        $query = $db->prepare($sql);
        $query->bindParam(':guide_ID', $guide_ID); 
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getGuideBalanace($guide_ID){
        $sql = "SELECT guide_balance FROM Guide 
        WHERE guide_ID = :guide_ID";
        
        $db = $this->connect();
        $query = $db->prepare($sql);
        $query->bindParam(':guide_ID', $guide_ID); 
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);
        return $result;
    }

    public function getAllPayoutofGuide($guide_ID){
        $sql = "SELECT 
            COALESCE(SUM(amount), 0.00) AS total_payout 
            FROM guide_money_history  
        WHERE guide_ID = :guide_ID AND reference_name = 'Payout'";
        
        $db = $this->connect();
        $query = $db->prepare($sql);
        $query->bindParam(':guide_ID', $guide_ID); 
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);
        return $result;
    }

    public function getuserIDByGuide($guide_ID){
        $sql = "SELECT ul.user_ID FROM guide g
            JOIN Account_Info ai ON g.account_ID = ai.account_ID
            JOIN User_Login ul ON ai.user_ID = ul.user_ID
            WHERE g.guide_ID = :guide_ID";
        
        $db = $this->connect();
        $query = $db->prepare($sql);
        $query->bindParam(':guide_ID', $guide_ID); 
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);
        return $result;
    }

}

<?php
require_once __DIR__ . "/../config/database.php";
require_once "trait/person/trait-name-info.php";
require_once "trait/person/trait-address.php";
require_once "trait/person/trait-phone.php";
require_once "trait/person/trait-emergency.php";
require_once "trait/person/trait-contact-info.php";
require_once "trait/person/trait-person.php";
require_once "trait/person/trait-user.php";


class Tourist extends Database {
    use PersonTrait, UserTrait, NameInfoTrait, AddressTrait, PhoneTrait, EmergencyTrait, ContactInfoTrait;


    public function getTouristBirthdateByTouristID($tourist_ID) {
        $sql  = "SELECT p.person_DateOfBirth
                 FROM account_info ai
                 JOIN user_login ul   ON ul.user_ID   = ai.user_ID
                 JOIN person p        ON p.person_ID  = ul.person_ID
                 WHERE ai.account_ID = :tourist_ID";
        $db   = $this->connect();
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':tourist_ID', $tourist_ID, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchColumn();             
    }

    public function getTouristPWDStatusByTouristID($tourist_ID) {
        $db   = $this->connect();
        $sql  = "SELECT p.person_isPWD
                 FROM account_info ai
                 JOIN user_login ul   ON ul.user_ID   = ai.user_ID
                 JOIN person p        ON p.person_ID  = ul.person_ID
                 WHERE ai.account_ID = :id";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':id', $tourist_ID, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn();          // 1 or 0
    }

    public function getTouristCategory($tourist_ID) {
        $birthDate = $this->getTouristBirthdateByTouristID($tourist_ID);
        $isPWD     = $this->getTouristPWDStatusByTouristID($tourist_ID);

       
        if (!$birthDate) {
            return 'Unknown';
        }

        $birth = new DateTime($birthDate);
        $today = new DateTime();
        $age   = $today->diff($birth)->y;

        if ($isPWD) {
            return 'PWD';
        }

        if ($age < 2)          return 'Infant';        // 0-1
        if ($age <= 12)        return 'Child';         // 2-12
        if ($age <= 17)        return 'Young Adult';   // 13-17
        if ($age <= 59)        return 'Adult';         // 18-59
        return 'Senior';                               // 60+
    }

    public function getPricingOfTourist($tourist_category, $booking_ID){
        $db = $this->connect(); // make sure your class has connect() method
        $sql = "SELECT p.pricing_foradult, p.pricing_foryoungadult, p.pricing_forsenior, p.pricing_forpwd 
                FROM booking b 
                JOIN tour_package tp ON b.tourpackage_ID = tp.tourpackage_ID
                JOIN schedule s ON tp.schedule_ID = s.schedule_ID
                JOIN number_of_people nop ON s.numberofpeople_ID = nop.numberofpeople_ID
                JOIN pricing p ON p.pricing_ID = nop.pricing_ID 
                WHERE b.booking_ID = :booking_ID";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':booking_ID', $booking_ID, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            return 0; // no data found
        }

        switch ($tourist_category) {
            case 'Young Adult':
                return (float)$result['pricing_foryoungadult']; 
            case 'Adult':
                return (float)$result['pricing_foradult'];
            case 'Senior':
                return (float)$result['pricing_forsenior'];
            case 'PWD':
                return (float)$result['pricing_forpwd'];
            default:
                return 0;
        }
    }
 
    public function getEmailByID(int $tourist_ID): ?string {
        $sql = " SELECT ci.contactinfo_email 
            FROM contact_info ci
            INNER JOIN person p ON p.contactinfo_ID = ci.contactinfo_ID
            INNER JOIN user_login u ON u.person_ID = p.person_ID
            INNER JOIN account_info a ON a.user_ID = u.user_ID
            WHERE a.account_ID = :tourist_ID
            LIMIT 1 ";

        try {
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([':tourist_ID' => $tourist_ID]);
            $result = $stmt->fetchColumn();

            return $result ?: null;
        } catch (PDOException $e) {
            error_log("Tourist::getEmailByID() error: " . $e->getMessage());
            return null;
        }
    }
 
    public function getFullNameByID(int $tourist_ID): ?string  {
        $sql = " SELECT CONCAT(p.person_firstname, ' ', p.person_lastname) AS fullname
            FROM person p
            INNER JOIN user_login u ON u.person_ID = p.person_ID
            INNER JOIN account_info a ON a.user_ID = u.user_ID
            WHERE a.account_ID = :tourist_ID
            LIMIT 1 ";

        try {
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([':tourist_ID' => $tourist_ID]);
            $result = $stmt->fetchColumn();

            return $result ?: null;
        } catch (PDOException $e) {
            error_log("Tourist::getFullNameByID() error: " . $e->getMessage());
            return null;
        }
    }

    public function getTouristByID($tourist_ID) {
        $sql = "SELECT 
                    -- Account Info
                    ai.account_ID,
                    ai.account_status,
                    ai.account_rating_score,
                    ai.account_created_at,
                    
                    -- User Login Info
                    ul.user_ID,
                    ul.user_username,
                    
                    -- Person Info
                    p.person_ID,
                    p.person_Nationality,
                    p.person_Gender,
                    p.person_DateOfBirth,
                    
                    -- Name Info
                    ni.name_ID,
                    ni.name_first,
                    ni.name_second,
                    ni.name_middle,
                    ni.name_last,
                    ni.name_suffix,
                    
                    -- Full Name (Computed)
                    CONCAT(
                        ni.name_first,
                        IF(ni.name_second IS NOT NULL AND ni.name_second != '', CONCAT(' ', ni.name_second), ''),
                        IF(ni.name_middle IS NOT NULL AND ni.name_middle != '', CONCAT(' ', ni.name_middle), ''),
                        ' ', ni.name_last,
                        IF(ni.name_suffix IS NOT NULL AND ni.name_suffix != '', CONCAT(' ', ni.name_suffix), '')
                    ) AS tourist_name,
                    
                    -- Contact Info
                    ci.contactinfo_ID,
                    ci.contactinfo_email,
                    
                    -- Phone Number
                    pn.phone_ID,
                    pn.phone_number,
                    
                    -- Country Info
                    c.country_ID,
                    c.country_name,
                    c.country_codename,
                    c.country_codenumber,
                    
                    -- Address Info
                    addr.address_ID,
                    addr.address_houseno,
                    addr.address_street,
                    
                    -- Barangay
                    b.barangay_ID,
                    b.barangay_name,
                    
                    -- City
                    city.city_ID,
                    city.city_name,
                    
                    -- Province
                    prov.province_ID,
                    prov.province_name,
                    
                    -- Region
                    reg.region_ID,
                    reg.region_name,
                    
                    -- Emergency Contact
                    ei.emergency_ID,
                    ei.emergency_Name,
                    ei.emergency_Relationship,
                    epn.phone_number AS emergency_phone
                    
                FROM Account_Info ai
                
                -- Join User and Person
                JOIN User_Login ul ON ai.user_ID = ul.user_ID
                JOIN Person p ON ul.person_ID = p.person_ID
                
                -- Join Name Info
                LEFT JOIN Name_Info ni ON p.name_ID = ni.name_ID
                
                -- Join Contact Info
                LEFT JOIN Contact_Info ci ON p.contactinfo_ID = ci.contactinfo_ID
                
                -- Join Phone Number and Country
                LEFT JOIN Phone_Number pn ON ci.phone_ID = pn.phone_ID
                LEFT JOIN Country c ON pn.country_ID = c.country_ID
                
                -- Join Address Info
                LEFT JOIN Address_Info addr ON ci.address_ID = addr.address_ID
                LEFT JOIN Barangay b ON addr.barangay_ID = b.barangay_ID
                LEFT JOIN City city ON b.city_ID = city.city_ID
                LEFT JOIN Province prov ON city.province_ID = prov.province_ID
                LEFT JOIN Region reg ON prov.region_ID = reg.region_ID
                
                -- Join Emergency Contact
                LEFT JOIN Emergency_Info ei ON ci.emergency_ID = ei.emergency_ID
                LEFT JOIN Phone_Number epn ON ei.phone_ID = epn.phone_ID
                
                WHERE ai.account_ID = :tourist_ID
                AND ai.role_ID = (SELECT role_ID FROM Role WHERE role_name = 'Tourist')";
        
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([':tourist_ID' => $tourist_ID]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } 

    public function getTouristBasicInfo($tourist_ID) {
        $sql = "SELECT 
                    ai.account_ID,
                    CONCAT(ni.name_first, ' ', ni.name_last) AS tourist_name,
                    ci.contactinfo_email,
                    pn.phone_number
                    
                FROM Account_Info ai
                JOIN User_Login ul ON ai.user_ID = ul.user_ID
                JOIN Person p ON ul.person_ID = p.person_ID
                LEFT JOIN Name_Info ni ON p.name_ID = ni.name_ID
                LEFT JOIN Contact_Info ci ON p.contactinfo_ID = ci.contactinfo_ID
                LEFT JOIN Phone_Number pn ON ci.phone_ID = pn.phone_ID
                
                WHERE ai.account_ID = :tourist_ID";
        
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([':tourist_ID' => $tourist_ID]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
     
    public function getTouristFullAddress($tourist_ID) {
        $sql = "SELECT 
                    CONCAT(
                        addr.address_houseno, ' ',
                        addr.address_street, ', ',
                        b.barangay_name, ', ',
                        city.city_name, ', ',
                        prov.province_name, ', ',
                        reg.region_name, ', ',
                        c.country_name
                    ) AS full_address
                    
                FROM Account_Info ai
                JOIN User_Login ul ON ai.user_ID = ul.user_ID
                JOIN Person p ON ul.person_ID = p.person_ID
                JOIN Contact_Info ci ON p.contactinfo_ID = ci.contactinfo_ID
                LEFT JOIN Address_Info addr ON ci.address_ID = addr.address_ID
                LEFT JOIN Barangay b ON addr.barangay_ID = b.barangay_ID
                LEFT JOIN City city ON b.city_ID = city.city_ID
                LEFT JOIN Province prov ON city.province_ID = prov.province_ID
                LEFT JOIN Region reg ON prov.region_ID = reg.region_ID
                LEFT JOIN Phone_Number pn ON ci.phone_ID = pn.phone_ID
                LEFT JOIN Country c ON pn.country_ID = c.country_ID
                
                WHERE ai.account_ID = :tourist_ID";
        
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([':tourist_ID' => $tourist_ID]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? $result['full_address'] : null;
    }
     
    public function getTouristBookings($tourist_ID) {
        $sql = "SELECT 
                    b.booking_ID,
                    b.booking_status,
                    b.booking_created_at,
                    b.booking_start_date,
                    b.booking_end_date,
                    tp.tourpackage_name,
                    CONCAT(gn.name_first, ' ', gn.name_last) AS guide_name,
                    pt.transaction_total_amount,
                    pr.pricing_currency
                    
                FROM Booking b
                JOIN Tour_Package tp ON b.tourpackage_ID = tp.tourpackage_ID
                LEFT JOIN Guide g ON tp.guide_ID = g.guide_ID
                LEFT JOIN Account_Info gai ON g.account_ID = gai.account_ID
                LEFT JOIN User_Login gul ON gai.user_ID = gul.user_ID
                LEFT JOIN Person gp ON gul.person_ID = gp.person_ID
                LEFT JOIN Name_Info gn ON gp.name_ID = gn.name_ID
                LEFT JOIN Payment_Transaction pt ON b.booking_ID = pt.booking_ID
                LEFT JOIN Schedule s ON tp.schedule_ID = s.schedule_ID
                LEFT JOIN Number_Of_People nop ON s.numberofpeople_ID = nop.numberofpeople_ID
                LEFT JOIN Pricing pr ON nop.pricing_ID = pr.pricing_ID
                
                WHERE b.tourist_ID = :tourist_ID
                ORDER BY b.booking_created_at DESC";
        
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([':tourist_ID' => $tourist_ID]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
     
    public function getTouristStats($tourist_ID) {
        $sql = "SELECT 
                    COUNT(b.booking_ID) AS total_bookings,
                    COUNT(CASE WHEN b.booking_status = 'Completed' THEN 1 END) AS completed_bookings,
                    COUNT(CASE WHEN b.booking_status = 'Cancelled' THEN 1 END) AS cancelled_bookings,
                    IFNULL(SUM(pt.transaction_total_amount), 0) AS total_spent
                    
                FROM Booking b
                LEFT JOIN Payment_Transaction pt ON b.booking_ID = pt.booking_ID
                
                WHERE b.tourist_ID = :tourist_ID";
        
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([':tourist_ID' => $tourist_ID]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
     
    public function updateTouristProfile($tourist_ID, $data) {
        try {
            $db = $this->connect();
            $db->beginTransaction();
            
            // Update Name Info
            if (isset($data['name_first']) || isset($data['name_last'])) {
                $sql = "UPDATE Name_Info ni
                        JOIN Person p ON ni.name_ID = p.name_ID
                        JOIN User_Login ul ON p.person_ID = ul.person_ID
                        JOIN Account_Info ai ON ul.user_ID = ai.user_ID
                        SET 
                            ni.name_first = COALESCE(:name_first, ni.name_first),
                            ni.name_middle = COALESCE(:name_middle, ni.name_middle),
                            ni.name_last = COALESCE(:name_last, ni.name_last)
                        WHERE ai.account_ID = :tourist_ID";
                
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    ':tourist_ID' => $tourist_ID,
                    ':name_first' => $data['name_first'] ?? null,
                    ':name_middle' => $data['name_middle'] ?? null,
                    ':name_last' => $data['name_last'] ?? null
                ]);
            }
            
            // Update Person Info (Nationality, Gender, DOB)
            if (isset($data['person_Nationality']) || isset($data['person_Gender']) || isset($data['person_DateOfBirth'])) {
                $sql = "UPDATE Person p
                        JOIN User_Login ul ON p.person_ID = ul.person_ID
                        JOIN Account_Info ai ON ul.user_ID = ai.user_ID
                        SET 
                            p.person_Nationality = COALESCE(:nationality, p.person_Nationality),
                            p.person_Gender = COALESCE(:gender, p.person_Gender),
                            p.person_DateOfBirth = COALESCE(:dob, p.person_DateOfBirth)
                        WHERE ai.account_ID = :tourist_ID";
                
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    ':tourist_ID' => $tourist_ID,
                    ':nationality' => $data['person_Nationality'] ?? null,
                    ':gender' => $data['person_Gender'] ?? null,
                    ':dob' => $data['person_DateOfBirth'] ?? null
                ]);
            }
            
            // Update Contact Info (Email)
            if (isset($data['contactinfo_email'])) {
                $sql = "UPDATE Contact_Info ci
                        JOIN Person p ON ci.contactinfo_ID = p.contactinfo_ID
                        JOIN User_Login ul ON p.person_ID = ul.person_ID
                        JOIN Account_Info ai ON ul.user_ID = ai.user_ID
                        SET ci.contactinfo_email = :email
                        WHERE ai.account_ID = :tourist_ID";
                
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    ':tourist_ID' => $tourist_ID,
                    ':email' => $data['contactinfo_email']
                ]);
            }
            
            $db->commit();
            return ['success' => true, 'message' => 'Profile updated successfully'];
            
        } catch (Exception $e) {
            $db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getBookingHistory($tourist_ID) {
        $sql = "SELECT 
                    b.booking_ID,
                    b.booking_status,
                    b.booking_created_at,
                    b.booking_start_date,
                    b.booking_end_date,
                    tp.tourpackage_name,
                    pt.transaction_total_amount,
                    pr.pricing_currency
                    
                    
                FROM Booking b
                JOIN Tour_Package tp ON b.tourpackage_ID = tp.tourpackage_ID
                LEFT JOIN Payment_Transaction pt ON b.booking_ID = pt.booking_ID
                LEFT JOIN Schedule s ON tp.schedule_ID = s.schedule_ID
                LEFT JOIN Number_Of_People nop ON s.numberofpeople_ID = nop.numberofpeople_ID
                LEFT JOIN Pricing pr ON nop.pricing_ID = pr.pricing_ID
                JOIN booking_bundle bb ON b.booking_ID = bb.booking_ID
                JOIN companion c ON bb.companion_ID = c.companion_ID
                JOIN companion_category cc ON c.companion_category_ID = cc.companion_category_ID
                
                WHERE b.tourist_ID = :tourist_ID AND b.booking_status IN ('Completed', 'Cancelled', 'Refunded', 'Failed', 'Rejected by the Guide', 'Booking Expired — Payment Not Completed', 'Booking Expired — Guide Did Not Confirm in Time')
                ORDER BY b.booking_created_at DESC";
        
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([':tourist_ID' => $tourist_ID]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getGuideRating($guide_ID){
        $sql = "SELECT * FROM Account_Info ai
            JOIN guide g ON ai.account_ID = g.guide_ID
            WHERE g.guide_ID = :guide_ID";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([':guide_ID' => $guide_ID]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
    }

    public function getTouristByAccountID($account_ID) {
        $sql = "SELECT 
                    ai.account_ID,
                    ul.user_ID,
                    p.person_ID,
                    ni.name_first,
                    ni.name_last,
                    ci.contactinfo_email
                FROM Account_Info ai
                JOIN User_Login ul ON ai.user_ID = ul.user_ID
                JOIN Person p ON ul.person_ID = p.person_ID
                LEFT JOIN Name_Info ni ON p.name_ID = ni.name_ID
                LEFT JOIN Contact_Info ci ON p.contactinfo_ID = ci.contactinfo_ID
                WHERE ai.account_ID = :account_ID";
        
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([':account_ID' => $account_ID]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function tourSpotsExplored($account_ID){
        $sql = "SELECT COUNT(DISTINCT ts.spots_ID) AS spots_explored
            FROM Booking b
            JOIN Tour_Package tp ON b.tourpackage_ID = tp.tourpackage_ID
            JOIN Tour_Package_Spots tps ON tp.tourpackage_ID = tps.tourpackage_ID
            JOIN Tour_Spots ts ON tps.spots_ID = ts.spots_ID
            WHERE b.tourist_ID = :account_ID AND b.booking_status = 'Completed'";
        $stmt = $this->connect()->prepare($sql);
        $stmt->bindParam(':account_ID', $account_ID, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? (int)$result['spots_explored'] : 0;
    }

    public function touristAveRating($account_ID){
        $sql = "SELECT AVG(r.rating_value) AS average_rating
            FROM Rating r
            WHERE r.rater_account_ID = :account_ID";
        $stmt = $this->connect()->prepare($sql);
        $stmt->bindParam(':account_ID', $account_ID, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? round((float)$result['average_rating'], 2) : 0.0;
    }

}


<?php 

trait BookingDetails{

    public function getBookingByIDAndTourist(int $booking_ID, int $tourist_ID): array|false {
        $db = $this->connect();
        
        $sql = "SELECT 
                b.*,
                tp.tourpackage_name,
                tp.tourpackage_desc,
                s.schedule_days,
                nop.numberofpeople_maximum,
                nop.numberofpeople_based,
                p.pricing_currency,
                p.pricing_foradult,
                p.pricing_discount,
                tp.guide_ID
            FROM booking b
            INNER JOIN tour_package tp ON b.tourpackage_ID = tp.tourpackage_ID
            INNER JOIN schedule s ON s.schedule_ID = tp.schedule_ID
            INNER JOIN number_of_people nop ON nop.numberofpeople_ID = s.numberofpeople_ID
            INNER JOIN pricing p ON p.pricing_ID = nop.pricing_ID
            WHERE 
                b.booking_ID = :booking_ID 
                AND b.tourist_ID = :tourist_ID
            LIMIT 1
        ";

        try {
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':booking_ID', $booking_ID, PDO::PARAM_INT);
            $stmt->bindValue(':tourist_ID', $tourist_ID, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC); // Returns array or false
        } catch (PDOException $e) {
            error_log("getBookingByIDAndTourist Error: " . $e->getMessage());
            return false;
        }
    } 
    // Add to your Booking class
    public function markItinerarySent(int $booking_ID): bool {
        try {
            $sql = "UPDATE booking SET itinerary_sent = 1, itinerary_sent_at = NOW() WHERE booking_ID = :id";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([':id' => $booking_ID]);
            return true;
        } catch (Exception $e) {
            error_log("Failed to mark itinerary sent: " . $e->getMessage());
            return false;
        }
    } 

    public function hasItineraryBeenSent(int $booking_ID): bool {
        $sql = "SELECT itinerary_sent FROM booking WHERE booking_ID = :id";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([':id' => $booking_ID]);
        $result = $stmt->fetchColumn();
        return $result == 1;
    } 

    // Method to get complete booking details with all joins
    public function getBookingWithDetails($booking_ID) {
        $sql = "SELECT 
                    -- Booking Info
                    b.booking_ID,
                    b.booking_status,
                    b.booking_created_at,
                    b.booking_start_date,
                    b.booking_end_date,
                    b.booking_isselfincluded,
                    
                    -- Tourist Info
                    ai.account_ID AS tourist_account_ID,
                    CONCAT(ni.name_first, 
                        IF(ni.name_middle IS NOT NULL AND ni.name_middle != '', CONCAT(' ', ni.name_middle), ''), 
                        ' ', ni.name_last) AS tourist_fullname,
                    ci.contactinfo_email AS tourist_email,
                    
                    -- Tour Package Info
                    tp.tourpackage_ID,
                    tp.tourpackage_name,
                    tp.tourpackage_desc,
                    
                    -- Pricing Info
                    pr.pricing_currency,
                    pr.pricing_foradult,
                    pr.pricing_discount,
                    
                    -- Guide Info
                    g.guide_ID,
                    CONCAT(gn.name_first, ' ', gn.name_last) AS guide_fullname,
                    gci.contactinfo_email AS guide_email,
                    gpn.phone_number AS guide_phone,
                    
                    -- Payment Info
                    pi.paymentinfo_ID,
                    pi.paymentinfo_total_amount,
                    pi.paymentinfo_date,
                    
                    -- Transaction Info
                    pt.transaction_ID,
                    pt.transaction_status,
                    pt.transaction_reference,
                    pt.transaction_created_date
                    
                FROM Booking b
                
                -- Tourist Info
                JOIN Account_Info ai ON b.tourist_ID = ai.account_ID
                JOIN User_Login ul ON ai.user_ID = ul.user_ID
                JOIN Person p ON ul.person_ID = p.person_ID
                JOIN Name_Info ni ON p.name_ID = ni.name_ID
                LEFT JOIN Contact_Info ci ON p.contactinfo_ID = ci.contactinfo_ID
                
                -- Tour Package Info
                JOIN Tour_Package tp ON b.tourpackage_ID = tp.tourpackage_ID
                JOIN Schedule s ON tp.schedule_ID = s.schedule_ID
                JOIN Number_Of_People nop ON s.numberofpeople_ID = nop.numberofpeople_ID
                JOIN Pricing pr ON nop.pricing_ID = pr.pricing_ID
                
                -- Guide Info
                LEFT JOIN Guide g ON tp.guide_ID = g.guide_ID
                LEFT JOIN Account_Info gai ON g.account_ID = gai.account_ID
                LEFT JOIN User_Login gul ON gai.user_ID = gul.user_ID
                LEFT JOIN Person gp ON gul.person_ID = gp.person_ID
                LEFT JOIN Name_Info gn ON gp.name_ID = gn.name_ID
                LEFT JOIN Contact_Info gci ON gp.contactinfo_ID = gci.contactinfo_ID
                LEFT JOIN Phone_Number gpn ON gci.phone_ID = gpn.phone_ID
                
                -- Payment Info
                LEFT JOIN Payment_Info pi ON b.booking_ID = pi.booking_ID
                LEFT JOIN Payment_Transaction pt ON pi.paymentinfo_ID = pt.paymentinfo_ID
                
                WHERE b.booking_ID = :booking_ID";
                
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([':booking_ID' => $booking_ID]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Get companions with their category names and age
    public function getCompanionsByBookingID($booking_ID) {
        $sql = "SELECT 
                    c.companion_ID,
                    c.companion_name,
                    c.companion_age,
                    cc.companion_category_name
                FROM Booking_Bundle bb
                JOIN Companion c ON bb.companion_ID = c.companion_ID
                JOIN Companion_Category cc ON c.companion_category_ID = cc.companion_category_ID
                WHERE bb.booking_ID = :booking_ID
                ORDER BY c.companion_name";
                
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([':booking_ID' => $booking_ID]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPaymentInfoByBookingID(int $bookingID): ?array {
        $sql = "
            SELECT 
                paymentinfo_ID,
                booking_ID,
                paymentinfo_total_amount AS total_amount,
                paymentinfo_date         AS payment_date
            FROM Payment_Info
            WHERE booking_ID = :booking_id
            ORDER BY paymentinfo_date DESC
            LIMIT 1
        ";

        try {
            $db = $this->connect();
            $stmt = $db->prepare($sql);
            $stmt->execute([':booking_id' => $bookingID]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            // Return null if no payment found
            return $result ?: null;

        } catch (PDOException $e) {
            // Log error in production, don't expose details to user
            error_log("Error in getPaymentInfoByBookingID: " . $e->getMessage());
            return null;
        }
    }

    public function getTourPackageDetailsByBookingID($booking_ID){
        $sql = "SELECT tp.* FROM booking b 
            JOIN tour_package tp ON b.tourpackage_ID = tp.tourpackage_ID
            WHERE b.booking_ID = :booking_ID";
        try {
            $db = $this->connect();
            $stmt = $db->prepare($sql);
            $stmt->execute([':booking_ID' => $booking_ID]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            // Return null if no payment found
            return $result ?: null;

        } catch (PDOException $e) {
            // Log error in production, don't expose details to user
            error_log("Error in getTourPackageDetailsByBookingID: " . $e->getMessage());
            return null;
        }
    }

}

?>
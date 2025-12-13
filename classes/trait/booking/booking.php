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
        $sql = " SELECT 
        pi.paymentinfo_ID,
        pi.booking_ID,
        pi.paymentinfo_total_amount AS total_amount,
        pi.paymentinfo_date         AS payment_date,
        pt.transaction_status    AS transaction_status
                    FROM Payment_Info pi
                    JOIN Payment_Transaction pt ON pi.paymentinfo_ID = pt.paymentinfo_ID
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

    public function getBookingHistoryByGuideID(int $guide_ID): array{
        $sql = "SELECT 
            b.booking_ID,
            b.booking_created_at AS booking_date,
            DATE(b.booking_start_date) AS tour_date,
            b.booking_status, 
            tp.tourpackage_name, 
            -- Full tourist name
            TRIM( CONCAT( ni.name_first, IF(ni.name_middle IS NOT NULL AND TRIM(ni.name_middle) != '', CONCAT(' ', ni.name_middle),  '' ), ' ', ni.name_last ) ) AS tourist_name, 
            -- CORRECT TOTAL PAX using your booking_isselfIncluded flag
            ( COUNT(c.companion_ID) +  IF(b.booking_isselfIncluded = 1 OR b.booking_isselfIncluded IS NULL, 1, 0) ) AS total_pax, 
            -- Money
            pi.paymentinfo_total_amount AS total_amount,
            pt.transaction_status AS payment_status, 
            -- Contact
            CONCAT(COALESCE(coun.country_codenumber, ''), pn.phone_number) AS phone_number

            FROM booking b
            JOIN tour_package tp  ON b.tourpackage_ID = tp.tourpackage_ID
            JOIN account_info ai  ON ai.account_ID = b.tourist_ID
            JOIN user_login ul ON ul.user_ID = ai.user_ID
            JOIN person p ON p.person_ID = ul.person_ID
            JOIN name_info ni ON ni.name_ID = p.name_ID

            LEFT JOIN contact_info ci ON ci.contactinfo_ID = p.contactinfo_ID
            LEFT JOIN phone_number pn ON pn.phone_ID = ci.phone_ID
            LEFT JOIN country coun    ON coun.country_ID = pn.country_ID
            LEFT JOIN booking_bundle bb ON bb.booking_ID = b.booking_ID
            LEFT JOIN companion c ON c.companion_ID = bb.companion_ID
            LEFT JOIN payment_info pi ON pi.booking_ID = b.booking_ID
            LEFT JOIN payment_transaction pt        ON pt.paymentinfo_ID = pi.paymentinfo_ID 
            
            WHERE tp.guide_ID = :guide_ID  AND b.booking_status IN (
            'Completed','Cancelled','Refunded','Failed','Rejected by the Guide',
            'Booking Expired — Payment Not Completed',
            'Booking Expired — Guide Did Not Confirm in Time',
            'Cancelled - No Refund' )
            
            GROUP BY b.booking_ID, tp.tourpackage_name, 
                ni.name_first, ni.name_middle, ni.name_last,
                b.booking_isselfIncluded, b.booking_created_at, b.booking_start_date, b.booking_status,
                pi.paymentinfo_total_amount, pt.transaction_status,
                coun.country_codenumber, pn.phone_number
            
            ORDER BY booking_date DESC";

        try {
            $db = $this->connect();
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':guide_ID', $guide_ID, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error in getBookingHistoryByGuideID: "  . $e->getMessage());
            return [];
        }
    }

    private function getBaseSql(): string {
        return " SELECT  b.booking_ID, b.booking_created_at AS booking_date,
                DATE(b.booking_start_date) AS tour_date,
                b.booking_status, 
                tp.tourpackage_name, 
                TRIM(CONCAT(
                    ni.name_first, 
                    IF(ni.name_middle IS NOT NULL AND TRIM(ni.name_middle) != '', CONCAT(' ', ni.name_middle), ''), 
                    ' ', ni.name_last
                )) AS tourist_name, 
                
                (COUNT(c.companion_ID) + IF(b.booking_isselfIncluded = 1 OR b.booking_isselfIncluded IS NULL, 1, 0)) AS total_pax, 
                 
                COALESCE(pi.paymentinfo_total_amount, 0) AS total_amount,
                
                pt.transaction_status AS payment_status, 
                CONCAT(COALESCE(coun.country_codenumber, ''), pn.phone_number) AS phone_number,

            FROM booking b
                JOIN tour_package tp ON b.tourpackage_ID = tp.tourpackage_ID
                JOIN account_info ai ON ai.account_ID = b.tourist_ID
                JOIN user_login ul ON ul.user_ID = ai.user_ID
                JOIN person p ON p.person_ID = ul.person_ID
                JOIN name_info ni ON ni.name_ID = p.name_ID
                LEFT JOIN contact_info ci ON ci.contactinfo_ID = p.contactinfo_ID
                LEFT JOIN phone_number pn ON pn.phone_ID = ci.phone_ID
                LEFT JOIN country coun ON coun.country_ID = pn.country_ID
                LEFT JOIN booking_bundle bb ON bb.booking_ID = b.booking_ID
                LEFT JOIN companion c ON c.companion_ID = bb.companion_ID
                LEFT JOIN payment_info pi ON pi.booking_ID = b.booking_ID
                LEFT JOIN payment_transaction pt ON pt.paymentinfo_ID = pi.paymentinfo_ID 
                
            WHERE tp.guide_ID = :guide_ID 
            AND b.booking_status IN (
                'Completed','Cancelled','Refunded','Failed','Rejected by the Guide',
                'Booking Expired — Payment Not Completed',
                'Booking Expired — Guide Did Not Confirm in Time',
                'Cancelled - No Refund'
            )

            GROUP BY 
                b.booking_ID, tp.tourpackage_name, ni.name_first, ni.name_middle, ni.name_last,
                b.booking_isselfIncluded, b.booking_created_at, b.booking_start_date, b.booking_status,
                pi.paymentinfo_total_amount, pt.transaction_status,
                coun.country_codenumber, pn.phone_number

            ORDER BY
                FIELD(b.booking_status,
                    'In Progress', 'Approved', 'Pending for Approval', 'Pending for Payment',
                    'Completed', 'Cancelled', 'Cancelled - No Refund', 'Refunded', 'Failed',
                    'Rejected by the Guide', 'Booking Expired — Payment Not Completed',
                    'Booking Expired — Guide Did Not Confirm in Time'
                ),
                b.booking_start_date DESC,
                b.booking_created_at DESC ";
    }

    private function executeQuery(string $sql, int $guide_ID, string $extraWhere = '', array $extraParams = []): array {
        try {
            $db = $this->connect();
            $stmt = $db->prepare($sql . $extraWhere);
            $stmt->bindParam(':guide_ID', $guide_ID, PDO::PARAM_INT);
            foreach ($extraParams as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in BookingHistoryModel: " . $e->getMessage());
            return [];
        }
    }

    // Variants of booking history based on time frames
    public function getTodayBookings(int $guide_ID): array {
        $extraWhere = " AND DATE(b.booking_start_date) = CURDATE()";
        return $this->executeQuery($this->getBaseSql(), $guide_ID, $extraWhere);
    }

    public function getPastWeekBookings(int $guide_ID): array {
        $extraWhere = " AND b.booking_start_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        return $this->executeQuery($this->getBaseSql(), $guide_ID, $extraWhere);
    }

    public function getPastMonthBookings(int $guide_ID): array {
        $extraWhere = " AND b.booking_start_date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
        return $this->executeQuery($this->getBaseSql(), $guide_ID, $extraWhere);
    }

    public function getFirstHalfMonthBookings(int $guide_ID): array {
        $extraWhere = " AND DAY(b.booking_start_date) BETWEEN 1 AND 15 
                        AND MONTH(b.booking_start_date) = MONTH(CURDATE()) 
                        AND YEAR(b.booking_start_date) = YEAR(CURDATE())";
        return $this->executeQuery($this->getBaseSql(), $guide_ID, $extraWhere);
    }

    public function getSecondHalfMonthBookings(int $guide_ID): array {
        $extraWhere = " AND DAY(b.booking_start_date) >= 16 
                        AND MONTH(b.booking_start_date) = MONTH(CURDATE()) 
                        AND YEAR(b.booking_start_date) = YEAR(CURDATE())";
        return $this->executeQuery($this->getBaseSql(), $guide_ID, $extraWhere);
    }

    public function getPast3MonthsBookings(int $guide_ID): array {
        $extraWhere = " AND b.booking_start_date >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)";
        return $this->executeQuery($this->getBaseSql(), $guide_ID, $extraWhere);
    }

    public function getPast6MonthsBookings(int $guide_ID): array {
        $extraWhere = " AND b.booking_start_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)";
        return $this->executeQuery($this->getBaseSql(), $guide_ID, $extraWhere);
    }

    public function getGuideAccountIDByBookingID(int $booking_ID): ?int
    {
        $sql = "
            SELECT g.account_ID
            FROM Booking b
            INNER JOIN Tour_Package tp ON b.tourpackage_ID = tp.tourpackage_ID
            INNER JOIN Guide g ON tp.guide_ID = g.guide_ID
            WHERE b.booking_ID = :booking_ID
            LIMIT 1
        ";

        try {
            $db = $this->connect();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':booking_ID', $booking_ID, PDO::PARAM_INT);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ? (int)$result['account_ID'] : null;

        } catch (PDOException $e) {
            error_log("Error in getGuideAccountIDByBookingID: " . $e->getMessage());
            return null;
        }
    }

    public function getGuideDetailsByAccountID(int $account_ID): ?array {
        $sql = "SELECT ai.*,
                g.guide_ID,
                CONCAT(ni.name_first, 
                    IF(ni.name_middle IS NOT NULL AND ni.name_middle != '', CONCAT(' ', ni.name_middle), ''), 
                    ' ', ni.name_last) AS guide_fullname,
                ci.contactinfo_email AS guide_email,
                pn.phone_number AS guide_phone
            FROM guide g
            JOIN account_info ai ON g.account_ID = ai.account_ID
            JOIN user_login ul ON ai.user_ID = ul.user_ID
            JOIN person p ON ul.person_ID = p.person_ID
            JOIN name_info ni ON p.name_ID = ni.name_ID
            LEFT JOIN contact_info ci ON p.contactinfo_ID = ci.contactinfo_ID
            LEFT JOIN phone_number pn ON ci.phone_ID = pn.phone_ID
            WHERE g.account_ID = :account_ID
            LIMIT 1";

        try {
            $db = $this->connect();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':account_ID', $account_ID, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ?: null;

        } catch (PDOException $e) {
            error_log("Error in getGuideDetailsByAccountID: " . $e->getMessage());
            return null;
        }

    }

    public function getBookingDetailsByBookingID(int $booking_ID): ?array {
        $sql = "SELECT 
                b.*,
                tp.tourpackage_name,
                tp.tourpackage_desc,
                s.schedule_days,
                nop.numberofpeople_maximum,
                nop.numberofpeople_based,
                p.pricing_currency,
                p.pricing_foradult,
                p.pricing_discount
            FROM booking b
            INNER JOIN tour_package tp ON b.tourpackage_ID = tp.tourpackage_ID
            INNER JOIN schedule s ON s.schedule_ID = tp.schedule_ID
            INNER JOIN number_of_people nop ON s.numberofpeople_ID = nop.numberofpeople_ID
            INNER JOIN pricing p ON nop.pricing_ID = p.pricing_ID
            WHERE b.booking_ID = :booking_ID
            LIMIT 1";

        try {
            $db = $this->connect();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':booking_ID', $booking_ID, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ?: null;

        } catch (PDOException $e) {
            error_log("Error in getBookingDetailsByBookingID: " . $e->getMessage());
            return null;
        }
    }

    public function startingDateAndTime(int $booking_ID): ?string {
        $sql = "SELECT  b.booking_ID,
                CONCAT(
                    DATE_FORMAT(DATE_ADD(b.booking_start_date, INTERVAL (tps.packagespot_day - 1) DAY), '%b %d %Y'),
                    ' at ',
                    TIME_FORMAT(tps.packagespot_starttime, '%l:%i %p')
                ) AS first_itinerary_start,
                tps.packagespot_activityname AS activity_name,
                ts.spots_name AS spot_name
            FROM 
                Booking b
            JOIN 
                Tour_Package tp ON b.tourpackage_ID = tp.tourpackage_ID
            JOIN 
                Tour_Package_Spots tps ON tp.tourpackage_ID = tps.tourpackage_ID
            LEFT JOIN 
                Tour_Spots ts ON tps.spots_ID = ts.spots_ID
            WHERE 
                b.booking_ID = :booking_ID
            ORDER BY 
                ADDTIME(
                    CAST(DATE_ADD(b.booking_start_date, INTERVAL (tps.packagespot_day - 1) DAY) AS DATETIME), 
                    tps.packagespot_starttime
                ) ASC
            LIMIT 1";

        try {
            $db = $this->connect();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':booking_ID', $booking_ID, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ? $result['first_itinerary_start'] : null;

        } catch (PDOException $e) {
            error_log("Error in startingDateAndTime: " . $e->getMessage());
            return null;
        }
    }

    public function getMeetingPoint(int $booking_ID): ?string {
        $sql = "SELECT 
                b.booking_ID,
                CASE 
                    WHEN b.booking_meeting_ID IS NOT NULL THEN b.booking_meeting_ID
                    ELSE NULL  -- Or handle custom meeting separately in app logic
                END AS meeting_ID,
                mp.meeting_name,
                mp.meeting_description,
                mp.meeting_address,
                mp.meeting_googlelink
            FROM 
                Booking b
            LEFT JOIN 
                Meeting_Point mp ON b.booking_meeting_ID = mp.meeting_ID
            WHERE 
                b.booking_ID = :booking_ID";

        try {
            $db = $this->connect();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':booking_ID', $booking_ID, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ? $result['meeting_name'] : null;

        } catch (PDOException $e) {
            error_log("Error in getMeetingPoint: " . $e->getMessage());
            return null;
        }
    }

}
?>

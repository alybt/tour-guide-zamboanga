<?php 

require_once __DIR__ . "/../config/database.php";
require_once "trait/booking/booking-bundle.php";
require_once "trait/booking/companion.php";
require_once "trait/booking/update.php";
require_once "trait/booking/booking.php";
require_once "activity-log.php";

class Booking extends Database{
    private ActivityLogs $activity;
    use BookingBundleTrait, CompanionTrait, UpdateBookings, BookingDetails;
    
    public function __construct() {
        $this->activity = new ActivityLogs(); 
    }

    public function getAllCompanionCategories(){
        $sql = "SELECT * FROM `companion_category`";
        $db = $this->connect();
        $query = $db->prepare($sql);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function viewBookingByTourist($tourist_ID){
        $sql = "SELECT 
            b.booking_ID,
            b.tourist_ID AS tourist_ID,
            tp.tourpackage_ID,
            tp.tourpackage_name,
            tp.tourpackage_desc,
            CONCAT(n.name_first, ' ', n.name_last) AS guide_name,
            b.booking_start_date,
            b.booking_end_date,
            b.booking_status,
            s.schedule_days,
            np.numberofpeople_maximum,
            np.numberofpeople_based,
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
        FROM booking b
        JOIN tour_package tp ON b.tourpackage_ID = tp.tourpackage_ID
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
        WHERE b.tourist_ID = :tourist_ID
        GROUP BY b.booking_ID
        ORDER BY ABS(DATEDIFF(b.booking_start_date, CURRENT_TIMESTAMP)) ASC";

        $db = $this->connect();
        $query = $db->prepare($sql);
        $query->bindParam(':tourist_ID', $tourist_ID);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function viewBookingByTouristANDBookingID($booking_ID){
        $sql = "SELECT 
            b.booking_ID,
            b.tourist_ID,
            b.booking_isselfincluded,
            tp.tourpackage_ID,
            tp.tourpackage_name,
            tp.tourpackage_desc,
            CONCAT(n.name_first, ' ', n.name_last) AS guide_name,
            b.booking_start_date,
            b.booking_end_date,
            b.booking_status,
            s.schedule_days,
            np.numberofpeople_maximum,
            np.numberofpeople_based,
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
        FROM booking b
        JOIN tour_package tp ON b.tourpackage_ID = tp.tourpackage_ID
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
        LEFT JOIN booking_bundle bb ON b.booking_ID = bb.booking_ID
        WHERE b.booking_ID = :booking_ID
        GROUP BY b.booking_ID
        ORDER BY ABS(DATEDIFF(b.booking_start_date, CURRENT_TIMESTAMP)) ASC";
        $db = $this->connect();
        $query = $db->prepare($sql);
        $query->bindParam(':booking_ID', $booking_ID);
        $query->execute();
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function viewBookingByBookingIDForGuide($booking_ID){
        $sql = "SELECT 
            b.booking_ID,
            b.tourist_ID,
            b.booking_isselfincluded,
            tp.tourpackage_ID,
            tp.tourpackage_name,
            tp.tourpackage_desc,
            CONCAT(n.name_first, ' ', n.name_last) AS tourist_name,
            b.booking_start_date,
            b.booking_end_date,
            b.booking_status,
            s.schedule_days,
            np.numberofpeople_maximum,
            np.numberofpeople_based,
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
        FROM booking b
        JOIN tour_package tp ON b.tourpackage_ID = tp.tourpackage_ID
        JOIN schedule s ON tp.schedule_ID = s.schedule_ID
        JOIN Number_Of_People np ON np.numberofpeople_ID = s.numberofpeople_ID
        JOIN pricing pc ON pc.pricing_ID = np.pricing_ID
        JOIN account_info ai ON ai.account_ID = b.tourist_ID
        JOIN user_login ul ON ai.user_ID = ul.user_ID
        JOIN person p ON ul.person_ID = p.person_ID
        JOIN name_info n ON p.name_ID = n.name_ID
        JOIN tour_package_spots tps ON tp.tourpackage_ID = tps.tourpackage_ID
        JOIN tour_spots ts ON tps.spots_ID = ts.spots_ID    
        LEFT JOIN booking_bundle bb ON b.booking_ID = bb.booking_ID
        WHERE b.booking_ID = :booking_ID
        GROUP BY b.booking_ID
        ORDER BY ABS(DATEDIFF(b.booking_start_date, CURRENT_TIMESTAMP)) ASC";
        $db = $this->connect();
        $query = $db->prepare($sql);
        $query->bindParam(':booking_ID', $booking_ID);
        $query->execute();
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function addBookingForTourist($tourist_ID, $tourpackage_ID, $booking_start_date, $booking_end_date, $booking_isselfincluded){
        $db = $this->connect();
        $db->beginTransaction();

        try {
            $sql = "INSERT INTO booking (tourist_ID, tourpackage_ID, booking_start_date, booking_end_date, booking_isselfincluded) 
                    VALUES (:tourist_ID, :tourpackage_ID, :booking_start_date, :booking_end_date, :booking_isselfincluded)";
            $query = $db->prepare($sql);
            $query->bindParam(':tourist_ID', $tourist_ID, PDO::PARAM_INT);
            $query->bindParam(':tourpackage_ID', $tourpackage_ID, PDO::PARAM_INT);
            $query->bindParam(':booking_start_date', $booking_start_date);
            $query->bindParam(':booking_end_date', $booking_end_date);
            $query->bindParam(':booking_isselfincluded', $booking_isselfincluded);
            $result = $query->execute();

            if ($result) {
                $booking_ID = $db->lastInsertId();
                $db->commit();
                return $booking_ID;
            } else {
                $db->rollBack();
                return false;
            }
        } catch (PDOException $e) {
            $db->rollBack();
            error_log("Booking Transaction Error: " . $e->getMessage());
            return false;
        }
    }

    public function cancelBookingIfPendingForPayment($booking_ID, $account_ID){
        $booking_ID = (int)$booking_ID;
        $account_ID = (int)$account_ID;

        if ($booking_ID <= 0 || $account_ID <= 0) {
            return "Invalid ID provided.";
        }

        $db = $this->connect();
        $db->beginTransaction();

        try {
            // Step 1: Check current booking status
            $checkSql = "SELECT booking_status FROM Booking WHERE booking_ID = :booking_ID";
            $checkStmt = $db->prepare($checkSql);
            $checkStmt->bindParam(':booking_ID', $booking_ID, PDO::PARAM_INT);
            $checkStmt->execute();
            $status = $checkStmt->fetchColumn();

            if ($status === false) {
                $db->rollback();
                return "Booking not found.";
            }

            if ($status !== 'Pending for Payment') {
                $db->rollback();
                return "Cannot cancel booking. Current status: {$status}";
            }

            // Step 3: Atomic update with status check
            $updateSql = "UPDATE Booking 
                        SET booking_status = 'Cancelled' 
                        WHERE booking_ID = :booking_ID 
                            AND booking_status = 'Pending for Payment'";
            $updateStmt = $db->prepare($updateSql);
            $updateStmt->bindParam(':booking_ID', $booking_ID, PDO::PARAM_INT);
            $updateStmt->execute();

            if ($updateStmt->rowCount() === 0) {
                $db->rollback();
                return "Update failed: Status may have changed or booking not found.";
            }

            
            $db->commit();
            return true;

        } catch (Exception $e) {
            $db->rollback();
            error_log("Cancel Booking Error: " . $e->getMessage());
            return false;
        }
    }

    public function getBookingDateByOldBooking($old_bookingID){
        $sql = "SELECT 
                booking_start_date AS booking_start,
                booking_end_date AS booking_end 
                FROM Booking WHERE booking_ID = :booking_ID";
        $db = $this->connect();
        $query = $db->prepare($sql);
        $query->bindParam(":booking_ID", $old_bookingID);
        
        $result = $query->execute();

        if($result){
            return $query->fetch(PDO::FETCH_ASSOC);
        } else {
            return false;
        }
    }

    public function getBookingDetailsByBooking($old_bookingID){
        $sql = "SELECT booking_ID,
                tourist_ID,
                booking_status,
                tourpackage_ID,
                booking_start_date,
                booking_end_date 
                FROM Booking WHERE booking_ID = :booking_ID";
        $db = $this->connect();
        $query = $db->prepare($sql);
        $query->bindParam(":booking_ID", $old_bookingID);
        
        $result = $query->execute();

        if($result){
            return $query->fetch(PDO::FETCH_ASSOC);
        } else {
            return false;
        }


    }

    public function getBookingByGuideID($guide_ID){
        $sql = "SELECT
            tp.tourpackage_name, tp.tourpackage_desc, s.schedule_days, 
            CONCAT(ni.name_first, ' ', ni.name_last) AS tourist_name, 
            b.booking_ID, b.booking_start_date, b.booking_end_date, b.booking_status, 
            s.schedule_days, GROUP_CONCAT(ts.spots_name SEPARATOR ', ') AS tour_spots 
            FROM booking b 
            JOIN tour_package tp ON b.tourpackage_ID = tp.tourpackage_ID 
            JOIN account_info ai ON b.tourist_ID = ai.account_ID 
            JOIN user_login ul ON ai.user_ID = ul.user_ID 
            JOIN person p ON ul.person_ID = p.person_ID 
            JOIN name_info ni ON p.name_ID = ni.name_ID 
            JOIN schedule s ON s.schedule_ID = tp.schedule_ID 
            JOIN tour_package_spots tps ON tp.tourpackage_ID = tps.tourpackage_ID 
            JOIN tour_spots ts ON tps.spots_ID = ts.spots_ID
            WHERE tp.guide_ID = :guide_ID
            GROUP BY b.booking_ID
            ORDER BY ABS(DATEDIFF(b.booking_start_date, CURRENT_TIMESTAMP)) ASC";
        $db = $this->connect();
        $query = $db->prepare($sql);
        $query->bindParam(":guide_ID", $guide_ID);
        
        $result = $query->execute();

        if($result){
            return $query->fetchAll(PDO::FETCH_ASSOC);
        } else {
            return false;
        }
    }

    public function existingBookingsInGuide($guide_ID){
        $db = $this->connect();
        $sql = "SELECT b.booking_start_date, b.booking_end_date
                FROM booking b
                JOIN tour_package tp ON tp.tourpackage_ID = b.tourpackage_ID
                WHERE tp.guide_ID = :guide_ID
                AND b.booking_status IN ('Pending for Payment', 'Pending for Approval', 'In Progress', 'Waiting for the Schedule Date', 'Approved')";
        
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':guide_ID', $guide_ID, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function cancelBookingNoRefund($booking_ID, $tourist_ID) {
        $db = $this->connect();
        $db->beginTransaction();

        try {
            $sql = "UPDATE booking 
                    SET booking_status = 'Cancelled - No Refund' 
                    WHERE booking_ID = :booking_ID 
                    AND tourist_ID = :tourist_ID";
            $stmt = $db->prepare($sql);
            $stmt->bindPaam(':booking_ID', $booking_ID, PDO::PARAM_INT);
            $stmt->bindParam(':tourist_ID', $tourist_ID, PDO::PARAM_INT);

            if (!$stmt->execute()) {
                $db->rollback();
                throw new Exception("Failed to update Booking status.");
                return false;
            }

            $sql = "UPDATE Payment_Transaction pt
                    SET pt.transaction_status = 'No Refund'
                    WHERE pt.booking_ID = :booking_ID";
            $query = $db->prepare($sql);
            $query->bindParam(':booking_ID', $booking_ID);

            if (!$query->execute()) {
                $db->rollback();
                throw new Exception("Failed to update Transaction status.");
                return false;
            } 

            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollback();
            error_log("Cancel Booking No Refund Error: " . $e->getMessage());
            return false;
        }
 
    }

    public function getActiveBookingCount($guide_ID) {
        $sql = "SELECT COUNT(*) AS active_count
            FROM booking b 
            JOIN tour_package tp ON b.tourpackage_ID = tp.tourpackage_ID 
            WHERE tp.guide_ID = :guide_ID 
            AND b.booking_status IN ('Pending for Payment', 'Pending for Approval', 'Approved')";

        try {
            $db = $this->connect();
            $query = $db->prepare($sql);
            $query->bindParam(':guide_ID', $guide_ID, PDO::PARAM_INT);
            $query->execute();

            $result = $query->fetch(PDO::FETCH_ASSOC);
            return $result['active_count'] ?? 0;
        } catch (Exception $e) {
            error_log("getActiveBookingCount Error: " . $e->getMessage());
            return 0;
        }
    }
    
    public function getCompanions($booking_ID){
        $sql = "SELECT 
                C.companion_name,
                CC.companion_category_name
            FROM Booking AS B
            JOIN Booking_Bundle AS BB ON B.booking_ID = BB.booking_ID
            JOIN Companion AS C ON BB.companion_ID = C.companion_ID
            JOIN Companion_Category CC ON CC.companion_category_ID = C.companion_category_ID
            WHERE B.booking_ID = :booking_ID
            ORDER BY C.companion_ID";
        $db = $this->connect();
        $query = $db->prepare($sql);
        $query->bindParam(':booking_ID', $booking_ID);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getDates($booking_ID){
        $sql = "SELECT booking_start_date, booking_end_date FROM booking 
            WHERE booking_ID = :booking_ID";
        $db = $this->connect();
        $query = $db->prepare($sql);
        $query->bindParam(':booking_ID', $booking_ID);
        $query->execute();
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function countBookings(){
        $sql = "SELECT COUNT(*) AS countbookings FROM booking WHERE booking_status IN ('Pending for Payment','Pending for Approval')";
        $db = $this->connect();
        $query = $db->prepare($sql);
        $query->execute();

        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function countAllBookings(){
      $sql = "SELECT COUNT(*) AS countallbookings FROM booking";
        $db = $this->connect();
        $query = $db->prepare($sql);
        $query->execute();

        return $query->fetch(PDO::FETCH_ASSOC);  
    }


    // GUIDE
    public function updateBookingStatus_Approved($booking_ID) {
        try {
            $sql = "UPDATE Booking 
                    SET booking_status = 'Approved' 
                    WHERE booking_ID = :booking_ID";

            $db = $this->connect();
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $query = $db->prepare($sql);
            $query->bindParam(':booking_ID', $booking_ID, PDO::PARAM_INT);
            $query->execute();

            if ($query->rowCount() > 0) {
                // Success
                error_log(" Booking ID {$booking_ID} successfully updated to 'Approved'.");
                return true;
            } else {
                // No rows affected (booking ID might not exist)
                error_log(" No rows updated. Booking ID {$booking_ID} may not exist or is already 'Approved'.");
                return false;
            }

        } catch (PDOException $e) {
            // Log the detailed error message
            error_log(" Database error while updating booking status for ID {$booking_ID}: " . $e->getMessage());
            return false;
        } catch (Exception $e) { 
            error_log(" General error in updateBookingStatus_Approved(): " . $e->getMessage());
            return false;
        }
    }

    public function updateBookingStatus_Complete($booking_ID) {
        try {
            $sql = "UPDATE Booking 
                    SET booking_status = 'Completed' 
                    WHERE booking_ID = :booking_ID";

            $db = $this->connect();
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $query = $db->prepare($sql);
            $query->bindParam(':booking_ID', $booking_ID, PDO::PARAM_INT);
            $query->execute();

            if ($query->rowCount() > 0) {
                // Success
                error_log(" Booking ID {$booking_ID} successfully updated to 'Completed'.");
                return true;
            } else {
                // No rows affected (booking ID might not exist)
                error_log(" No rows updated. Booking ID {$booking_ID} may not exist or is already 'Completed'.");
                return false;
            }

        } catch (PDOException $e) {
            // Log the detailed error message
            error_log(" Database error while updating booking status for ID {$booking_ID}: " . $e->getMessage());
            return false;
        } catch (Exception $e) { 
            error_log(" General error in updateBookingStatus_Complete(): " . $e->getMessage());
            return false;
        }
    }



    // public function viewBookingByBookingID($tourist_ID){
    //     $sql = "SELECT * FROM Booking WHERE booking_ID = :booking_ID";
    //     $db = $this->connect();
    //     $query = $db->prepare($sql);
    //     $query->bindParam(':booking_ID', $booking_ID);
    //     $query->execute();
    //     return $query->fetchAll(PDO::FETCH_ASSOC);
    // }
    




    // public function addCompanionToBooking($booking_ID, $companion_name, $category_ID){
    //     $db = $this->connect();
    //     $db->beginTransaction();

    //     try{
    //             $bookingbundle_ID = $this->addCompanionToBooking($booking_ID, $companion_name, $category_ID, $db);

    //             if (!$bookingbundle_ID) {
    //                 $db->rollBack();
    //                 return false;
    //             } else {
    //                 $db->commit();
    //                 return true;
    //             }

    //         } catch (PDOException $e) {
    //             $db->rollBack();
    //             error_log("Booking Bundle: " . $e->getMessage()); 
    //             return false;
    //         }

    // }






}

?>
<?php

require_once __DIR__ . "/../config/database.php";
require_once "trait/tour/tour-packages.php";
require_once "trait/tour/tour-spots.php";
require_once "trait/tour/tour-packagespots.php";
require_once "trait/tour/schedule.php";
require_once "trait/tour/pricing.php";
require_once "trait/tour/people.php";

class TourManager extends Database {
    use TourPackagesTrait, PeopleTrait, PricingTrait, ScheduleTrait;
    use TourSpotsTrait, TourPackageSpot;

    // spots_ID, packagespots_activityname, packagespots_starttime, packagespots_endtime, packagespot_day
    public function addTourPackagesAndItsSpots($tour_spots, $packagespots_activityname, $packagespots_starttime, $packagespots_endtime, $packagespot_day, $guide_ID, $name, $desc, $days, $numberofpeople_maximum, $numberofpeople_based, $currency, $forAdult, $forChild, $forYoungAdult, $forSenior, $forPWD, $includeMeal, $mealFee, $transportFee, $discount){
        $db = $this->connect();
        $db->beginTransaction();

        try {
            $tourpackage_ID = $this->addTourPackage(
                $guide_ID, $name, $desc, $days, $numberofpeople_maximum,
                $numberofpeople_based, $currency, $forAdult, $forChild,
                $forYoungAdult, $forSenior, $forPWD, $includeMeal,
                $mealFee, $transportFee, $discount, $db
            );

            if (!$tourpackage_ID) {
                throw new Exception('Failed to create tour package');
            }

            $sql = "INSERT INTO Tour_Package_Spots
                    (tourpackage_ID, spots_ID, packagespot_activityname,
                    packagespot_starttime, packagespot_endtime, packagespot_day)
                    VALUES
                    (:tourpackage_ID, :spots_ID, :activity, :start, :end, :day)";

            $stmt = $db->prepare($sql);

            $count = count($tour_spots);
            for ($i = 0; $i < $count; $i++) {
                $stmt->execute([
                    ':tourpackage_ID' => $tourpackage_ID,
                    ':spots_ID'       => $tour_spots[$i] ?? null,
                    ':activity'       => $packagespots_activityname[$i] ?? null,
                    ':start'          => $packagespots_starttime[$i] ?? null,
                    ':end'            => $packagespots_endtime[$i] ?? null,
                    ':day'            => $packagespot_day[$i] ?? null,
                ]);
            }

            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            error_log("addTourPackagesAndItsSpots error: " . $e->getMessage());
            return false;
        }
    }


    //updateTourPackage($tourpackage_ID, $guide_ID, $name, $desc, $schedule_ID, $days, $numberofpeople_ID, $numberofpeople_maximum, $numberofpeople_based, $pricing_ID, $currency, $forAdult, $forChild, $forYoungAdult, $forSenior, $forPWD, $includeMeal, $mealFee, $transportFee, $discount, $db)
    public function updateTourPackagesAndItsSpots($packagespot_ID, $tour_spots, $packagespots_activityname, $packagespots_starttime, $packagespots_endtime, $packagespot_day, $tourpackage_ID, $guide_ID, $name, $desc, $schedule_ID, $days, $numberofpeople_ID, $numberofpeople_maximum, $numberofpeople_based, $pricing_ID, $currency, $forAdult, $forChild, $forYoungAdult, $forSenior, $forPWD, $includeMeal, $mealFee, $transportFee, $discount) {
        try {
            $db = $this->connect();
            $db->beginTransaction();

            // ✅ STEP 1: Update main tour package
            $result = $this->updateTourPackages(
                $tourpackage_ID, $guide_ID, $name, $desc, $schedule_ID, $days,
                $numberofpeople_ID, $numberofpeople_maximum, $numberofpeople_based,
                $pricing_ID, $currency, $forAdult, $forChild, $forYoungAdult,
                $forSenior, $forPWD, $includeMeal, $mealFee, $transportFee, $discount, $db
            );

            if (!$result) {
                throw new Exception("Failed to update tour package (ID: $tourpackage_ID)");
            }

            // ✅ STEP 2: Delete old spots
            $sqlDelete = "DELETE FROM Tour_Package_Spots WHERE tourpackage_ID = :tourpackage_ID";
            $stmtDelete = $db->prepare($sqlDelete);
            if (!$stmtDelete->execute([':tourpackage_ID' => $tourpackage_ID])) {
                throw new Exception("Failed to delete old spots for package ID: $tourpackage_ID");
            }

            // ✅ STEP 3: Insert new spots (only if there are any)
            if (!empty($tour_spots) && is_array($tour_spots)) {
                $sqlInsert = "INSERT INTO Tour_Package_Spots 
                    (tourpackage_ID, spots_ID, packagespot_activityname, packagespot_starttime, 
                    packagespot_endtime, packagespot_day)
                    VALUES 
                    (:tourpackage_ID, :spots_ID, :activity, :start_time, :end_time, :packagespot_day)";
                $stmtInsert = $db->prepare($sqlInsert);

                foreach ($tour_spots as $i => $spotID) {
                    // Skip invalid/empty spots
                    if (empty($spotID)) continue;

                    $activity   = $packagespots_activityname[$i] ?? null;
                    $start_time = $packagespots_starttime[$i] ?? null;
                    $end_time   = $packagespots_endtime[$i] ?? null;
                    $day        = $packagespot_day[$i] ?? null;

                    // Optional: Validate day, times, or nulls
                    if ($start_time && $end_time && strtotime($end_time) < strtotime($start_time)) {
                        throw new Exception("Invalid time range on spot index $i");
                    }

                    $stmtInsert->execute([
                        ':tourpackage_ID'  => $tourpackage_ID,
                        ':spots_ID'        => $spotID,
                        ':activity'        => $activity,
                        ':start_time'      => $start_time,
                        ':end_time'        => $end_time,
                        ':packagespot_day' => $day
                    ]);
                }
            }

            $db->commit();
            return true;
        } catch (Exception $e) {
            if ($db && $db->inTransaction()) {
                $db->rollBack();
            }

            // 🔥 Improved error logging
            error_log("[updateTourPackagesAndItsSpots] Error: " . $e->getMessage());
            error_log("Trace: " . $e->getTraceAsString());

            return false;
        }
    }


    public function deleteTourPackage($spots, $tourpackage_ID, $schedule_ID, $numberofpeople_ID, $pricing_ID){
        $db = $this->connect();
        $db->beginTransaction();

        try {
            $pricingDelete = $this->deletePricingByID($pricing_ID,$db);
            $numberofpeopleDelete = $this->deletePeopleByID($numberofpeople_ID, $db);
            $scheduleDelete = $this->deleteScheduleByID($schedule_ID, $db);
            $count = count($spots);
            for ($i = 0; $i < $count; $i++){
                $tourpackage_spots = $this->deleteTourPackageSpotsByTourPackageID($tourpackage_ID, $db);
            }

            $sql = "DELETE FROM Tour_Package WHERE tourpackage_ID = :tourpackage_ID";
            $query = $db->prepare($sql);        
            $query->bindParam(":tourpackage_ID", $tourpackage_ID);
            $query->execute();

            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            error_log("delete Tour Package error: " . $e->getMessage());
            return false;
        }
    }
    // Additional methods specific to TourManager can be added here

    public function guideAccountInfo(){
        
        $sql = "SELECT a.account_profilepic, a.account_aboutme, a.account_bio, a.account_nickname, a.account_rating_score
            FROM Account_Info a
            INNER JOIN Guide g ON a.account_ID = g.account_ID
            WHERE g.guide_ID = :guide_ID";
        $db = $this->connect();
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':guide_ID', $guide_ID, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function upcomingToursCount($guide_ID){
        $sql = "SELECT COUNT(*) as upcoming_count
            FROM Bookings b
            INNER JOIN Tour_Package tp ON b.tourpackage_ID = tp.tourpackage_ID
            WHERE tp.guide_ID = :guide_ID AND b.booking_status = 'Confirmed' AND b.tour_date >= CURDATE()";
        $db = $this->connect();
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':guide_ID', $guide_ID, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? (int)$result['upcoming_count'] : 0;
    }

    public function upcomingToursCountForTourist($tourist_ID){
        $sql = "SELECT COUNT(*) as upcoming_count
            FROM Booking b
            INNER JOIN Tour_Package tp ON b.tourpackage_ID = tp.tourpackage_ID
            WHERE b.tourist_ID = :tourist_ID AND b.booking_status = 'Confirmed' AND b.booking_start_date >= CURDATE()";
        $db = $this->connect();
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':tourist_ID', $tourist_ID, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? (int)$result['upcoming_count'] : 0;
    }




}
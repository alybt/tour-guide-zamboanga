<?php

trait TourPackageSpot {

    /**
     * Link multiple spots or activities to a tour package
     * $tour_spots is expected to be an array of associative arrays:
     * [
     *   [ 'spots_ID' => 1, 'activity_name' => null, 'start_time' => '08:00', 'end_time' => '09:30' ],
     *   [ 'spots_ID' => null, 'activity_name' => 'Lunch Break', 'start_time' => '12:00', 'end_time' => '13:00' ]
     * ]
     */
    public function linkSpotToPackage($tourpackage_ID, $tour_spots) {
        if (empty($tour_spots)) {
            return true;               // nothing to link – still a success
        }

        $db = $this->db;
        $db->beginTransaction();

        try {
            $sql = "INSERT INTO Tour_Package_Spots 
                        (tourpackage_ID, spots_ID, packagespot_activityname,
                         packagespot_starttime, packagespot_endtime, packagespot_day)
                    VALUES 
                        (:tourpackage_ID, :spots_ID, :activity_name,
                         :start_time, :end_time, :day)";
            $q = $db->prepare($sql);

            foreach ($tour_spots as $spot) {
                $spots_ID      = !empty($spot['spots_ID']) ? (int)$spot['spots_ID'] : null;
                $activity_name = trim($spot['packagespot_activityname'] ?? '');
                $start_time    = $spot['packagespot_starttime'] ?? null;
                $end_time      = $spot['packagespot_endtime'] ?? null;
                $day           = (int)($spot['packagespot_day'] ?? 1);

                // ---- VALIDATION -------------------------------------------------
                if (is_null($spots_ID) && empty($activity_name)) {
                    throw new Exception("Custom activity name required when no spot selected.");
                }

                $q->bindValue(':tourpackage_ID', $tourpackage_ID, PDO::PARAM_INT);
                $q->bindValue(':spots_ID',       $spots_ID,       PDO::PARAM_INT);
                $q->bindValue(':activity_name',  $activity_name ?: null, PDO::PARAM_STR);
                $q->bindValue(':start_time',     $start_time,     PDO::PARAM_STR);
                $q->bindValue(':end_time',       $end_time,       PDO::PARAM_STR);
                $q->bindValue(':day',            $day,            PDO::PARAM_INT);
                $q->execute();
            }

            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            error_log("linkSpotToPackage error (pkg $tourpackage_ID): " . $e->getMessage());
            return false;
        }
    }

    public function getSpotsByPackageID($tourpackage_ID){
        $sql = "SELECT * FROM Tour_Package_Spots WHERE tourpackage_ID = :tourpackage_ID";
        $db = $this->connect();
        $query = $db->prepare($sql);
        $query->bindParam(':tourpackage_ID', $tourpackage_ID);
        $query->execute();

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSpotsByPackage($tourpackage_ID) {
        $sql = "SELECT tps.packagespot_ID, tps.packagespot_activityname, tps.packagespot_starttime, tps.packagespot_endtime, ts.spots_ID, ts.spots_name, ts.spots_description
                FROM Tour_Package_Spots tps LEFT JOIN Tour_Spots ts ON tps.spots_ID = ts.spots_ID
                WHERE tps.tourpackage_ID = :tourpackage_ID";
        $db = $this->connect();
        $query = $db->prepare($sql);
        $query->bindParam(':tourpackage_ID', $tourpackage_ID);
        $query->execute();

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPackagesBySpot($spotID) {
        $sql = "SELECT tp.* 
                FROM Tour_Package_Spots tps
                JOIN Tour_Package tp ON tps.tourpackage_ID = tp.tourpackage_ID
                WHERE tps.spots_ID = ?";
        $query = $this->conn->prepare($sql);
        $query->execute([$spotID]);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getViewAll() {
        $sql = "SELECT * FROM View_TourSpots_With_Packages";
        $query = $this->conn->prepare($sql);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteTourPackageSpotsByTourPackageID($tourpackage_ID, $db){
        $sql = "DELETE FROM Tour_Package_Spots WHERE tourpackage_ID = :tourpackage_ID";
        
        try {
            $query = $db->prepare($sql);
            $query->bindParam(":tourpackage_ID", $tourpackage_ID);
            
            if ($query->execute()) {
                return true;
            }
            error_log("TourPackageSpot Delete Error: " . print_r($query->errorInfo(), true));
            return false;
        } catch (PDOException $e) {
            error_log("TourPackageSpot Delete Exception: " . $e->getMessage());
            return false;
        }
    }

}


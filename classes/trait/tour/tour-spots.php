<?php

trait TourSpotsTrait {

    public function getAllSpots(){
        $sql = "SELECT * FROM tour_spots";
        $db = $this->connect();
        $query = $db->prepare($sql);
        $query->execute();

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOneImageFromTourPackages($spots_ID){
        $sql = "SELECT spotimage_PATH FROM Tour_Spots_Packages WHERE spots_ID = :spots_ID";
        $db = $this->connect();
        $query = $db->prepare($sql);
        $query->bindParam(':spots_ID', $spots_ID);
        $query->execute();

        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function getTourSpotById($spots_ID) {
        $sql = "SELECT * FROM Tour_Spots WHERE spots_ID = :spots_ID";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":spots_ID", $spots_ID);
        
        if ($query->execute()) {
            return $query->fetch();
        }
        return null;
    }


    public function addTourSpots($spots_name, $spots_category, $spots_description, $spots_address, $spots_googlelink){
        $db = $this->connect();
        $db->beginTransaction();
        try{
            $sql = "INSERT INTO tour_spots (spots_name, spots_category, spots_description, spots_address, spots_googlelink) 
                    VALUES (:spots_name, :spots_category, :spots_description, :spots_address, :spots_googlelink)";
            $query = $db->prepare($sql);
            $query->bindParam(':spots_name', $spots_name, PDO::PARAM_STR);
            $query->bindParam(':spots_category', $spots_category, PDO::PARAM_STR);
            $query->bindParam(':spots_description', $spots_description, PDO::PARAM_STR);
            $query->bindParam(':spots_address', $spots_address, PDO::PARAM_STR);
            $query->bindParam(':spots_googlelink', $spots_googlelink, PDO::PARAM_STR);
            if($query->execute()){
                $db->commit();
                return true;
            } else {
                $db->rollBack();
                return false;
            }
        } catch (PDOException $e) {
            $db->rollBack();
            error_log("Error adding tour spot: " . $e->getMessage());
            return false;
        }

    }

    public function editTourSpots($tourspot_ID, $spots_name, $spots_category, $spots_description, $spots_address, $spots_googlelink){
        $db = $this->connect();
        $db->beginTransaction();
        try{
            $sql = "UPDATE tour_spots 
                    SET spots_name = :spots_name, 
                        spots_category = :spots_category, 
                        spots_description = :spots_description, 
                        spots_address = :spots_address, 
                        spots_googlelink = :spots_googlelink
                    WHERE tour_spot_ID = :tour_spot_ID";
            $query = $db->prepare($sql);
            $query->bindParam(':spots_name', $spots_name, PDO::PARAM_STR);
            $query->bindParam(':spots_category', $spots_category, PDO::PARAM_STR);
            $query->bindParam(':spots_description', $spots_description, PDO::PARAM_STR);
            $query->bindParam(':spots_address', $spots_address, PDO::PARAM_STR);
            $query->bindParam(':spots_googlelink', $spots_googlelink, PDO::PARAM_STR);
            $query->bindParam(':tour_spot_ID', $tour_spot_ID, PDO::PARAM_INT);
            if($query->execute()){
                $db->commit();
                return true;
            } else {
                $db->rollBack();
                return false;
            }
        } catch (PDOException $e) {
            $db->rollBack();
            error_log("Error editing tour spot: " . $e->getMessage());
            return false;
        }
    }

    public function deleteTourSpot($spots_ID) {
        $sql = "DELETE FROM Tour_Spots WHERE spots_ID = :spots_ID";
        
        try {
            $query = $this->connect()->prepare($sql);
            $query->bindParam(":spots_ID", $spots_ID);
            
            if ($query->execute()) {
                return true;
            }
            error_log("TourSpot Delete Error: " . print_r($query->errorInfo(), true));
            return false;
        } catch (PDOException $e) {
            error_log("TourSpot Delete Exception: " . $e->getMessage());
            return false;
        }
    }

    public function getCategoryandImage(){
        $sql = "SELECT ts.spots_category, GROUP_CONCAT(tsi.spotsimage_PATH SEPARATOR ',') AS images FROM tour_spots ts JOIN tour_spots_images tsi ON ts.spots_ID = tsi.spots_ID GROUP BY ts.spots_category ORDER BY `images` ASC ";
        $db = $this->connect();
        $query = $db->prepare($sql);
        $query->execute();

        return $query->fetchAll(PDO::FETCH_ASSOC);

    }

    public function getTourSpotsCategory(){
        $sql = "SELECT spots_category FROM tour_spots GROUP BY spots_category";
        $db = $this->connect();
        $query = $db->prepare($sql);
        $query->execute();

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getImageByCategory($spots_category){
        $sql = "SELECT tsi.spotsimage_PATH FROM tour_spots ts JOIN tour_spots_images tsi ON ts.spots_ID = tsi.spots_ID GROUP BY ts.spots_category WHERE ts.spots_category = :spots_category LIMIT 1 ORDER BY tsi.spotsimage_PATH DESC";
        $db = $this->connect();
        $query = $db->prepare($sql);
        $query->bindParam(':spots_category', $spots_category);
        $query->execute();

        return $query->fetch(PDO::FETCH_ASSOC);

    }

    public function countSpots(){
        $sql = "SELECT COUNT(*) AS countspots FROM tour_spots ";
        $db = $this->connect();
        $query = $db->prepare($sql);
        $query->execute();

        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function getAllSpotCategories(){
        $sql = "SELECT DISTINCT spots_category FROM tour_spots";
        $db = $this->connect();
        $query = $db->prepare($sql);
        $query->execute();

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

}
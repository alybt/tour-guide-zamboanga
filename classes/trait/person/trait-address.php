<?php

trait AddressTrait {

    public function addgetAddress($houseno, $street, $barangay_ID, $db) {
        if (!($db instanceof PDO)) {
            $this->setLastError("addgetAddress: \$db not PDO");
            return false;
        }

        $sql = "SELECT address_ID FROM address_info
                WHERE address_houseno = :houseno
                AND address_street  = :street
                AND barangay_ID     = :barangay_ID";
        $q = $db->prepare($sql);
        $q->execute([
            ':houseno'      => $houseno,
            ':street'       => $street,
            ':barangay_ID'  => $barangay_ID,
        ]);

        if ($row = $q->fetch(PDO::FETCH_ASSOC)) {
            return $row['address_ID'];
        }

        $sql = "INSERT INTO address_info (address_houseno, address_street, barangay_ID)
                VALUES (:houseno, :street, :barangay_ID)";
        $q = $db->prepare($sql);
        $q->execute([
            ':houseno'      => $houseno,
            ':street'       => $street,
            ':barangay_ID'  => $barangay_ID,
        ]);

        return $q->rowCount() ? $db->lastInsertId() : false;
    }

    public function fetchCountry(){
        $sql = "SELECT * FROM Country";
        $query = $this->connect()->prepare($sql);
        if ($query->execute()) {
            return $query->fetchAll();
        } else {
            return null;
        }
    }

    public function fetchCountries(){
        $sql = "SELECT country_ID, country_name, country_codenumber FROM Country ORDER BY country_name";
        $q = $this->connect()->prepare($sql);
        $q->execute();
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    public function fetchRegion($country_ID = null){
        if ($country_ID === null) {
            $sql = "SELECT * FROM Region";
            $query = $this->connect()->prepare($sql);
        } else {
            $sql = "SELECT * FROM Region WHERE country_ID = :country_ID";
            $query = $this->connect()->prepare($sql);
            $query->bindParam(":country_ID", $country_ID);
        }
        if ($query->execute()) {
            return $query->fetchAll();
        } else {
            return null;
        }
    }

    public function fetchProvince($region_ID = null){
        if ($region_ID === null || $region_ID === "") {
            $sql = "SELECT * FROM Province";
            $query = $this->connect()->prepare($sql);
        } else {
            $sql = "SELECT * FROM Province WHERE region_ID = :region_ID";
            $query = $this->connect()->prepare($sql);
            $query->bindParam(":region_ID", $region_ID);
        }
        if ($query->execute()) {
            return $query->fetchAll();
        } else {
            return null;
        }
    }

    public function fetchCity($province_ID = null){
        if ($province_ID === null || $province_ID === "") {
            $sql = "SELECT * FROM City";
            $query = $this->connect()->prepare($sql);
        } else {
            $sql = "SELECT * FROM City WHERE province_ID = :province_ID";
            $query = $this->connect()->prepare($sql);
            $query->bindParam(":province_ID", $province_ID);
        }
        if ($query->execute()) {
            return $query->fetchAll();
        } else {
            return null;
        }
    }

    public function fetchBarangay($city_ID = null){
        if ($city_ID === null || $city_ID === "") {
            $sql = "SELECT * FROM Barangay";
            $query = $this->connect()->prepare($sql);
        } else {
            $sql = "SELECT * FROM Barangay WHERE city_ID = :city_ID";
            $query = $this->connect()->prepare($sql);
            $query->bindParam(":city_ID", $city_ID);
        }
        if ($query->execute()) {
            return $query->fetchAll();
        } else {
            return null;
        }
    }

    public function addgetRegion($region_name, $country_ID, $db){
        $sql_select = "SELECT region_ID 
                       FROM Region 
                       WHERE region_name = :region_name 
                       AND country_ID = :country_ID";
        $query_select = $db->prepare($sql_select);
        $query_select->bindParam(":region_name", $region_name);
        $query_select->bindParam(":country_ID", $country_ID);
        $query_select->execute();
        $result = $query_select->fetch();

        if($result){
            return $result["region_ID"];
        }

        $sql_insert = "INSERT INTO region (region_name, country_ID) 
                       VALUES (:region_name, :country_ID)";
        $query_insert = $db->prepare($sql_insert);
        $query_insert->bindParam(":region_name", $region_name);
        $query_insert->bindParam(":country_ID", $country_ID);
        if ($query_insert->execute()) {
            return $db->lastInsertId();
        } else {
            return false;
        }
    }

    public function addgetProvince($province_name, $region_ID, $db){
        $sql_select = "SELECT province_ID 
                       FROM Province 
                       WHERE province_name = :province_name 
                       AND region_ID = :region_ID";
        $query_select = $db->prepare($sql_select);
        $query_select->bindParam(":province_name", $province_name);
        $query_select->bindParam(":region_ID", $region_ID);
        $query_select->execute();
        $result = $query_select->fetch();

        if($result){
            return $result["province_ID"];
        }

        $sql_insert = "INSERT INTO province (province_name, region_ID) 
                       VALUES (:province_name, :region_ID)";
        $query_insert = $db->prepare($sql_insert);
        $query_insert->bindParam(":province_name", $province_name);
        $query_insert->bindParam(":region_ID", $region_ID);
        if ($query_insert->execute()) {
            return $db->lastInsertId();
        } else {
            return false;
        }
    }

    public function addgetCity($city_name,$province_ID, $db){
        $sql_select = "SELECT city_ID 
                       FROM City 
                       WHERE city_name = :city_name 
                       AND province_ID = :province_ID";
        $query_select = $db->prepare($sql_select);
        $query_select->bindParam(":city_name", $city_name);
        $query_select->bindParam(":province_ID", $province_ID);
        $query_select->execute();
        $result = $query_select->fetch();

        if($result){
            return $result["city_ID"];
        }

        $sql_insert = "INSERT INTO city_municipality (city_name, province_ID) 
                       VALUES (:city_name, :province_ID)";
        $query_insert = $db->prepare($sql_insert);
        $query_insert->bindParam(":city_name", $city_name);
        $query_insert->bindParam(":province_ID", $province_ID);
        if ($query_insert->execute()) {
            return $db->lastInsertId();
        } else {
            return false;
        }

    }

    public function addgetBarangay($barangay_name, $city_ID, $db){
        $sql_select = "SELECT barangay_ID 
                       FROM Barangay 
                       WHERE barangay_name = :barangay_name 
                       AND city_ID = :city_ID";
        $query_select = $db->prepare($sql_select);
        $query_select->bindParam(":barangay_name", $barangay_name);
        $query_select->bindParam(":city_ID", $city_ID);
        $query_select->execute();
        $result = $query_select->fetch();

        if($result){
            return $result["barangay_ID"];
        }

        $sql_insert = "INSERT INTO barangay (barangay_name, city_ID) 
                       VALUES (:barangay_name, :city_ID)";
        $query_insert = $db->prepare($sql_insert);
        $query_insert->bindParam(":barangay_name", $barangay_name);
        $query_insert->bindParam(":city_ID", $city_ID);
        if ($query_insert->execute()) {
            return $db->lastInsertId();
        } else {
            return false;
        }
    }

    public function deleteAddress($address_ID){
        $sql = "DELETE FROM address_info WHERE address_ID = :id";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":id", $address_ID);

        return $query->execute();
    }

    public function deleteAddressIfUnused($address_ID){
        $db = $this->connect();

        $sql_check = "SELECT COUNT(*) AS total FROM contact_info WHERE address_ID = :id";
        $query_check = $db->prepare($sql_check);
        $query_check->bindParam(":id", $address_ID);
        $query_check->execute();
        $count = $query_check->fetch(PDO::FETCH_ASSOC)['total'];

        if($count == 0){
            $sql_delete = "DELETE FROM address_info WHERE address_ID = :id";
            $query_delete = $db->prepare($sql_delete);
            $query_delete->bindParam(":id", $address_ID);
            $query_delete->execute();
        }
    }

    public function updateAddressInfo($address_ID, $houseno, $street, $barangay, $db){
        try{
            $sql_count = "SELECT COUNT(DISTINCT address_ID) AS address_count
                FROM Contact_Info WHERE address_ID = :address_ID ";

            $q_count = $db->prepare($sql_count);
            $q_count->execute([':address_ID' => $address_ID]);
            $address_count = (int) $q_count->fetchColumn();

            if ($address_count > 1) {
                echo "Address {$address_ID} is shared by {$address_count} people. Creating new for this person.\n";
                $address_ID = $this->addgetAddress($houseno, $street, $barangay_ID, $db);

            } else {
                echo "Reusing existing Address ID: {$address_ID} (Linked to {$contact_ID} person).\n";
                $sql_insert = "UPDATE address_info SET
                    address_houseno = :address_houseno,
                    address_street = :address_street,
                    barangay_ID = :barangay_ID
                    WHERE address_ID = :address_ID";
                $q_insert = $db->prepare($sql_insert);
                $q_insert->bindParam(":barangay_ID", $barangay_ID);
                $q_insert->bindParam(":address_street", $address_street);
                $q_insert->bindParam(":address_houseno", $address_houseno);

                if ($q_insert->execute()) {
                    return $db->lastInsertId();
                } else {
                    return false;
                }
                
            }
            
        } catch (PDOException $e) {
            if (method_exists($this, 'setLastError')) {
                $this->setLastError("Address error: " . $e->getMessage());
            }
            error_log("Address error: " . $e->getMessage());
            return false;
        }
    }

    
}

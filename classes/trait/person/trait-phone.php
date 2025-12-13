<?php

trait PhoneTrait {

    // Check if phone number already exists
    public function checkPhoneExists($country_ID, $phone_number) {
        $sql = "SELECT COUNT(*) AS total FROM Phone_Number 
                WHERE country_ID = :country_ID AND phone_number = :phone_number";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":country_ID", $country_ID);
        $query->bindParam(":phone_number", $phone_number);
        
        if ($query->execute()) {
            $record = $query->fetch();
            return $record["total"] > 0;
        }
        return false;
    }
    
    public function addgetPhoneNumber($country_ID, $phone_number, $db) {
        if (!($db instanceof PDO)) {
            $this->setLastError("addgetPhoneNumber: \$db missing");
            return false;
        }

        // Optional placeholder
        if (empty($phone_number)) {
            $phone_number = 'PLACEHOLDER-' . uniqid();
        }

        $sql = "SELECT phone_ID FROM phone_number
                WHERE country_ID = :country_ID AND phone_number = :phone_number";
        $q = $db->prepare($sql);
        $q->execute([':country_ID' => $country_ID, ':phone_number' => $phone_number]);

        if ($row = $q->fetch(PDO::FETCH_ASSOC)) {
            return $row['phone_ID'];
        }

        $sql = "INSERT INTO phone_number (country_ID, phone_number)
                VALUES (:country_ID, :phone_number)";
        $q = $db->prepare($sql);
        $q->execute([':country_ID' => $country_ID, ':phone_number' => $phone_number]);

        return $q->rowCount() ? $db->lastInsertId() : false;
    }

    public function fetchCountryCode(){
        $sql = "SELECT * FROM country";
        $query = $this->connect()->prepare($sql);
        if ($query->execute()) {
            return $query->fetchAll();
        } else {
            return null;
        }
    }

    public function deletePhoneNumber($pid){
        $sql = "DELETE FROM phone_number WHERE phone_ID = :id";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":id", $pid);
        return $query->execute();
    }

    public function deletePhoneIfUnused($phone_ID){
        $db = $this->connect();

        $sql_check = "SELECT COUNT(*) AS total FROM contact_info WHERE phone_ID = :id1
                    UNION ALL 
                    SELECT COUNT(*) AS total FROM Emergency_Info WHERE phone_ID = :id2";
        $query_check = $db->prepare($sql_check);
        $query_check->bindParam(":id1", $phone_ID);
        $query_check->bindParam(":id2", $phone_ID);
        $query_check->execute();
        $counts = $query_check->fetchAll(PDO::FETCH_COLUMN);

        // If total references == 0 in both tables
        if(array_sum($counts) == 0){
            $sql_delete = "DELETE FROM phone_number WHERE phone_ID = :id";
            $query_delete = $db->prepare($sql_delete);
            $query_delete->bindParam(":id", $phone_ID);
            $query_delete->execute();
        }
    }

    public function updatePhoneNumber($phone_ID, $country_ID, $phone_number, $db){
        if (empty($phone_number)) {
            $phone_number = "PLACEHOLDER-" . uniqid();
        }
        
        try {
            $sql_count = "SELECT COUNT(DISTINCT phone_ID) AS phone_count
                FROM Contact_Info WHERE phone_ID = :phone_ID ";

            $q_count = $db->prepare($sql_count);
            $q_count->execute([':phone_ID' => $phone_ID]);
            $phone_count = (int) $q_count->fetchColumn();

            if ($phone_count > 1) {
                echo "Phone {$phone_ID} is shared by {$phone_count} people. Creating new for this person.\n";
                $address_ID = $this->addgetPhoneNumber($country_ID, $phone_number, $db);

            } else {
                echo "Reusing existing Phone: {$phone_ID} (Linked to {$contact_ID} person).\n";
                $sql_insert = "UPDATE Phone_Number SET
                    country_ID = :country_ID,
                    phone_number = :phone_number
                    WHERE phone_ID = :phone_ID";
                $q_insert = $db->prepare($sql_insert);
                $q_insert->bindParam(":country_ID", $country_ID);
                $q_insert->bindParam(":phone_number", $phone_number);

                if ($q_insert->execute()) {
                    return $db->lastInsertId();
                } else {
                    return false;
                }
                
            }
        } catch (PDOException $e) {
            if (method_exists($this, 'setLastError')) {
                $this->setLastError("Update Phone number error: " . $e->getMessage());
            }
            error_log("Update Phone number error: " . $e->getMessage());
            return false;
        }
    }


}

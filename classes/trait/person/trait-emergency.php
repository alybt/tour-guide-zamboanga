<?php

trait EmergencyTrait {

    public function addgetEmergencyID($country_ID, $phone_number, $ename, $erelationship, $db) {
        if (!($db instanceof PDO)) {
            $this->setLastError("addgetEmergencyID: \$db missing");
            return false;
        }

        // Try to find existing
        $sql = "SELECT ei.emergency_ID
                FROM Emergency_Info ei
                JOIN Phone_Number pn ON ei.phone_ID = pn.phone_ID
                WHERE pn.country_ID = :country_ID
                AND pn.phone_number = :phone_number
                AND ei.emergency_Name = :ename
                AND ei.emergency_Relationship = :erelationship";
        $q = $db->prepare($sql);
        $q->execute([
            ':country_ID' => $country_ID,
            ':phone_number' => $phone_number,
            ':ename' => $ename,
            ':erelationship' => $erelationship,
        ]);

        if ($row = $q->fetch(PDO::FETCH_ASSOC)) {
            return $row['emergency_ID'];
        }

        // Create phone first
        $phone_ID = $this->addgetPhoneNumber($country_ID, $phone_number, $db);
        if (!$phone_ID) {
            $this->setLastError("Emergency phone creation failed");
            return false;
        }

        $sql = "INSERT INTO Emergency_Info (phone_ID, emergency_Name, emergency_Relationship)
                VALUES (:phone_ID, :ename, :erelationship)";
        $q = $db->prepare($sql);
        $q->execute([
            ':phone_ID' => $phone_ID,
            ':ename' => $ename,
            ':erelationship' => $erelationship,
        ]);

        return $q->rowCount() ? $db->lastInsertId() : false;
    }

    public function deleteEmergencyIfUnused($emergency_ID){
        $db = $this->connect();

        $sql_check = "SELECT COUNT(*) AS total FROM contact_info WHERE emergency_ID = :id";
        $query_check = $db->prepare($sql_check);
        $query_check->bindParam(":id", $emergency_ID);
        $query_check->execute();
        $count = $query_check->fetch(PDO::FETCH_ASSOC)['total'];

        if($count == 0){
            $sql_delete = "DELETE FROM Emergency_Info WHERE emergency_ID = :id";
            $query_delete = $db->prepare($sql_delete);
            $query_delete->bindParam(":id", $emergency_ID);
            $query_delete->execute();
        }
    }

    public function updateEmergencyID($emergency_ID, $country_ID, $phone_number, $ename, $erelationship, $db){
        try {
            $sql_count = "SELECT COUNT(DISTINCT emergency_ID) AS emergencyID_count
                FROM Contact_Info WHERE emergency_ID = :emergency_ID ";

            $q_count = $db->prepare($sql_count);
            $q_count->execute([':name_ID' => $name_ID]);
            $emergencyID_count = (int) $q_count->fetchColumn();

            if ($emergencyID_count > 1) {
                echo "Emergency ID {$emergency_ID} is shared by {$emergencyID_count} contact. Creating new name_ID for this person.\n";
                $emergency_ID = $this->addgetEmergencyID($country_ID, $phone_number, $ename, $erelationship, $db);

            } else {
                echo "Reusing existing Emergency ID: {$emergency_ID} (Linked to {$emergencyID_count} person).\n";
                
                $phone_ID = $this->updatePhoneNumber($phone_ID, $country_ID, $phone_number, $db);

                $sql_insert = "UPDATE emergency_info SET
                    emergency_Name = :emergency_Name,
                    emergency_Relationship = :emergency_Relationship,
                    phone_ID = :phone_ID,
                    WHERE emergency_ID = :emergency_ID";
                $q_insert = $db->prepare($sql_insert);
                $q_insert->bindParam(":firstname", $name_first);
                $q_insert->bindParam(":secondname", $name_second);
                $q_insert->bindParam(":middlename", $name_middle);
                $q_insert->bindParam(":lastname", $name_last);
                $q_insert->bindParam(":suffix", $name_suffix);
                $existing = $q_check->fetch(PDO::FETCH_ASSOC);
                if ($q_insert->execute()) {
                    return $db->lastInsertId();
                } else {
                    return false;
                }
                
            }


        } catch (PDOException $e) {
            return false;
        }
    }

}

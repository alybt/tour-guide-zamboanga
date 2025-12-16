<?php

trait ContactInfoTrait {

    // Check Email If Exists
    public function checkEmailExists($email) {
        $sql = "SELECT COUNT(*) AS total FROM Contact_Info WHERE contactinfo_email = :email";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":email", $email);
        
        if ($query->execute()) {
            $record = $query->fetch();
            return $record["total"] > 0;
        }
        return false;
    }

    public function addgetContact_Info(
    $houseno, $street, $barangay,
    $country_ID, $phone_number,
    $emergency_name, $emergency_country_ID, $emergency_phonenumber, $emergency_relationship,
    $contactinfo_email,
    $db ) {
        
        if (!($db instanceof PDO)) {
            $this->setLastError("addgetContact_Info: \$db missing");
            return false;
        }

        $barangay_ID = $barangay;
        $address_ID = $this->addgetAddress($houseno, $street, $barangay_ID, $db);
        $phone_ID = $this->addgetPhoneNumber($country_ID, $phone_number, $db);
        $emergency_ID = $this->addgetEmergencyID($emergency_country_ID, $emergency_phonenumber, $emergency_name, $emergency_relationship, $db);

        if (!$address_ID || !$phone_ID || !$emergency_ID) {
            return false;
        }

        $sql = "INSERT INTO Contact_Info (
                    address_ID, 
                    phone_ID, 
                    contactinfo_email, 
                    emergency_ID
                ) VALUES (
                    :address_ID, 
                    :phone_ID, 
                    :contactinfo_email, 
                    :emergency_ID
                )";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':address_ID'        => $address_ID,
            ':phone_ID'          => $phone_ID,
            ':contactinfo_email' => $contactinfo_email,
            ':emergency_ID'      => $emergency_ID,
        ]);

        return $stmt->rowCount() ? $db->lastInsertId() : false;
    }

    public function deleteContactInfo($contact_ID){
        $sql = "DELETE FROM contact_info WHERE contact_ID = :id";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":id", $contact_ID);

        return $query->execute();
    }

    public function deleteContactInfoSafe($contactinfo_ID){
        $db = $this->connect();

        $sql = "SELECT phone_ID, emergency_ID FROM contact_info WHERE contactinfo_ID = :id";
        $query = $db->prepare($sql);
        $query->bindParam(":id", $contactinfo_ID);
        $query->execute();
        $data = $query->fetch(PDO::FETCH_ASSOC);

        if(!$data){ return false; }

        $phone_ID = $data['phone_ID'];
        $emergency_ID = $data['emergency_ID'];
        $emergency_phone_ID = null;
        if ($emergency_ID) {
            $qE = $db->prepare("SELECT phone_ID FROM Emergency_Info WHERE emergency_ID = :eid");
            $qE->execute([':eid' => $emergency_ID]);
            $emergency_phone_ID = $qE->fetchColumn();
        }

        $sql_delete = "DELETE FROM contact_info WHERE contactinfo_ID = :id";
        $query_delete = $db->prepare($sql_delete);
        $query_delete->bindParam(":id", $contactinfo_ID);
        $query_delete->execute();

        if ($phone_ID) $this->deletePhoneIfUnused($phone_ID);
        if ($emergency_phone_ID) $this->deletePhoneIfUnused($emergency_phone_ID);
        if ($emergency_ID) $this->deleteEmergencyIfUnused($emergency_ID);

        return true;
    }

    public function updateContact_Info($contactinfo_ID, $houseno, $street, $barangay,  
        $country_ID, $phone_number, $emergency_name, $emergency_country_ID, $emergency_phonenumber, $emergency_relationship, $contactinfo_email, $db) {

        try{
            $sql_count = "SELECT COUNT(*) AS person_count
                FROM Person WHERE contactinfo_ID = :contactinfo_ID ";

            $q_count = $db->prepare($sql_count);
            $q_count->execute([':contactinfo_ID' => $contactinfo_ID]);
            $contactinfo_count = (int) $q_count->fetchColumn();

            if ($contactinfo_count > 1) {
                return $this->addgetContact_Info(
                    $houseno, $street, $barangay,
                    $country_ID, $phone_number,
                    $emergency_name, $emergency_country_ID, $emergency_phonenumber, $emergency_relationship,
                    $contactinfo_email,
                    $db
                );

            } else {
                $barangay_ID = $barangay;
                $address_ID = $this->addgetAddress($houseno, $street, $barangay_ID, $db);
                $phone_ID = $this->addgetPhoneNumber($country_ID, $phone_number, $db);
                $emergency_ID = $this->addgetEmergencyID($emergency_country_ID, $emergency_phonenumber, $emergency_name, $emergency_relationship, $db);

                $sql = "UPDATE Contact_Info SET 
                    address_ID = :address_ID, 
                    phone_ID = :phone_ID, 
                    emergency_ID = :emergency_ID,
                    contactinfo_email = :contactinfo_email
                    WHERE contactinfo_ID = :contactinfo_ID";
                
                $query = $db->prepare($sql);
                $query->execute([
                    ':address_ID'           => $address_ID,
                    ':phone_ID'             => $phone_ID,
                    ':emergency_ID'         => $emergency_ID,
                    ':contactinfo_email'    => $contactinfo_email,
                    ':contactinfo_ID'       => $contactinfo_ID
                ]);

                return $contactinfo_ID;
            }
            
        } catch (PDOException $e) {
            if (method_exists($this, 'setLastError')) {
                $this->setLastError("ContactUpdate info error: " . $e->getMessage());
            }
            error_log("ContactUpdate info error: " . $e->getMessage());
            return false;
        }
    }

    public function addContact_InfoByID( $houseno, $street, $barangay_ID, $phone_ID, $contactinfo_email, $emergency_name, $emergency_relationship, $emergency_phone_ID, $db ){
        $address_ID = $this->addgetAddress($houseno, $street, $barangay_ID, $db);
        $emergency_ID = $this->addgetEmergencyID(null, $emergency_phone_ID, $emergency_name, $emergency_relationship, $db);
        if (!$address_ID || !$phone_ID || !$emergency_ID) {
            return false;
        }
        $sql = "INSERT INTO Contact_Info(
                    address_ID, 
                    phone_ID, 
                    contactinfo_email, 
                    emergency_ID
                ) VALUES (
                    :address_ID, 
                    :phone_ID, 
                    :contactinfo_email, 
                    :emergency_ID
                )";
        $query = $db->prepare($sql);
        $query->execute([
            ':address_ID'        => $address_ID,
            ':phone_ID'          => $phone_ID,
            ':contactinfo_email' => $contactinfo_email,
            ':emergency_ID'      => $emergency_ID,
        ]);
        return $query->rowCount() ? $db->lastInsertId() : false;
    }

    
}

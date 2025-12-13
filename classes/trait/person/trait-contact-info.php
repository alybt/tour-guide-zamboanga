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

        $address_ID   = $this->addgetAddress($houseno, $street, $barangay, $db);
        $phone_ID     = $this->addgetPhoneNumber($country_ID, $phone_number, $db);
        $emergency_ID = $this->addgetEmergencyID(
            $emergency_country_ID, $emergency_phonenumber, $emergency_name, $emergency_relationship, $db
        );

        if (!$address_ID || !$phone_ID || !$emergency_ID) {
            $this->setLastError("Contact sub-record missing");
            return false;
        }

        $sql = "INSERT INTO Contact_Info (address_ID, phone_ID, emergency_ID, contactinfo_email)
                VALUES (:address_ID, :phone_ID, :emergency_ID, :contactinfo_email)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':address_ID'       => $address_ID,
            ':phone_ID'         => $phone_ID,
            ':emergency_ID'     => $emergency_ID,
            ':contactinfo_email'=> $contactinfo_email,
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

        // Step 1: Get linked phone, address, emergency IDs
        $sql = "SELECT address_ID, phone_ID, emergency_ID FROM contact_info WHERE contactinfo_ID = :id";
        $query = $db->prepare($sql);
        $query->bindParam(":id", $contactinfo_ID);
        $query->execute();
        $data = $query->fetch(PDO::FETCH_ASSOC);

        if(!$data){ return false; }

        $address_ID = $data['address_ID'];
        $phone_ID = $data['phone_ID'];
        $emergency_ID = $data['emergency_ID'];

        // Step 2: Delete Contact_Info Row
        $sql_delete = "DELETE FROM contact_info WHERE contactinfo_ID = :id";
        $query_delete = $db->prepare($sql_delete);
        $query_delete->bindParam(":id", $contactinfo_ID);
        $query_delete->execute();

        // Step 3: Individually remove unused pieces
        $this->deleteAddressIfUnused($address_ID);
        $this->deletePhoneIfUnused($phone_ID);
        $this->deleteEmergencyIfUnused($emergency_ID);

        return true;
    }

    public function updateContact_Info($contactinfo_ID, $houseno, $street, $barangay,  
        $country_ID, $phone_number, $emergency_name, $emergency_country_ID, $emergency_phonenumber, $emergency_relationship, $contactinfo_email, $db) {

        try{
            $sql_count = "SELECT COUNT(DISTINCT contactinfo_ID) AS contactinfo_count
                FROM person WHERE contactinfo_ID = :contactinfo_ID ";

            $q_count = $db->prepare($sql_count);
            $q_count->execute([':contactinfo_ID' => $contactinfo_ID]);
            $contactinfo_count = (int) $q_count->fetchColumn();

            if ($contactinfo_count > 1) {
                echo "ContactInfo {$contactinfo_ID} is shared by {$contactinfo_count} people. Creating new   for this person.\n";
                $address_ID = $this->updateAddressInfo($address_ID, $houseno, $street, $barangay, $db);
                $phone_ID = $this->updatePhoneNumber($phone_ID, $country_ID, $phone_number, $db);
                $emergency_ID = $this->updateEmergencyID($emergency_ID, $emergency_country_ID, $emergency_phonenumber, $emergency_name,
                $emergency_name, $emergency_relationship, $db);
                $contact_ID = $this->addContact_InfoByID( $address_ID, $phone_ID, $contactinfo_email, $emergency_ID, $db );
                return $contact_ID;

            } else {
                echo "Reusing existing name ID: {$contactinfo_ID} (Linked to {$contactinfo_count} person).\n";
                $address_ID = $this->updateAddressInfo($address_ID, $houseno, $street, $barangay, $db);
                $phone_ID = $this->updatePhoneNumber($phone_ID, $country_ID, $phone_number, $db);
                $emergency_ID = $this->updateEmergencyID($emergency_ID, $country_ID, $phone_number, $ename, $erelationship, $db);

                $sql = "UPDATE Contact_Info SET 
                    address_ID = :address_ID, 
                    phone_ID = :phone_ID, 
                    emergency_ID = :emergency_ID,    
                    contactinfo_email = :contactinfo_email";
                $query = $db->prepare($sql);
                $query->bindParam(":address_ID", $address_ID);
                $query->bindParam(":phone_ID", $phone_ID);
                $query->bindParam(":emergency_ID", $emergency_ID);
                $query->bindParam(":contactinfo_email", $contactinfo_email);
                if ($query->execute()) {
                    return $db->lastInsertId();
                } else {
                    return false;
                }
                
            }
            
        } catch (PDOException $e) {
            if (method_exists($this, 'setLastError')) {
                $this->setLastError("ContactUpdate info error: " . $e->getMessage());
            }
            error_log("ContactUpdate info error: " . $e->getMessage());
            return false;
        }
    }

    public function addContact_InfoByID( $address_ID, $phone_ID, $contactinfo_email, $emergency_ID, $db ){
        $sql = "INSERT INTO Conctact_Info(address_ID, phone_ID, contactinfo_email, emergency_ID) 
            VALUES (:address_ID, :phone_ID, :contactinfo_email, :emergency_ID)";
        $query = $db->prepare($sql);
        $query->bindParam(":address_ID", $address_ID);
        $query->bindParam(":phone_ID", $phone_ID);
        $query->bindParam(":emergency_ID", $emergency_ID);
        $query->bindParam(":contactinfo_email", $contactinfo_email);        

        
            if ($query->execute()){
                return $db->lastInsertId();
            } else {
                
                return false;
            }
    }

    
}

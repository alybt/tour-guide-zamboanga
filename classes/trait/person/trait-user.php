<?php

trait UserTrait {
    
    public function checkUsernameExists($user_username, $db) {
        $sql = "SELECT COUNT(*) AS total FROM User_Login WHERE user_username = :user_username";
        $query = $db->prepare($sql);
        $query->bindParam(":user_username", $user_username);
        
        if ($query->execute()) {
            $record = $query->fetch();
            return $record["total"] > 0;
        }
        return false;
    }
    
    public function addUser( $name_first,  $name_second, $name_middle, $name_last, $name_suffix, 
        $houseno, $street, $barangay,
        $country_ID, $phone_number,
        $emergency_name, $emergency_country_ID, $emergency_phonenumber, $emergency_relationship,
        $contactinfo_email, $person_nationality, $person_gender, $person_dateofbirth, 
        $username, $password, $db) {

        if (!($db instanceof PDO)) {
            $this->setLastError("addUser: \$db is not a PDO instance");
            return false;
        }

        // First, create Contact_Info with address and emergency data
        $contactinfo_ID = $this->addContactInfo(
            $houseno, $street, $barangay,
            $country_ID, $phone_number,
            $emergency_name, $emergency_country_ID, $emergency_phonenumber, $emergency_relationship,
            $contactinfo_email,
            $db
        );

        if (!$contactinfo_ID) {
            $this->setLastError($this->getLastError() ?: "addContactInfo failed");
            return false;
        }

        // Now insert into User_Login with all denormalized data
        $sql = "INSERT INTO User_Login (
                    name_first, name_second, name_middle, name_last, name_suffix,
                    contactinfo_ID,
                    person_isPWD, person_Nationality, person_Gender, person_DateOfBirth,
                    user_username, user_password
                ) VALUES (
                    :name_first, :name_second, :name_middle, :name_last, :name_suffix,
                    :contactinfo_ID,
                    0, :person_nationality, :person_gender, :person_dateofbirth,
                    :user_username, :user_password
                )";
        
        $stmt = $db->prepare($sql);
        $stmt->bindParam(":name_first", $name_first);
        $stmt->bindParam(":name_second", $name_second);
        $stmt->bindParam(":name_middle", $name_middle);
        $stmt->bindParam(":name_last", $name_last);
        $stmt->bindParam(":name_suffix", $name_suffix);
        $stmt->bindParam(":contactinfo_ID", $contactinfo_ID, PDO::PARAM_INT);
        $stmt->bindParam(":person_nationality", $person_nationality);
        $stmt->bindParam(":person_gender", $person_gender);
        $stmt->bindParam(":person_dateofbirth", $person_dateofbirth);
        $stmt->bindParam(":user_username", $username);
        $stmt->bindParam(":user_password", $password);

        if ($stmt->execute()) {
            return $db->lastInsertId();
        }

        $this->setLastError("User_Login insert failed: " . implode(" ", $stmt->errorInfo()));
        return false;
    }

    private function addContactInfo($houseno, $street, $barangay, $country_ID, $phone_number,
        $emergency_name, $emergency_country_ID, $emergency_phonenumber, $emergency_relationship,
        $contactinfo_email, $db) {
        
        // Insert phone number
        $phone_ID = $this->addPhoneNumber($phone_number, $country_ID, $db);
        if (!$phone_ID) {
            $this->setLastError("Failed to add phone number");
            return false;
        }

        // Insert emergency phone number
        $emergency_phone_ID = $this->addPhoneNumber($emergency_phonenumber, $emergency_country_ID, $db);
        if (!$emergency_phone_ID) {
            $this->setLastError("Failed to add emergency phone number");
            return false;
        }

        // Insert Contact_Info with all denormalized data
        $sql = "INSERT INTO Contact_Info (
                    contactinfo_email, address_houseno, address_street, barangay_ID,
                    phone_ID, emergency_name, emergency_relationship, emergency_phone_ID
                ) VALUES (
                    :contactinfo_email, :address_houseno, :address_street, :barangay_ID,
                    :phone_ID, :emergency_name, :emergency_relationship, :emergency_phone_ID
                )";
        
        $stmt = $db->prepare($sql);
        $stmt->bindParam(":contactinfo_email", $contactinfo_email);
        $stmt->bindParam(":address_houseno", $houseno);
        $stmt->bindParam(":address_street", $street);
        $stmt->bindParam(":barangay_ID", $barangay, PDO::PARAM_INT);
        $stmt->bindParam(":phone_ID", $phone_ID, PDO::PARAM_INT);
        $stmt->bindParam(":emergency_name", $emergency_name);
        $stmt->bindParam(":emergency_relationship", $emergency_relationship);
        $stmt->bindParam(":emergency_phone_ID", $emergency_phone_ID, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return $db->lastInsertId();
        }

        $this->setLastError("Contact_Info insert failed: " . implode(" ", $stmt->errorInfo()));
        return false;
    }

    private function addPhoneNumber($phone_number, $country_ID, $db) {
        $sql = "INSERT INTO Phone_Number (phone_number, country_ID) VALUES (:phone_number, :country_ID)";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(":phone_number", $phone_number);
        $stmt->bindParam(":country_ID", $country_ID, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return $db->lastInsertId();
        }

        $this->setLastError("Phone_Number insert failed: " . implode(" ", $stmt->errorInfo()));
        return false;
    }

}
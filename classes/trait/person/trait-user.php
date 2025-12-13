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

        $person_ID = $this->addgetPerson(
            $name_first, $name_second, $name_middle, $name_last, $name_suffix,
            $houseno, $street, $barangay,
            $country_ID, $phone_number,
            $emergency_name, $emergency_country_ID, $emergency_phonenumber, $emergency_relationship,
            $contactinfo_email,
            $person_nationality, $person_gender, $person_dateofbirth,
            $db
        );

        if (!$person_ID) {
            $this->setLastError($this->getLastError() ?: "addgetPerson failed");
            return false;
        }


        $sql = "INSERT INTO User_Login (person_ID, user_username, user_password)
                VALUES (:person_ID, :user_username, :user_password)";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(":person_ID", $person_ID, PDO::PARAM_INT);
        $stmt->bindParam(":user_username", $username);
        $stmt->bindParam(":user_password", $password);

        if ($stmt->execute()) {
            return $db->lastInsertId();
        }

        $this->setLastError("User_Login insert failed: " . implode(" ", $stmt->errorInfo()));
        return false;
    }

}
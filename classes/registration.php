<?php

require_once __DIR__ . "/../config/database.php";
require_once "trait/person/trait-user.php";
require_once "trait/person/trait-account.php";
require_once "trait/account/account-logs.php";
require_once "trait/person/trait-phone.php";
require_once "trait/person/trait-address.php";
require_once "trait/person/trait-person.php";

class Registration extends Database {

    use UserTrait, Account_InfoTrait, AccountLogs, PhoneTrait, AddressTrait, PersonTrait;
    
    public function addTourist($name_first, $name_second, $name_middle, $name_last, $name_suffix,
        $houseno, $street, $barangay,
        $country_ID, $phone_number,
        $emergency_name, $emergency_country_ID, $emergency_phonenumber, $emergency_relationship,
        $contactinfo_email,
        $person_nationality, $person_gender, $person_dateofbirth, 
        $username, $password) {
    
        $db = $this->connect();
        if (!$db) {
            $this->setLastError("Database connection failed");
            error_log("Database connection failed in addTourist");
            return false;
        }

        $db->beginTransaction();

        try {
            error_log("Calling addUser from addTourist");
            $user_ID = $this->addUser(
                $name_first, 
                $name_second, 
                $name_middle, 
                $name_last, 
                $name_suffix,
                $houseno, 
                $street, 
                $barangay,
                $country_ID, 
                $phone_number,
                $emergency_name, 
                $emergency_country_ID, 
                $emergency_phonenumber, 
                $emergency_relationship,
                $contactinfo_email,
                $person_nationality, 
                $person_gender, 
                $person_dateofbirth, $username, $password, $db);

            error_log("addUser returned user_ID: " . ($user_ID ?: 'false'));

            if (!$user_ID) {
                $error = $this->getLastError() ?: "Failed to create user account";
                error_log("addUser failed: " . $error);
                $db->rollBack();
                $this->setLastError($error);
                return false;
            }


            $sql = "INSERT INTO Account_Info (user_ID, role_ID, account_status) VALUES (:user_ID, 3, 'Active')";
            $query = $db->prepare($sql);
            $query->bindParam(":user_ID", $user_ID, PDO::PARAM_INT);

            $result = $query->execute();
            
            if ($result) {
                
                $db->commit();
                error_log("Tourist registration successful for user: " . $username);
                return $db->lastInsertId();
            } else {
                $errorInfo = $query->errorInfo();
                $error = "Database error: " . ($errorInfo[2] ?? 'Unknown error');
                error_log("Failed to add role: " . $error);
                $db->rollBack();
                $this->setLastError($error);
                return false;
            }
        } catch (PDOException $e) {
            $db->rollBack();
            $this->setLastError($e->getMessage());
            error_log("Tourist Registration Error: " . $e->getMessage()); 
            return false;
        }
    }

    public function addgetGuide($languages, $name_first, $name_second, $name_middle, $name_last, $name_suffix, $houseno, $street, $barangay, $country_ID, $phone_number, $emergency_name, $emergency_country_ID, $emergency_phonenumber, $emergency_relationship, $contactinfo_email, $person_nationality, $person_gender, $person_dateofbirth, $user_username, $user_password){
        
            $db = $this->connect();
            if (!$db) {
                $this->setLastError("Database connection failed");
                error_log("Database connection failed in addgetGuide");
                return false;
            }

            $db->beginTransaction();

            try{
                $user_ID = $this->addUser($name_first, $name_second, $name_middle, $name_last, $name_suffix,
                $houseno, $street, $barangay, 
                $country_ID, $phone_number, 
                $emergency_name, $emergency_country_ID, $emergency_phonenumber, $emergency_relationship, 
                $contactinfo_email, $person_nationality, $person_gender, $person_dateofbirth, 
                $user_username, $user_password, $db);


                if (!$user_ID) {
                    $db->rollBack();
                    return false;
                }

                $account_ID = $this->addAccountGuide($user_ID, $db);

                if (!$account_ID) {
                    $db->rollBack();
                    return false;
                }

                $guide_ID = $this->addGuide_ID($account_ID, $db);

                $sql = "INSERT INTO Guide_Languages(guide_ID, languages_ID) 
                        VALUES (:guide_ID, :languages)";
                $stmt = $db->prepare($sql);

                foreach ($languages as $l) {
                    $stmt->bindParam(':guide_ID', $guide_ID, PDO::PARAM_INT);
                    $stmt->bindParam(':languages', $l, PDO::PARAM_INT);
                    $stmt->execute();
                }

                if (!$guide_ID) {
                    $db->rollBack();
                    return false;
                } else {
                    $db->commit();
                    return true;
                }

            } catch (PDOException $e) {
            $db->rollBack();
            $this->setLastError($e->getMessage());
            error_log("Guide Registration Error: " . $e->getMessage()); 
            return false;
        }


    }

    public function addGuide_ID($account_ID, $db){

        $license_number = $this->generateLicenseNumber($db);

        if (!$license_number) {
            return false;
        }

        $license_ID = $this->addLicense($license_number, $db);

        if (!$license_ID) {
            $db->rollBack();
            return false;
        }

        $sql = "INSERT INTO Guide(account_ID, license_ID)
                VALUES (:account_ID, :license_ID)";
        $query = $db->prepare($sql);
        $query->bindParam(":license_ID", $license_ID);
        $query->bindParam(":account_ID", $account_ID);

        if ($query->execute()) {
            return $db->lastInsertId();
        } else {
            return false;
        }

    }

    public function generateLicenseNumber($db){
        $year = date('Y');
        $stmt = $db->prepare(" SELECT license_number FROM Guide_License
            WHERE license_number LIKE ? ORDER BY license_ID DESC
            LIMIT 1 ");
        $stmt->execute(["LIS-$year-%"]);
        $last = $stmt->fetchColumn();

        if ($last) {
            $lastSeq = intval(substr($last, -4));
            $nextSeq = $lastSeq + 1;
        } else {
            $nextSeq = 1;
        }

        return sprintf("LIS-%s-%04d", $year, $nextSeq);

    }

    public function addLicense($license_number, $db){
        $sql = "INSERT INTO Guide_License(license_number) VALUES (:license_number)";
        $query = $db->prepare($sql);
        $query->bindParam(":license_number", $license_number);
        if ($query->execute()) {
            return $db->lastInsertId();
        } else {
            return false;
        }

    }



}


?>
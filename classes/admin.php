<?php

require_once __DIR__ . "/../config/database.php";
require_once "trait/account/account-login.php";
require_once "trait/booking/booking_bundle.php";
require_once "trait/booking/companion.php";
require_once "trait/tour/tour-packages.php";
require_once "trait/tour/tour-spots.php";
require_once "trait/tour/tour-packagespots.php";
require_once "trait/tour/schedule.php";
require_once "trait/tour/pricing.php";
require_once "trait/tour/people.php";
require_once "trait/payment-info/method.php";
require_once "trait/payment-info/transaction-reference.php";
require_once "trait/payment-info/payment-info.php";
require_once "trait/payment-info/payment-transaction.php";
require_once "trait/payment-info/refund.php";
require_once "trait/person/trait-phone.php";
require_once "trait/person/trait-name-info.php";
require_once "trait/person/trait-address.php";
require_once "trait/person/trait-emergency.php";
require_once "trait/person/trait-contact-info.php";
require_once "trait/person/trait-person.php";
require_once "trait/person/trait-account.php";

class Admin extends Database {
    use AccountLoginTrait; 
    use BookingBundleTrait, CompanionTrait;
    use AccountLoginTrait;
    use TourPackagesTrait, PeopleTrait, PricingTrait, ScheduleTrait;
    use TourSpotsTrait;
    use TourPackageSpot;
    use MethodTrait, TransactionReferenceTrait, PaymentInfo, PaymentTransaction, PhoneTrait, Refund;
    use PersonTrait, NameInfoTrait, AddressTrait, EmergencyTrait, ContactInfoTrait, Account_InfoTrait;

    public function getAllUsersDetails(){
        $sql = "SELECT u.user_ID, u.user_username AS username, '***' AS password,
        a.account_status AS status, p.person_ID AS person_ID,
            GROUP_CONCAT(DISTINCT r.role_name 
                        ORDER BY r.role_name SEPARATOR ', ') AS role_name,
            GROUP_CONCAT(DISTINCT a.role_ID ORDER BY a.role_ID) AS role_ID,
            GROUP_CONCAT(DISTINCT a.account_ID ORDER BY a.account_ID) AS account_ID,
            CONCAT_WS(' ', ni.name_first, ni.name_last) AS full_name
            FROM User_Login      AS u
            LEFT JOIN Account_Info AS a ON a.user_ID = u.user_ID
            LEFT JOIN Role         AS r ON a.role_ID = r.role_ID
			JOIN person p ON u.person_ID = p.person_ID
			JOIN name_info ni ON p.name_ID = ni.name_ID
            WHERE a.role_ID != 1
            GROUP BY u.user_ID, u.user_username";
        $db = $this->connect();
        $query = $db->prepare($sql); 
        
        if($query->execute()){
            return $query->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    public function getAllRoles(){
        $sql = "SELECT * FROM role";
        $db = $this->connect();
        $query = $db->prepare($sql); 
        
        if($query->execute()){
            return $query->fetchAll();
        }

    }

    public function getUsersDetailsByID($user_ID){
        $sql = "SELECT u.user_ID, u.user_username, u.user_password,
        a.account_status, p.person_ID AS person_ID,
            GROUP_CONCAT(DISTINCT r.role_name 
                        ORDER BY r.role_name SEPARATOR ', ') AS role_name,
            GROUP_CONCAT(DISTINCT a.role_ID ORDER BY a.role_ID) AS role_ID,
            GROUP_CONCAT(DISTINCT a.account_ID ORDER BY a.account_ID) AS account_ID,
            ni.name_first, ni.name_last
            FROM User_Login      AS u
            LEFT JOIN Account_Info AS a ON a.user_ID = u.user_ID
            LEFT JOIN Role         AS r ON a.role_ID = r.role_ID
			JOIN person p ON u.person_ID = p.person_ID
			JOIN name_info ni ON p.name_ID = ni.name_ID
            WHERE u.user_ID = :user_ID
            GROUP BY u.user_ID, u.user_username";
        $db = $this->connect();
        $query = $db->prepare($sql); 
        $query->bindParam(':user_ID', $user_ID);
        
        if($query->execute()){
            return $query->fetch(PDO::FETCH_ASSOC);
        }
    }

    public function updateUser(){
       $db = $this->connect();
            if (!$db) {
                $this->setLastError("Database connection failed");
                error_log("Database connection failed in addTourist");
                return false;
            }

            $db->beginTransaction();

            try{
               


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
                    $stmt->execute([$guide_ID, $l]);
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
            error_log("Guide Registration Error: " . $e->getMessage()); 
            return false;
        }


    }



}
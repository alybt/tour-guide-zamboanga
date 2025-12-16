<?php

require_once __DIR__ . "/../config/database.php";
require_once "trait/account/account-login.php";
require_once "trait/booking/booking-bundle.php";
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
    private $pdo;
    public function __construct() {
        $db = $this->connect();
    }

    public function getAllUsersDetails(){
        $sql = "SELECT u.user_ID, u.user_username AS username, '***' AS password,
        a.account_status AS status, 
            GROUP_CONCAT(DISTINCT r.role_name 
                        ORDER BY r.role_name SEPARATOR ', ') AS role_name,
            GROUP_CONCAT(DISTINCT a.role_ID ORDER BY a.role_ID) AS role_ID,
            GROUP_CONCAT(DISTINCT a.account_ID ORDER BY a.account_ID) AS account_ID,
            CONCAT_WS(' ', u.name_first, u.name_last) AS full_name
            FROM User_Login      AS u
            LEFT JOIN Account_Info AS a ON a.user_ID = u.user_ID
            LEFT JOIN Role         AS r ON a.role_ID = r.role_ID  
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
        a.account_status, 
            GROUP_CONCAT(DISTINCT r.role_name 
                        ORDER BY r.role_name SEPARATOR ', ') AS role_name,
            GROUP_CONCAT(DISTINCT a.role_ID ORDER BY a.role_ID) AS role_ID,
            GROUP_CONCAT(DISTINCT a.account_ID ORDER BY a.account_ID) AS account_ID,
            u.name_first, u.name_last
            FROM User_Login      AS u
            LEFT JOIN Account_Info AS a ON a.user_ID = u.user_ID
            LEFT JOIN Role         AS r ON a.role_ID = r.role_ID 
            WHERE u.user_ID = :user_ID
            GROUP BY u.user_ID, u.user_username";
        $db = $this->connect();
        $query = $db->prepare($sql); 
        $query->bindParam(':user_ID', $user_ID);
        
        if($query->execute()){
            return $query->fetch(PDO::FETCH_ASSOC);
        }
    }

    public function updateUserWithRoles($user_ID, $firstname, $lastname, $username, $password, $role_ids, $statuses, $account_info_ids) {
        try {
            $db = $this->connect();
            $db->beginTransaction();

            // Update user login info
            if (!empty($password)) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE User_Login 
                        SET name_first = :firstname, 
                            name_last = :lastname, 
                            user_username = :username, 
                            user_password = :password 
                        WHERE user_ID = :user_ID";
                $stmt = $db->prepare($sql);
                $stmt->bindParam(':password', $hashedPassword);
            } else {
                $sql = "UPDATE User_Login 
                        SET name_first = :firstname, 
                            name_last = :lastname, 
                            user_username = :username 
                        WHERE user_ID = :user_ID";
                $stmt = $db->prepare($sql);
            }
            
            $stmt->bindParam(':user_ID', $user_ID);
            $stmt->bindParam(':firstname', $firstname);
            $stmt->bindParam(':lastname', $lastname);
            $stmt->bindParam(':username', $username);
            $stmt->execute();

            // Process each role assignment
            foreach ($role_ids as $index => $rid) {
                $rid = intval($rid);
                $status = $statuses[$index];
                $accountinfo_ID = intval($account_info_ids[$index] ?? 0);

                if ($rid > 0) {
                    if ($accountinfo_ID > 0) {
                        // UPDATE existing role assignment
                        if ($status === 'Deleted') {
                            // Soft delete
                            $stmt = $db->prepare("
                                UPDATE Account_Info 
                                SET account_status = 'Deleted', 
                                    is_deleted = NOW()
                                WHERE accountinfo_ID = ?
                            ");
                            $stmt->execute([$accountinfo_ID]);
                        } else {
                            // Regular update (also clear is_deleted if previously deleted)
                            $stmt = $db->prepare("
                                UPDATE Account_Info 
                                SET role_ID = ?, 
                                    account_status = ?,
                                    is_deleted = NULL
                                WHERE accountinfo_ID = ?
                            ");
                            $stmt->execute([$rid, $status, $accountinfo_ID]);
                        }
                    }
                }
            }

            $db->commit();
            return true;

        } catch (Exception $e) {
            $db->rollBack();
            error_log("User update failed: " . $e->getMessage());
            throw $e;  
        }
    }
 
    public function getUserRoleAssignments($user_ID) {
        $db = $this->connect();
        $sql = "SELECT ai.account_ID, ai.role_ID, ai.account_status, ai.is_deleted,
                   r.role_name
            FROM Account_Info ai
            JOIN Role r ON ai.role_ID = r.role_ID
            WHERE ai.user_ID = ?
            ORDER BY ai.account_ID";
        $stmt = $db->prepare($sql);
        $stmt->execute([$user_ID]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
 
    public function hardDeleteUserRole($account_ID) {
        try {
            $db = $this->connect();  
            $sql = "DELETE FROM account_info WHERE account_ID = :i";
              
            $stmt = $db->prepare($sql);
            $stmt->bindParam(":i", $account_ID); 
            if ($stmt->execute()) {
                return $stmt->fetch();
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Error hard deleting user role: " . $e->getMessage());
            throw $e;
        }
    }

    public function updateUserDetails( $user_ID, $firstname, $lastname, $username, $password ){
        $db = $this->connect();
        $db->beginTransaction();
        try { 
             if (!empty($password)) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE User_Login 
                        SET name_first = :firstname, 
                            name_last = :lastname, 
                            user_username = :username, 
                            user_password = :password 
                        WHERE user_ID = :user_ID";
                $stmt = $db->prepare($sql);
                $stmt->bindParam(':password', $hashedPassword);
            } else {
                $sql = "UPDATE User_Login 
                        SET name_first = :firstname, 
                            name_last = :lastname, 
                            user_username = :username 
                        WHERE user_ID = :user_ID";
                $stmt = $db->prepare($sql);
            }
            
            $stmt->bindParam(':user_ID', $user_ID);
            $stmt->bindParam(':firstname', $firstname);
            $stmt->bindParam(':lastname', $lastname);
            $stmt->bindParam(':username', $username);
            $result = $stmt->execute();

            if ($result) {
                $db->commit();
                return true;
            } else {
                $db->rollBack();
                return false;
            }
        } catch (PDOException $e) {
            $db->rollBack();
            error_log("updateUserDetails Error: " . $e->getMessage());
            return false;
        }

    }
 
}
?>
 
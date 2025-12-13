<?php

require_once "user-login.php";
require_once __DIR__ . "/../person/trait-person.php";
trait AccountLoginTrait {
    use UserLoginTrait, PersonTrait;


    public function addgetAccountLogin($user_ID, $role_ID, $db) {
        $sql = "SELECT accountlogin_ID FROM Account_Login 
                WHERE user_ID = :user_ID AND role_ID = :role_ID";
        $query = $db->prepare($sql);
        $query->bindParam(":user_ID", $user_ID);
        $query->bindParam(":role_ID", $role_ID);
        $query->execute();
        $result = $query->fetch();

        if ($result) {
            return $result["accountlogin_ID"];
        }
        
        $created_at = date("Y-m-d H:i:s");

        $sql = "INSERT INTO Account_Login (user_ID, role_ID, account_created_at) 
                VALUES (:user_ID, :role_ID, :created_at)";
        $query = $db->prepare($sql);
        $query->bindParam(":user_ID", $user_ID);
        $query->bindParam(":role_ID", $role_ID);
        $query->bindParam(":created_at", $created_at);
        
        if ($query->execute()) {
            return $db->lastInsertId();
        } else {
            return false;
        }
    }

    public function countAccount(){
        $sql = "SELECT COUNT(*) AS accounts FROM account_info a
            LEFT JOIN Role AS r ON a.role_ID = r.role_ID
            WHERE r.role_ID != 1";
        $db = $this->connect();
        $query = $db->prepare($sql);
        $query->execute();

        return $query->fetch(PDO::FETCH_ASSOC);
    }





}
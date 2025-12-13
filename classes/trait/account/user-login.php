<?php

trait UserLoginTrait {

    // Check if username exists
    public function checkUsernameExists($username) {
        $sql = "SELECT COUNT(*) AS total FROM User_Login WHERE userlogin_username = :username";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":username", $username);
        
        if ($query->execute()) {
            $record = $query->fetch();
            return $record["total"] > 0;
        }
        return false;
    }

    public function addgetUserLogin($person_ID, $user_username, $user_password, $db) {
        $sql = "SELECT user_ID FROM User_Login 
                WHERE person_ID = :person_ID";
        $query = $db->prepare($sql);
        $query->bindParam(":person_ID", $person_ID);
        $query->execute();
        $result = $query->fetch();

        if ($result) {
            return $result["user_ID"];
        }
        
        $sql = "INSERT INTO User_Login (person_ID, user_username, user_password 
                VALUES (:person_ID, :username, :user_password)";
        $query = $db->prepare($sql);
        $query->bindParam(":person_ID", $person_ID);
        $query->bindParam(":username", $username);
        $query->bindParam(":password_hash", $password);
        
        if ($query->execute()) {
            return $db->lastInsertId();
        } else {
            return false;
        }
    }
}
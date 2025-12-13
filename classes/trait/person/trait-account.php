<?php 

trait Account_InfoTrait {

    public function addAccountGuide($user_ID, $db){
        $sql = "INSERT INTO Account_Info (user_ID, role_ID, account_status) VALUES (:user_ID, 2, 'Pending')";
        $query = $db->prepare($sql);
        $query->bindParam(':user_ID', $user_ID);
        $result = $query->execute();

            if ($query->execute()) {
                return $db->lastInsertId();
            } else {
                return false;
            }

    }

}


?>
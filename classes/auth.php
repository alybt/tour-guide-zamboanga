<?php

require_once __DIR__ . "/../config/database.php";

class Auth extends Database {
    public $username = "";
    public $password = "";

    
    public function login($username, $password) {
        $sql = "SELECT 
                ul.user_ID, 
                ul.user_password, 
                r.role_name,
                r.role_ID, 
                ai.account_status,
                ai.account_ID
            FROM User_Login ul
            JOIN Account_Info ai ON ul.user_ID = ai.user_ID
            JOIN Role r ON ai.role_ID = r.role_ID
            WHERE ul.user_username = :username
            LIMIT 1";
        
        try {
            $pdo = $this->connect();
            $query = $pdo->prepare($sql);
            $query->bindParam(':username', $username);
            $query->execute();

            if ($query->rowCount() === 1) {
                $user = $query->fetch();

                if ($password === $user['user_password']) {
                    return [
                        "success" => true,
                        "user_ID" => $user['user_ID'],
                        "user_username" => $username,
                        "role_name" => $user['role_name'],
                        "role_ID" => $user['role_ID'],
                        "account_status" => $user['account_status'],
                        "account_ID" => $user['account_ID']
                    ];
                } else {
                    // Password does not match
                    return [
                        "success" => false,
                        "message" => "Invalid username or password." // Generic error for security
                    ];
                }
            } else {
                // User not found
                return [
                    "success" => false,
                    "message" => "Invalid username or password." // Generic error for security
                ];
            }
        } catch (PDOException $e) {
            // Log the error and return a generic failure message
            error_log("Login PDO Error: " . $e->getMessage());
            return [
                "success" => false,
                "message" => "An error occurred during login. Please try again."
            ];
        }
    }
    
    public function getAllUsers() {
        $sql = " SELECT 
                a.account_ID,
                CONCAT_WS(' ',
                    n.name_first,
                    n.name_second,
                    n.name_middle,
                    n.name_last,
                    n.name_suffix
                ) AS full_name,
                r.role_name AS role,
                r.role_ID AS role_ID,
                a.account_status AS status,
                a.account_rating_score,
                a.account_created_at,
                u.user_username AS username,
                u.user_ID AS user_ID,
                p.person_ID AS person_ID,
                CASE 
                    WHEN ad.admin_ID IS NOT NULL THEN 'Admin'
                    WHEN g.guide_ID IS NOT NULL THEN 'Guide'
                    ELSE 'Tourist'
                END AS account_type
            FROM Account_Info a
            JOIN User_Login u ON a.user_ID = u.user_ID
            JOIN Person p ON u.person_ID = p.person_ID
            JOIN Name_Info n ON p.name_ID = n.name_ID
            JOIN Role r ON a.role_ID = r.role_ID
            LEFT JOIN Admin ad ON a.account_ID = ad.account_ID
            LEFT JOIN Guide g ON a.account_ID = g.account_ID
            WHERE r.role_ID != 1
            ORDER BY full_name
            ";
        
        try {
            $pdo = $this->connect();
            $query = $pdo->prepare($sql);
            $query->execute();
            
            return $query->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("GetAllUsers PDO Error: " . $e->getMessage());
            return [];
        }
    }
}

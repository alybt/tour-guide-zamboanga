<?php

trait AccountInfoTrait {

    public function getInfobyAccountID($account_ID){
        $sql = "SELECT 
            ul.name_first,
            ul.name_second,
            ul.name_middle,
            ul.name_last,
            ul.name_suffix,
            pn.phone_number,
            ci.contactinfo_email AS email,
            acc.account_aboutme,
            acc.account_bio,
            acc.account_nickname
        FROM Account_Info acc
        JOIN User_Login ul ON acc.user_ID = ul.user_ID  
        LEFT JOIN Contact_Info ci ON ul.contactinfo_ID = ci.contactinfo_ID
        LEFT JOIN Phone_Number pn ON ci.phone_ID = pn.phone_ID
        WHERE acc.account_ID = :account_ID"; 
        $db = $this->connect();
        $query = $db->prepare($sql);
        $query->bindParam(':account_ID', $account_ID, PDO::PARAM_INT);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC); 
    }
 
    public function updateProfileInfo(int $account_ID, array $data): array {
        try {
            $this->conn->beginTransaction();

            // Get related IDs
            $sql = "SELECT 
                ul.user_ID, 
                ul.contactinfo_ID,
                ci.phone_ID
            FROM Account_Info acc
            JOIN User_Login ul ON acc.user_ID = ul.user_ID 
            LEFT JOIN Contact_Info ci ON ul.contactinfo_ID = ci.contactinfo_ID
            WHERE acc.account_ID = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$account_ID]);
            $ids = $stmt->fetch();

            if (!$ids) {
                throw new Exception('Account not found');
            }

            $stmt = $this->conn->prepare("
                UPDATE User_Login 
                SET name_first = ?, 
                    name_second = ?, 
                    name_middle = ?, 
                    name_last = ?, 
                    name_suffix = ?
                WHERE user_ID = ?
            ");
            $stmt->execute([
                $data['name_first'],
                $data['name_second'],
                $data['name_middle'],
                $data['name_last'],
                $data['name_suffix'],
                $ids['name_ID']
            ]);

            // Update Contact_Info email
            if (!empty($ids['contactinfo_ID'])) {
                $stmt = $this->conn->prepare("
                    UPDATE Contact_Info 
                    SET contactinfo_email = ?
                    WHERE contactinfo_ID = ?
                ");
                $stmt->execute([
                    $data['email'],
                    $ids['contactinfo_ID']
                ]);

                // Update Phone_Number
                if (!empty($ids['phone_ID'])) {
                    $stmt = $this->conn->prepare("
                        UPDATE Phone_Number 
                        SET phone_number = ?
                        WHERE phone_ID = ?
                    ");
                    $stmt->execute([
                        $data['phone_number'],
                        $ids['phone_ID']
                    ]);
                }
            }

            // Update Account_Info
            $stmt = $this->conn->prepare("
                UPDATE Account_Info 
                SET account_aboutme = ?,
                    account_bio = ?,
                    account_nickname = ?
                WHERE account_ID = ?
            ");
            $stmt->execute([
                $data['account_aboutme'],
                $data['account_bio'],
                $data['account_nickname'],
                $account_ID
            ]);

            $this->conn->commit();
            return ['success' => true, 'message' => 'Profile updated successfully'];

        } catch (PDOException $e) {
            $this->conn->rollBack();
            $this->setLastError('Database error: ' . $e->getMessage());
            return ['success' => false, 'message' => $this->getLastError()];
        } catch (Exception $e) {
            $this->conn->rollBack();
            $this->setLastError('Error: ' . $e->getMessage());
            return ['success' => false, 'message' => $this->getLastError()];
        }
    }

  
    public function getSecurityInfo(int $account_ID): array {
        try {
            $sql = "SELECT 
                ul.username,
                ul.user_last_password_change as last_password_change
            FROM Account_Info acc
            JOIN User_Login ul ON acc.user_ID = ul.user_ID
            WHERE acc.account_ID = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$account_ID]);
            $result = $stmt->fetch();
            
            if ($result) {
                $result['last_password_change'] = $result['last_password_change'] 
                    ? date('F d, Y', strtotime($result['last_password_change'])) 
                    : 'Never';
            }
            
            return $result ?: [];
        } catch (PDOException $e) {
            $this->setLastError('Database error: ' . $e->getMessage());
            return [];
        }
    }
 
    public function updateSecurity(int $account_ID, string $current_password, string $new_username = '', string $new_password = '', string $confirm_password = ''): array {
        try {
            // Get user_ID and current credentials
            $sql = "SELECT ul.user_ID, ul.username, ul.password
            FROM Account_Info acc
            JOIN User_Login ul ON acc.user_ID = ul.user_ID
            WHERE acc.account_ID = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$account_ID]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return ['success' => false, 'message' => 'Account not found'];
            }
            
            // Verify current password
            if (!password_verify($current_password, $user['password'])) {
                return ['success' => false, 'message' => 'Current password is incorrect'];
            }
            
            // Validate new password if provided
            if ($new_password) {
                if (strlen($new_password) < 8) {
                    return ['success' => false, 'message' => 'New password must be at least 8 characters'];
                }
                
                if (!preg_match('/[A-Z]/', $new_password) || !preg_match('/[a-z]/', $new_password) || !preg_match('/[0-9]/', $new_password)) {
                    return ['success' => false, 'message' => 'Password must contain uppercase, lowercase, and numbers'];
                }
                
                if ($new_password !== $confirm_password) {
                    return ['success' => false, 'message' => 'New passwords do not match'];
                }
            }
            
            // Validate new username if provided
            if ($new_username && $new_username !== $user['username']) {
                if (strlen($new_username) < 4) {
                    return ['success' => false, 'message' => 'Username must be at least 4 characters'];
                }
                
                // Check if username already exists
                $stmt = $this->conn->prepare("SELECT user_ID FROM User_Login WHERE username = ? AND user_ID != ?");
                $stmt->execute([$new_username, $user['user_ID']]);
                if ($stmt->fetch()) {
                    return ['success' => false, 'message' => 'Username already taken'];
                }
            }
            
            $this->conn->beginTransaction();
            
            $updates = [];
            $params = [];
            
            // Update username if provided and different
            if ($new_username && $new_username !== $user['username']) {
                $updates[] = "username = ?";
                $params[] = $new_username;
            }
            
            // Update password if provided
            if ($new_password) {
                $updates[] = "password = ?";
                $params[] = password_hash($new_password, PASSWORD_DEFAULT);
                $updates[] = "user_last_password_change = NOW()";
            }
            
            if (empty($updates)) {
                return ['success' => false, 'message' => 'No changes to update'];
            }
            
            $params[] = $user['user_ID'];
            
            $sql = "UPDATE User_Login SET " . implode(", ", $updates) . " WHERE user_ID = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            
            // Log the activity
            $this->logActivity($account_ID, 1, 'Security settings updated'); // action_ID 1 for security updates
            
            $this->conn->commit();
            return ['success' => true, 'message' => 'Security settings updated successfully'];
            
        } catch (PDOException $e) {
            $this->conn->rollBack();
            $this->setLastError('Database error: ' . $e->getMessage());
            return ['success' => false, 'message' => $this->getLastError()];
        } catch (Exception $e) {
            $this->conn->rollBack();
            $this->setLastError('Error: ' . $e->getMessage());
            return ['success' => false, 'message' => $this->getLastError()];
        }
    }
 
    public function getActivityLogs(int $account_ID, int $limit = 20): array {
        try {
            $sql = "SELECT 
                al.activity_ID,
                al.activity_description,
                al.activity_timestamp,
                a.action_name
            FROM Activity_Log al
            LEFT JOIN Action a ON al.action_ID = a.action_ID
            WHERE al.account_ID = ?
            ORDER BY al.activity_timestamp DESC
            LIMIT ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$account_ID, $limit]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->setLastError('Database error: ' . $e->getMessage());
            return [];
        }
    } 
    
    public function logActivity(int $account_ID, int $action_ID, string $description): bool {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO Activity_Log (account_ID, action_ID, activity_description) 
                VALUES (?, ?, ?)
            ");
            return $stmt->execute([$account_ID, $action_ID, $description]);
        } catch (PDOException $e) {
            $this->setLastError('Failed to log activity: ' . $e->getMessage());
            return false;
        }
    }

}





?>

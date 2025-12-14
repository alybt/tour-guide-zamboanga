<?php

trait MessageTrait{

    public function conversation($sender_ID, $user2_ID, $message) {
        $db = $this->connect();
        $db->beginTransaction();

        try {
            $conversation_ID = $this->addgetUsers($sender_ID, $user2_ID, $db);
            if (!$conversation_ID) {
                throw new Exception("Unable to get conversation ID");
            }

            $message_ID = $this->addMessage($conversation_ID, $sender_ID, $message, $db);
            if (!$message_ID) {
                throw new Exception("Unable to insert message");
            }
 
            $sql = "UPDATE Conversation 
                    SET last_message_ID = :message_ID 
                    WHERE conversation_ID = :conversation_ID";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':message_ID', $message_ID, PDO::PARAM_INT);
            $stmt->bindParam(':conversation_ID', $conversation_ID, PDO::PARAM_INT);
            $stmt->execute();

            $db->commit();
            return $message_ID;

        } catch (Exception $e) {
            $db->rollBack();
            error_log("Conversation error: " . $e->getMessage());
            return null;
        }
    } 

    public function addgetUsers($user1_ID, $user2_ID, $db) { 
        $sql = "SELECT conversation_ID
                FROM Conversation
                WHERE (user1_account_ID = :u1 AND user2_account_ID = :u2)
                OR (user1_account_ID = :u2 AND user2_account_ID = :u1)";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':u1', $user1_ID);
        $stmt->bindParam(':u2', $user2_ID);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return $row['conversation_ID'];
        }

        // Create conversation
        $sqlInsert = "INSERT INTO Conversation (user1_account_ID, user2_account_ID)
                    VALUES (:u1, :u2)";
        $stmt = $db->prepare($sqlInsert);
        $stmt->bindParam(':u1', $user1_ID);
        $stmt->bindParam(':u2', $user2_ID);
        $stmt->execute();

        return $db->lastInsertId();
    } 

    public function addMessage($conversation_ID, $sender_ID, $message, $db) {
        $sql = "INSERT INTO Message (conversation_ID, sender_account_ID, message_content)
                VALUES (:conversation_ID, :sender_ID, :message)";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':conversation_ID', $conversation_ID, PDO::PARAM_INT);
        $stmt->bindParam(':sender_ID', $sender_ID, PDO::PARAM_INT);
        $stmt->bindParam(':message', $message);
        $stmt->execute();

        return $db->lastInsertId();
    }

    //inbox list
    public function fetchConversations($account_ID) {
        $db = $this->connect();

        $sql = "SELECT 
                c.conversation_ID,

                CASE 
                    WHEN c.user1_account_ID = :uid1 
                    THEN c.user2_account_ID 
                    ELSE c.user1_account_ID 
                END AS other_user_ID,

                m.message_content AS last_message,
                m.sent_at AS last_message_time,

                EXISTS (
                    SELECT 1
                    FROM Message m2
                    WHERE m2.conversation_ID = c.conversation_ID
                    AND m2.sender_account_ID != :uid2
                    AND m2.is_read = 0
                ) AS has_unread

            FROM Conversation c
            LEFT JOIN Message m 
                ON m.message_ID = c.last_message_ID

            WHERE c.user1_account_ID = :uid3
            OR c.user2_account_ID = :uid4

            ORDER BY m.sent_at DESC
        ";

        $stmt = $db->prepare($sql);

        $stmt->bindParam(':uid1', $account_ID, PDO::PARAM_INT);
        $stmt->bindParam(':uid2', $account_ID, PDO::PARAM_INT);
        $stmt->bindParam(':uid3', $account_ID, PDO::PARAM_INT);
        $stmt->bindParam(':uid4', $account_ID, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }



    //messages 
    public function fetchMessages($conversation_ID) {
        $db = $this->connect();

        $sql = "
            SELECT 
                message_ID,
                sender_account_ID,
                message_content,
                is_read,
                sent_at
            FROM Message
            WHERE conversation_ID = :conversation_ID
            ORDER BY sent_at ASC
        ";

        $stmt = $db->prepare($sql);
        $stmt->bindParam(':conversation_ID', $conversation_ID, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    //mark as read
    public function markAsRead($conversation_ID, $account_ID) {
        $db = $this->connect();

        $sql = "
            UPDATE Message
            SET is_read = 1
            WHERE conversation_ID = :conversation_ID
            AND sender_account_ID != :account_ID
        ";

        $stmt = $db->prepare($sql);
        $stmt->bindParam(':conversation_ID', $conversation_ID, PDO::PARAM_INT);
        $stmt->bindParam(':account_ID', $account_ID, PDO::PARAM_INT);
        $stmt->execute();
    }




}



?>

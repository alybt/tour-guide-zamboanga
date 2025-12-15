<?php

require_once __DIR__ . "/../config/database.php";

class Rating extends Database {
     
    public function getReviewByBooking( $booking_ID,  $rating_type): ?array {
        try {
            $sql = "SELECT * FROM Rating WHERE booking_ID = ? AND rating_type = ? LIMIT 1";
            $db = $this->connect();
            $stmt = $db->prepare($sql);
            $stmt->execute([$booking_ID, $rating_type]);
            $result = $stmt->fetch();
            return $result ?: null;
        } catch (PDOException $e) {
            $this->setLastError('Error fetching review: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get all reviews for a booking
     */
    public function getAllReviewsByBooking(int $booking_ID): array {
        try {
            $sql = "SELECT * FROM Rating WHERE booking_ID = ? ORDER BY rating_date DESC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$booking_ID]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->setLastError('Error fetching reviews: ' . $e->getMessage());
            return [];
        }
    }
}
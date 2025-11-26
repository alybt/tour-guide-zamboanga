<?php

trait UpdateBookings{
 
    public function updateBookings($timedate = null) {
        $now = $timedate ? new DateTime($timedate, new DateTimeZone('Asia/Manila')) 
                        : new DateTime('now', new DateTimeZone('Asia/Manila'));
        $nowStr = $now->format('Y-m-d H:i:s'); 

        $sql = "UPDATE booking
                SET booking_status = CASE
                    WHEN booking_status = 'Pending for Payment'
                        AND booking_start_date <= DATE_ADD(?, INTERVAL 1 DAY)
                        THEN 'Booking Expired — Payment Not Completed'

                    WHEN booking_status = 'Pending for Approval'
                        AND booking_start_date <= ?
                        THEN 'Booking Expired — Guide Did Not Confirm in Time'

                    WHEN booking_status IN ('Approved', 'In Progress')
                        AND booking_end_date <= ?
                        THEN 'Completed'

                    ELSE booking_status
                END
                WHERE booking_status IN ('Pending for Payment', 'Pending for Approval', 'Approved', 'In Progress')
                AND (booking_start_date <= DATE_ADD(?, INTERVAL 1 DAY) OR booking_end_date <= ?)
                HAVING booking_status <> OLD.booking_status  
                RETURNING booking_ID, 
                        OLD.booking_status AS old_status, 
                        booking_status AS new_status";

        try {
            $db = $this->connect();
            $stmt = $db->prepare($sql);
            $stmt->execute([$nowStr, $nowStr, $nowStr, $nowStr, $nowStr]);

            $updatedRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $updatedCount = count($updatedRows);
 
            if ($updatedCount > 0) {
                foreach ($updatedRows as $row) {
                    $bookingId = $row['booking_ID'];
                    $oldStatus = $row['old_status'];
                    $newStatus = $row['new_status'];
 
                    $description = match ($newStatus) {
                        'Booking Expired — Payment Not Completed' => "Booking expired: Payment not completed in time",
                        'Booking Expired — Guide Did Not Confirm in Time' => "Booking expired: Guide did not confirm on time",
                        'Completed' => "Booking completed successfully",
                        default => "Booking status changed from '$oldStatus' to '$newStatus'"
                    };
 
                    $this->logBookingStatusChange($bookingId, $newStatus, $description);
                }

                $this->activity->systemUpdateBooking();  
            }

            error_log("[AUTO-UPDATE SUCCESS] $nowStr (PH) | Updated: $updatedCount bookings | IDs: " . 
                    implode(', ', array_column($updatedRows, 'booking_ID')));

            return [
                'success' => true,
                'updated' => $updatedCount,
                'updated_ids' => array_column($updatedRows, 'booking_ID'),
                'message' => "$updatedCount booking(s) auto-updated"
            ];

        } catch (Exception $e) {
            error_log("BOOKING AUTO-UPDATE FAILED: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function logBookingStatusChange($bookingId, $newStatus, $description) {
        try {
            $db = $this->connect();
            $db->beginTransaction();

            $actionName = 'Booking Status Auto-Updated';
            $actionId = $this->addgetActionID($actionName, $db);

            if (!$actionId) {
                error_log("Failed to get action_ID for booking $bookingId auto-update");
                $db->rollBack();
                return false;
            } 
            $sql = "INSERT INTO Activity_Log 
                    (account_ID, action_ID, activity_description, reference_id, reference_type) 
                    VALUES (NULL, :action_ID, :description, :booking_id, 'booking')";

            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':action_ID' => $actionId,
                ':description' => "$description (Booking ID: $bookingId → $newStatus)",
                ':booking_id' => $bookingId
            ]);

            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Failed to log status change for booking $bookingId: " . $e->getMessage());
            return false;
        }
    }




}


?>
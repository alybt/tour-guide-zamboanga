<?php

trait UpdateBookings{
 
    public function updateBookings($timedate = null) {
        $now = $timedate
            ? new DateTime($timedate, new DateTimeZone('Asia/Manila'))
            : new DateTime('now', new DateTimeZone('Asia/Manila'));

        $nowStr = $now->format('Y-m-d H:i:s');

        try {
            $db = $this->connect(); 

            // SELECT OLD STATUSES BEFORE UPDATE 
            $sqlSelectBefore = " SELECT booking_ID, booking_status
                FROM booking
                WHERE booking_status IN ('Pending for Payment', 'Pending for Approval', 'Approved', 'In Progress' )
                AND ( booking_start_date <= DATE_ADD(?, INTERVAL 1 DAY) OR booking_end_date <= ? ) ";

            $stmt = $db->prepare($sqlSelectBefore);
            $stmt->execute([$nowStr, $nowStr]);
            $oldRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($oldRows)) {
                return [
                    'success' => true,
                    'updated' => 0,
                    'updated_ids' => [],
                    'message' => "No bookings matched the auto-update criteria."
                ];
            }

            // Convert to map: [bookingId => oldStatus]
            $oldMap = [];
            foreach ($oldRows as $row) {
                $oldMap[$row['booking_ID']] = $row['booking_status'];
            } 
            
            // UPDATE BOOKING STATUSES 
            $sqlUpdate = " UPDATE booking
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
                WHERE booking_status IN (
                    'Pending for Payment',
                    'Pending for Approval',
                    'Approved',
                    'In Progress'
                )
                AND (
                    booking_start_date <= DATE_ADD(?, INTERVAL 1 DAY)
                    OR booking_end_date <= ?
                ) ";

            $stmt = $db->prepare($sqlUpdate);
            $stmt->execute([$nowStr, $nowStr, $nowStr, $nowStr, $nowStr]);
 
            // SELECT UPDATED STATUSES AFTER UPDATE 
            $bookingIds = array_column($oldRows, 'booking_ID');
            $placeholder = implode(',', array_fill(0, count($bookingIds), '?'));

            $sqlSelectAfter = "  SELECT booking_ID, booking_status
                FROM booking WHERE booking_ID IN ($placeholder) ";

            $stmt = $db->prepare($sqlSelectAfter);
            $stmt->execute($bookingIds);
            $newRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Convert to map: [bookingId => newStatus]
            $newMap = [];
            foreach ($newRows as $row) {
                $newMap[$row['booking_ID']] = $row['booking_status'];
            }
 
            // COMPARE OLD AND NEW — LOG ONLY CHANGES 
            $updatedIds = [];

            foreach ($oldMap as $id => $oldStatus) {
                $newStatus = $newMap[$id] ?? $oldStatus;

                if ($oldStatus !== $newStatus) {
                    $updatedIds[] = $id;

                    // Choose description
                    $description = match ($newStatus) {
                        'Booking Expired — Payment Not Completed'
                            => "Booking expired: Payment not completed in time",

                        'Booking Expired — Guide Did Not Confirm in Time'
                            => "Booking expired: Guide did not confirm on time",

                        'Completed'
                            => "Booking completed successfully",

                        default
                            => "Booking status changed from '$oldStatus' to '$newStatus'"
                    };

                    // Log
                    $this->logBookingStatusChange($id, $newStatus, $description);
                }
            }

            // Trigger general system activity log
            if (!empty($updatedIds)) {
                $this->activity->systemUpdateBooking();
            }

            error_log("[AUTO-UPDATE SUCCESS] $nowStr | Updated: "
                . count($updatedIds)
                . " | IDs: " . implode(', ', $updatedIds));

            return [
                'success' => true,
                'updated' => count($updatedIds),
                'updated_ids' => $updatedIds,
                'message' => count($updatedIds) . " booking(s) auto-updated"
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
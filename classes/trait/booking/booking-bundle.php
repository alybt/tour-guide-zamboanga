<?php

require_once "companion.php";
trait BookingBundleTrait
{
    use CompanionTrait; // Reuse companion logic

    public function addCompanionToBooking($booking_ID, $companion_name, $category_ID)
    {
        $db = $this->connect();
        $db->beginTransaction();

        try {
            // Step 1: Get or create the companion
            $companion_ID = $this->getOrCreateCompanion($companion_name, $category_ID, $db);

            if (!$companion_ID) {
                $db->rollBack();
                return false;
            }

            // Step 2: Insert into booking_bundle
            $sql = "INSERT INTO booking_bundle (booking_ID, companion_ID) 
                    VALUES (:booking_ID, :companion_ID)";
            $query = $db->prepare($sql);
            $query->bindParam(':booking_ID', $booking_ID, PDO::PARAM_INT);
            $query->bindParam(':companion_ID', $companion_ID, PDO::PARAM_INT);
            $result = $query->execute();

            if ($result) {
                $db->commit();
                return true;
            } else {
                $db->rollBack();
                return false;
            }
        } catch (PDOException $e) {
            $db->rollBack();
            error_log("Add Companion Error: " . $e->getMessage());
            return false;
        }
    }

}
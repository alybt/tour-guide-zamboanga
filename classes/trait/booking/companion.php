<?php
trait CompanionTrait{
    /**
     * Get existing companion OR create a new one
     * @param string $companion_name
     * @param int $category_ID
     * @param PDO $db
     * @return int|false  companion_ID or false
     */
    public function getOrCreateCompanion($companion_name, $category_ID, $db){
        // 1. Try to find existing companion
        $sql = "SELECT companion_ID 
                FROM companion 
                WHERE companion_name = :companion_name 
                AND companion_category_ID = :category_ID";
        $query = $db->prepare($sql);
        $query->bindParam(':companion_name', $companion_name);
        $query->bindParam(':category_ID', $category_ID, PDO::PARAM_INT);
        $query->execute();
        $row = $query->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            return (int)$row['companion_ID'];
        }

        // 2. Insert new companion
        $sql = "INSERT INTO companion (companion_name, companion_category_ID) 
                VALUES (:companion_name, :category_ID)";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':companion_name', $companion_name);
        $stmt->bindParam(':category_ID', $category_ID, PDO::PARAM_INT);
        $success = $stmt->execute();

        return $success ? $db->lastInsertId() : false;
    }

    public function getCompanionsByBooking($booking_ID) {
        $sql = "SELECT c.companion_name, c.companion_category, c.companion_age
                FROM Booking_Bundle b
                JOIN Companion c ON b.companion_ID = c.companion_ID 
                WHERE b.booking_ID = :id";
        $db = $this->connect();
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':id', $booking_ID, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getCompanionBreakdown($booking_ID) {
        $sql = "SELECT 
                c.companion_category AS category,
                COUNT(c.companion_ID) AS qty,
                tp.pricing_forchild,
                tp.pricing_foryoungadult,
                tp.pricing_foradult,
                tp.pricing_forsenior,
                tp.pricing_forpwd
            FROM companion c 
            JOIN booking_bundle bb ON bb.companion_ID = c.companion_ID
            JOIN booking b ON bb.booking_ID = b.booking_ID
            JOIN tour_package tp ON b.tourpackage_ID = tp.tourpackage_ID 
            WHERE bb.booking_ID = :booking_ID
            GROUP BY c.companion_category
            HAVING qty > 0
            ORDER BY FIELD(c.companion_category, 'Infant', 'Child', 'Young Adult', 'Adult', 'Senior', 'PWD')
        ";

        $db = $this->connect();
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':booking_ID', $booking_ID, PDO::PARAM_INT);
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $breakdown = [];
        foreach ($results as $row) {
            $cat = $row['category'];
            $qty = (int)$row['qty'];

            $pricePer = 0;
            switch ($cat) {
                case 'Child':        $pricePer = $row['pricing_forchild']; break;
                case 'Young Adult':  $pricePer = $row['pricing_foryoungadult']; break;
                case 'Adult':        $pricePer = $row['pricing_foradult']; break;
                case 'Senior':       $pricePer = $row['pricing_forsenior']; break;
                case 'PWD':          $pricePer = $row['pricing_forpwd']; break;
                case 'Infant':       $pricePer = 0; break; // Usually free
            }

            $total = $pricePer * $qty;

            if ($qty > 0 && $total > 0) {
                $breakdown[] = [
                    'category' => $cat,
                    'qty'      => $qty,
                    'price'    => $pricePer,
                    'total'    => $total
                ];
            }
        }

        return $breakdown;
    }

    public function getAllCompanionCategories() {
        $sql = "SELECT DISTINCT companion_category 
                FROM companion 
                ORDER BY companion_category";
        $db = $this->connect();
        $stmt = $db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

}
<?php
trait TransactionReferenceTrait{
    
    public function generateUniqueReference(PDO $db){
        $attempts = 0;
        $maxAttempts = 10;

        do {
            $ref = $this->generateRandomReference();
            
            // Check if exists
            $stmt = $db->prepare("SELECT COUNT(*) FROM payment_transaction WHERE transaction_reference = ?");
            $stmt->execute([$ref]);
            $exists = $stmt->fetchColumn();
            
            $attempts++;
        } while ($exists && $attempts < $maxAttempts);

        if ($exists) {
            throw new Exception("Could not generate unique reference after $maxAttempts attempts");
        }

        return $ref;
    }

    /**
     * Generate single random reference (without uniqueness check)
     * @return string
     */
    private function generateRandomReference()
    {
        // Format: TXN-YYYYMMDD-RANDOM6
        $timestamp = date('Ymd');  // 20250615
        $random6 = strtoupper(substr(sha1(random_bytes(16)), 0, 6));  // A7B2C9
        
        return "TXN-{$timestamp}-{$random6}";
    }

    /**
     * Alternative: Pure random (no timestamp)
     * @return string  e.g., "REF-AB12CD34EF56"
     */
    public function generatePureRandomReference()
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $length = 12;
        $ref = 'REF-';
        
        for ($i = 0; $i < $length; $i++) {
            $ref .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        return $ref;
    }
}
<?php
require_once __DIR__ . "/../config/database.php";

class Payment extends Database {
    private $db;
    private $paymongo;

    public function __construct($db_connection, $paymongo_secret_key) {
        $this->db = $db_connection;
        $this->paymongo = new Paymongo($paymongo_secret_key);
    }

    private function getProcessingFee($methodcategory_ID) {
        $sql = "SELECT methodcategory_processing_fee FROM Method_Category WHERE methodcategory_ID = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $methodcategory_ID]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['methodcategory_processing_fee'] : 0;
    }

    public function calculateTotalWithFee($amount, $methodcategory_ID) {
        $processing_fee = $this->getProcessingFee($methodcategory_ID);
        return $amount + $processing_fee;
    }

    /**
     * Create Transaction (updated to use calculateTotalWithFee)
     */
    public function createTransaction($paymentinfo_ID, $amount, $method_data) {
        // Calculate final total including processing fee
        $final_amount = $this->calculateTotalWithFee($amount, $method_data['methodcategory_ID']);

        // Create PayMongo PaymentIntent
        $paymentIntent = $this->paymongo->paymentIntents()->create([
            'amount' => $final_amount * 100, // PayMongo uses centavos
            'currency' => 'PHP',
            'payment_method_allowed' => [$method_data['methodcategory_type']],
            'description' => "Booking #$paymentinfo_ID"
        ]);

        $transaction_reference = $paymentIntent['id']; // PayMongo transaction reference

        // Insert Method
        $sqlMethod = "INSERT INTO Method 
                      (methodcategory_ID, method_name, method_email, method_line1, method_city, method_postalcode, method_country, method_phone, method_status) 
                      VALUES (:category_ID, :name, :email, :line1, :city, :postal, :country, :phone, 'Active')";
        $stmtMethod = $this->db->prepare($sqlMethod);
        $stmtMethod->execute([
            ':category_ID' => $method_data['methodcategory_ID'],
            ':name' => $method_data['name'],
            ':email' => $method_data['email'],
            ':line1' => $method_data['line1'] ?? null,
            ':city' => $method_data['city'] ?? null,
            ':postal' => $method_data['postalcode'] ?? null,
            ':country' => $method_data['country'] ?? null,
            ':phone' => $method_data['phone'] ?? null
        ]);

        $method_ID = $this->db->lastInsertId();

        // Insert Transaction
        $sqlTrans = "INSERT INTO Payment_Transaction 
                     (paymentinfo_ID, method_ID, transaction_status, transaction_reference, transaction_created_date, transaction_updated_date)
                     VALUES (:paymentinfo_ID, :method_ID, 'Pending', :reference, NOW(), NOW())";
        $stmtTrans = $this->db->prepare($sqlTrans);
        $stmtTrans->execute([
            ':paymentinfo_ID' => $paymentinfo_ID,
            ':method_ID' => $method_ID,
            ':reference' => $transaction_reference
        ]);

        return [
            'paymentIntent' => $paymentIntent,
            'transaction_ID' => $this->db->lastInsertId(),
            'final_amount' => $final_amount
        ];
    }

    // ... existing updateTransactionStatus() function ...
}

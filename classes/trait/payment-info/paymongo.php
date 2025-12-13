<?php

 

trait PayMongoTrait {
    
    // Get PayMongo secret key from environment
     
    private function getPayMongoSecretKey() {
        return getenv('PAYMONGO_SECRET_KEY') ?: $_ENV['PAYMONGO_SECRET_KEY'] ?? null;
    }

    // Get PayMongo public key from environment
    private function getPayMongoPublicKey() {
        return getenv('PAYMONGO_PUBLIC_KEY') ?: $_ENV['PAYMONGO_PUBLIC_KEY'] ?? null;
    }

    /**
     * Main method to process PayMongo payment
     * Returns payment result with intent ID if successful
     */
    public function processPayMongoPayment(
        $amount,
        $currency,
        $cardNumber,
        $expMonth,
        $expYear,
        $cvc,
        $billingName,
        $billingEmail,
        $billingPhone,
        $billingLine1,
        $billingCity,
        $billingPostalCode,
        $billingCountry,
        $description = '',
        $metadata = []
    ) {
        try {
            // Step 1: Create Payment Method
            $paymentMethod = $this->createPayMongoPaymentMethod(
                $cardNumber,
                $expMonth,
                $expYear,
                $cvc,
                $billingName,
                $billingEmail,
                $billingPhone,
                $billingLine1,
                $billingCity,
                $billingPostalCode,
                $billingCountry
            );

            if (!$paymentMethod['success']) {
                return $paymentMethod;
            }

            // Step 2: Create Payment Intent
            $paymentIntent = $this->createPayMongoPaymentIntent(
                $amount,
                $currency,
                $description,
                $metadata
            );

            if (!$paymentIntent['success']) {
                return $paymentIntent;
            }

            // Step 3: Attach Payment Method and Process
            $result = $this->attachPayMongoPaymentMethod(
                $paymentIntent['data']['id'],
                $paymentMethod['data']['id']
            );

            return $result;

        } catch (Exception $e) {
            error_log("[processPayMongoPayment] " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create PayMongo Payment Method (Tokenize card)
     */
    private function createPayMongoPaymentMethod(
        $cardNumber,
        $expMonth,
        $expYear,
        $cvc,
        $name,
        $email,
        $phone,
        $line1,
        $city,
        $postalCode,
        $country
    ) {
        try {
            $url = 'https://api.paymongo.com/v1/payment_methods';
            
            $data = [
                'data' => [
                    'attributes' => [
                        'type' => 'card',
                        'details' => [
                            'card_number' => $cardNumber,
                            'exp_month' => intval($expMonth),
                            'exp_year' => intval($expYear),
                            'cvc' => $cvc
                        ],
                        'billing' => [
                            'name' => $name,
                            'email' => $email,
                            'phone' => $phone,
                            'address' => [
                                'line1' => $line1,
                                'city' => $city,
                                'postal_code' => $postalCode,
                                'country' => $country
                            ]
                        ]
                    ]
                ]
            ];

            $response = $this->makePayMongoRequest($url, 'POST', $data);

            if (isset($response['data']['id'])) {
                return [
                    'success' => true,
                    'data' => $response['data']
                ];
            }

            return [
                'success' => false,
                'error' => $response['errors'][0]['detail'] ?? 'Failed to create payment method'
            ];

        } catch (Exception $e) {
            error_log("[createPayMongoPaymentMethod] " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create PayMongo Payment Intent
     */
    private function createPayMongoPaymentIntent(
        $amount,
        $currency = 'PHP',
        $description = '',
        $metadata = []
    ) {
        try {
            $url = 'https://api.paymongo.com/v1/payment_intents';
            
            // Convert amount to centavos/cents
            $amountInCents = intval($amount * 100);

            $data = [
                'data' => [
                    'attributes' => [
                        'amount' => $amountInCents,
                        'payment_method_allowed' => ['card'],
                        'currency' => strtoupper($currency),
                        'description' => $description,
                        'statement_descriptor' => 'Payment',
                        'metadata' => $metadata
                    ]
                ]
            ];

            $response = $this->makePayMongoRequest($url, 'POST', $data);

            if (isset($response['data']['id'])) {
                return [
                    'success' => true,
                    'data' => $response['data']
                ];
            }

            return [
                'success' => false,
                'error' => $response['errors'][0]['detail'] ?? 'Failed to create payment intent'
            ];

        } catch (Exception $e) {
            error_log("[createPayMongoPaymentIntent] " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Attach Payment Method to Payment Intent
     */
    private function attachPayMongoPaymentMethod($paymentIntentId, $paymentMethodId) {
        try {
            $url = "https://api.paymongo.com/v1/payment_intents/{$paymentIntentId}/attach";
            
            $data = [
                'data' => [
                    'attributes' => [
                        'payment_method' => $paymentMethodId,
                        'return_url' => getenv('PAYMENT_RETURN_URL') ?: $_ENV['PAYMENT_RETURN_URL'] ?? ''
                    ]
                ]
            ];

            $response = $this->makePayMongoRequest($url, 'POST', $data);

            if (isset($response['data']['attributes']['status'])) {
                $status = $response['data']['attributes']['status'];
                
                return [
                    'success' => $status === 'succeeded',
                    'status' => $status,
                    'payment_intent_id' => $response['data']['id'],
                    'data' => $response['data'],
                    'error' => $status !== 'succeeded' 
                        ? ($response['data']['attributes']['last_payment_error']['failed_message'] ?? 'Payment failed')
                        : null
                ];
            }

            return [
                'success' => false,
                'error' => $response['errors'][0]['detail'] ?? 'Failed to attach payment method'
            ];

        } catch (Exception $e) {
            error_log("[attachPayMongoPaymentMethod] " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // Make HTTP request to PayMongo API
     
    private function makePayMongoRequest($url, $method = 'GET', $data = null) {
        $secretKey = $this->getPayMongoSecretKey();
        
        if (!$secretKey) {
            throw new Exception("PayMongo secret key not configured");
        }

        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($secretKey . ':')
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);

        $decoded = json_decode($response, true);

        if ($httpCode >= 400) {
            error_log("[PayMongo API Error] HTTP {$httpCode}: " . $response);
        }

        return $decoded;
    }

    // Retrieve Payment Intent status 
    public function getPayMongoPaymentIntent($paymentIntentId) {
        try {
            $url = "https://api.paymongo.com/v1/payment_intents/{$paymentIntentId}";
            $response = $this->makePayMongoRequest($url, 'GET');

            if (isset($response['data']['id'])) {
                return [
                    'success' => true,
                    'data' => $response['data'],
                    'status' => $response['data']['attributes']['status']
                ];
            }

            return [
                'success' => false,
                'error' => 'Payment intent not found'
            ];

        } catch (Exception $e) {
            error_log("[getPayMongoPaymentIntent] " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create PayMongo Source (for non-card payments like GCash, GrabPay)
     */
    public function createPayMongoSource($amount, $type = 'gcash', $currency = 'PHP', $description = '', $metadata = []) {
        try {
            $url = 'https://api.paymongo.com/v1/sources';
            
            // Convert amount to centavos/cents
            $amountInCents = intval($amount * 100);

            $data = [
                'data' => [
                    'attributes' => [
                        'amount' => $amountInCents,
                        'currency' => strtoupper($currency),
                        'type' => $type,
                        'redirect' => [
                            'success' => getenv('PAYMENT_SUCCESS_URL') ?: $_ENV['PAYMENT_SUCCESS_URL'] ?? '',
                            'failed' => getenv('PAYMENT_FAILED_URL') ?: $_ENV['PAYMENT_FAILED_URL'] ?? ''
                        ],
                        'description' => $description,
                        'metadata' => $metadata
                    ]
                ]
            ];

            $response = $this->makePayMongoRequest($url, 'POST', $data);

            if (isset($response['data']['id'])) {
                return [
                    'success' => true,
                    'data' => $response['data'],
                    'checkout_url' => $response['data']['attributes']['redirect']['checkout_url']
                ];
            }

            return [
                'success' => false,
                'error' => $response['errors'][0]['detail'] ?? 'Failed to create source'
            ];

        } catch (Exception $e) {
            error_log("[createPayMongoSource] " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function refundPayMongoPayment(string $paymentIntentId, float $amount, string $reason = 'Customer requested refund'): array {
        try {
            $url = "https://api.paymongo.com/v1/refunds";

            $amountInCentavos = intval($amount * 100);

            $data = [
                'data' => [
                    'attributes' => [
                        'amount' => $amountInCentavos,
                        'payment_intent' => $paymentIntentId,
                        'reason' => $reason,
                        'metadata' => [
                            'refunded_by' => 'system_or_admin',
                            'timestamp' => date('c')
                        ]
                    ]
                ]
            ];

            $response = $this->makePayMongoRequest($url, 'POST', $data);

            // Check if refund was created
            if (isset($response['data']['id']) && $response['data']['attributes']['status'] === 'succeeded') {
                return [
                    'success' => true,
                    'refund_id' => $response['data']['id'],
                    'status' => $response['data']['attributes']['status'],
                    'amount_refunded' => $response['data']['attributes']['amount'] / 100,
                    'data' => $response['data']
                ];
            }

            // Partial or pending refund
            if (isset($response['data']['id'])) {
                return [
                    'success' => true,
                    'refund_id' => $response['data']['id'],
                    'status' => $response['data']['attributes']['status'],
                    'note' => 'Refund may be processing (async)',
                    'data' => $response['data']
                ];
            }

            // Error from PayMongo
            return [
                'success' => false,
                'error' => $response['errors'][0]['detail'] ?? 'Refund failed',
                'raw' => $response
            ];

        } catch (Exception $e) {
            error_log("[PayMongo Refund Failed] PaymentIntent: $paymentIntentId | Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Exception during refund: ' . $e->getMessage()
            ];
        }
    }

    public function approveAndProcessRefund(int $refund_ID): array {
        $db = $this->connect();

        try {
            $db->beginTransaction();

            // Get refund + transaction
            $sql = "SELECT r.*, pt.paymongo_intent_id, pt.transaction_ID
                    FROM Refund r
                    JOIN Payment_Transaction pt ON r.transaction_ID = pt.transaction_ID
                    WHERE r.refund_ID = :refund_ID 
                    AND r.refund_status = 'Pending'";

            $stmt = $db->prepare($sql);
            $stmt->execute([':refund_ID' => $refund_ID]);
            $refund = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$refund) {
                throw new Exception("Refund request not found or already processed.");
            }

            if (empty($refund['paymongo_intent_id'])) {
                throw new Exception("No PayMongo Payment Intent ID found.");
            }

            // 1. Issue PayMongo Refund
            $payMongoResult = $this->refundPayMongoPayment(
                $refund['paymongo_intent_id'],
                $refund['refund_refundfee'],  // this is the amount before fee
                "Refund approved: " . $refund['refund_reason']
            );

            if (!$payMongoResult['success']) {
                throw new Exception("PayMongo refund failed: " . ($payMongoResult['error'] ?? 'Unknown'));
            }

            // 2. Update Refund Record
            $updateRefund = "UPDATE Refund 
                            SET refund_status = 'Processed',
                                paymongo_refund_id = :paymongo_refund_id,
                                refund_approval_date = NOW()
                            WHERE refund_ID = :refund_ID";

            $ur = $db->prepare($updateRefund);
            $ur->execute([
                ':paymongo_refund_id' => $payMongoResult['refund_id'],
                ':refund_ID' => $refund_ID
            ]);

            // 3. Update Transaction
            $updateTrans = "UPDATE Payment_Transaction 
                            SET transaction_status = 'Refunded',
                                paymongo_refund_id = :refund_id,
                                refunded_at = NOW()
                            WHERE transaction_ID = :transaction_ID";

            $ut = $db->prepare($updateTrans);
            $ut->execute([
                ':refund_id' => $payMongoResult['refund_id'],
                ':transaction_ID' => $refund['transaction_ID']
            ]);

            // 4. Update Booking Status
            $this->updateBookingStatus($refund['booking_ID'] ?? null, 'Cancelled - Refunded');

            // 5. Log
            $this->activity->adminApprovedRefund($refund_ID, $payMongoResult['refund_id']);

            $db->commit();

            return [
                'success' => true,
                'message' => "Refund processed successfully!",
                'paymongo_refund_id' => $payMongoResult['refund_id'],
                'amount_refunded' => $refund['refund_total_amount']
            ];

        } catch (Exception $e) {
            $db->rollBack();
            error_log("REFUND PROCESSING FAILED [Refund #$refund_ID]: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

}
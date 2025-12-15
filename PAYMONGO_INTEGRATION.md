# Paymongo Payment Integration

## Overview
This document describes the Paymongo payment integration for the Tour Guide Zamboanga system. The integration allows tourists to securely process card payments for tour bookings.

## Architecture

### Components

1. **Payment Form** (`pages/tourist/payment-form.php`)
   - Collects payment information from tourists
   - Displays booking summary and fee breakdown
   - Handles form submission and Paymongo processing
   - Shows error messages if payment fails

2. **PaymentManager Class** (`classes/payment-manager.php`)
   - Orchestrates payment processing
   - Uses PayMongoTrait for Paymongo API integration
   - Manages database transactions

3. **PayMongoTrait** (`classes/trait/payment-info/paymongo.php`)
   - Handles all Paymongo API calls
   - Creates payment methods and payment intents
   - Processes refunds
   - Manages payment status updates

4. **Webhook Handler** (`pages/tourist/payment-webhook.php`)
   - Receives webhook events from Paymongo
   - Updates payment status in database
   - Updates booking status based on payment result

5. **Success Page** (`pages/tourist/payment-success.php`)
   - Displays payment confirmation
   - Shows booking details and transaction reference
   - Provides links to itinerary and bookings

6. **Failure Page** (`pages/tourist/payment-failed.php`)
   - Displays payment failure message
   - Provides troubleshooting tips
   - Allows retry or return to bookings

## Payment Flow

```
1. Tourist fills payment form (payment-form.php)
   ↓
2. Form submitted with payment details
   ↓
3. Payment info saved to database
   ↓
4. PayMongoTrait processes payment:
   - Create Payment Method (tokenize card)
   - Create Payment Intent
   - Attach Payment Method to Intent
   ↓
5. If successful → Redirect to payment-success.php
   If failed → Show error and allow retry
   ↓
6. Paymongo sends webhook to payment-webhook.php
   ↓
7. Webhook updates transaction status
   ↓
8. Booking status updated to "Confirmed"
```

## Configuration

### Environment Variables

Create a `.env` file or `paymongo.env` file in the root directory with:

```
PAYMONGO_SECRET_KEY=sk_test_xxxxxxxxxxxxx
PAYMONGO_PUBLIC_KEY=pk_test_xxxxxxxxxxxxx
PAYMENT_RETURN_URL=https://yourdomain.com/pages/tourist/payment-success.php
PAYMENT_SUCCESS_URL=https://yourdomain.com/pages/tourist/payment-success.php
PAYMENT_FAILED_URL=https://yourdomain.com/pages/tourist/payment-failed.php
```

### Database Schema

The following tables are used:

- **Payment_Transaction**: Stores transaction details and Paymongo intent IDs
- **Method**: Stores payment method details
- **Booking**: Stores booking information with status

Key columns in Payment_Transaction:
- `transaction_ID`: Primary Key
- `booking_ID`: Reference to Booking table
- `transaction_total_amount`: Payment amount in decimal format
- `transaction_status`: Status (Pending, Paid, Failed, Refunded)
- `transaction_created_date`: Timestamp of transaction creation
- `transaction_updated_date`: Timestamp of last update
- `paymongo_intent_id`: PayMongo Payment Intent ID
- `paymongo_refund_id`: PayMongo Refund ID for refund tracking

## API Integration

### Creating a Payment

```php
$result = $paymentObj->processPayMongoPayment(
    $amountInCentavos,      // Amount * 100
    'PHP',                  // Currency
    $cardNumber,            // Card number
    $expMonth,              // Expiration month (MM)
    $expYear,               // Expiration year (YYYY)
    $cvc,                   // CVC code
    $billingName,           // Cardholder name
    $billingEmail,          // Email
    $billingPhone,          // Phone
    $billingLine1,          // Address line 1
    $billingCity,           // City
    $billingPostalCode,     // Postal code
    $billingCountry,        // Country
    "Booking #$booking_ID", // Description
    ['booking_id' => $booking_ID] // Metadata
);

if ($result['success']) {
    // Payment succeeded
    $paymentIntentId = $result['payment_intent_id'];
} else {
    // Payment failed
    $error = $result['error'];
}
```

### Processing Refunds

```php
$refundResult = $paymentObj->refundPayMongoPayment(
    $paymentIntentId,
    $refundAmount,
    'Customer requested refund'
);

if ($refundResult['success']) {
    $refundId = $refundResult['refund_id'];
}
```

## Webhook Setup

### Configure Webhook in Paymongo Dashboard

1. Go to Paymongo Dashboard → Webhooks
2. Add new webhook with URL: `https://yourdomain.com/pages/tourist/payment-webhook.php`
3. Subscribe to events:
   - `payment.paid`
   - `payment.failed`

### Webhook Events Handled

- **payment.paid**: Updates transaction status to "Paid" and booking to "Confirmed"
- **payment.failed**: Updates transaction status to "Failed"

## Error Handling

### Common Errors

1. **Invalid Card**: Card number, expiry, or CVC is incorrect
2. **Insufficient Funds**: Card doesn't have enough balance
3. **Card Declined**: Card issuer declined the transaction
4. **Network Error**: Connection issue with Paymongo API

### Error Messages

All errors are logged to PHP error log and displayed to user on the payment form.

## Testing

### Test Card Numbers

Use these test cards in sandbox mode:

- **Visa (Success)**: 4242 4242 4242 4242
- **Visa (Failure)**: 4000 0000 0000 0002
- **Mastercard (Success)**: 5555 5555 5555 4444
- **Mastercard (Failure)**: 5105 1051 0510 5100

Use any future expiry date and any 3-digit CVC.

## Security Considerations

1. **PCI Compliance**: Never store full card numbers in database
2. **HTTPS Only**: All payment pages must use HTTPS
3. **Environment Variables**: Store API keys in environment, not in code
4. **Input Validation**: All user inputs are validated and sanitized
5. **Database Transactions**: Payment operations use database transactions for consistency

## Troubleshooting

### Payment Not Processing

1. Check Paymongo API keys are correct
2. Verify HTTPS is enabled
3. Check payment-webhook.php is accessible
4. Review error logs in PHP error log

### Webhook Not Updating Status

1. Verify webhook URL is publicly accessible
2. Check webhook is configured in Paymongo dashboard
3. Review webhook logs in Paymongo dashboard
4. Check database connection in payment-webhook.php

### Card Details Not Accepted

1. Verify card format (no spaces or special characters)
2. Check expiry date is in future
3. Ensure CVC is 3-4 digits
4. Try different test card

## Files Modified/Created

### Modified Files
- `classes/payment-manager.php` - Added PayMongoTrait
- `pages/tourist/payment-form.php` - Integrated Paymongo processing

### New Files
- `pages/tourist/payment-webhook.php` - Webhook handler
- `pages/tourist/payment-success.php` - Success page
- `pages/tourist/payment-failed.php` - Failure page
- `PAYMONGO_INTEGRATION.md` - This documentation

## Support

For issues or questions:
1. Check Paymongo API documentation: https://developers.paymongo.com/
2. Review error logs in PHP error log
3. Check Paymongo dashboard for webhook logs
4. Contact Paymongo support if API issue persists

## Future Enhancements

1. Add support for e-wallet payments (GCash, GrabPay)
2. Implement 3D Secure for additional security
3. Add payment history and receipts
4. Implement automatic refund processing
5. Add payment retry logic

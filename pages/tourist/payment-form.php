<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role_name'] !== 'Tourist') {
    header('Location: ../../index.php');
    exit;
} else if ($_SESSION['user']['account_status'] == 'Suspended') {
    header('Location: account-suspension.php');
    exit;
} else if ($_SESSION['user']['account_status'] == 'Pending') {
    header('Location: account-pending.php');
    exit;
}

require_once "../../classes/tourist.php";
require_once "../../classes/payment-manager.php";
require_once "../../classes/booking.php";
require_once "../../classes/activity-log.php";

$tourist_ID = $_SESSION['user']['account_ID'];
$booking_ID = $_GET['id'] ?? null;


if (!$booking_ID || !is_numeric($booking_ID)) {
    die("Invalid booking ID.");
} 

$touristObj = new Tourist();
$paymentObj = new PaymentManager();
$bookingObj = new Booking();
$activityObj = new ActivityLogs();

$hasPaymentTransaction = $paymentObj->hasPaymentTransaction($booking_ID);
if($hasPaymentTransaction < 0){
    header('Location: booking.php');
    exit;
}

$booking = $bookingObj->viewBookingByTouristANDBookingID($booking_ID);
$companions = $bookingObj->getCompanionsByBooking($booking_ID);
$companionBreakdown = $bookingObj->getCompanionBreakdown($booking_ID);
$countryCodes = $touristObj->fetchCountryCode();
 
$categories = ['Infant', 'Child', 'Young Adult', 'Adult', 'Senior', 'PWD'];
$mealFee = (float)($booking['pricing_mealfee'] ?? 0);
$transportFee = (float)($booking['transport_fee'] ?? 0);
$self_included = (int)($booking['booking_isselfincluded'] ?? 0);
$userCategory = $touristObj->getTouristCategory($tourist_ID);
$userPrice = $touristObj->getPricingOfTourist($userCategory, $booking_ID);
$totalNumberOfPeople = $self_included + count($companions);
$max_people = (int)($booking['numberofpeople_maximum'] ?? 0);
$discount = (float)($booking['pricing_discount'] ?? 0);

$methodCategories = $paymentObj->viewAllPaymentMethodCategory();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 
    $methodcategory_ID = $_POST['methodcategory_ID'] ?? null;
    $method_amount = $_POST['method_amount'] ?? 0;
    $method_currency = 'PHP';
    $method_cardnumber = $_POST['method_cardnumber'] ?? null;
    $method_expmonth = $_POST['method_expmonth'] ?? null;
    $method_expyear = $_POST['method_expyear'] ?? null;
    $method_cvc = $_POST['method_cvc'] ?? null;
    $method_name = trim($_POST['method_name'] ?? '');
    $method_email = trim($_POST['method_email'] ?? '');
    $method_line1 = trim($_POST['method_line1'] ?? '');
    $method_city = trim($_POST['method_city'] ?? '');
    $method_postalcode = trim($_POST['method_postalcode'] ?? '');
    $method_country = trim($_POST['method_country'] ?? '');
    $country_ID = $_POST['country_ID'] ?? '';
    $phone_number = trim($_POST['phone_number'] ?? '');
    $method_status = $_POST['method_status'] ?? 'Pending';
 
    $methodcategory_processing_fee = (float)($_POST['methodcategory_processing_fee'] ?? 0);
    $paymentinfo_total_amount = (float)$method_amount + $methodcategory_processing_fee;
 
    $method_ID = null;
 
    $required_fields = [
        'methodcategory_ID' => $methodcategory_ID,
        'method_name' => $method_name,
        'method_email' => $method_email,
        'method_line1' => $method_line1,
        'method_city' => $method_city,
        'method_postalcode' => $method_postalcode,
        'method_country' => $method_country,
        'country_ID' => $country_ID,
        'phone_number' => $phone_number
    ];

    foreach ($required_fields as $field => $value) {
        if (empty($value)) {
            $error[$field] = "Require Missing $field";
        }
    }

    try { 
        // Step 1: Save payment info to database first
        $result = $paymentObj->addAllPaymentInfo($booking_ID, $paymentinfo_total_amount,
            $method_ID, $methodcategory_ID, $method_amount,
            $method_currency, $method_cardnumber, $method_expmonth, $method_expyear,
            $method_cvc, $method_name, $method_email, $method_line1,
            $method_city, $method_postalcode, $method_country,
            $country_ID, $phone_number );

        if ($result) {
            // Step 2: Get the payment info ID for Paymongo processing
            $paymentData = $paymentObj->getPaymentByBooking($booking_ID);
            $paymentinfo_ID = $paymentData['paymentinfo_ID'] ?? null;

            if ($paymentinfo_ID && $method_cardnumber) { 
                $paymongoResult = $paymentObj->processPayMongoPayment(
                    $paymentinfo_total_amount * 100, // Convert to centavos
                    $method_currency,
                    $method_cardnumber,
                    $method_expmonth,
                    $method_expyear,
                    $method_cvc,
                    $method_name,
                    $method_email,
                    $phone_number,
                    $method_line1,
                    $method_city,
                    $method_postalcode,
                    $method_country,
                    "Booking #$booking_ID",
                    ['booking_id' => $booking_ID, 'tourist_id' => $tourist_ID]
                );

                if ($paymongoResult['success']) {
                    // Payment succeeded
                    $paymentResolve = $activityObj->touristpayment($booking_ID, $tourist_ID);
                    header("Location: itinerary-view.php?id=" . urlencode($booking_ID));
                    exit;
                } else {
                    // Payment failed
                    $errors['payment'] = "Payment processing failed: " . ($paymongoResult['error'] ?? 'Unknown error');
                }
            } else {
                // No card number or payment ID - payment saved but not processed
                $paymentResolve = $activityObj->touristpayment($booking_ID, $tourist_ID);
                header("Location: itinerary-view.php?id=" . urlencode($booking_ID));
                exit;
            }
        } else {
            $errors['payment'] = "Failed to save payment information.";
        }

    } catch (Exception $e) {
        $errors['payment'] = "Error processing payment: " . htmlspecialchars($e->getMessage());
    }

}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tourismo Zamboanga</title> 
    <link rel="stylesheet" href="../../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/vendor/bootstrap-icons/bootstrap-icons.css" >
    <!-- <link rel="stylesheet" href="../../assets/css/tourist/index.css"> -->
    <link rel="stylesheet" href="../../assets/css/tourist/header.css">
    <link rel="stylesheet" href="../../assets/css/tourist/header.css">
    <style>
    .payment-container {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
        max-width: 1400px;
        margin: 30px auto;
        padding: 0 20px;
    }

    .section-card {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.08);
        padding: 25px;
        height: fit-content;
    }

    .section-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1a5d1a;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 3px solid #e0f2e0;
    }

    .booking-info-grid {
        display: grid;
        grid-template-columns: max-content 1fr;
        gap: 12px 20px;
        margin-bottom: 20px;
        font-size: 1rem;
    }

    .booking-info-grid strong {
        color: #2c3e50;
    }

    #feeBreakdown {
        width: 100%;
        border-collapse: collapse;
        margin: 20px 0;
        font-size: 0.95rem;
    }

    #feeBreakdown th {
        background: #f8f9fa;
        text-align: left;
        padding: 12px;
        font-weight: 600;
        color: #2c3e50;
    }

    #feeBreakdown td {
        padding: 10px 12px;
        border-bottom: 1px solid #eee;
    }

    #feeBreakdown .total-row {
        font-weight: bold;
        font-size: 1.1rem;
        background: #f0f8f0;
    }

    #feeBreakdown .total-row td {
        padding: 16px 12px;
    }

    .payment-form {
        position: sticky;
        top: 20px;
    }

    .pay-btn {
        width: 100%;
        padding: 16px;
        font-size: 1.2rem;
        font-weight: bold;
        background: #1a5d1a;
        color: white;
        border: none;
        border-radius: 12px;
        cursor: pointer;
        margin-top: 25px;
        transition: all 0.3s;
    }

    .pay-btn:hover {
        background: #146b3a;
        transform: translateY(-2px);
    }

    .form-group {
        margin-bottom: 18px;
    }

    .form-group label {
        display: block;
        margin-bottom: 6px;
        font-weight: 600;
        color: #2c3e50;
    }

    .form-group input,
    .form-group select {
        width: 100%;
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 1rem;
    }

    .form-group input:focus,
    .form-group select:focus {
        outline: none;
        border-color: #1a5d1a;
        box-shadow: 0 0 0 3px rgba(26, 93, 26, 0.1);
    }

    .row {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 15px;
    }

    .address-section {
        background: #f9f9f9;
        padding: 20px;
        border-radius: 12px;
        margin: 20px 0;
    }

    .address-section legend {
        font-weight: 600;
        color: #1a5d1a;
        padding: 0 10px;
    }

    @media (max-width: 992px) {
        .payment-container {
            grid-template-columns: 1fr;
        }
        .payment-form {
            position: static;
        }
    }

    .highlight-box {
        background: linear-gradient(135deg, #e3f9e3, #f0fff0);
        padding: 20px;
        border-radius: 12px;
        border-left: 5px solid #1a5d1a;
        margin: 20px 0;
        font-size: 1.1rem;
    }
</style>
</head>
<body>

    <?php include 'includes/header.php'; ?>

<main class="payment-container">

    <!-- ERROR MESSAGES -->
    <?php if (!empty($errors)): ?>
        <div style="grid-column: 1 / -1; background: #fee; border: 1px solid #fcc; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
            <h3 style="color: #c33; margin: 0 0 10px 0;">⚠️ Payment Error</h3>
            <?php foreach ($errors as $error): ?>
                <p style="margin: 5px 0; color: #c33;"><?= htmlspecialchars($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- LEFT SIDE: Booking Details + Fee Breakdown -->
    <div class="section-card">
        <h2 class="section-title">Booking Summary</h2>

        <div class="booking-info-grid">
            <strong>Package Name:</strong> 
            <span><?= htmlspecialchars($booking['tourpackage_name'] ?? '') ?></span>

            <strong>Tour Guide:</strong>
            <span><?= htmlspecialchars($booking['guide_name'] ?? 'Not Assigned') ?></span>

            <strong>Travel Dates:</strong>
            <span><?= date('M j, Y', strtotime($booking['booking_start_date'])) ?> → <?= date('M j, Y', strtotime($booking['booking_end_date'])) ?></span>

            <strong>Total People:</strong>
            <span><strong><?= $totalNumberOfPeople ?></strong> / <?= $booking['numberofpeople_maximum'] ?> max</span>
        </div>

        <?php if ($totalNumberOfPeople > 0 && !empty($companions)): ?>
        <h3 style="margin: 25px 0 10px; color: #1a5d1a;">Travel Companions</h3>
        <ul style="margin: 0; padding-left: 20px;">
            <?php foreach ($companions as $c): ?>
                <li style="margin: 8px 0;">
                    <?= htmlspecialchars($c['companion_name']) ?> 
                    <small style="color: #666;">
                        (<?= $c['companion_age'] ?> yrs • <?= $c['companion_category_name'] ?>)
                    </small>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>

        <h3 class="section-title" style="margin-top: 30px;">Fee Breakdown</h3>
        <table id="feeBreakdown">
            <thead>
                <tr>
                    <th>Category</th>
                    <th style="text-align: right;">Qty</th>
                    <th style="text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody id="feeBreakdownBody"></tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="2"><strong>Grand Total (After Discount)</strong></td>
                    <td style="text-align: right; font-size: 1.2rem; color: #1a5d1a;" id="grandTotal">₱0.00</td>
                </tr>
            </tfoot>
        </table>

        <div class="highlight-box">
            <strong>Total Amount to Pay:</strong> 
            <span id="finalPayable" style="font-size: 1.4rem; color: #1a5d1a;">₱0.00</span>
        </div>
    </div>

    <!-- RIGHT SIDE: Payment Form -->
    <div class="section-card payment-form">
        <h2 class="section-title">Complete Your Payment</h2>

        <form id="paymentForm" method="POST">
            <div class="form-group">
                <label for="methodcategory_ID">Payment Method</label>
                <select name="methodcategory_ID" id="methodcategory_ID" required>
                    <option value="">-- Choose Payment Method --</option>
                    <?php foreach ($methodCategories as $category): ?>
                        <option value="<?= $category['methodcategory_ID'] ?>"
                                data-type="<?= strtolower($category['methodcategory_type']) ?>"
                                data-fee="<?= $category['methodcategory_processing_fee'] ?>">
                            <?= htmlspecialchars($category['methodcategory_name']) ?>
                            <?php if($category['methodcategory_processing_fee'] > 0): ?>
                                (+₱<?= number_format($category['methodcategory_processing_fee'], 2) ?> fee)
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Processing Fee</label>
                <input type="text" id="methodcategory_processing_fee" readonly value="₱0.00">
                <input type="hidden" name="methodcategory_processing_fee" value="0">
            </div>

            <div class="form-group">
                <label><strong>Total Amount to Pay</strong></label>
                <input type="text" id="method_amount" readonly style="font-size: 1.4rem; font-weight: bold; color: #1a5d1a;">
                <input type="hidden" name="method_amount" value="0">
            </div>

            <div class="form-group">
                <label>Name on Card / Account</label>
                <input type="text" name="method_name" required placeholder="Juan Dela Cruz">
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="method_email" required placeholder="juan@example.com">
            </div>

            <div class="form-group">
                <label>Phone Number</label>
                <div style="display: flex; gap: 10px;">
                    <select name="country_ID" id="country_ID" style="width: 40%;">
                        <option value="">Code</option>
                        <?php foreach ($touristObj->fetchCountryCode() as $c): ?>
                            <option value="<?= $c['country_ID'] ?>" 
                                <?= ($c['country_codenumber'] == '+63') ? 'selected' : '' ?>>
                                <?= $c['country_name'] ?> (<?= $c['country_codenumber'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="phone_number" maxlength="10" pattern="[0-9]{10}" 
                           placeholder="9123456789" required style="flex: 1;">
                </div>
            </div>

            <fieldset class="address-section">
                <legend>Billing Address</legend>
                <input type="text" name="method_line1" placeholder="Street Address" required>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 10px;">
                    <input type="text" name="method_city" placeholder="City" required>
                    <input type="text" name="method_postalcode" placeholder="Postal Code" required>
                </div>
                <input type="text" name="method_country" value="Philippines" readonly>
            </fieldset>

            <!-- Card Section -->
            <div id="cardSection" style="display:none; margin-top: 20px;">
                <h3 style="color: #1a5d1a; margin-bottom: 15px;">Card Details</h3>
                <div class="form-group">
                    <label>Card Number</label>
                    <input type="text" name="method_cardnumber" maxlength="19" placeholder="1234 5678 9012 3456">
                </div>
                <div class="row">
                    <div class="form-group">
                        <label>MM</label>
                        <input type="text" name="method_expmonth" maxlength="2" placeholder="09">
                    </div>
                    <div class="form-group">
                        <label>YYYY</label>
                        <input type="text" name="method_expyear" maxlength="4" placeholder="2027">
                    </div>
                    <div class="form-group">
                        <label>CVC</label>
                        <input type="text" name="method_cvc" maxlength="4" placeholder="123">
                    </div>
                </div>
            </div>

            <input type="hidden" name="method_status" value="Pending">
            <button type="submit" class="pay-btn">
                Pay Now • <span id="payButtonAmount">₱0.00</span>
            </button>
        </form>
    </div>
</main>


<script> 
    const bookingData = {
        companions: <?= json_encode($companionBreakdown) ?>,
        mealFee: <?= $mealFee ?>,
        transportFee: <?= $transportFee ?>,
        self_included: <?= $self_included ?>,
        userCategory: <?= json_encode($userCategory) ?>,
        userPrice: <?= $userPrice ?>,
        discount: <?= $discount ?>
    };

    function calculateFees() {
        console.log('Initial booking data:', bookingData);
        const summary = {};
        let grandTotal = 0;
 
        ['Infant', 'Child', 'Young Adult', 'Adult', 'Senior', 'PWD'].forEach(cat => {
            summary[cat] = { qty: 0, baseTotal: 0 };
        });
 
        if (bookingData.self_included) {
            console.log('Adding self:', {
                category: bookingData.userCategory,
                price: bookingData.userPrice
            });
            summary[bookingData.userCategory].qty += 1;
            summary[bookingData.userCategory].baseTotal += parseFloat(bookingData.userPrice);
        }
 
        bookingData.companions.forEach(comp => {
            const category = comp.category || 'Adult';
            const qty = parseInt(comp.qty);
            const total = parseFloat(comp.total);
            console.log('Adding companion:', { category, qty, total });
            summary[category].qty += qty;
            summary[category].baseTotal += total;
        }); 
        const tbody = document.getElementById('feeBreakdownBody');
        tbody.innerHTML = '';

        for (const [category, data] of Object.entries(summary)) {
            if (data.qty > 0) { 
                const baseRow = document.createElement('tr');
                baseRow.innerHTML = `
                    <td>${category}</td>
                    <td style="text-align: right">${data.qty}</td>
                    <td style="text-align: right">₱${data.baseTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                `;
                tbody.appendChild(baseRow);

                let categorySubtotal = data.baseTotal;
                console.log(`${category} base total:`, data.baseTotal);
 
                const mealTotal = category === 'Infant' ? 0 : bookingData.mealFee * data.qty;
                console.log(`${category} meal total:`, mealTotal);

                const mealRow = document.createElement('tr');
                mealRow.innerHTML = `
                    <td style="padding-left: 20px;">Meal Fee</td>
                    <td style="text-align: right">${data.qty}</td>
                    <td style="text-align: right">₱${mealTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                `;
                tbody.appendChild(mealRow);
                categorySubtotal += mealTotal;
 
                let transportTotal;
                if (category === 'Infant') {
                    transportTotal = 0;
                } else if (category === 'Child') {
                    transportTotal = (bookingData.transportFee * 0.5) * data.qty;
                } else {
                    transportTotal = bookingData.transportFee * data.qty;
                }
                console.log(`${category} transport total:`, transportTotal);

                const transportRow = document.createElement('tr');
                transportRow.innerHTML = `
                    <td style="padding-left: 20px;">Transport Fee</td>
                    <td style="text-align: right">${data.qty}</td>
                    <td style="text-align: right">₱${transportTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                `;
                tbody.appendChild(transportRow);
                categorySubtotal += transportTotal;

                console.log(`${category} subtotal:`, categorySubtotal);
                grandTotal += categorySubtotal;
            }
        }
 
        if (bookingData.discount > 0) {
            const discountRow = document.createElement('tr');
            discountRow.innerHTML = `
                <td>Discount</td>
                <td style="text-align: right">-</td>
                <td style="text-align: right">-₱${bookingData.discount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
            `;
            tbody.appendChild(discountRow);
        }
 
        console.log('Grand total before discount:', grandTotal);
        console.log('Discount amount:', bookingData.discount);
        grandTotal -= bookingData.discount;
        console.log('Final grand total:', grandTotal);
 
        document.getElementById('grandTotal').textContent = 
            `₱${grandTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
    }
 
    document.addEventListener('DOMContentLoaded', calculateFees);
 
    function forceCountryForEwallet(lock = false) {
        const countrySelect = document.getElementById("country_ID");
        if (!countrySelect) return;

        if (lock) { 
            for (let option of countrySelect.options) {
                const text = option.textContent.toLowerCase();
                if (text.includes("philippines") || text.includes("+63")) {
                    countrySelect.value = option.value;
                    break;
                }
            }
            countrySelect.disabled = true;  
            console.log("✅ Country locked to Philippines (E-wallet selected).");
        } else {
            countrySelect.disabled = false;  
            console.log("🔓 Country dropdown unlocked.");
        }
    }

        document.getElementById('methodcategory_ID').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const type = selectedOption.getAttribute('data-type');
            const selected = this.options[this.selectedIndex];
            const fee = parseFloat(selected.getAttribute('data-fee')) || 0;
            const grandTotal = parseFloat(document.getElementById('grandTotal').textContent.replace(/[₱,]/g, '')) || 0;

            document.getElementById('methodcategory_processing_fee').value = fee.toFixed(2);
            document.getElementById('method_amount').value = (grandTotal + fee).toFixed(2); 
            document.querySelectorAll('.payment-type-section').forEach(section => {
                section.style.display = 'none';
            });
 
            if (type === 'card') {
                document.getElementById('cardSection').style.display = 'block';
                forceCountryForEwallet(false);
            } else if (type === 'ewallet') {
                document.getElementById('ewalletSection').style.display = 'block';        
                forceCountryForEwallet(true);
            } else if (type === 'bank') {
                document.getElementById('bankSection').style.display = 'block';
                forceCountryForEwallet(false);
            }
            
        });

    function forceCountryForEwallet(shouldLock) {
        const countrySelect = document.getElementById("country_ID");
        if (!countrySelect) {
            console.warn("  No country select element found!");
            return;
        }

        if (shouldLock) {
            let found = false;
            for (let option of countrySelect.options) {
                const text = option.textContent.toLowerCase();
                if (text.includes("philippines") || text.includes("+63")) {
                    countrySelect.value = option.value;  
                    found = true;
                    break;
                }
            }

            if (found) {
                countrySelect.disabled = true;
                console.log("  Country locked to Philippines.");
            } else {
                console.warn("  Philippines not found in dropdown.");
            }
        } else {
            countrySelect.disabled = false;  
            console.log(" Country dropdown unlocked.");
        }
    }

    function updatePaymentAmount() {
    const grandTotalText = document.getElementById('grandTotal').textContent;
    const baseAmount = parseFloat(grandTotalText.replace(/[₱,]/g, '')) || 0;
    const fee = parseFloat(document.querySelector('[name="methodcategory_processing_fee"]').value) || 0;
    const total = baseAmount + fee;

    document.getElementById('method_amount').value = total.toFixed(2);
    document.querySelector('[name="method_amount"]').value = total.toFixed(2);
    document.getElementById('finalPayable').textContent = '₱' + total.toLocaleString('en-US', {minimumFractionDigits: 2});
    document.getElementById('payButtonAmount').textContent = '₱' + total.toLocaleString('en-US', {minimumFractionDigits: 2});
    document.querySelector('[name="methodcategory_processing_fee"]').value = fee;
}

document.getElementById('methodcategory_ID').addEventListener('change', function() {
    const fee = parseFloat(this.selectedOptions[0].dataset.fee) || 0;
    document.getElementById('methodcategory_processing_fee').value = '₱' + fee.toFixed(2);
    document.querySelector('[name="methodcategory_processing_fee"]').value = fee;

    // Show/hide sections
    document.querySelectorAll('.payment-type-section, #cardSection').forEach(s => s.style.display = 'none');
    if (this.selectedOptions[0].dataset.type === 'card') {
        document.getElementById('cardSection').style.display = 'block';
    }

    updatePaymentAmount();
});

// Call after calculateFees()
const originalCalculateFees = calculateFees;
calculateFees = function() {
    originalCalculateFees();
    updatePaymentAmount();
};

</script>

</body>
</html>

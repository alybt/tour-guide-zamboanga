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
        $result = $paymentObj->addAllPaymentInfo($booking_ID, $paymentinfo_total_amount,
            $method_ID, $methodcategory_ID, $method_amount,
            $method_currency, $method_cardnumber, $method_expmonth, $method_expyear,
            $method_cvc, $method_name, $method_email, $method_line1,
            $method_city, $method_postalcode, $method_country,
            $country_ID, $phone_number );

        if ($result) {
            $paymentResolve = $activityObj->touristpayment($booking_ID, $tourist_ID);
            header("Location: itinerary-view.php?id=" . urlencode($booking_ID));
            exit;
        } else {
            echo "❌ Failed to save payment information.";
        }

    } catch (Exception $e) {
        echo "⚠️ Error saving payment info: " . htmlspecialchars($e->getMessage());
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
</head>
<body>

    <?php include 'includes/header.php'; ?>

<main>
    <h1>Booking Details</h1>
        <p><strong>Package Name:</strong> <?= htmlspecialchars($booking['tourpackage_name'] ?? '') ?></p>
        <p><strong>Description:</strong> <?= htmlspecialchars($booking['tourpackage_desc'] ?? '') ?></p>
        <p><strong>Schedule Days:</strong> <?= htmlspecialchars($booking['schedule_days'] ?? '') ?></p>
        <p><strong>Start Date:</strong> <?= htmlspecialchars($booking['booking_start_date'] ?? '') ?></p>
        <p><strong>End Date:</strong> <?= htmlspecialchars($booking['booking_end_date'] ?? '') ?></p>
        <p><strong>Tour Guide:</strong> <?= htmlspecialchars($booking['guide_name'] ?? '') ?></p>
        <p><strong>Number of People:</strong> <?= $totalNumberOfPeople ?>/<?= htmlspecialchars($booking['numberofpeople_maximum'] ?? '') ?></p>

        <?php if ($totalNumberOfPeople > 0 && ($max_people > 1 || !$self_included)): ?>
        <h2>Companion Details</h2>
        <?php if (!empty($companions)): ?>
        <ul>
        <?php foreach ($companions as $c): ?>
            <li><?= htmlspecialchars($c['companion_name'] ?? 'Unknown') ?> (<?= htmlspecialchars($c['companion_age'] ?? 'N/A') ?> yrs, <?= htmlspecialchars($c['companion_category_name'] ?? 'Unknown') ?>)</li>
        <?php endforeach; ?>
        </ul>
        <?php else: ?>
        <p>No companions added.</p>
        <?php endif; ?>
        <?php endif; ?>

    <h2>Category & Fee Breakdown</h2>
    <table id="feeBreakdown" border="1" cellpadding="5" cellspacing="0">
        <tr>
            <th>Category</th>
            <th>Qty</th>
            <th>Total</th>
        </tr>
        <tbody id="feeBreakdownBody"></tbody>
        <tr class="total-row">
            <td colspan="2">Grand Total (After Discount)</td>
            <td style="text-align: right;" id="grandTotal">₱0.00</td>
        </tr>
    </table>
    <form id="paymentForm" method="POST" class="payment-form">
        <h2>💳 Payment Section</h2>

        <!-- Payment Category -->
        <label for="methodcategory_ID">Select Payment Method</label>
        <select name="methodcategory_ID" id="methodcategory_ID" required>
            <option value="">-- Choose Payment Method --</option>
            <?php foreach ($methodCategories as $category): ?>
                <option value="<?= htmlspecialchars($category['methodcategory_ID']) ?>"
                        data-type="<?= htmlspecialchars($category['methodcategory_type']) ?>"
                        data-name="<?= htmlspecialchars(strtolower($category['methodcategory_name'])) ?>"
                        data-fee="<?= htmlspecialchars($category['methodcategory_processing_fee']) ?>">
                    <?= htmlspecialchars($category['methodcategory_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <!-- Common Fields -->
        <div class="common-section">
            <label for="methodcategory_processing_fee">Processing Fee (₱)</label>
            <input type="number" step="0.01" name="methodcategory_processing_fee" id="methodcategory_processing_fee" readonly>


            <label>Total Amount (₱)</label>
            <input type="number" step="0.01" name="method_amount" id="method_amount" readonly>
            
            <br>
            <label>Name</label>
            <input type="text" name="method_name" required>

            <label>Email</label>
            <input type="email" name="method_email" required>
            <br>
            <label>Phone</label>
            <select name="country_ID" id="country_ID">
                <option value="">--SELECT COUNTRY CODE--</option>
                
                    
                <?php foreach ($touristObj->fetchCountryCode() as $country_code){ 
                    $temp = $country_code["country_ID"];
                ?>
                <option value="<?= $temp ?>" <?= ($temp == ($tourist["country_ID"] ?? "")) ? "selected" : "" ?>> <?= $country_code["country_name"] ?> <?= $country_code["country_codenumber"]?> </option> 
            <?php } ?>
            </select>
            <input type="text" name="phone_number" id="phone_number" maxlength="10" inputmode="numeric" pattern="[0-9]*" value = "<?= $tourist["phone_number"] ?? "" ?>">
            <p style="color: red; font-weight: bold;"> <?= $errors["phone_number"] ?? "" ?> </p>
            
        </div>

        <!-- Address Section -->
        <fieldset class="address-section">
            <legend>Billing Address</legend>
            <input type="text" name="method_line1" placeholder="Street Address" required>
            <input type="text" name="method_city" placeholder="City" required>
            <input type="text" name="method_postalcode" placeholder="Postal Code" required>
            <input type="text" name="method_country" placeholder="Country" required>
        </fieldset>

        <!-- Card Fields -->
        <div id="cardSection" class="payment-type-section" style="display:none;">
            <h3>Card Information</h3>
            <label>Card Number</label>
            <input type="text" name="method_cardnumber" maxlength="16" placeholder="1234 5678 9012 3456">
            
            <div class="row">
                <div>
                    <label>Expiry Month</label>
                    <input type="text" name="method_expmonth" maxlength="2" placeholder="MM">
                </div>
                <div>
                    <label>Expiry Year</label>
                    <input type="text" name="method_expyear" maxlength="4" placeholder="YYYY">
                </div>
                <div>
                    <label>CVC</label>
                    <input type="text" name="method_cvc" maxlength="4" placeholder="123">
                </div>
            </div>
        </div>

        <!-- E-Wallet Fields -->
        <!-- <div id="ewalletSection" class="payment-type-section" style="display:none;">
            <h3>E-Wallet Information</h3>
            <label>E-Wallet Account / Email</label>
            <input type="text" name="ewallet_account" placeholder="e.g., yourwallet@gmail.com">
        </div> -->

        <!-- Bank Transfer Fields -->
        <div id="bankSection" class="payment-type-section" style="display:none;">
            <h3>Bank Transfer Details</h3>
            <label>Bank Name</label>
            <input type="text" name="bank_name" placeholder="e.g., BDO, BPI, Metrobank">
            <label>Reference Number</label>
            <input type="text" name="bank_reference" placeholder="Enter Reference Number">
        </div>
 
        <input type="hidden" name="method_status" value="Pending">

        <button type="submit" class="pay-btn">Proceed to Pay</button>
    </form>

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

</script>

</body>
</html>

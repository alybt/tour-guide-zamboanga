<?php
session_start(); 
if (!isset($_SESSION['user']) || $_SESSION['user']['role_name'] !== 'Tour Guide') {
    header('Location: ../../index.php');
    exit;
}

// Status-based redirects
if ($_SESSION['user']['account_status'] === 'Suspended') {
    header('Location: account-suspension.php');
    exit;
}
if ($_SESSION['user']['account_status'] === 'Pending') {
    header('Location: account-pending.php');
    exit;
}
require_once "../../classes/guide.php"; 
require_once "../../classes/payment-manager.php";  
require_once "../../classes/activity-log.php";
require_once "../../classes/tourist.php";
 

$errors = [];



$guideObj = new Guide();
$touristObj = new Tourist();
$paymentObj = new PaymentManager(); 
$activityObj = new ActivityLogs();



$guide_ID = $guideObj->getGuide_ID($_SESSION['user']['account_ID']); 

$guide_balance = $guideObj->getGuideBalanace($guide_ID);
$countryCodes = $touristObj->fetchCountryCode();
$methodCategories = $paymentObj->viewAllPaymentMethodCategory();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $methodcategory_ID = $_POST['methodcategory_ID'] ?? null;
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
    $processing_fee = (float)($_POST['methodcategory_processing_fee'] ?? 0);
    $current_balance = (float)($guide_balance['guide_balance'] ?? 0);
    $new_balance = $current_balance - $processing_fee;
    $method_amount = $processing_fee;
    $booking_ID = null;

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
            $errors[$field] = "Require Missing $field";
        }
    }

    if (empty($errors)) {
        $db = $paymentObj->connect();
        $db->beginTransaction();
        try {
            $transaction_ID = $paymentObj->addPaymentTransaction(
                null,
                $methodcategory_ID,
                $method_amount,
                $method_currency,
                $method_cardnumber,
                $method_expmonth,
                $method_expyear,
                $method_cvc,
                $method_name,
                $method_email,
                $method_line1,
                $method_city,
                $method_postalcode,
                $method_country,
                $country_ID,
                $phone_number,
                $booking_ID,
                $new_balance,
                $db
            );

            if (!$transaction_ID) {
                throw new Exception("Failed to create transaction");
            }

            $qBal = $db->prepare("SELECT guide_balance FROM Guide WHERE guide_ID = :guide_ID FOR UPDATE");
            $qBal->bindParam(':guide_ID', $guide_ID);
            $qBal->execute();
            $row = $qBal->fetch(PDO::FETCH_ASSOC);
            $balance_before = (float)($row['guide_balance'] ?? 0);

            if ($processing_fee > $balance_before) {
                throw new Exception("Insufficient balance");
            }

            $upd = $db->prepare("UPDATE Guide SET guide_balance = :balance_after WHERE guide_ID = :guide_ID");
            $upd->bindParam(':guide_ID', $guide_ID);
            $upd->bindParam(':balance_after', $new_balance);
            $upd->execute();

            if ($upd->rowCount() <= 0) {
                throw new Exception("Failed to update balance");
            }

            $hist = $db->prepare("INSERT INTO guide_money_history (guide_ID, balance_before, amount, balance_after, reference_name) VALUES (:guide_ID, :balance_before, :amount, :balance_after, 'Payout')");
            $hist->bindParam(':guide_ID', $guide_ID);
            $hist->bindParam(':balance_before', $balance_before);
            $hist->bindParam(':amount', $processing_fee);
            $hist->bindParam(':balance_after', $new_balance);
            $hist->execute();

            $db->commit();
            $_SESSION['success'] = "Payout info saved successfully.";
            header("Location: payout-request.php");
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            $errors['payment'] = "Error processing payout.";
        }
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
    :root {
        --primary-color: #ffffff;
        --secondary-color: #213638;
        --accent: #E5A13E;
        --secondary-accent: #CFE7E5;
        --muted-color: gainsboro;

        /*Booking Status Color*/
        --pending-for-payment: #F9A825 ;
        --pending-for-approval: #EF6C00 ;
        --approved: #3A8E5C;
        --in-progress: #009688;
        --completed: #1A6338;
        --cancelled: #F44336;
        --cancelled-no-refund: #BC2E2A;
        --refunded: #42325D;    
        --failed: #820000;
        --rejected-by-guide: #B71C1C;
        --booking-expired-payment-not-completed: #695985;
        --booking-expired-guide-did-not-confirm-in-time: #695985;
    }
    body{
        margin-top: 5rem;
        background: var(--muted-color);
    }
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
        border-bottom: 3px solid var(--accent);
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
        border-bottom: 1px solid var(--accent);
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
        border: 1px solid var(--accent);
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
        <h2 class="section-title">PayoutSummary</h2>
            <form method="POST">
                <input type="hidden" name="action" value="request_payout">
                <div class="mb-3">
                    <label class="form-label">Amount (max ₱<?php //number_format($balance['guide_balance'] ?? 0, 2) ?>)</label>
                    <input type="number" name="payout_amount" class="form-control" min="1" step="0.01" max="<?php // htmlspecialchars($balance['guide_balance'] ?? 0) ?>" required>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Submit Payout</button>
            </form>

        <h3 class="section-title" style="margin-top: 30px;">Fee Breakdown</h3>
        <table id="feeBreakdown">
            <thead>
                <tr>
                    <th>Balance</th>
                    <th>Payout</th>
                </tr>
            </thead>
            <tbody id="feeBreakdownBody"></tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="2"><strong>Package Total Price</strong></td>
                    <td style="text-align: right; font-size: 1.2rem; color: #1a5d1a;" id="grandTotal">₱0.00</td>
                </tr>
            </tfoot>
        </table>

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
                <input type="text" id="paymentinfo_total_amount" readonly style="font-size: 1.4rem; font-weight: bold; color: #1a5d1a;">
                <input type="hidden" name="paymentinfo_total_amount" value="0">
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

            <!-- Billing Address Section (Consistent UI) -->
            <h3 class="section-title" style="margin-top: 30px; font-size: 1.3rem;">Billing Address</h3>
            <div class="form-group">
                <label>Street Address</label>
                <input type="text" name="method_line1" placeholder="Street Address" required>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label>City</label>
                    <input type="text" name="method_city" placeholder="City" required>
                </div>
                <div class="form-group">
                    <label>Postal Code</label>
                    <input type="text" name="method_postalcode" placeholder="Postal Code" required>
                </div>
            </div>
            <div class="form-group">
                <label>Country</label>
                <input type="text" name="method_country" value="Philippines" readonly>
            </div>

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
            document.getElementById('paymentinfo_total_amount').value = (grandTotal + fee).toFixed(2); 
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

    document.getElementById('paymentinfo_total_amount').value = total.toFixed(2);
    document.querySelector('[name="paymentinfo_total_amount"]').value = total.toFixed(2);
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

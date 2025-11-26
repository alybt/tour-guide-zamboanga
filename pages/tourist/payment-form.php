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
    <link rel="stylesheet" href="../../assets/css/tourist/payment-form.css">
    <link rel="stylesheet" href="../../assets/css/tourist/header.css">
</head>
<body>

    <?php include 'includes/header.php'; ?>

<main class="container-fluid py-4">
    <div class="row g-5">
        <!-- LEFT COLUMN: Booking Summary + Fee Breakdown -->
        <div class="col-lg-6">
            <div class="summary-card">
                <h1 class="mb-4">Booking Summary</h1>

                <div class="info-grid">
                    <div><strong>Package Name:</strong></div>
                    <div><?= htmlspecialchars($booking['tourpackage_name'] ?? '') ?></div>

                    <div><strong>Description:</strong></div>
                    <div><?= htmlspecialchars($booking['tourpackage_desc'] ?? '') ?></div>

                    <div><strong>Schedule:</strong></div>
                    <div><?= htmlspecialchars($booking['schedule_days'] ?? '') ?></div>

                    <div><strong>Start Date:</strong></div>
                    <div><?= date('F j, Y', strtotime($booking['booking_start_date'])) ?></div>

                    <div><strong>End Date:</strong></div>
                    <div><?= date('F j, Y', strtotime($booking['booking_end_date'])) ?></div>

                    <div><strong>Tour Guide:</strong></div>
                    <div><?= htmlspecialchars($booking['guide_name'] ?? 'To be assigned') ?></div>

                    <div><strong>Total People:</strong></div>
                    <div><strong><?= $totalNumberOfPeople ?> / <?= $booking['numberofpeople_maximum'] ?></strong></div>
                </div>

                <?php if ($totalNumberOfPeople > 0 && !empty($companions)): ?>
                <h2 class="mt-5 mb-3">Companion Details</h2>
                <ul class="companion-list">
                    <?php foreach ($companions as $c): ?>
                        <li>
                            <span class="name"><?= htmlspecialchars($c['companion_name']) ?></span>
                            <span class="details">(<?= $c['companion_age'] ?> yrs • <?= htmlspecialchars($c['companion_category_name']) ?>)</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>

                <h2 class="mt-5 mb-3">Category & Fee Breakdown</h2>
                <table id="feeBreakdown" class="table fee-table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th class="text-end">Qty</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody id="feeBreakdownBody"></tbody>
                    <tfoot>
                        <tr class="total-row">
                            <td colspan="2"><strong>Grand Total (After Discount)</strong></td>
                            <td class="text-end"><strong id="grandTotal">₱0.00</strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- RIGHT COLUMN: Payment Form -->
        <div class="col-lg-6">
            <div class="payment-card">
                <form id="paymentForm" method="POST" class="payment-form">
                    <h2 class="mb-4 text-primary">Complete Your Payment</h2>

                    <!-- Payment Method Selection -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Select Payment Method</label>
                        <select name="methodcategory_ID" id="methodcategory_ID" class="form-select form-select-lg" required>
                            <option value="">-- Choose Payment Method --</option>
                            <?php foreach ($methodCategories as $category): ?>
                                <option value="<?= $category['methodcategory_ID'] ?>"
                                        data-type="<?= strtolower($category['methodcategory_type']) ?>"
                                        data-fee="<?= $category['methodcategory_processing_fee'] ?>">
                                    <?= htmlspecialchars($category['methodcategory_name']) ?>
                                    <?php if ($category['methodcategory_processing_fee'] > 0): ?>
                                        (+₱<?= number_format($category['methodcategory_processing_fee'], 2) ?> fee)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Processing Fee & Total -->
                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <label class="form-label">Processing Fee</label>
                            <input type="text" id="methodcategory_processing_fee" class="form-control form-control-lg text-end fw-bold text-success" readonly value="₱0.00">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Total Amount to Pay</label>
                            <input type="text" name="method_amount" id="method_amount" class="form-control form-control-lg text-end fw-bold text-primary fs-4" readonly value="₱0.00">
                        </div>
                    </div>

                    <!-- Common Fields -->
                    <div class="common-fields mb-4">
                        <input type="text" name="method_name" placeholder="Full Name on Card / Account" class="form-control mb-3" required>
                        <input type="email" name="method_email" placeholder="Email Address" class="form-control mb-3" required>

                        <div class="row g-3">
                            <div class="col-4">
                                <select name="country_ID" id="country_ID" class="form-select" required>
                                    <option value="">Code</option>
                                    <?php foreach ($touristObj->fetchCountryCode() as $c): ?>
                                        <option value="<?= $c['country_ID'] ?>" <?= ($c['country_codenumber'] == '+63') ? 'selected' : '' ?>>
                                            <?= $c['country_name'] ?> (<?= $c['country_codenumber'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-8">
                                <input type="text" name="phone_number" placeholder="9123456789" maxlength="10" class="form-control" required>
                            </div>
                        </div>
                    </div>

                    <!-- Billing Address -->
                    <fieldset class="border rounded-3 p-4 mb-4">
                        <legend class="float-none w-auto px-2 fw-bold text-muted">Billing Address</legend>
                        <input type="text" name="method_line1" placeholder="Street Address" class="form-control mb-3" required>
                        <div class="row g-3">
                            <div class="col-md-6"><input type="text" name="method_city" placeholder="City" class="form-control" required></div>
                            <div class="col-md-6"><input type="text" name="method_postalcode" placeholder="Postal Code" class="form-control" required></div>
                        </div>
                        <input type="text" name="method_country" placeholder="Country" class="form-control mt-3" value="Philippines" required>
                    </fieldset>

                    <!-- Dynamic Sections (Card, E-Wallet, Bank) -->
                    <div id="cardSection" class="payment-type-section" style="display:none;">
                        <h5 class="mb-3">Card Details</h5>
                        <input type="text" name="method_cardnumber" placeholder="1234 5678 9012 3456" maxlength="19" class="form-control mb-3">
                        <div class="row g-3">
                            <div class="col-4"><input type="text" name="method_expmonth" placeholder="MM" maxlength="2" class="form-control"></div>
                            <div class="col-4"><input type="text" name="method_expyear" placeholder="YYYY" maxlength="4" class="form-control"></div>
                            <div class="col-4"><input type="text" name="method_cvc" placeholder="CVC" maxlength="4" class="form-control"></div>
                        </div>
                    </div>

                    <div id="bankSection" class="payment-type-section" style="display:none;">
                        <h5 class="mb-3">Bank Transfer Details</h5>
                        <input type="text" name="bank_name" placeholder="Bank Name (e.g., BDO, BPI)" class="form-control mb-3">
                        <input type="text" name="bank_reference" placeholder="Reference Number" class="form-control" required>
                    </div>

                    <input type="hidden" name="method_status" value="Pending">

                    <button type="submit" class="btn btn-primary btn-lg w-100 mt-4 shadow-lg pay-btn">
                        <i class="bi bi-lock-fill me-2"></i> Proceed to Pay ₱<span id="payAmount">0.00</span>
                    </button>
                </form>
            </div>
        </div>
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

</script>

</body>
</html>

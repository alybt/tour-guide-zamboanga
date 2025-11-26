<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role_name'] !== 'Tourist') {
    header('Location: ../../index.php');
    exit;
}

require_once "../../classes/tour-manager.php";
require_once "../../classes/guide.php";
require_once "../../classes/tourist.php";
require_once "../../classes/booking.php";

$tourist_ID = $_SESSION['user']['account_ID'];

$tourManager = new TourManager();
$guideObj = new Guide();
$bookingObj = new Booking();
$touristObj = new Tourist();

$errors = [];
$rebookData = null;

// ✅ Check for rebooking
$oldBookingID = isset($_GET['id']) ? intval($_GET['id']) : 0; // old booking ID
$tourpackage_ID = isset($_GET['ref']) ? intval($_GET['ref']) : 0; // target tour package

if ($oldBookingID > 0) {
    $rebookData = $bookingObj->getBookingDetailsByBooking($oldBookingID);

    // ✅ Validate rebook ownership & status
    if (
        !$rebookData ||
        $rebookData['tourist_ID'] != $tourist_ID ||
        $rebookData['booking_status'] !== 'Cancelled'
    ) {
        $_SESSION['error'] = "Invalid or unauthorized booking to rebook.";
        header("Location: tour-packages.php");
        exit();
    }

    // ✅ If no ?ref provided, use the same package as before
    if ($tourpackage_ID <= 0) {
        $tourpackage_ID = $rebookData['tourpackage_ID'];
    }
}

// ✅ Load tour package data
$package = $tourManager->getTourPackageDetailsByID($tourpackage_ID);

if (!$package) {
    $_SESSION['error'] = "Tour package not found.";
    header("Location: tour-packages.php");
    exit();
}

$spots = $tourManager->getSpotsByPackage($tourpackage_ID);

// ✅ Get guide name
$guides = $guideObj->viewAllGuide();
$guideName = "";
foreach ($guides as $guide) {
    if ($guide['guide_ID'] == $package['guide_ID']) {
        $guideName = $guide['guide_name'];
        break;
    }
} 

// ✅ Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $errors = []; // always initialize as array

    // Initialize variables
    $is_selfIncluded = (isset($_POST['is_selfIncluded']) && $_POST['is_selfIncluded'] === 'yes') ? 1 : 0;
    $companion_names = $_POST['companion_name'] ?? [];
    $companion_categories = $_POST['companion_category'] ?? [];

    if (!is_array($companion_names)) $companion_names = [];
    if (!is_array($companion_categories)) $companion_categories = [];

    $companions_count = count($companion_names);

    $min_people = (int)$package['numberofpeople_based'];
    $max_people = (int)$package['numberofpeople_maximum'];
    $total_people = $is_selfIncluded + $companions_count;

    // Validate total people against min/max
    if ($total_people < $min_people) {
        $errors[] = "You must have at least {$min_people} person(s) in total (including yourself if selected).";
    }
    if ($total_people > $max_people) {
        $errors[] = "You can only have up to {$max_people} people (including yourself).";
    }

    // Special case: max = 1
    if ($max_people === 1) {
        if ($is_selfIncluded && $companions_count > 0) {
            $errors[] = "Only one person is allowed for this package — remove companions.";
        }
        if (!$is_selfIncluded && $companions_count === 0) {
            $errors[] = "One companion is required if you do not include yourself.";
        }
    }

    // Booking dates
    $booking_start_date = $_POST['booking_start_date'] ?? '';
    $booking_end_date = $_POST['booking_end_date'] ?? '';

    $bookings = $bookingObj->existingBookingsInGuide($package['guide_ID']);

    foreach ($bookings as $b) {
        if (
            strtotime($booking_start_date) <= strtotime($b['booking_end_date']) &&
            strtotime($b['booking_start_date']) <= strtotime($booking_end_date)
        ) {
            $errors[] = "The guide is already booked during this period.";
            break;
        }
    }

    if (empty($booking_start_date) || empty($booking_end_date)) {
        $errors[] = "Please select both a start and end date.";
    } elseif ($booking_start_date > $booking_end_date) {
        $errors[] = "End date must be after the start date.";
    } elseif (strtotime($booking_start_date) < strtotime('today')) {
        $errors[] = "Start date cannot be in the past.";
    }

    // ✅ Only add companions if there are companion names
    if (empty($errors)) {
        $booking_ID = $bookingObj->addBookingForTourist(
            $tourist_ID,
            $tourpackage_ID,
            $booking_start_date,
            $booking_end_date,
            $is_selfIncluded
        );

        if ($booking_ID && $companions_count > 0) {
            foreach ($companion_names as $index => $name) {
                $category_ID = $companion_categories[$index] ?? null;
                if ($name && $category_ID) {
                    $bookingObj->addCompanionToBooking($booking_ID, $name, $category_ID);
                }
            }
        }

        $_SESSION['success'] = "Booking successful. Proceeding to payment.";
        $action = $activityObj->touristBook($booking_ID, $tourist_ID);
        header("Location: payment-form.php?id=" . $booking_ID);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Booking Again</title>
    <link rel="stylesheet" href="../../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/vendor/bootstrap-icons/bootstrap-icons.css" >
    <link rel="stylesheet" href="../../assets/css/tourist/header.css">
    <link rel="stylesheet" href="../../assets/css/tourist/booking-again.css">
</head>
<body>
    <?php require_once "includes/header.php"; 
    include_once "includes/header.php";?>
<main>
    <div class="cointainer-class">
        <h1>Book Tour Package</h1>

        <?php if (!empty($errors)): ?>
            <div style="color:red;">
                <?php foreach ($errors as $error): ?>
                    <p><?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="package-details">
            <h3>Package Information</h3>
            <p><strong>Package Name:</strong> <?= htmlspecialchars($package['tourpackage_name']); ?></p>
            <p><strong>Description:</strong> <?= htmlspecialchars($package['tourpackage_desc']); ?></p>
            <p><strong>Guide:</strong> <?= htmlspecialchars($guideName ?: 'N/A'); ?></p>
            <p><strong>Schedule Days:</strong> <?= htmlspecialchars($package['schedule_days']); ?> days</p>
            <p><strong>Maximum People:</strong> <?= htmlspecialchars($package['numberofpeople_maximum']); ?></p>
            <p><strong>Minimum People:</strong> <?= htmlspecialchars($package['numberofpeople_based']); ?></p>
            <p><strong>Base Amount:</strong> <?= htmlspecialchars($package['pricing_currency'] . ' ' . number_format($package['pricing_foradult'], 2)); ?></p>
            <p><strong>Discount:</strong> <?= htmlspecialchars($package['pricing_currency'] . ' ' . number_format($package['pricing_discount'], 2)); ?></p>

            <?php if (!empty($spots)): ?>
                <p><strong>Tour Spots:</strong></p>
                <ul>
                    <?php foreach ($spots as $spot): ?>
                        <li><strong><?= htmlspecialchars($spot['spots_name']); ?></strong> - <?= htmlspecialchars($spot['spots_description']); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p><em>No associated tour spots.</em></p>
            <?php endif; ?>
        </div>

        <?php if ($rebookData): ?>
            <div style="background:#e8f7ff; padding:10px; border-left:4px solid #007bff;">
                <strong>Rebooking:</strong> This form is pre-filled from your cancelled booking #<?= htmlspecialchars($oldBookingID) ?>.
            </div>
        <?php endif; ?>

        <form action="" method="post">
            <h2>Booking Dates</h2>
            <label for="booking_start_date">Start Date:</label>
            <input type="date" name="booking_start_date" id="booking_start_date"
                    value="<?= htmlspecialchars($rebookData['booking_start_date'] ?? '') ?>" required>

            <label for="booking_end_date">End Date:</label>
            <input type="date" name="booking_end_date" id="booking_end_date"
                    value="<?= htmlspecialchars($rebookData['booking_end_date'] ?? '') ?>" readonly required>

            <div id="overlapWarning" style="color:red; display:none;">
                The selected dates overlap with another booking for this guide.
            </div>

            <h2>Companions</h2>
            <p>Are you including yourself in the booking?</p>
            <label>
                <input type="radio" name="is_selfIncluded" value="yes"
                    <?= isset($rebookData['is_selfIncluded']) && $rebookData['is_selfIncluded'] ? 'checked' : '' ?> required>
                Yes
            </label>
            <label>
                <input type="radio" name="is_selfIncluded" value="no"
                    <?= isset($rebookData['is_selfIncluded']) && !$rebookData['is_selfIncluded'] ? 'checked' : '' ?> required>
                No
            </label>

            <div id="inputcointainer-class">
                <?php
                $oldCompanions = [];
                $categories = $bookingObj->getAllCompanionCategories();
                
                if (!empty($rebookData)) {
                    $oldCompanions = $bookingObj->getCompanionsByBooking($oldBookingID);
                }

                if (!empty($oldCompanions)) {
                    foreach ($oldCompanions as $comp) { ?>
                        <div>
                            <input type="text" name="companion_name[]" value="<?= htmlspecialchars($comp['companion_name']) ?>" placeholder="Name" required>
                            <select name="companion_category[]" required>
                                <option value="">-- SELECT CATEGORY ---</option>
                                <?php foreach ($categories as $c) { ?>
                                    <option value="<?= $c['companion_category_ID'] ?? ''?>" 
                                        <?= (isset($comp['companion_category_ID']) && $comp['companion_category_ID'] == $c['companion_category_ID']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c['companion_category_name']) ?>
                                    </option>
                                <?php } ?>
                            </select>
                            <button type="button" onclick="this.parentNode.remove();">Remove</button>
                        </div>
                    <?php }
                } else { ?>
                    <div>
                        <input type="text" name="companion_name[]" placeholder="Name" required>
                        <select name="companion_category[]" required>
                            <option value="">-- SELECT CATEGORY ---</option>
                            <?php foreach ($categories as $c) { ?>
                                <option value="<?= $c['companion_category_ID'] ?>"> <?= htmlspecialchars($c['companion_category_name']) ?> </option>
                            <?php } ?>
                        </select>
                        <button type="button" onclick="this.parentNode.remove();">Remove</button>
                    </div>
                <?php } ?>
            </div>

            <button type="button" onclick="addInput()">Add Companion</button>
            <br><br>

            <input type="submit" value="Proceed to Payment">
        </form>

        <a href="tour-packages-browse.php">← Back to Tour Packages</a>
    </div>
</main>

<script src="../../assets/vendor/twbs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const guideID = <?= intval($package['guide_ID']); ?>;
    const startDateInput = document.getElementById('booking_start_date');
    const endDateInput = document.getElementById('booking_end_date');
    const overlapWarning = document.getElementById('overlapWarning');
    const scheduleDays = <?= intval($package['schedule_days']); ?>;
    const inputcointainer-class = document.getElementById('inputcointainer-class');

    // --- Date Logic & Calculation ---
    function calculateEndDate(startDateString, days) {
        if (!startDateString || days <= 0) return '';
        const date = new Date(startDateString);
        date.setDate(date.getDate() + (days - 1));
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function updateEndDate() {
        const startDate = startDateInput.value;
        if (startDate) {
            endDateInput.value = calculateEndDate(startDate, scheduleDays);
        } else {
            endDateInput.value = '';
        }
    }

    // --- Companion Input Management ---
    function createCompanionInput() {
        // PHP variables used to populate category options in JavaScript
        const categoriesHtml = `
            <select name="companion_category[]" required>
                <option value="">-- SELECT CATEGORY ---</option>
                <?php foreach ($categories as $c) { ?>
                    <option value="<?= $c['companion_category_ID'] ?>"> <?= htmlspecialchars($c['companion_category_name']) ?> </option>
                <?php } ?>
            </select>`;

        const newDiv = document.createElement('div');
        newDiv.innerHTML = `
            <input type="text" name="companion_name[]" placeholder="Name" required>
            ${categoriesHtml}
            <button type="button" onclick="this.parentNode.remove();">Remove</button>
        `;
        return newDiv;
    }

    function addInput() {
        inputcointainer-class.appendChild(createCompanionInput());
    }


    // --- Overlap Check (AJAX Simulation) ---
    async function checkOverlap() {
        const startDate = startDateInput.value;
        const endDate = endDateInput.value;

        if (!startDate || !endDate) return;

        try {
            const response = await fetch('booking-overlap.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    guide_ID: guideID,
                    start_date: startDate,
                    end_date: endDate
                })
            });

            const result = await response.json();
            console.log("Overlap response:", result);

            if (result.overlap) {
                overlapWarning.style.display = 'block';
            } else {
                overlapWarning.style.display = 'none';
            }
        } catch (error) {
            console.error("Error checking overlap:", error);
        }
    }

    // --- Event Listeners ---
    startDateInput.addEventListener('change', () => {
        updateEndDate();
        checkOverlap();
    });
    endDateInput.addEventListener('change', checkOverlap);

    document.addEventListener('DOMContentLoaded', () => {
        if (startDateInput.value) {
            updateEndDate();
        }
        if (startDateInput.value && endDateInput.value) {
            checkOverlap();
        }
    });
</script>
</body>
</html>
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
require_once "../../classes/activity-log.php";
require_once "../../classes/mailer.php";

$mailerObj = new Mailer();

$activityObj = new ActivityLogs();

$tourist_ID = $_SESSION['user']['account_ID'];

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "Invalid tour package ID.";
    header("Location: tour-packages.php");
    exit();
}

$tourpackage_ID = intval($_GET['id']);
$tourManager = new TourManager();
$guideObj = new Guide();
$bookingObj = new Booking();
$touristObj = new Tourist();

$errors = [];

$package = $tourManager->getTourPackageDetailsByID($tourpackage_ID);

if (!$package) {
    $_SESSION['error'] = "Tour package not found.";
    header("Location: tour-packages.php");
    exit();
}

$guides = $guideObj->viewAllGuide();
$guideName = "";
foreach ($guides as $guide) {
    if ($guide['guide_ID'] == $package['guide_ID']) {
        $guideName = $guide['guide_name'];
        break;
    }
}

$spots = $tourManager->getSpotsByPackage($tourpackage_ID);
$categories = $bookingObj->getAllCompanionCategories(); // Fetch categories once for PHP/JS use

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
    <title>Booking</title>
    <link rel="stylesheet" href="../../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/vendor/bootstrap-icons/bootstrap-icons.css" >
    <link rel="stylesheet" href="../../assets/css/tourist/header.css">
    <link rel="stylesheet" href="../../assets/css/tourist/booking-add.css">

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
                        <li>
                            <strong><?= htmlspecialchars($spot['spots_name']); ?></strong> - 
                            <?= htmlspecialchars($spot['spots_description']); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p><em>No associated tour spots.</em></p>
            <?php endif; ?>
        </div>

        <form action="" method="post">
            <h2>Booking Dates</h2>
            <label for="booking_start_date">Start Date:</label>
            <input type="date" name="booking_start_date" id="booking_start_date" required>

            <label for="booking_end_date">End Date:</label>
            <input type="date" name="booking_end_date" id="booking_end_date" readonly required>
            <div id="overlapWarning" style="color:red; display:none;">
                ⚠️ The selected dates overlap with another booking for this guide.
            </div>


            <h2>Companions</h2>
            <p>Are you including yourself in the booking?</p>
            <label><input type="radio" name="is_selfIncluded" value="yes" required> Yes</label>
            <label><input type="radio" name="is_selfIncluded" value="no" required> No</label>


            <div id="inputContainerClass">
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
            </div>
            <button type="button" onclick="addInput()">Add Companion</button>

            <input type="submit" value="Proceed to Payment">
        </form>

        <a href="index.php">← Back to Tour Packages</a>
    </div>
</main>
<script>
    const maxPeople = <?= intval($package['numberofpeople_maximum']); ?>;
    const minPeople = <?= intval($package['numberofpeople_based']); ?>;
    const inputContainerClass = document.getElementById('inputContainerClass');
    const addBtn = document.querySelector('button[onclick="addInput()"]');
    const selfIncludedRadios = document.querySelectorAll('input[name="is_selfIncluded"]');

    // Companion Categories are passed via PHP for the addInput function
    const categoriesJson = '<?= json_encode($categories); ?>';
    let categories;
    try {
        categories = JSON.parse(categoriesJson);
    } catch (e) {
        console.error("Failed to parse companion categories.", e);
        categories = [];
    }

    // Initially hide add companion button if max = 1
    if (maxPeople === 1) {
        addBtn.style.display = 'none';
        inputContainerClass.innerHTML = ''; // remove any companion field
    } else if (inputContainerClass.children.length === 1 && inputContainerClass.children[0].querySelectorAll('input[type="text"]').length === 1 && inputContainerClass.children[0].querySelectorAll('input[type="text"]')[0].value === '') {
        // If it's a fresh booking and maxPeople > 1, ensure at least one companion slot is ready if 'No' is selected initially.
        // For now, we'll leave the initial empty slot as rendered by PHP.
    }

    // Handle self-inclusion change
    selfIncludedRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            const selfIncluded = this.value === 'yes';

            // Max = 1 logic
            if (maxPeople === 1) {
                if (selfIncluded) {
                    // User included themselves → remove companion fields
                    inputContainerClass.innerHTML = '';
                } else {
                    // User not included → add exactly one companion
                    if (inputContainerClass.children.length === 0) {
                        addInput(false); // Add one, but suppress subsequent removal for the first element
                    }
                }
            }
        });
    });

    // Function to generate the <select> HTML
    function getCategoriesHtml() {
        let options = '<option value="">-- SELECT CATEGORY ---</option>';
        categories.forEach(c => {
            // Note: If companion_category_ID is null/empty, the value won't be set, which is okay for this context.
            options += `<option value="${c.companion_category_ID}">${c.companion_category_name}</option>`;
        });
        return `<select name="companion_category[]" required>${options}</select>`;
    }

    // Function to add companion fields
    function addInput(showRemove = true) {
        // Prevent adding if maxPeople is reached (only applies if maxPeople > 1)
        if (maxPeople > 1) {
            const selfIncluded = document.querySelector('input[name="is_selfIncluded"]:checked')?.value === 'yes' ? 1 : 0;
            const currentCompanions = inputContainerClass.children.length;
            if (selfIncluded + currentCompanions >= maxPeople) {
                alert(`You have reached the maximum allowed people for this package (${maxPeople}).`);
                return;
            }
        }

        const div = document.createElement('div');
        div.innerHTML = `
            <input type="text" name="companion_name[]" placeholder="Name" required>
            ${getCategoriesHtml()}
            ${showRemove ? '<button type="button" onclick="this.parentNode.remove();">Remove</button>' : ''}
        `;
        inputContainerClass.appendChild(div);
    }

    // Auto-calculate booking end date
    const scheduleDays = <?= intval($package['schedule_days']); ?>;
    document.getElementById('booking_start_date').addEventListener('change', function () {
        const startDate = new Date(this.value);
        if (isNaN(startDate.getTime())) return;
        startDate.setDate(startDate.getDate() + scheduleDays - 1);
        document.getElementById('booking_end_date').value = startDate.toISOString().split('T')[0];
    });

        const guideID = <?= intval($package['guide_ID']); ?>;
        const startDateInput = document.getElementById('booking_start_date');
        const endDateInput = document.getElementById('booking_end_date');
        const overlapWarning = document.getElementById('overlapWarning');

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

                if (result.overlap) {
                    overlapWarning.style.display = 'block';
                } else {
                    overlapWarning.style.display = 'none';
                }
            } catch (error) {
                console.error("Error checking overlap:", error);
            }
        }

        // Combine date change and overlap check
        startDateInput.addEventListener('change', function() {
            // Update End Date
            const startDate = new Date(this.value);
            if (!isNaN(startDate.getTime())) {
                startDate.setDate(startDate.getDate() + scheduleDays - 1);
                endDateInput.value = startDate.toISOString().split('T')[0];
            } else {
                endDateInput.value = '';
            }
            
            // Check for overlap after date calculation
            checkOverlap();
        });
        endDateInput.addEventListener('change', checkOverlap);

</script>


</body>
</html> 
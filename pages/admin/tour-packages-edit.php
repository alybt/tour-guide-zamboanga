<?php
session_start();

require_once "../../classes/tour-manager.php";
require_once "../../classes/guide.php";

$error = "";
$package = null;

// Get package ID from URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: tour-packages.php");
    exit();
}

$tourpackage_ID = intval($_GET['id']);
$tourManager = new TourManager();
$guideObj = new Guide();

// Fetch existing package data
$package = $tourManager->getTourPackageById($tourpackage_ID);

if (!$package) {
    $_SESSION['error'] = "Tour package not found.";
    header("Location: tour-packages.php");
    exit();
}

// Get all spots and guides for the form
$spots = $tourManager->getAllSpots();
$guides = $guideObj->viewAllGuide();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize input
    $guide_ID = trim($_POST['guide_ID']);
    $tourpackage_name = trim($_POST['tourpackage_name']);
    $tourpackage_desc = trim($_POST['tourpackage_desc']);
    $schedule_days = trim($_POST['schedule_days']);
    $numberofpeople_maximum = trim($_POST['numberofpeople_maximum']);
    $numberofpeople_based = trim($_POST['numberofpeople_based']);
    $basedAmount = trim($_POST['basedAmount']);
    $discount = trim($_POST['discount'] ?? '0');
    $currency = 'PHP';
    $spots_array = $_POST['spots'] ?? [];

    // Validation
    if (empty($guide_ID)) {
        $error = "Please select a guide";
    } elseif (empty($tourpackage_name)) {
        $error = "Tour package name is required";
    } elseif (empty($tourpackage_desc)) {
        $error = "Description is required";
    } elseif (empty($schedule_days) || !is_numeric($schedule_days) || $schedule_days < 1) {
        $error = "Number of days must be at least 1";
    } elseif (empty($numberofpeople_maximum) || !is_numeric($numberofpeople_maximum) || $numberofpeople_maximum < 1) {
        $error = "Maximum people must be at least 1";
    } elseif (empty($numberofpeople_based) || !is_numeric($numberofpeople_based) || $numberofpeople_based < 1) {
        $error = "Minimum people must be at least 1";
    } elseif (empty($basedAmount) || !is_numeric($basedAmount) || $basedAmount < 0) {
        $error = "Base amount must be a positive number";
    } elseif (!is_numeric($discount) || $discount < 0) {
        $error = "Discount must be 0 or more";
    } else {
        $result = $tourManager->updateTourPackage(
            $tourpackage_ID,
            $guide_ID,
            $tourpackage_name,
            $tourpackage_desc,
            $schedule_days,
            $numberofpeople_maximum,
            $numberofpeople_based,
            $currency,
            $basedAmount,
            $discount,
            $spots_array
        );

        if ($result) {
            $_SESSION['success'] = "Tour package updated successfully!";
            header("Location: tour-packages.php");
            exit();
        } else {
            $error = "Failed to update tour package. Please try again.";
        }
    }

    // Update package data with posted values for display
    $package['guide_ID'] = $guide_ID;
    $package['tourpackage_name'] = $tourpackage_name;
    $package['tourpackage_desc'] = $tourpackage_desc;
    $package['schedule_days'] = $schedule_days;
    $package['numberofpeople_maximum'] = $numberofpeople_maximum;
    $package['numberofpeople_based'] = $numberofpeople_based;
    $package['pricing_based'] = $basedAmount;
    $package['pricing_discount'] = $discount;
    $package['spots'] = $spots_array;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Tour Package - Admin</title>
</head>
<body>
    <h1>Edit Tour Package</h1>
    
    <nav>
        <a href="dashboard.php">Dashboard</a> |
        <a href="bookings.php">Bookings</a> |
        <a href="users.php">Users</a> |
        <a href="tour-packages.php">Tour Packages</a> |
        <a href="tour-spots.php">Tour Spots</a> |
        <a href="schedules.php">Schedules</a> |
        <a href="payments.php">Payments</a> |
        <a href="logout.php">Logout</a>
    </nav>
    
    <hr>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form action="" method="post">
        <!-- Tour Package Name -->
        <div>
            <label for="tourpackage_name">Tour Package Name:</label>
            <input type="text" name="tourpackage_name" id="tourpackage_name" 
                   value="<?= htmlspecialchars($package['tourpackage_name']) ?>" required>
        </div>
        <br>

        <!-- Description -->
        <div>
            <label for="tourpackage_desc">Description:</label>
            <textarea name="tourpackage_desc" id="tourpackage_desc" required><?= htmlspecialchars($package['tourpackage_desc']) ?></textarea>
        </div>
        <br>

        <!-- Schedule Days -->
        <div>
            <label for="schedule_days">Schedule Days:</label>
            <input type="number" name="schedule_days" id="schedule_days" min="1"
                   value="<?= htmlspecialchars($package['schedule_days']) ?>" required>
        </div>
        <br>

        <!-- Maximum People -->
        <div>
            <label for="numberofpeople_maximum">Maximum People:</label>
            <input type="number" name="numberofpeople_maximum" id="numberofpeople_maximum" min="1"
                   value="<?= htmlspecialchars($package['numberofpeople_maximum']) ?>" required>
        </div>
        <br>

        <!-- Minimum People -->
        <div>
            <label for="numberofpeople_based">Minimum People:</label>
            <input type="number" name="numberofpeople_based" id="numberofpeople_based" min="1"
                   value="<?= htmlspecialchars($package['numberofpeople_based']) ?>" required>
        </div>
        <br>

        <!-- Base Amount -->
        <div>
            <label for="basedAmount">Base Amount (PHP):</label>
            <input type="number" name="basedAmount" id="basedAmount" min="0" step="0.01"
                   value="<?= htmlspecialchars($package['pricing_based']) ?>" required>
        </div>
        <br>

        <!-- Discount -->
        <div>
            <label for="discount">Discount (PHP):</label>
            <input type="number" name="discount" id="discount" min="0" step="0.01"
                   value="<?= htmlspecialchars($package['pricing_discount']) ?>">
        </div>
        <br>

        <!-- Guide -->
        <div>
            <label for="guide_ID">Select Guide:</label>
            <select name="guide_ID" id="guide_ID" required>
                <option value="">-- Select Guide --</option>
                <?php foreach ($guides as $guide): ?>
                    <option value="<?= $guide['guide_ID'] ?>" 
                        <?= ($package['guide_ID'] == $guide['guide_ID']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($guide['guide_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <br>

        <!-- Tourist Spots -->
        <div>
            <label>Select Tourist Spots:</label><br>
            <?php foreach ($spots as $spot): ?>
                <label style="display: block; margin: 8px 0;">
                    <input type="checkbox" name="spots[]" value="<?= $spot['spots_ID'] ?>"
                        <?= in_array($spot['spots_ID'], $package['spots']) ? 'checked' : '' ?>>
                    <strong><?= htmlspecialchars($spot['spots_name']) ?></strong>
                    <small style="color: #666; display: block;">
                        <?= htmlspecialchars($spot['spots_description']) ?>
                    </small>
                </label>
            <?php endforeach; ?>
        </div>
        <br>

        <div>
            <button type="submit">Update Package</button>
            <a href="tour-packages.php">Cancel</a>
        </div>
    </form>
</body>
</html>
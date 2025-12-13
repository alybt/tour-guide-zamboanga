<?php 
session_start();

require_once "../../classes/tour-manager.php";
require_once "../../classes/guide.php";

$tourPackageObj = new TourManager();
$guideObj = new Guide();

// Load old input & errors from session
$tourPackage = $_SESSION['old_input'] ?? [];
$errors = $_SESSION['errors'] ?? [];
$success = $_SESSION['success'] ?? '';

// Clear session data
unset($_SESSION['old_input'], $_SESSION['errors'], $_SESSION['success']);

$spots = $tourPackageObj->getAllSpots();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // === SANITIZE INPUT ===
    $tourPackage["spots"]               = $_POST['spots'] ?? [];
    $tourPackage["guide_ID"]            = trim(htmlspecialchars($_POST['guide_ID'] ?? ''));
    $tourPackage["tourpackage_name"]    = trim(htmlspecialchars($_POST['tourpackage_name'] ?? ''));
    $tourPackage["tourpackage_desc"]    = trim(htmlspecialchars($_POST['tourpackage_desc'] ?? ''));
    $tourPackage["schedule_days"]       = trim(htmlspecialchars($_POST['schedule_days'] ?? ''));
    $tourPackage["numberofpeople_maximum"] = trim(htmlspecialchars($_POST['numberofpeople_maximum'] ?? ''));
    $tourPackage["numberofpeople_based"] = trim(htmlspecialchars($_POST['numberofpeople_based'] ?? ''));
    $tourPackage["basedAmount"]         = trim(htmlspecialchars($_POST['basedAmount'] ?? ''));
    $tourPackage["discount"]            = trim(htmlspecialchars($_POST['discount'] ?? ''));
    $tourPackage["currency"]            = 'PHP';

    // === VALIDATION ===
    if (empty($tourPackage["guide_ID"])) {
        $errors["guide_ID"] = "Please select a guide";
    }

    if (empty($tourPackage["tourpackage_name"])) {
        $errors["tourpackage_name"] = "Tour package name is required";
    }

    if (empty($tourPackage["tourpackage_desc"])) {
        $errors["tourpackage_desc"] = "Description is required";
    }

    if (empty($tourPackage["schedule_days"]) || !is_numeric($tourPackage["schedule_days"]) || $tourPackage["schedule_days"] < 1) {
        $errors["schedule_days"] = "Number of days must be at least 1";
    }

    if (empty($tourPackage["numberofpeople_maximum"]) || !is_numeric($tourPackage["numberofpeople_maximum"]) || $tourPackage["numberofpeople_maximum"] < 1) {
        $errors["numberofpeople_maximum"] = "Maximum people must be at least 1";
    }

    if (empty($tourPackage["numberofpeople_based"])) {
        $errors["numberofpeople_based"] = "Minimum people is required";
    } elseif (!is_numeric($tourPackage["numberofpeople_based"]) || $tourPackage["numberofpeople_based"] < 1) {
        $errors["numberofpeople_based"] = "Minimum people must be at least 1";
    }

    if (empty($tourPackage["basedAmount"]) || !is_numeric($tourPackage["basedAmount"]) || $tourPackage["basedAmount"] < 0) {
        $errors["basedAmount"] = "Base amount must be a positive number";
    }

    if (!is_numeric($tourPackage["discount"]) || $tourPackage["discount"] < 0) {
        $errors["discount"] = "Discount must be 0 or more";
    }

    // === SAVE ONLY IF NO ERRORS ===
    if (empty($errors)) {
        $tourpackage_ID = $tourPackageObj->addTourPackage( 
            $tourPackage["guide_ID"],
            $tourPackage["tourpackage_name"],
            $tourPackage["tourpackage_desc"],
            $tourPackage["schedule_days"],
            $tourPackage["numberofpeople_maximum"],
            $tourPackage["numberofpeople_based"],
            $tourPackage["currency"],
            $tourPackage["basedAmount"],
            $tourPackage["discount"]
        );

        $result = $tourPackageObj->linkSpotToPackage($tourpackage_ID, $tourPackage["spots"] );

        if ($result) {
            $_SESSION['success'] = "Tour package added successfully!";
            header("Location: tour-packages.php"); // Changed from view_packages.php to tour-packages.php
            exit;
        } else {
            $errors['general'] = "Failed to save package. Please try again.";
        }
    }

    // === KEEP DATA IF ERRORS ===
    $_SESSION['errors'] = $errors;
    $_SESSION['old_input'] = $tourPackage;
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Tour Package</title>

</head>
<body>
    <h1>Add Tour Package</h1>

    <?php if ($success): ?>
        <p class="success"><?= $success ?></p>
    <?php endif; ?>

    <?php if (isset($errors['general'])): ?>
        <p class="error"><?= $errors['general'] ?></p>
    <?php endif; ?>

    <form action="" method="post">
        <!-- Tour Package Name -->
        <div>
            <label for="tourpackage_name">Tour Package Name:</label>
            <input type="text" name="tourpackage_name" id="tourpackage_name" 
                   value="<?= $tourPackage['tourpackage_name'] ?? '' ?>">
            <?php if (isset($errors['tourpackage_name'])): ?>
                <span class="error"><?= $errors['tourpackage_name'] ?></span>
            <?php endif; ?>
        </div>
        <br>

        <!-- Description -->
        <div>
            <label for="tourpackage_desc">Description:</label>
            <textarea name="tourpackage_desc" id="tourpackage_desc"><?= $tourPackage['tourpackage_desc'] ?? '' ?></textarea>
            <?php if (isset($errors['tourpackage_desc'])): ?>
                <span class="error"><?= $errors['tourpackage_desc'] ?></span>
            <?php endif; ?>
        </div>
        <br>

        <!-- Schedule Days -->
        <div>
            <label for="schedule_days">Schedule Days:</label>
            <input type="number" name="schedule_days" id="schedule_days" min="1"
                   value="<?= $tourPackage['schedule_days'] ?? '' ?>">
            <?php if (isset($errors['schedule_days'])): ?>
                <span class="error"><?= $errors['schedule_days'] ?></span>
            <?php endif; ?>
        </div>
        <br>

        <!-- Maximum People -->
        <div>
            <label for="numberofpeople_maximum">Maximum People:</label>
            <input type="number" name="numberofpeople_maximum" id="numberofpeople_maximum" min="1"
                   value="<?= $tourPackage['numberofpeople_maximum'] ?? '' ?>">
            <?php if (isset($errors['numberofpeople_maximum'])): ?>
                <span class="error"><?= $errors['numberofpeople_maximum'] ?></span>
            <?php endif; ?>
        </div>
        <br>

        <!-- Minimum People -->
        <div>
            <label for="numberofpeople_based">Minimum People:</label>
            <input type="number" name="numberofpeople_based" id="numberofpeople_based" min="1"
                   value="<?= $tourPackage['numberofpeople_based'] ?? '' ?>">
            <?php if (isset($errors['numberofpeople_based'])): ?>
                <span class="error"><?= $errors['numberofpeople_based'] ?></span>
            <?php endif; ?>
        </div>
        <br>

        <!-- Base Amount -->
        <div>
            <label for="basedAmount">Base Amount (PHP):</label>
            <input type="number" name="basedAmount" id="basedAmount" min="0" step="0.01"
                   value="<?= $tourPackage['basedAmount'] ?? '' ?>">
            <?php if (isset($errors['basedAmount'])): ?>
                <span class="error"><?= $errors['basedAmount'] ?></span>
            <?php endif; ?>
        </div>
        <br>

        <!-- Discount -->
        <div>
            <label for="discount">Discount (PHP):</label>
            <input type="number" name="discount" id="discount" min="0" step="0.01"
                   value="<?= $tourPackage['discount'] ?? '0' ?>">
            <?php if (isset($errors['discount'])): ?>
                <span class="error"><?= $errors['discount'] ?></span>
            <?php endif; ?>
        </div>
        <br>

        <!-- Guide -->
        <div>
            <label for="guide_ID">Select Guide:</label>
            <select name="guide_ID" id="guide_ID" required>
                <option value="">-- Select Guide --</option>
                <?php foreach ($guideObj->viewAllGuide() as $guide): ?>
                    <option value="<?= $guide['guide_ID'] ?>" 
                        <?= ($tourPackage['guide_ID'] ?? '') == $guide['guide_ID'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($guide['guide_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['guide_ID'])): ?>
                <span class="error"><?= $errors['guide_ID'] ?></span>
            <?php endif; ?>
        </div>
        <br>

        <!-- Tourist Spots -->
        <div>
            <label>Select Tourist Spots (Optional):</label><br>
            <div class="checkbox-list">
                <?php foreach ($spots as $spot): ?>
                    <label style="display: block; margin: 8px 0;">
                        <input type="checkbox" name="spots[]" value="<?= $spot['spots_ID'] ?>"
                            <?= in_array($spot['spots_ID'], $tourPackage['spots'] ?? []) ? 'checked' : '' ?>>
                        <strong><?= htmlspecialchars($spot['spots_name']) ?></strong>
                        <small style="color: #666; display: block;">
                            <?= htmlspecialchars($spot['spots_description']) ?>
                        </small>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        <br>

        <button type="submit" style="padding: 10px 20px; font-size: 16px;">Add Package</button>
    </form>
    <!-- Lorem ipsum dolor, sit amet consectetur adipisicing elit. Eum maiores laborum dolorem doloribus tempore nulla debitis provident tempora beatae deleniti officiis consequatur, minima modi magnam dicta expedita numquam corporis delectus? !-->
</body>
</html>
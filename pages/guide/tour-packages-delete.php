<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role_name'] !== 'Tour Guide') {
    header('Location: ../../index.php');
    exit;
} else if ($_SESSION['user']['account_status'] == 'Suspended'){
    header('Location: account-suspension.php');
    exit;
} else if ($_SESSION['user']['account_status'] == 'Pending'){
    header('Location: account-pending.php');
}

require_once "../../classes/tour-manager.php";
require_once "../../classes/guide.php";

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "Invalid tour package ID.";
    header("Location: tour-packages.php");
    exit();
}

$tourpackage_ID = intval($_GET['id']);
$tourMgrObj = new TourManager();
$guideObj = new Guide();

 
// Check if package exists and get its details
$package = $tourMgrObj->getTourPackageById($tourpackage_ID);
$schedule          = $tourMgrObj->getScheduleByID($package['schedule_ID']);
$numberofpeople    = $tourMgrObj->getPeopleByID($schedule['numberofpeople_ID']);
$pricing           = $tourMgrObj->getPricingByID($numberofpeople['pricing_ID']);
$tourpackage_spots = $tourMgrObj->getSpotsByPackageID($tourpackage_ID);


$pkg = [
    'tourpackage_name'       => $old['tourpackage_name']       ?? $tourpackage['tourpackage_name'] ?? '',
    'tourpackage_desc'       => $old['tourpackage_desc']       ?? $tourpackage['tourpackage_desc'] ?? '',
    'schedule_days'          => $old['schedule_days']          ?? $schedule['schedule_days'] ?? 1,
    'numberofpeople_maximum' => $old['numberofpeople_maximum'] ?? $numberofpeople['numberofpeople_maximum'] ?? '',
    'numberofpeople_based'   => $old['numberofpeople_based']   ?? $numberofpeople['numberofpeople_based'] ?? '',
    'pricing_foradult'       => $old['pricing_foradult']       ?? $pricing['pricing_foradult'] ?? '',
    'pricing_currency'       => $old['pricing_currency']       ?? $pricing['pricing_currency'] ?? '',
    'pricing_forchild'       => $old['pricing_forchild']       ?? $pricing['pricing_forchild'] ?? '',
    'pricing_foryoungadult'  => $old['pricing_foryoungadult']  ?? $pricing['pricing_foryoungadult'] ?? '',
    'pricing_forsenior'      => $old['pricing_forsenior']      ?? $pricing['pricing_forsenior'] ?? '',
    'pricing_forpwd'         => $old['pricing_forpwd']         ?? $pricing['pricing_forpwd'] ?? '',
    'include_meal'           => $old['include_meal']           ?? $pricing['include_meal'] ?? 0,
    'meal_fee'               => $old['meal_fee']               ?? $pricing['meal_fee'] ?? '0.00',
    'transport_fee'          => $old['transport_fee']          ?? $pricing['transport_fee'] ?? '0.00',
    'discount'               => $old['discount']               ?? $pricing['discount'] ?? '0.00',
];

if (!$package) {
    $_SESSION['error'] = "Tour package not found.";
    header("Location: tour-packages.php");
    exit();
}

// Get guide information
$guides = $guideObj->viewAllGuide();
$guideName = "";
foreach ($guides as $guide) {
    if ($guide['guide_ID'] == $package['guide_ID']) {
        $guideName = $guide['guide_name'];
        break;
    }
}

// Get associated spots
$spots = $tourMgrObj->getSpotsByPackage($tourpackage_ID);

// Handle confirmation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_delete'])) {
    $result = $tourMgrObj->deleteTourPackage($spots, $tourpackage_ID, $package['schedule_ID'], $schedule['numberofpeople_ID'], $numberofpeople['pricing_ID']);
    
    if ($result) {
        $_SESSION['success'] = "Tour package deleted successfully!";
    } else {
        $_SESSION['error'] = "Failed to delete tour package. Please try again.";
    }
    
    header("Location: tour-packages.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Tour Packages Delete</title>

    <link rel="stylesheet" href="../../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/vendor/bootstrap-icons/bootstrap-icons.css"> 

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet"/>

    <link rel="stylesheet" href="../../assets/css/guide/tour-packages-delete.css">
    <link rel="stylesheet" href="../../assets/css/guide/dashboard.css">
    
</head>
<body>
    <div class="container">
        <h1>Delete Tour Package</h1>
        
        <div class="warning">
            <strong>⚠️ Warning:</strong> You are about to delete this tour package. This action cannot be undone.
        </div>
        
        <div class="package-details">
            <h3>Tour Package Details:</h3>
            <p><strong>Package Name:</strong> <?= $pkg['tourpackage_name']; ?></p>
            <p><strong>Description:</strong> <?= $pkg['tourpackage_desc']; ?></p>
            <p><strong>Schedule Days:</strong> <?= $pkg['schedule_days']; ?> days</p>
            <p><strong>Maximum People:</strong> <?= $pkg['numberofpeople_maximum']; ?></p>
            <p><strong>Minimum People:</strong> <?= $pkg['numberofpeople_based']; ?></p>
            <p><strong>Base Amount:</strong> <?= $pricing['pricing_currency'] . ' ' . number_format($pricing['pricing_foradult'], 2); ?></p>
            <p><strong>Discount:</strong> <?= $pricing['pricing_currency'] . ' ' . number_format($pricing['pricing_discount'], 2); ?></p>
            
            <?php if (!empty($spots)): ?>
            <p><strong>Tour Spots:</strong></p>
            <ul class="spots-list">
                <?php foreach ($spots as $spot): ?>
                <li>
                    <strong><?= $spot['spots_name']; ?></strong>
                    <div style="margin-left: 20px; color: #666;">
                        <?= $spot['spots_description']; ?>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
        
        <p><strong>Note:</strong> This will permanently remove the tour package and all its associations with tour spots.</p>
        
        <form method="POST" action="">
            <button type="submit" name="confirm_delete" class="btn btn-danger" onclick="return confirm('Are you absolutely sure you want to delete this tour package?')">
                Yes, Delete This Tour Package
            </button>
            <a href="tour-packages.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</body>
</html>
<?php
session_start();

require_once "../../classes/tour-manager.php";
require_once "../../classes/guide.php";

// Get package ID from URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "Invalid tour package ID.";
    header("Location: tour-packages.php");
    exit();
}

$tourpackage_ID = intval($_GET['id']);
$tourManager = new TourManager();
$guideObj = new Guide();

// Check if package exists and get its details
$package = $tourManager->getTourPackageById($tourpackage_ID);

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
$spots = $tourManager->getSpotsByPackage($tourpackage_ID);

// Handle confirmation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_delete'])) {
    $result = $tourManager->deleteTourPackage($spots, $tourpackage_ID);
    
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
<html>
<head>
    <title>Delete Tour Package - Admin</title>
    <style>
        .container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .package-details {
            background-color: #f8f9fa;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .package-details p {
            margin: 10px 0;
        }
        .package-details strong {
            display: inline-block;
            width: 150px;
        }
        .spots-list {
            margin-left: 150px;
            padding-left: 20px;
        }
        .btn {
            padding: 10px 20px;
            margin: 5px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            border-radius: 3px;
        }
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Delete Tour Package</h1>
        
        <div class="warning">
            <strong>⚠️ Warning:</strong> You are about to delete this tour package. This action cannot be undone.
        </div>
        
        <div class="package-details">
            <h3>Tour Package Details:</h3>
            <p><strong>Package Name:</strong> <?php echo htmlspecialchars($package['tourpackage_name']); ?></p>
            <p><strong>Description:</strong> <?php echo htmlspecialchars($package['tourpackage_desc']); ?></p>
            <p><strong>Guide:</strong> <?php echo htmlspecialchars($guideName); ?></p>
            <p><strong>Schedule Days:</strong> <?php echo htmlspecialchars($package['schedule_days']); ?> days</p>
            <p><strong>Maximum People:</strong> <?php echo htmlspecialchars($package['numberofpeople_maximum']); ?></p>
            <p><strong>Minimum People:</strong> <?php echo htmlspecialchars($package['numberofpeople_based']); ?></p>
            <p><strong>Base Amount:</strong> <?php echo htmlspecialchars($package['pricing_currency'] . ' ' . number_format($package['pricing_based'], 2)); ?></p>
            <p><strong>Discount:</strong> <?php echo htmlspecialchars($package['pricing_currency'] . ' ' . number_format($package['pricing_discount'], 2)); ?></p>
            
            <?php if (!empty($spots)): ?>
            <p><strong>Tour Spots:</strong></p>
            <ul class="spots-list">
                <?php foreach ($spots as $spot): ?>
                <li>
                    <strong><?php echo htmlspecialchars($spot['spots_name']); ?></strong>
                    <div style="margin-left: 20px; color: #666;">
                        <?php echo htmlspecialchars($spot['spots_description']); ?>
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
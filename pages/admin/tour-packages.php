<?php
session_start();

require_once "../../classes/tour-manager.php";
require_once "../../classes/guide.php";

$tourManager = new TourManager();
$guideObj = new Guide();

// Get all tour packages with their related information
$packages = $tourManager->viewAllPackages();

// Get all guides for reference
$guides = $guideObj->viewAllGuide();
$guidesById = [];
foreach ($guides as $guide) {
    $guidesById[$guide['guide_ID']] = $guide;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Tour Packages - Admin</title>
</head>
<body>
    <h1>Manage Tour Packages</h1>
    
    <nav>
        <a href="dashboard.php">Dashboard</a> |
        <a href="bookings.php">Bookings</a> |
        <a href="tour-spots.php">Tour Spots</a> |
        <a href="logout.php">Logout</a>
    </nav>
    
    <hr>
    
    <h2>All Tour Packages</h2>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php 
                echo $_SESSION['success']; 
                unset($_SESSION['success']);
            ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <?php 
                echo $_SESSION['error']; 
                unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>
    
    <p><a href="tour-packages-add.php">Add New Package</a></p>
    
    <table border="1">
        <tr>
            <th>No.</th>
            <th>Package Name</th>
            <th>Description</th>
            <th>Guide</th>
            <th>Schedule Days</th>
            <th>Max People</th>
            <th>Min People</th>
            <th>Base Amount</th>
            <th>Discount</th>
            <th>Tour Spots</th>
            <th>Actions</th>
        </tr>
        <?php $no = 1; foreach ($packages as $package): 
            // Get schedule information
            $schedule = $tourManager->getScheduleByID($package['schedule_ID']);
            // Get number of people information
            $people = $tourManager->getPeopleByID($schedule['numberofpeople_ID']);
            // Get pricing information
            $pricing = $tourManager->getPricingByID($people['pricing_ID']);
            // Get spots for this package
            $spots = $tourManager->getSpotsByPackage($package['tourpackage_ID']);
            $spotNames = array_map(function($spot) {
                return $spot['spots_name'];
            }, $spots);
        ?>
        <tr>
            <td><?= $no++; ?></td>
            <td><?= htmlspecialchars($package['tourpackage_name']); ?></td>
            <td><?= htmlspecialchars($package['tourpackage_desc']); ?></td>
            <td><?= htmlspecialchars($guidesById[$package['guide_ID']]['guide_name'] ?? 'Unknown'); ?></td>
            <td><?= htmlspecialchars($schedule['schedule_days']); ?></td>
            <td><?= htmlspecialchars($people['numberofpeople_maximum']); ?></td>
            <td><?= htmlspecialchars($people['numberofpeople_based']); ?></td>
            <td><?= htmlspecialchars($pricing['pricing_currency'] . ' ' . number_format($pricing['pricing_based'], 2)); ?></td>
            <td><?= htmlspecialchars($pricing['pricing_currency'] . ' ' . number_format($pricing['pricing_discount'], 2)); ?></td>
            <td><?= htmlspecialchars(implode(', ', $spotNames)); ?></td>
            <td>
                <a href="tour-packages-edit.php?id=<?= $package['tourpackage_ID']; ?>">Edit</a> |
                <a href="tour-packages-delete.php?id=<?= $package['tourpackage_ID']; ?>" onclick="return confirm('Are you sure?')">Delete</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
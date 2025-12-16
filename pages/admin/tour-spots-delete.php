<?php


require_once "../../classes/tour-manager.php";

// Get spot ID from URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "Invalid tour spot ID.";
    header("Location: tour-spots.php");
    exit();
}

$spots_ID = intval($_GET['id']);
$tourSpot = new TourManager();

$spot = $tourSpot->getTourSpotById($spots_ID);

if (!$spot) {
    $_SESSION['error'] = "Tour spot not found.";
    header("Location: tour-spots.php");
    exit();
}

// Handle confirmation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_delete'])) {
    $result = $tourSpot->deleteTourSpot($spots_ID);
    
    if ($result) {
        $_SESSION['success'] = "Tour spot deleted successfully!";
    } else {
        $_SESSION['error'] = "Failed to delete tour spot. It may be associated with tour packages.";
    }
    
    header("Location: tour-spots.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Delete Tour Spot - Admin</title>
    <style>
        .container {
            max-width: 600px;
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
        .spot-details {
            background-color: #f8f9fa;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .spot-details p {
            margin: 10px 0;
        }
        .spot-details strong {
            display: inline-block;
            width: 120px;
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
        <h1>Delete Tour Spot</h1>
        
        <div class="warning">
            <strong>⚠️ Warning:</strong> You are about to delete this tour spot. This action cannot be undone.
        </div>
        
        <div class="spot-details">
            <h3>Tour Spot Details:</h3>
            <p><strong>Name:</strong> <?php echo htmlspecialchars($spot['spots_name']); ?></p>
            <p><strong>Description:</strong> <?php echo htmlspecialchars($spot['spots_description']); ?></p>
            <p><strong>Category:</strong> <?php echo htmlspecialchars($spot['spots_category']); ?></p>
            <p><strong>Address:</strong> <?php echo htmlspecialchars($spot['spots_address']); ?></p>
            <?php if (!empty($spot['spots_googlelink'])): ?>
            <p><strong>Google Link:</strong> <a href="<?php echo htmlspecialchars($spot['spots_googlelink']); ?>" target="_blank">View Map</a></p>
            <?php endif; ?>
        </div>
        
        <p><strong>Note:</strong> If this tour spot is associated with any tour packages, the deletion may fail. Please remove the associations first.</p>
        
        <form method="POST" action="">
            <button type="submit" name="confirm_delete" class="btn btn-danger" onclick="return confirm('Are you absolutely sure you want to delete this tour spot?')">
                Yes, Delete This Tour Spot
            </button>
            <a href="tour-spots.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</body>
</html>

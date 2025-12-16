<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role_name'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once "../../classes/tour-manager.php";

$tourManager = new TourManager();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;

    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Invalid package ID']);
        exit;
    }

    // Fetch the package
    $current = $tourManager->getPackageById($id);

    // DEBUG: See what columns are actually returned
    // Remove this in production!
    error_log("Package ID: $id | Full row: " . print_r($current, true));

    if (!$current) {
        echo json_encode(['success' => false, 'message' => 'Package not found']);
        exit;
    }

    // Check what the actual status column is called
    $statusKey = null;
    if (isset($current['tourpackage_status'])) {
        $statusKey = 'tourpackage_status';
    } elseif (isset($current['status'])) {
        $statusKey = 'status';
    }

    if (!$statusKey) {
        error_log("No status column found for package ID: $id");
        echo json_encode(['success' => false, 'message' => 'Status column not found in database']);
        exit;
    }

    $currentStatus = $current[$statusKey];
    $newStatus = $currentStatus === 'Active' ? 'Suspended' : 'Active';

    error_log("Toggling package $id from $currentStatus to $newStatus");

    $result = $tourManager->updatePackageStatus($id, $newStatus);

    if ($result) {
        echo json_encode([
            'success' => true,
            'newStatus' => $newStatus,
            'message' => "Package has been {$newStatus}."
        ]);
    } else {
        // Get MySQL error
        $error = $tourManager->connect()->error ?? 'Unknown DB error';
        error_log("Update failed for package $id: $error");
        echo json_encode(['success' => false, 'message' => 'Failed to update status: ' . $error]);
    }
}
?>
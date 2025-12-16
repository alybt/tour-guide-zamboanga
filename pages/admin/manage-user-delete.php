<?php
session_start();
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user']) || $_SESSION['user']['role_name'] !== 'Admin' || $_SESSION['user']['account_status'] == 'Suspended') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

require_once "../../classes/admin.php";

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// Get and validate input
$account_ID = isset($_POST['account_ID']) ? intval($_POST['account_ID']) : 0;
$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;

if ($account_ID <= 0 || $user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid account or user ID.']);
    exit;
}

try {
    $adminObj = new Admin();
    
    // Check if user has multiple roles
    $userRoles = $adminObj->getUserRoleAssignments($user_id);
    
    if (count($userRoles) <= 1) {
        echo json_encode([
            'success' => false, 
            'message' => 'Cannot delete the last role. User must have at least one role.'
        ]);
        exit;
    }
     
    $result = $adminObj-> hardDeleteUserRole($account_ID);
    
    if ($result) {
        echo json_encode([
            'success' => true, 
            'message' => 'Role assignment deleted successfully.'
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to delete role assignment.'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Role deletion failed: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred while deleting the role.'
    ]);
}
?>
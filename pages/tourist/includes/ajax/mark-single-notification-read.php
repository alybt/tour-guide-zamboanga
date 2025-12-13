<?php
ob_clean();
header('Content-Type: application/json');
session_start();

error_log("=== MARK SINGLE NOTIFICATION READ ===");

if (!isset($_SESSION['account_ID']) || !is_numeric($_SESSION['account_ID'])) {
    error_log("ERROR: Invalid or missing account_ID");
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
error_log("Request data: " . print_r($data, true));

$activity_id = (int)($data['activity_id'] ?? 0);
$account_id = (int)($data['account_id'] ?? 0);

error_log("Activity ID: $activity_id | Account ID: $account_id");

if ($activity_id <= 0) {
    error_log("ERROR: Invalid activity_id: $activity_id");
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid activity ID']);
    exit;
}

require_once "../../../../classes/activity-log.php";
$account_ID = (int)$_SESSION['account_ID'];

try {
    $activityObj = new ActivityLogs();
    error_log("ActivityLogs object created");
    
    // Check if method exists
    if (!method_exists($activityObj, 'markSingleNotificationAsViewed')) {
        error_log("ERROR: Method markSingleNotificationAsViewed does not exist in ActivityLogs class");
        error_log("Available methods: " . implode(', ', get_class_methods($activityObj)));
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Method not found']);
        exit;
    }
    
    $success = $activityObj->markSingleNotificationAsViewed($activity_id, $account_ID);
    error_log("markSingleNotificationAsViewed($activity_id, $account_ID) result: " . ($success ? 'TRUE' : 'FALSE'));
    
    echo json_encode(['success' => (bool)$success, 'activity_id' => $activity_id, 'account_id' => $account_ID]);
} catch (Throwable $e) {
    error_log('EXCEPTION in mark_single_notification_read: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
exit;

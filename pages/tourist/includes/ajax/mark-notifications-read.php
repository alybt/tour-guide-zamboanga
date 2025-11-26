<?php
ob_clean();
header('Content-Type: application/json');
session_start();

error_log("=== MARK NOTIFICATIONS READ ===");
error_log("Session account_ID: " . ($_SESSION['account_ID'] ?? 'NOT SET'));

if (!isset($_SESSION['account_ID']) || !is_numeric($_SESSION['account_ID'])) {
    error_log("ERROR: Invalid or missing account_ID");
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$account_ID = (int)$_SESSION['account_ID'];
error_log("Processing for account_ID: $account_ID");

require_once "../../../../classes/activity-log.php";

try {
    $activityObj = new ActivityLogs();
    error_log("ActivityLogs object created successfully");
    
    $success = $activityObj->markTouristNotificationsAsViewed($account_ID);
    error_log("markTouristNotificationsAsViewed result: " . ($success ? 'TRUE' : 'FALSE'));
    
    echo json_encode(['success' => (bool)$success, 'account_id' => $account_ID]);
} catch (Throwable $e) {
    error_log('EXCEPTION in mark_notifications_read: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
exit;
<?php
ob_clean();
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['account_ID']) || !is_numeric($_SESSION['account_ID'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$activity_id = (int)($data['activity_id'] ?? 0);

if ($activity_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid activity ID']);
    exit;
}

require_once __DIR__ . '/../../../../classes/activity-log.php';

try {
    $activityObj = new ActivityLogs();
    $success = $activityObj->markNotificationAsViewed($activity_id);
    echo json_encode(['success' => (bool)$success]);
} catch (Throwable $e) {
    error_log('mark_single_notification_read error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
exit;

<?php
session_start();
$current_page = basename($_SERVER['PHP_SELF']);
// Redirect if not logged in or not a Guide
if (!isset($_SESSION['user']) || $_SESSION['user']['role_name'] !== 'Tour Guide') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Status-based redirects
if ($_SESSION['user']['account_status'] === 'Suspended') {
    echo json_encode(['success' => false, 'error' => 'Account suspended']);
    exit;
}
if ($_SESSION['user']['account_status'] === 'Pending') {
    echo json_encode(['success' => false, 'error' => 'Account pending']);
    exit;
}

require_once "../../../../classes/conversation.php";

$conversationObj = new Conversation();
$guide_ID = $_SESSION['account_ID'];
$tourist_ID = isset($_POST['tourist_id']) ? intval($_POST['tourist_id']) : 0;
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

if (!$tourist_ID || empty($message)) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

$message_ID = $conversationObj->conversation($guide_ID, $tourist_ID, $message);

if ($message_ID) { 
    $db = $conversationObj->connect();
    $stmt = $db->prepare("SELECT sent_at FROM Message WHERE message_ID = :message_ID");
    $stmt->bindParam(':message_ID', $message_ID, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $sent_at = $row ? $row['sent_at'] : date('Y-m-d H:i:s');

    echo json_encode([
        'success' => true,
        'message_id' => $message_ID,
        'sent_at' => $sent_at
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to send message']);
}
?>

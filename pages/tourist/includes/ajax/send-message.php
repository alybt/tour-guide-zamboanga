<?php 
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role_name'] !== 'Tourist') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once "../../../../classes/conversation.php";

$conversationObj = new Conversation();
$tourist_ID = $_SESSION['account_ID'];
$guide_ID = isset($_POST['guide_id']) ? intval($_POST['guide_id']) : 0;
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

if (!$guide_ID || empty($message)) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

$message_ID = $conversationObj->conversation($tourist_ID, $guide_ID, $message);

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
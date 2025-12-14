<?php
session_start();
require_once '../../../../classes/Conversation.php';

header('Content-Type: application/json');

$sessionAccount = null;
if (isset($_SESSION['account_ID']) && is_numeric($_SESSION['account_ID'])) {
    $sessionAccount = (int)$_SESSION['account_ID'];
} elseif (isset($_SESSION['user']['account_ID']) && is_numeric($_SESSION['user']['account_ID'])) {
    $sessionAccount = (int)$_SESSION['user']['account_ID'];
}
if (!$sessionAccount) {
    echo json_encode(['success' => false, 'error' => 'unauthorized']);
    exit;
}

if (!isset($_POST['message'])) {
    echo json_encode(['success' => false, 'error' => 'invalid_request']);
    exit;
}

$conversation_ID = isset($_POST['conversation_ID']) ? (int) $_POST['conversation_ID'] : 0;
$guide_ID        = isset($_POST['guide_id']) ? (int) $_POST['guide_id'] : 0;
$sender_ID       = $sessionAccount;
$message         = trim($_POST['message']);

if ($sender_ID <= 0 || $message === '') {
    echo json_encode(['success' => false, 'error' => 'invalid_params']);
    exit;
}

$convo = new Conversation();
try {
    // Debug: Log incoming parameters
    error_log("send-message params: conversation_ID=$conversation_ID, guide_ID=$guide_ID, sender_ID=$sender_ID");
    
    if ($conversation_ID <= 0 && $guide_ID > 0) {
        error_log("Getting conversation with guide ID: $guide_ID, sender ID: $sender_ID");
        $details = $convo->getConversationWithGuide($guide_ID, $sender_ID);
        error_log("Conversation details: " . print_r($details, true));
        
        if (is_array($details) && !empty($details['conversation_ID'])) {
            $conversation_ID = (int)$details['conversation_ID'];
            error_log("Found conversation ID: $conversation_ID");
        }
    }
    
    if ($conversation_ID <= 0) {
        error_log("No valid conversation found, conversation_ID=$conversation_ID");
        echo json_encode(['success' => false, 'error' => 'no_conversation', 'debug' => ['conversation_ID' => $conversation_ID, 'guide_ID' => $guide_ID]]);
        exit;
    }
    
    error_log("Attempting to update conversation: $conversation_ID");
    $result = $convo->updateConvo($conversation_ID, $sender_ID, $message);
    error_log("Update result: " . ($result ? 'true' : 'false'));
    
    echo json_encode(['success' => (bool)$result, 'conversation_ID' => $conversation_ID]);
} catch (Throwable $e) {
    error_log("send-message error: " . $e->getMessage());
    error_log("Error trace: " . $e->getTraceAsString());
    echo json_encode(['success' => false, 'error' => 'server_error', 'message' => $e->getMessage()]);
}

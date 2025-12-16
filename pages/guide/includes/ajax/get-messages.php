<?php
session_start();

require_once '../../../../classes/conversation.php';

$conversationObj = new Conversation();

if (!isset($_GET['conversation_ID'])) {
    http_response_code(400);
    exit;
}

$conversation_ID = (int)$_GET['conversation_ID'];
$currentUserID = $_SESSION['account_ID'] ?? $_SESSION['user']['account_ID'] ?? 0;

if (!$currentUserID) {
    http_response_code(401);
    exit;
}

$messages = $conversationObj->fetchMessages($conversation_ID);

if (empty($messages)): ?>
    <div class="empty-chat">
        <i class="fas fa-comment-dots"></i>
        <h4>No messages yet</h4>
        <p>Start the conversation!</p>
    </div>
<?php else: 
    $lastDate = null;
    foreach ($messages as $msg):
        $messageDate = date('F d, Y', strtotime($msg['sent_at']));
        $messageTime = date('g:i A', strtotime($msg['sent_at']));
        $isSent = ($msg['sender_account_ID'] == $currentUserID);
        if ($messageDate !== $lastDate):
?>
            <div class="date-divider">
                <span><?= $messageDate ?></span>
            </div>
<?php
            $lastDate = $messageDate;
        endif;
?>

        <div class="message <?= $isSent ? 'sent' : '' ?>">
            <?php if (!$isSent): ?>
                <img src="https://i.pravatar.cc/100?img=<?= htmlspecialchars($msg['sender_account_ID']) ?>" class="message-avatar" alt="User">
            <?php endif; ?>

            <div class="message-content">
                <div class="message-bubble">
                    <?= htmlspecialchars($msg['message_content']) ?>
                </div>
                <div class="message-time">
                    <?= $messageTime ?>
                </div>
            </div>
        </div>

<?php endforeach; ?>
<?php endif; ?>

<div id="typingIndicator" class="typing-indicator" style="display: none;">
    <span></span>
    <span></span>
    <span></span>
</div>

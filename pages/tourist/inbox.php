<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role_name'] !== 'Tourist') {
    header('Location: ../../index.php');
    exit;
}

require_once "../../classes/guide.php";
require_once "../../classes/tour-manager.php";
require_once "../../classes/tourist.php";
require_once "../../classes/conversation.php";


$guideObj = new Guide();
$conversationObj = new Conversation();

if (!isset($_GET['guide_id']) || empty($_GET['guide_id'])) {
    $_SESSION['error'] = "Invalid guide ID.";
    header("Location: index.php");
    exit();
}

$guide_ID = intval($_GET['guide_id']);
$tourist_ID = $_SESSION['account_ID']; // Assuming this is set in session as account_ID

// Get or create conversation with this specific guide
$db = $conversationObj->connect();
$selected_conversation_ID = $conversationObj->addgetUsers($tourist_ID, $guide_ID, $db);

if (!$selected_conversation_ID) {
    $_SESSION['error'] = "Unable to load conversation.";
    header("Location: index.php");
    exit();
}

// Fetch messages for the selected conversation
$messages = $conversationObj->fetchMessages($selected_conversation_ID);

// Mark messages as read for this conversation
$conversationObj->markAsRead($selected_conversation_ID, $tourist_ID);

// Fetch all conversations for the sidebar
$conversationList = $conversationObj->fetchConversations($tourist_ID);

// Get guide details for the chat header
$guidedetails = $guideObj->getGuideByID($guide_ID);
 
$guide_name = $guidedetails['name_last'] . ' ' . $guidedetails['name_last'];
$guide_avatar = $guidedetails['profile_picture'] ?? 'https://i.pravatar.cc/100?img=' . $guide_ID;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Tourismo Zamboanga</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
        :root {
            --primary-color: #ffffff;
            --secondary-color: #213638;
            --accent: #E5A13E;
            --secondary-accent: #CFE7E5;
        }

        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            height: 100vh;
            overflow: hidden;
            margin-top: 4rem;
        }

        
        .navbar {
            background-color: var(--secondary-color) !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            z-index: 1000;
        }

        .navbar-brand {
            color: var(--primary-color) !important;
            font-weight: bold;
            font-size: 1.5rem;
        }

        .navbar-brand i {
            color: var(--accent);
        }

        .nav-link {
            color: var(--secondary-accent) !important;
            margin: 0 10px;
        }

        .nav-link:hover {
            color: var(--accent) !important;
        }

        
        .messages-container {
            height: calc(100vh - 56px);
            display: flex;
            background: white;
        }

        
        .conversations-sidebar {
            width: 380px;
            border-right: 2px solid #e0e0e0;
            display: flex;
            flex-direction: column;
            background: white;
        }

        .conversations-header {
            padding: 20px;
            border-bottom: 2px solid #e0e0e0;
            background: white;
        }

        .conversations-header h4 {
            color: var(--secondary-color);
            font-weight: bold;
            margin-bottom: 15px;
        }

        .search-box {
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 10px 15px 10px 40px;
            border: 2px solid #e0e0e0;
            border-radius: 25px;
            font-size: 0.9rem;
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--accent);
        }

        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }

        .conversations-list {
            flex: 1;
            overflow-y: auto;
        }

        .conversation-item {
            padding: 15px 20px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .conversation-item:hover {
            background: var(--secondary-accent);
        }

        .conversation-item.active {
            background: var(--secondary-accent);
            border-left: 4px solid var(--accent);
        }

        .conversation-avatar {
            position: relative;
            flex-shrink: 0;
        }

        .conversation-avatar img {
            width: 55px;
            height: 55px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--accent);
        }

        .online-indicator {
            width: 14px;
            height: 14px;
            background: #00ff00;
            border-radius: 50%;
            position: absolute;
            bottom: 2px;
            right: 2px;
            border: 3px solid white;
            box-shadow: 0 0 10px rgba(0, 255, 0, 0.5);
        }

        .conversation-content {
            flex: 1;
            min-width: 0;
        }

        .conversation-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }

        .conversation-name {
            font-weight: 600;
            color: var(--secondary-color);
            font-size: 1rem;
        }

        .conversation-time {
            font-size: 0.75rem;
            color: #6c757d;
        }

        .conversation-preview {
            font-size: 0.9rem;
            color: #6c757d;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .conversation-item.unread .conversation-name {
            color: var(--accent);
        }

        .conversation-item.unread .conversation-preview {
            font-weight: 600;
            color: var(--secondary-color);
        }

        .unread-badge {
            background: var(--accent);
            color: var(--secondary-color);
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: bold;
            flex-shrink: 0;
        }

        
        .chat-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #f8f9fa;
        }

        .chat-header {
            padding: 20px 25px;
            border-bottom: 2px solid #e0e0e0;
            background: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chat-header-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .chat-header-avatar img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: 2px solid var(--accent);
        }

        .chat-header-details h5 {
            margin: 0;
            color: var(--secondary-color);
            font-weight: bold;
        }

        .chat-header-status {
            font-size: 0.85rem;
            color: #00cc00;
            margin-top: 2px;
        }

        .chat-header-status i {
            font-size: 8px;
            margin-right: 5px;
        }

        .chat-header-status.offline {
            color: #6c757d;
        }

        .chat-header-actions button {
            background: transparent;
            border: 2px solid #e0e0e0;
            color: var(--secondary-color);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-left: 10px;
            transition: all 0.3s;
        }

        .chat-header-actions button:hover {
            border-color: var(--accent);
            color: var(--accent);
        }

        .chat-messages {
            flex: 1;
            padding: 25px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }

        .date-divider {
            text-align: center;
            margin: 20px 0;
        }

        .date-divider span {
            background: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            color: #6c757d;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .message {
            display: flex;
            margin-bottom: 20px;
            max-width: 70%;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .message.sent {
            margin-left: auto;
            flex-direction: row-reverse;
        }

        .message-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            margin: 0 10px;
            flex-shrink: 0;
        }

        .message-content {
            display: flex;
            flex-direction: column;
        }

        .message.sent .message-content {
            align-items: flex-end;
        }

        .message-bubble {
            background: white;
            padding: 12px 16px;
            border-radius: 18px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            word-wrap: break-word;
        }

        .message.sent .message-bubble {
            background: var(--accent);
            color: var(--secondary-color);
        }

        .message-time {
            font-size: 0.75rem;
            color: #6c757d;
            margin-top: 5px;
            padding: 0 5px;
        }

        .message.sent .message-time {
            text-align: right;
        }

        
        .chat-input {
            padding: 20px 25px;
            border-top: 2px solid #e0e0e0;
            background: white;
        }

        .chat-input-wrapper {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chat-input-wrapper button.attachment-btn {
            background: transparent;
            border: 2px solid #e0e0e0;
            color: var(--secondary-color);
            width: 45px;
            height: 45px;
            border-radius: 50%;
            transition: all 0.3s;
        }

        .chat-input-wrapper button.attachment-btn:hover {
            border-color: var(--accent);
            color: var(--accent);
        }

        .chat-input-wrapper input {
            flex: 1;
            border: 2px solid #e0e0e0;
            border-radius: 25px;
            padding: 12px 20px;
            font-size: 0.95rem;
        }

        .chat-input-wrapper input:focus {
            outline: none;
            border-color: var(--accent);
        }

        .chat-input-wrapper button.send-btn {
            background: var(--accent);
            border: none;
            color: var(--secondary-color);
            width: 45px;
            height: 45px;
            border-radius: 50%;
            font-size: 1.1rem;
            transition: all 0.3s;
        }

        .chat-input-wrapper button.send-btn:hover {
            background: #d89435;
            transform: scale(1.05);
        }

        
        .empty-chat {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #6c757d;
        }

        .empty-chat i {
            font-size: 5rem;
            margin-bottom: 20px;
            color: var(--accent);
            opacity: 0.5;
        }

        .empty-chat h4 {
            margin-bottom: 10px;
        }

        
        .conversations-list::-webkit-scrollbar,
        .chat-messages::-webkit-scrollbar {
            width: 6px;
        }

        .conversations-list::-webkit-scrollbar-track,
        .chat-messages::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .conversations-list::-webkit-scrollbar-thumb,
        .chat-messages::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }

        .conversations-list::-webkit-scrollbar-thumb:hover,
        .chat-messages::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        
        @media (max-width: 768px) {
            .conversations-sidebar {
                width: 100%;
            }

            .chat-area {
                display: none;
            }

            .chat-area.show-mobile {
                display: flex;
                position: absolute;
                top: 56px;
                left: 0;
                right: 0;
                bottom: 0;
                z-index: 999;
            }
        }

        
        .typing-indicator {
            display: flex;
            align-items: center;
            padding: 10px 15px;
            background: white;
            border-radius: 18px;
            width: fit-content;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .typing-indicator span {
            height: 8px;
            width: 8px;
            background: #6c757d;
            border-radius: 50%;
            display: inline-block;
            margin: 0 2px;
            animation: typing 1.4s infinite;
        }

        .typing-indicator span:nth-child(2) {
            animation-delay: 0.2s;
        }

        .typing-indicator span:nth-child(3) {
            animation-delay: 0.4s;
        }

        @keyframes typing {
            0%, 60%, 100% {
                transform: translateY(0);
            }
            30% {
                transform: translateY(-10px);
            }
        }
</style>
</head>
<body>
    <?php include 'includes/header.php'?>

    <div class="messages-container">
        <div class="conversations-sidebar">
            <div class="conversations-header">
                <h4>Messages</h4>
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" class="form-control" placeholder="Search conversations..." id="searchConversations">
                </div>
            </div>
            <div class="conversations-list">
                <?php include 'includes/components/conversation-list.php'?>
            </div>
        </div>

        <!-- Chat Area -->
        <div class="chat-area">
            <div class="chat-header">
                <div class="chat-header-info">
                    <div class="chat-header-avatar">
                        <img src="<?= htmlspecialchars($guide_avatar) ?>" alt="<?= htmlspecialchars($guide_name) ?>">
                    </div>
                    <div class="chat-header-details">
                        <h5><?= htmlspecialchars($guide_name) ?></h5>
                        <div class="chat-header-status">
                            <i class="fas fa-circle"></i> Online
                        </div>
                    </div>
                </div>
                <div class="chat-header-actions">
                    <button title="Video Call"><i class="fas fa-video"></i></button>
                    <button title="Phone Call"><i class="fas fa-phone"></i></button>
                    <button title="Booking Details"><i class="fas fa-info-circle"></i></button>
                    <button title="More Options"><i class="fas fa-ellipsis-v"></i></button>
                </div>
            </div>

            <div class="chat-messages" id="chatMessages">
                <?php 
                $currentUserID = $tourist_ID; // For messages.php to determine sent/received
                include 'includes/components/messages.php';
                ?>
            </div>

            <div class="chat-input">
                <div class="chat-input-wrapper">
                    <button class="attachment-btn" title="Attach File">
                        <i class="fas fa-paperclip"></i>
                    </button>
                    <input type="text" class="form-control" placeholder="Type your message..." id="messageInput">
                    <button class="send-btn" id="sendButton" title="Send Message">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const selectedGuideID = <?= json_encode($guide_ID) ?>;
        const selectedConversationID = <?= json_encode($selected_conversation_ID) ?>;
        const currentUserID = <?= json_encode($tourist_ID) ?>;

        $(document).ready(function() {
            function scrollToBottom() {
                const chatMessages = $('#chatMessages');
                chatMessages.scrollTop(chatMessages[0].scrollHeight);
            }

            scrollToBottom();

            function sendMessage() {
                const input = $('#messageInput');
                const message = input.val().trim();
                
                if (!message) return;

                // Disable button while sending
                const sendBtn = $('#sendButton');
                sendBtn.prop('disabled', true);
                
                $.ajax({
                    type: 'POST',
                    url: 'includes/ajax/send-message.php',
                    data: {
                        message: message,
                        conversation_ID: selectedConversationID,
                        guide_id: selectedGuideID
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Clear input
                            input.val('');
                            
                            // Reload messages
                            reloadMessages();
                        } else {
                            console.error('Failed to send message:', response.error);
                            alert('Failed to send message. Please try again.');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX error:', error);
                        alert('Error sending message. Please check your connection.');
                    },
                    complete: function() {
                        sendBtn.prop('disabled', false);
                        input.focus();
                    }
                });
            }

            function reloadMessages() {
                $.ajax({
                    type: 'GET',
                    url: 'includes/components/messages.php',
                    data: {
                        conversation_ID: selectedConversationID
                    },
                    dataType: 'html',
                    success: function(data) {
                        $('#chatMessages').html(data);
                        scrollToBottom();
                    },
                    error: function(xhr, status, error) {
                        console.error('Error loading messages:', error);
                    }
                });
            }

            $('#sendButton').on('click', sendMessage);

            $('#messageInput').on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    sendMessage();
                }
            });

            $('.conversation-item').on('click', function() {
                const conversationID = $(this).data('chat');
                const otherUserID = $(this).data('user-id') || $(this).find('.conversation-avatar img').attr('src').split('img=')[1];
                
                // Update active state
                $('.conversation-item').removeClass('active');
                $(this).addClass('active');
                $(this).removeClass('unread');
                $(this).find('.unread-badge').remove();
                
                // Load conversation
                window.location.href = 'inbox.php?guide_id=' + otherUserID;
            });

            $('#searchConversations').on('keyup', function() {
                const searchText = $(this).val().toLowerCase();
                
                $('.conversation-item').each(function() {
                    const name = $(this).find('.conversation-name').text().toLowerCase();
                    const preview = $(this).find('.conversation-preview').text().toLowerCase();
                    
                    if (name.includes(searchText) || preview.includes(searchText)) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });

            $('.attachment-btn').on('click', function() {
                alert('File attachment feature - coming soon');
            });

            $('.chat-header-actions button').on('click', function() {
                const icon = $(this).find('i').attr('class');
                if (icon.includes('video')) {
                    alert('Video call feature - coming soon');
                } else if (icon.includes('phone')) {
                    alert('Voice call feature - coming soon');
                } else if (icon.includes('info')) {
                    alert('View booking details');
                } else {
                    alert('More options menu');
                }
            });

            // Auto-refresh messages every 3 seconds
            setInterval(reloadMessages, 3000);
        });
    </script>
</body>
</html>
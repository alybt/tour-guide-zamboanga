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
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#"><i class="fas fa-map-marked-alt"></i> Tourismo Zamboanga</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="tourist-dashboard.html"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="search-guides.html"><i class="fas fa-search"></i> Find Guides</a></li>
                    <li class="nav-item"><a class="nav-link" href="bookings.html"><i class="fas fa-calendar-alt"></i> My Bookings</a></li>
                    <li class="nav-item"><a class="nav-link active" href="#"><i class="fas fa-comments"></i> Messages</a></li>
                    <li class="nav-item"><a class="nav-link" href="profile.html"><i class="fas fa-user-circle"></i> Profile</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Messages Container -->
    <div class="messages-container">
        <!-- Conversations Sidebar -->
        <div class="conversations-sidebar">
            <div class="conversations-header">
                <h4>Messages</h4>
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" class="form-control" placeholder="Search conversations..." id="searchConversations">
                </div>
            </div>
            <div class="conversations-list">
                <!-- Conversation 1 - Active & Unread -->
                <div class="conversation-item active unread" data-chat="1">
                    <div class="conversation-avatar">
                        <img src="https://i.pravatar.cc/100?img=33" alt="Marco Rossi">
                        <div class="online-indicator"></div>
                    </div>
                    <div class="conversation-content">
                        <div class="conversation-header">
                            <span class="conversation-name">Marco Rossi</span>
                            <span class="conversation-time">10:30 AM</span>
                        </div>
                        <p class="conversation-preview">Perfect! See you tomorrow at 9 AM sharp. Have a wonderful evening!</p>
                    </div>
                    <div class="unread-badge">2</div>
                </div>

                <!-- Conversation 2 -->
                <div class="conversation-item" data-chat="2">
                    <div class="conversation-avatar">
                        <img src="https://i.pravatar.cc/100?img=45" alt="Sofia Bianchi">
                    </div>
                    <div class="conversation-content">
                        <div class="conversation-header">
                            <span class="conversation-name">Sofia Bianchi</span>
                            <span class="conversation-time">Yesterday</span>
                        </div>
                        <p class="conversation-preview">Great tour! Thank you so much for showing us around</p>
                    </div>
                </div>

                <!-- Conversation 3 - Unread -->
                <div class="conversation-item unread" data-chat="3">
                    <div class="conversation-avatar">
                        <img src="https://i.pravatar.cc/100?img=68" alt="Luca Romano">
                        <div class="online-indicator"></div>
                    </div>
                    <div class="conversation-content">
                        <div class="conversation-header">
                            <span class="conversation-name">Luca Romano</span>
                            <span class="conversation-time">2 days ago</span>
                        </div>
                        <p class="conversation-preview">I've confirmed your booking for December 18th. Looking forward to it!</p>
                    </div>
                    <div class="unread-badge">1</div>
                </div>

                <!-- Conversation 4 -->
                <div class="conversation-item" data-chat="4">
                    <div class="conversation-avatar">
                        <img src="https://i.pravatar.cc/100?img=31" alt="Alessandro Conti">
                    </div>
                    <div class="conversation-content">
                        <div class="conversation-header">
                            <span class="conversation-name">Alessandro Conti</span>
                            <span class="conversation-time">3 days ago</span>
                        </div>
                        <p class="conversation-preview">The Trastevere evening walk was amazing! Thanks again</p>
                    </div>
                </div>

                <!-- Conversation 5 -->
                <div class="conversation-item" data-chat="5">
                    <div class="conversation-avatar">
                        <img src="https://i.pravatar.cc/100?img=47" alt="Giulia Ferrari">
                        <div class="online-indicator"></div>
                    </div>
                    <div class="conversation-content">
                        <div class="conversation-header">
                            <span class="conversation-name">Giulia Ferrari</span>
                            <span class="conversation-time">1 week ago</span>
                        </div>
                        <p class="conversation-preview">Hi! I'd love to book a photography tour with you</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chat Area -->
        <div class="chat-area">
            <div class="chat-header">
                <div class="chat-header-info">
                    <div class="chat-header-avatar">
                        <img src="https://i.pravatar.cc/100?img=33" alt="Marco Rossi">
                    </div>
                    <div class="chat-header-details">
                        <h5>Marco Rossi</h5>
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
                <div class="date-divider">
                    <span>December 11, 2025</span>
                </div>

                <div class="message">
                    <img src="https://i.pravatar.cc/100?img=33" class="message-avatar" alt="Marco">
                    <div class="message-content">
                        <div class="message-bubble">
                            Hello Sarah! Thanks for booking the Ancient Rome tour. I'm excited to show you around!
                        </div>
                        <div class="message-time">9:00 AM</div>
                    </div>
                </div>

                <div class="message sent">
                    <div class="message-content">
                        <div class="message-bubble">
                            Hi Marco! We're really looking forward to it. Can you confirm the meeting point?
                        </div>
                        <div class="message-time">9:15 AM</div>
                    </div>
                </div>

                <div class="message">
                    <img src="https://i.pravatar.cc/100?img=33" class="message-avatar" alt="Marco">
                    <div class="message-content">
                        <div class="message-bubble">
                            Absolutely! We'll meet at the main entrance of the Colosseum at 9:00 AM tomorrow. I'll be wearing a red cap with "Tourismo Zamboanga" logo.
                        </div>
                        <div class="message-time">9:20 AM</div>
                    </div>
                </div>

                <div class="message sent">
                    <div class="message-content">
                        <div class="message-bubble">
                            Perfect! Should we bring anything specific?
                        </div>
                        <div class="message-time">9:25 AM</div>
                    </div>
                </div>

                <div class="message">
                    <img src="https://i.pravatar.cc/100?img=33" class="message-avatar" alt="Marco">
                    <div class="message-content">
                        <div class="message-bubble">
                            Just comfortable walking shoes, water, and your valid ID for the Colosseum entry. I'll handle everything else including skip-the-line tickets! 😊
                        </div>
                        <div class="message-time">9:30 AM</div>
                    </div>
                </div>

                <div class="message sent">
                    <div class="message-content">
                        <div class="message-bubble">
                            Awesome! We're really excited about the gladiator stories you mentioned. See you tomorrow! 🏛️
                        </div>
                        <div class="message-time">10:00 AM</div>
                    </div>
                </div>

                <div class="message">
                    <img src="https://i.pravatar.cc/100?img=33" class="message-avatar" alt="Marco">
                    <div class="message-content">
                        <div class="message-bubble">
                            I have some great stories prepared! See you tomorrow at 9 AM sharp. Have a wonderful evening!
                        </div>
                        <div class="message-time">10:30 AM</div>
                    </div>
                </div>

                <!-- Typing Indicator (hidden by default) -->
                <div class="message" id="typingIndicator" style="display: none;">
                    <img src="https://i.pravatar.cc/100?img=33" class="message-avatar" alt="Marco">
                    <div class="typing-indicator">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>
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
        $(document).ready(function() {
                        function scrollToBottom() {
                const chatMessages = $('#chatMessages');
                chatMessages.scrollTop(chatMessages[0].scrollHeight);
            }

            scrollToBottom();

                        function sendMessage() {
                const input = $('#messageInput');
                const message = input.val().trim();
                
                if (message) {
                    const time = new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
                    
                    const messageHtml = `
                        <div class="message sent">
                            <div class="message-content">
                                <div class="message-bubble">
                                    ${message}
                                </div>
                                <div class="message-time">${time}</div>
                            </div>
                        </div>
                    `;
                    
                    $('#typingIndicator').before(messageHtml);
                    input.val('');
                    scrollToBottom();
                    
                                        setTimeout(() => {
                        $('#typingIndicator').show();
                        scrollToBottom();
                        
                                                setTimeout(() => {
                            $('#typingIndicator').hide();
                            const responseTime = new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
                            
                            const responseHtml = `
                                <div class="message">
                                    <img src="https://i.pravatar.cc/100?img=33" class="message-avatar" alt="Marco">
                                    <div class="message-content">
                                        <div class="message-bubble">
                                            Thanks for your message! I'll get back to you shortly.
                                        </div>
                                        <div class="message-time">${responseTime}</div>
                                    </div>
                                </div>
                            `;
                            
                            $('#typingIndicator').before(responseHtml);
                            scrollToBottom();
                        }, 2000);
                    }, 500);
                }
            }

                        $('#sendButton').on('click', sendMessage);

                        $('#messageInput').on('keypress', function(e) {
                if (e.which === 13) {
                    sendMessage();
                }
            });

                        $('.conversation-item').on('click', function() {
                $('.conversation-item').removeClass('active');
                $(this).addClass('active');
                $(this).removeClass('unread');
                $(this).find('.unread-badge').remove();
                
                                $('.chat-area').addClass('show-mobile');
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
                alert('File attachment feature - allows users to upload images or documents');
            });

                        $('.chat-header-actions button').on('click', function() {
                const icon = $(this).find('i').attr('class');
                if (icon.includes('video')) {
                    alert('Video call feature');
                } else if (icon.includes('phone')) {
                    alert('Voice call feature');
                } else if (icon.includes('info')) {
                    alert('View booking details');
                } else {
                    alert('More options menu');
                }
            });
        });
    </script>
</body>
</html>
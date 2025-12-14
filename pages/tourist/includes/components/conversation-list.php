<?php 

foreach ($conversationList as $list): 
    $is_active = ($list['other_user_ID'] == $guide_ID) ? 'active' : '';
    $is_unread = $list['has_unread'] ? 'unread' : '';
?>
<div class="conversation-item <?= $is_unread ?> <?= $is_active ?>"
     data-chat="<?= $list['conversation_ID'] ?>">

    <div class="conversation-avatar">
        <img src="https://i.pravatar.cc/100?img=<?= $list['other_user_ID'] ?>" alt="User">
        <div class="online-indicator"></div>
    </div>

    <div class="conversation-content">
        <div class="conversation-header">
            <span class="conversation-name">
                User #<?= htmlspecialchars($list['other_user_ID']) ?>
            </span>
            <span class="conversation-time">
                <?= date('h:i A', strtotime($list['last_message_time'])) ?>
            </span>
        </div>

        <p class="conversation-preview">
            <?= htmlspecialchars($list['last_message'] ?? 'No messages yet') ?>
        </p>
    </div>

    <?php if ($list['has_unread']): ?>
        <div class="unread-badge">●</div>
    <?php endif; ?>

</div>

<?php endforeach; ?>
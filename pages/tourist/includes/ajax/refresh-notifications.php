<?php
// includes/ajax/refresh-notifications.php
session_start();

$account_ID = $_SESSION['account_ID'] ?? null;

if (!$account_ID || !is_numeric($account_ID)) {
    echo '<div class="text-center text-muted py-5">
            <i class="bi bi-bell-slash fs-2 mb-3 text-muted"></i>
            <div>No notifications yet</div>
          </div>';
    exit;
}

require_once __DIR__ . '/../../../classes/booking.php';
require_once __DIR__ . '/../../../classes/activity-log.php';

$bookingObj  = new Booking();
$activityObj = new ActivityLogs();
$touristNotification = $activityObj->touristNotification((int)$account_ID);

// This file should output ONLY the content inside <div class="px-2">...</div>
?>
<?php if (empty($touristNotification)): ?>
    <div class="text-center text-muted py-5">
        <i class="bi bi-bell-slash fs-2 mb-3 text-muted"></i>
        <div>No notifications yet</div>
    </div>
<?php else: ?>
    <?php foreach ($touristNotification as $notif): 
        $isUnread = (int)$notif['is_viewed'] === 0;
        $desc = trim($notif['activity_description'] ?? '');
        preg_match('/^(\d+)\s+(.+?)\s+(\d+)$/i', $desc, $m);
        $booking_ID = (int)($m[3] ?? 0);
        $tourName = $bookingObj->getTourPackageDetailsByBookingID($booking_ID);
        $tourName = $tourName['tourpackage_name'] ?? 'Unknown Tour';

        $action = $notif['action_name'];
        switch ($action) {
            case 'Books':
            case 'Booking': $msg = "You booked <strong>$tourName</strong>"; break;
            case 'Cancel Booking': $msg = "You cancelled your booking for <strong>$tourName</strong>"; break;
            case 'Payment': $msg = "You paid for <strong>$tourName</strong>"; break;
            case 'Refund Booking': $msg = "Your booking for <strong>$tourName</strong> has been refunded"; break;
            default: $msg = htmlspecialchars($notif['action_name']); break;
        }
    ?>
        <li>
            <a class="dropdown-item py-3 rounded-3 mb-1 <?= $isUnread ? 'bg-primary bg-opacity-10 fw-semibold border-start border-primary border-4' : 'text-muted' ?> mark-as-read"
               href="javascript:void(0)"
               data-activity-id="<?= $notif['activity_ID'] ?>"
               data-account-id="<?= $account_ID ?>">
                <div class="d-flex">
                    <div class="me-3 mt-1">
                        <?php 
                        $act = strtolower($notif['action_name']);
                        if (str_contains($act, 'booking')): ?>
                            <i class="bi bi-calendar-check-fill text-primary fs-5"></i>
                        <?php elseif (str_contains($act, 'payment')): ?>
                            <i class="bi bi-credit-card-fill text-success fs-5"></i>
                        <?php elseif (str_contains($act, 'message')): ?>
                            <i class="bi bi-chat-dots-fill text-info fs-5"></i>
                        <?php else: ?>
                            <i class="bi bi-bell-fill text-secondary fs-5"></i>
                        <?php endif; ?>
                    </div>
                    <div class="flex-grow-1">
                        <div class="small <?= $isUnread ? 'text-dark' : '' ?>">
                            <?= htmlspecialchars($notif['action_name']) ?> · 
                            <?= date('M j, Y · g:i A', strtotime($notif['activity_timestamp'])) ?>
                        </div>
                        <div class="text-muted xsmall mt-1"><?= $msg ?></div>
                    </div>
                    <?php if ($isUnread): ?>
                        <div class="ms-2">
                            <span class="badge bg-danger rounded-pill">New</span>
                        </div>
                    <?php endif; ?>
                </div>
            </a>
        </li>
    <?php endforeach; ?>
<?php endif; ?>
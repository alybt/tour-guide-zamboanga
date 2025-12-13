
<?php

if (empty($touristNotification)): ?>
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
        $tourDetails = $booking_ID > 0 ? ($bookingObj->getTourPackageDetailsByBookingID($booking_ID) ?: []) : [];
        $tourName = $tourDetails['tourpackage_name'] ?? 'a tour';

        $action = $notif['action_name'];
        switch ($action) {
            case 'Books':
            case 'Booking':
                $message = "You booked <strong>$tourName</strong>";
                break;
            case 'Cancel Booking':
                $message = "You cancelled your booking for <strong>$tourName</strong>";
                break;
            case 'Payment':
                $message = "You paid for <strong>$tourName</strong>";
                break;
            case 'Refund Booking':
                $message = "Your booking for <strong>$tourName</strong> has been refunded";
                break;
            default:
                $message = htmlspecialchars($notif['action_name']);
        }
    ?>
        <div class="mb-1">
            <a class="dropdown-item py-3 rounded-3 <?= $isUnread ? 'bg-primary bg-opacity-10 fw-semibold border-start border-primary border-4' : 'text-muted' ?> mark-as-read"
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
                        <div class="text-muted xsmall mt-1"><?= $message ?></div>
                    </div>
                    <?php if ($isUnread): ?>
                        <div class="ms-2">
                            <span class="badge bg-danger rounded-pill">New</span>
                        </div>
                    <?php endif; ?>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
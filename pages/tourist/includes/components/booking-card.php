<?php?>


<head>
    <style>
        :root {
            --primary-color: #ffffff;
            --secondary-color: #213638;
            --accent: #E5A13E;
            --secondary-accent: #CFE7E5;
            --muted-color: gainsboro;

            /*Booking Status Color*/
            --pending-for-payment: #F9A825 ;
            --pending-for-approval: #EF6C00 ;
            --approved: #3A8E5C;
            --in-progress: #009688;
            --completed: #1A6338;
            --cancelled: #F44336;
            --cancelled-no-refund: #BC2E2A;
            --refunded: #42325D;    
            --failed: #820000;
            --rejected-by-guide: #B71C1C;
            --booking-expired-payment-not-completed: #695985;
            --booking-expired-guide-did-not-confirm-in-time: #695985;
        }
        .status-badge {
    padding: 6px 16px;
    border-radius: 999px;
    font-size: 0.85rem;
    font-weight: 600;
    color: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    white-space: nowrap;
}

/* Booking Status Colors */
.status-pending-payment {
    background-color: var(--pending-for-payment);
}

.status-pending {
    background-color: var(--pending-for-approval);
}

.status-approved {
    background-color: var(--approved);
}

.status-in-progress {
    background-color: var(--in-progress);
}

.status-completed {
    background-color: var(--completed);
}

.status-cancelled {
    background-color: var(--cancelled);
}

.status-cancelled-no-refund {
    background-color: var(--cancelled-no-refund);
}

.status-refunded {
    background-color: var(--refunded);
}

.status-failed {
    background-color: var(--failed);
}

.status-rejected {
    background-color: var(--rejected-by-guide);
}

.status-expired {
    background-color: var(--booking-expired-payment-not-completed);
}

    </style>
</head>
<?php foreach ($bookings as $booking){
    $guideAccount_ID = $bookingObj->getGuideAccountIDByBookingID($booking['booking_ID']);
    $guide = $bookingObj->getGuideDetailsByAccountID($guideAccount_ID);
    $bookingDetails = $bookingObj->getBookingDetailsByBookingID($booking['booking_ID']);
    $tourpackages = $bookingObj->getTourPackageDetailsByBookingID($booking['booking_ID']);
    $bookingdateattime = $bookingObj->startingDateAndTime($booking['booking_ID']);
    $active_statuses = [ 'Pending for Payment','Pending for Approval','Approved','In Progress','Completed'];
    if (in_array($booking['booking_status'], $active_statuses)) {
?>
<div class="col-md-6">
    <div class="booking-card">
        <div class="d-flex justify-content-between align-items-start">
            <div class="d-flex">
                <img src="<?= $guide['account_profilepic'] ?? ''; ?>"  alt = "Guide" class="guide-img me-3">
                <div>
                    <h5 class="mb-1"><?= $tourpackages['tourpackage_name'] ?? '';?></h5>
                    <p class="text-muted mb-1"><i class="fas fa-user"></i><?= $guide['guide_fullname'] ?? '';?></p>
                    <p class="text-muted mb-1"><i class="fas fa-calendar"></i> <?= $bookingdateattime ?></p>
                    <p class="text-muted mb-0"><i class="fas fa-map-marker-alt"></i> Rome, Italy</p>
                </div>
            </div>
            <?php
                $statusClassMap = [
                    'Pending for Payment' => 'status-pending-payment',
                    'Pending for Approval' => 'status-pending',
                    'Approved' => 'status-approved',
                    'In Progress' => 'status-in-progress',
                    'Completed' => 'status-completed',
                    'Cancelled' => 'status-cancelled',
                    'Cancelled - No Refund' => 'status-cancelled-no-refund',
                    'Refunded' => 'status-refunded',
                    'Failed' => 'status-failed',
                    'Rejected by the Guide' => 'status-rejected',
                    'Booking Expired — Payment Not Completed' => 'status-expired',
                    'Booking Expired — Guide Did Not Confirm in Time' => 'status-expired'
                ];

                if (isset($statusClassMap[$booking['booking_status']])) {
                    echo '<span class="status-badge ' . $statusClassMap[$booking['booking_status']] . '">'
                        . htmlspecialchars($booking['booking_status']) .
                        '</span>';
                }
                ?>

        </div>
        <div class="mt-3">
            <button class="btn btn-outline-primary btn-sm me-2"><i class="fas fa-comment"></i> Message</button>
            <button class="btn btn-outline-primary btn-sm me-2"><i class="fas fa-map"></i> View Details</button>
            <button class="btn btn-outline-primary btn-sm"><i class="fas fa-directions"></i> Directions</button>
        </div>
    </div>
</div>
<?php }}

?>
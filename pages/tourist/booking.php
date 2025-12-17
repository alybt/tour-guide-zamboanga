<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role_name'] !== 'Tourist') {
    header('Location: ../../index.php');
    exit;
}

require_once "../../classes/booking.php";
require_once "../../classes/tourist.php";

$tourist_ID = $_SESSION['account_ID'];
$touristObj = new Tourist();
$bookingObj = new Booking();

$bookings = $bookingObj->viewBookingByTourist($tourist_ID); 


$pendingCount = 0; 
$approvedCount = 0; 
$progressCount = 0; 
$completedCount = 0; 
$cancelledCount = 0; 
$upcomingCount = 0; 

foreach ($bookings as $booking) {

    switch ($booking['booking_status']) {
        case 'Pending for Payment':
        case 'Pending for Approval':
            $pendingCount++;
            break;
        case 'Approved':
            $approvedCount++;
            break;
        case 'In Progress':
            $progressCount++;
            break;
        case 'Completed':
            $completedCount++;
            break;
        case 'Cancelled':
        case 'Cancelled - No Refund':
        case 'Rejected by the Guide':
            $cancelledCount++;
            break;
    }

    $current_date = new DateTime();
    $start_date = new DateTime($booking['booking_start_date']);


    if (in_array($booking['booking_status'], ['Approved']) && $start_date > $current_date) {
        $upcomingCount++;
    }
}


$totalTours = count($bookings); 


function getStatusDetails($status) {
    $class = '';
    $text = htmlspecialchars($status);
    $icon = '';

    switch ($status) {
        case 'Approved':
            $class = 'status-approved';
            $icon = 'fa-check-circle';
            $text = 'Confirmed';
            break;
        case 'In Progress':
            $class = 'status-in-progress';
            $icon = 'fa-spinner fa-spin';
            $text = 'In Progress';
            break;
        case 'Completed':
            $class = 'status-completed';
            $icon = 'fa-check-double';
            $text = 'Completed';
            break;
        case 'Cancelled':
        case 'Cancelled - No Refund':
        case 'Rejected by the Guide':
            $class = 'status-cancelled';
            $icon = 'fa-times-circle';
            $text = 'Cancelled';
            break;
        case 'Pending for Payment':
            $class = 'status-pending';
            $icon = 'fa-money-bill-alt';
            $text = 'Pending Payment';
            break;
        case 'Pending for Approval':
            $class = 'status-pending-approval';
            $icon = 'fa-hourglass-half';
            $text = 'Awaiting Confirmation';
            break;
        default:
            $class = 'status-pending';
            $icon = 'fa-question-circle';
            $text = 'Pending';
            break;
    }
    return ['class' => $class, 'text' => $text, 'icon' => $icon];
}


function getTimeRemaining($date_string) {
    $now = new DateTime();
    $start_date = new DateTime($date_string);
    
    if ($start_date < $now) return false;
    
    $interval = $now->diff($start_date);
    $parts = [];

    if ($interval->y) $parts[] = $interval->y . ' year' . ($interval->y > 1 ? 's' : '');
    if ($interval->m) $parts[] = $interval->m . ' month' . ($interval->m > 1 ? 's' : '');
    if ($interval->d) $parts[] = $interval->d . ' day' . ($interval->d > 1 ? 's' : '');
    if ($interval->h) $parts[] = $interval->h . ' hour' . ($interval->h > 1 ? 's' : '');
    if ($interval->i && empty($parts)) $parts[] = $interval->i . ' minute' . ($interval->i > 1 ? 's' : '');

    return implode(' ', array_slice($parts, 0, 2));
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - Tourismo Zamboanga</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/main.min.css">

    <link rel="stylesheet" href="../../assets/node_modules/@fortawesome/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/tourist/booking-test.css">

</head>
<body>
    
    <?php include_once 'includes/header.php'; 
    ?>

    <div class="container mb-5">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="calendar-container h-100 p-4">
                    <h4 class="mb-4"><i class="fas fa-calendar-alt me-2"></i>Booking Schedule</h4>
                    <div id="calendar"></div>
                </div>
            </div>

            <div class="col-lg-5">
                <h4 class="mb-4"><i class="fas fa-list-ul me-2"></i>Active Bookings List</h4>
                <a href="booking-history.php" class="btn btn-outline-primary mb-3"><i class="fas fa-history me-2"></i>Booking History</a>
                <div id="booking-list-container" style="max-height: 800px; overflow-y: auto;">
                    <?php 
                        include 'includes\components\booking-card-for-bookings.php';
                    ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script src="../../assets/node_modules/jquery/dist/jquery.min.js"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
    <script src="../../assets/node_modules/@fullcalendar/core/index.global.min.js"></script>
    <script src="../../assets/node_modules/@fullcalendar/daygrid/index.global.min.js"></script>

    <script>
        $(document).ready(function() {

            var calendarEl = document.getElementById('calendar');
            
            if (calendarEl) {
                var calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    height: 'auto', 
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,dayGridWeek' 
                    },

                    buttonText: {
                        today: 'Today',
                        month: 'Month',
                        week: 'Week'
                    },
                    events: [
                        <?php 
                        foreach ($bookings as $booking): 

                            if (in_array($booking['booking_status'], ['Completed','Cancelled','Cancelled - No Refund','Refunded','Failed','Rejected by the Guide','Booking Expired — Payment Not Completed','Booking Expired — Guide Did Not Confirm in Time'])) continue;
                            
                            $color = match($booking['booking_status']) {
                                'Pending for Payment' => '#F9A825',
                                'Pending for Approval' => '#EF6C00',
                                'Approved' => '#3A8E5C',
                                'In Progress' => '#009688',
                                default => '#E5A13E'
                            };
                            $textColor = ($color == '#EF6C00' || $color == '#009688' || $color == '#3A8E5C') ? '#ffffff' : '#213638';
                        ?>
                        {
                            title: '<?= addslashes($booking['tourpackage_name']) ?> (<?= getStatusDetails($booking['booking_status'])['text'] ?>)',
                            start: '<?= $booking['booking_start_date'] ?>T<?= $booking['booking_start_time'] ?? '00:00:00' ?>',
                            end: '<?= date('Y-m-d', strtotime($booking['booking_end_date'] . ' +1 day')) ?>T<?= $booking['booking_end_time'] ?? '23:59:59' ?>',
                            backgroundColor: '<?= $color ?>',
                            borderColor: '<?= $color ?>',
                            url: 'booking-view.php?id=<?= $booking['booking_ID'] ?>',
                            textColor: '<?= $textColor ?>'
                        },
                        <?php endforeach; ?>
                    ],
                    eventClick: function(info) {
                        info.jsEvent.preventDefault();
                        if (info.event.url) {
                            window.location.href = info.event.url;
                        }
                    }
                });
                
                calendar.render();
            }


            $('.cancel-booking').on('click', function(e) {
                e.preventDefault();
                const url = $(this).attr('href');
                const tourName = $(this).data('name');

                if (confirm(`Are you sure you want to cancel your booking for "${tourName}"?`)) {
                    window.location.href = url;
                }
            });
        });
    </script>
</body>
</html>
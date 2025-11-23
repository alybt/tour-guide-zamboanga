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
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tourismo Zamboanga - My Bookings</title>

    <link rel="stylesheet" href="../../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/vendor/bootstrap-icons/bootstrap-icons.css" >
    
    <link rel="stylesheet" href="../../assets/css/tourist/booking.css">
    <link rel="stylesheet" href="../../assets/css/tourist/header.css">
    
    <style>
        
    </style>
</head>
<body>
<?php 
    require_once "includes/header.php"; 
    include_once "includes/header.php";
?>

 

<main class="main-content py-4">
    <div class="container">
        <!-- Alerts -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>
                <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3 mb-md-0">
                <div class="stats-card pending">
                    <div class="icon">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <h3><?= $pendingCount ?></h3>
                    <p>Pending Bookings</p>
                </div>
            </div>
            <div class="col-md-4 mb-3 mb-md-0">
                <div class="stats-card approved">
                    <div class="icon">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <h3><?= $approvedCount ?></h3>
                    <p>Approved Bookings</p>
                </div>
            </div>
            <div class="col-md-4 mb-3 mb-md-0">
                <div class="stats-card in-progress">
                    <div class="icon">
                        <i class="bi bi-compass"></i>
                    </div>
                    <h3><?= $progressCount ?></h3>
                    <p>In Progress</p>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons mb-5">
            <a href="tour-packages-browse.php" class="btn btn-primary">
                Browse Tour Packages
            </a>
            <a href="booking-history.php" class="btn btn-outline-secondary">
                View Booking History
            </a>
        </div>

        <!-- NEW: Two-column layout -->
        <div class="row g-5">
            <!-- LEFT: Calendar (2/3 on large screens) -->
            <div class="calendar-filter col-lg-8">
                <div class="calendar-container h-100">
                    <h4 class="mb-4">Booking Calendar</h4>
                    <div id="calendar" class="h-100"></div>
                </div>
            </div>

            <!-- RIGHT: Active Bookings (1/3 on large screens) -->
            <div class="col-lg-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="mb-0">Active Bookings</h4>
                    <div class="view-toggle">
                        <button class="btn active" id="gridViewBtn">Grid</button>
                        <button class="btn" id="listViewBtn">List</button>
                    </div>
                </div>

                <?php if (!empty($bookings)): ?>
                    <div id="bookingsContainer" class="grid-view" style="max-height: 80vh; overflow-y: auto;">
                        <?php 
                        $no = 1; 
                        foreach ($bookings as $booking): 
                            if (!in_array($booking['booking_status'], ['Pending for Payment', 'Pending for Approval', 'Approved', 'In Progress'])) continue;
                            $statusClass = match($booking['booking_status']) {
                                'Pending for Payment', 'Pending for Approval' => 'status-pending',
                                'Approved' => 'status-approved',
                                'In Progress' => 'status-progress',
                                default => ''
                            };
                        ?>
                            <div class="booking-card <?= $statusClass ?> mb-4">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <small class="text-white-50">#<?= $no++ ?></small>
                                    <span class="badge 
                                        <?= $booking['booking_status'] == 'Pending for Payment' ? 'bg-warning' : 
                                           ($booking['booking_status'] == 'Pending for Approval' ? 'bg-info' : 
                                           ($booking['booking_status'] == 'Approved' ? 'bg-success' : 'bg-secondary')) ?>">
                                        <?= htmlspecialchars($booking['booking_status']) ?>
                                    </span>
                                </div>
                                <div class="card-body py-3">
                                    <h6 class="mb-2"><?= htmlspecialchars($booking['tourpackage_name']) ?></h6>
                                    <small class="text-muted d-block mb-2">
                                        <?= date('M j', strtotime($booking['booking_start_date'])) ?> → <?= date('M j', strtotime($booking['booking_end_date'])) ?>
                                    </small>
                                    <div class="d-flex gap-2 mt-3">
                                        <?php if ($booking['booking_status'] == 'Pending for Payment'): ?>
                                            <a href="payment-form.php?id=<?= $booking['booking_ID'] ?>" class="btn btn-success btn-sm flex-fill">
                                                Pay
                                            </a>
                                        <?php endif; ?>
                                        <a href="booking-view.php?id=<?= $booking['booking_ID'] ?>" class="btn btn-primary btn-sm flex-fill">
                                            View
                                        </a>
                                        <a href="booking-cancel.php?id=<?= $booking['booking_ID'] ?>" 
                                           class="btn btn-danger btn-sm cancel-booking"
                                           data-name="<?= htmlspecialchars($booking['tourpackage_name']) ?>">
                                            Cancel
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state text-center p-4 bg-white rounded border">
                        <i class="bi bi-inbox fs-1 text-muted"></i>
                        <h5 class="mt-3">No Active Bookings</h5>
                        <p class="text-muted small">Start exploring tours!</p>
                        <a href="tour-packages-browse.php" class="btn btn-primary mt-2">Explore Tours</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<script src="../../assets/vendor/twbs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/vendor/components/jquery/jquery.min.js"></script>


<script src="../../assets/node_modules/@fullcalendar/core/index.global.min.js"></script>
    
<script src="../../assets/node_modules/@fullcalendar/daygrid/index.global.min.js"></script>


<script>
    $(document).ready(function() {
        // Initialize FullCalendar
        var calendarEl = document.getElementById('calendar');
        
        if (calendarEl) {
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,listWeek'
                },
                events: [
                    <?php foreach ($bookings as $booking): 
                        if (!in_array($booking['booking_status'], ['Pending for Payment', 'Pending for Approval', 'Approved', 'In Progress'])) continue;
                        
                        $color = '#E5A13E';
                        if ($booking['booking_status'] == 'Pending for Payment' || $booking['booking_status'] == 'Pending for Approval') {
                            $color = '#CFE7E5';
                        } elseif ($booking['booking_status'] == 'Approved') {
                            $color = '#E5A13E';
                        } elseif ($booking['booking_status'] == 'In Progress') {
                            $color = '#213638';
                        }
                    ?>
                    {
                        title: '<?= addslashes($booking['tourpackage_name']) ?>',
                        start: '<?= $booking['booking_start_date'] ?>',
                        end: '<?= date('Y-m-d', strtotime($booking['booking_end_date'] . ' +1 day')) ?>',
                        color: '<?= $color ?>',
                        url: 'booking-view.php?id=<?= $booking['booking_ID'] ?>',
                        textColor: '<?= $color == '#CFE7E5' ? '#213638' : '#ffffff' ?>'
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

        // Cancel booking confirmation
        $('.cancel-booking').on('click', function(e) {
            e.preventDefault();
            const url = $(this).attr('href');
            const tourName = $(this).data('name');

            if (confirm(`Are you sure you want to cancel your booking for "${tourName}"?`)) {
                window.location.href = url;
            }
        });

        // View toggle
        $('#gridViewBtn').on('click', function() {
            $('#bookingsContainer').removeClass('list-view').addClass('grid-view');
            $(this).addClass('active');
            $('#listViewBtn').removeClass('active');
        });

        $('#listViewBtn').on('click', function() {
            $('#bookingsContainer').removeClass('grid-view').addClass('list-view');
            $(this).addClass('active');
            $('#gridViewBtn').removeClass('active');
        });
    });
</script>
</body>
</html>
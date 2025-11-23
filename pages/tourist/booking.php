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
        :root {
            --primary-color: #ffffff;
            --secondary-color: #213638;
            --accent: #E5A13E;
            --secondary-accent: #CFE7E5;
            --muted-color: gainsboro;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            color: var(--secondary-color);
            line-height: 1.6;
        }

        .page-header {
            background: var(--secondary-color);
            color: var(--primary-color);
            padding: 3rem 0 2rem;
            margin-bottom: 2rem;
        }

        .page-header h2 {
            font-weight: 300;
            font-size: 2rem;
            letter-spacing: -0.5px;
        }

        .page-header p {
            opacity: 0.8;
            font-weight: 300;
        }

        .stats-card {
            background: var(--primary-color);
            border-radius: 8px;
            padding: 2rem;
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
        }

        .stats-card:hover {
            border-color: var(--accent);
            box-shadow: 0 2px 8px rgba(229, 161, 62, 0.1);
        }

        .stats-card .icon {
            width: 48px;
            height: 48px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .stats-card.pending .icon {
            background: var(--secondary-accent);
            color: var(--secondary-color);
        }

        .stats-card.approved .icon {
            background: var(--accent);
            color: var(--primary-color);
        }

        .stats-card.progress .icon {
            background: var(--secondary-color);
            color: var(--primary-color);
        }

        .stats-card h3 {
            font-size: 2rem;
            font-weight: 300;
            color: var(--secondary-color);
            margin-bottom: 0.25rem;
        }

        .stats-card p {
            color: #6c757d;
            font-size: 0.9rem;
            font-weight: 400;
            margin: 0;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .action-buttons .btn {
            border-radius: 6px;
            padding: 0.75rem 1.5rem;
            font-weight: 400;
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }

        .action-buttons .btn-primary {
            background: var(--accent);
            border-color: var(--accent);
            color: var(--primary-color);
        }

        .action-buttons .btn-primary:hover {
            background: #d69435;
            border-color: #d69435;
            transform: translateY(-1px);
        }

        .action-buttons .btn-outline-secondary {
            border-color: var(--muted-color);
            color: var(--secondary-color);
            background: var(--primary-color);
        }

        .action-buttons .btn-outline-secondary:hover {
            background: var(--secondary-color);
            border-color: var(--secondary-color);
            color: var(--primary-color);
        }

        .calendar-container {
            background: var(--primary-color);
            border-radius: 8px;
            padding: 2rem;
            border: 1px solid #e9ecef;
            margin-bottom: 2rem;
            width: 100vw;
            height: 100vh;
            position: relative;
            left: 50%;
            right: 50%;
            margin-left: -50vw;
            margin-right: -50vw;
            display: flex;
            flex-direction: column;
        }

        .calendar-container h4 {
            font-weight: 400;
            color: var(--secondary-color);
            margin-bottom: 1.5rem;
            font-size: 1.25rem;
        }

        #calendar {
            flex: 1;
            overflow: auto;
        }

        .fc {
            border: none;
        }

        .fc .fc-toolbar-title {
            font-size: 1.5rem;
            font-weight: 400;
            color: var(--secondary-color);
        }

        .fc .fc-button {
            background: var(--secondary-color);
            border: 1px solid var(--secondary-color);
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: 400;
            transition: all 0.2s ease;
        }

        .fc .fc-button:hover {
            background: var(--accent);
            border-color: var(--accent);
        }

        .fc .fc-button-active {
            background: var(--accent) !important;
            border-color: var(--accent) !important;
        }

        .fc .fc-daygrid-day.fc-day-today {
            background: rgba(229, 161, 62, 0.05);
        }

        .fc-event {
            border: none;
            border-radius: 4px;
            padding: 0.25rem 0.5rem;
            font-weight: 400;
            cursor: pointer;
        }

        .booking-list-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .booking-list-header h4 {
            font-weight: 400;
            color: var(--secondary-color);
            font-size: 1.25rem;
            margin: 0;
        }

        .view-toggle {
            display: flex;
            gap: 0.5rem;
        }

        .view-toggle .btn {
            border-radius: 6px;
            padding: 0.5rem 1rem;
            font-weight: 400;
            transition: all 0.3s ease;
            background: var(--primary-color);
            border: 1px solid var(--muted-color);
            color: var(--secondary-color);
        }

        .view-toggle .btn:hover {
            border-color: var(--accent);
            color: var(--accent);
        }

        .view-toggle .btn.active {
            background: var(--accent);
            border-color: var(--accent);
            color: var(--primary-color);
        }

        .booking-card {
            background: var(--primary-color);
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
            position: relative;
        }

        .booking-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--muted-color);
            transition: all 0.3s ease;
        }

        .booking-card:hover {
            border-color: var(--accent);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .booking-card:hover::before {
            background: var(--accent);
        }

        .booking-card.status-pending::before {
            background: var(--secondary-accent);
        }

        .booking-card.status-approved::before {
            background: var(--accent);
        }

        .booking-card.status-progress::before {
            background: var(--secondary-color);
        }

        .card-header {
            background: var(--secondary-color);
            border: none;
            padding: 1rem 1.5rem;
        }

        .card-header span {
            color: var(--primary-color);
        }

        .status-badge {
            padding: 0.375rem 0.875rem;
            border-radius: 4px;
            font-weight: 400;
            font-size: 0.875rem;
        }

        .bg-warning {
            background: var(--accent) !important;
            color: var(--primary-color) !important;
        }

        .bg-info {
            background: var(--secondary-accent) !important;
            color: var(--secondary-color) !important;
        }

        .bg-success {
            background: var(--accent) !important;
            color: var(--primary-color) !important;
        }

        .bg-secondary {
            background: var(--secondary-color) !important;
            color: var(--primary-color) !important;
        }

        .card-body {
            padding: 1.5rem;
        }

        .card-title {
            font-weight: 400;
            color: var(--secondary-color);
            font-size: 1.25rem;
            margin-bottom: 0.75rem;
        }

        .card-text {
            color: #6c757d;
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .booking-info {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 1.25rem;
            margin-top: 1rem;
            border: 1px solid #e9ecef;
        }

        .booking-info-item {
            display: flex;
            align-items: center;
            margin-bottom: 0.875rem;
            font-size: 0.95rem;
        }

        .booking-info-item:last-child {
            margin-bottom: 0;
        }

        .booking-info-item i {
            color: var(--accent);
            margin-right: 0.875rem;
            font-size: 1rem;
            width: 20px;
            text-align: center;
        }

        .booking-info-item span {
            color: var(--secondary-color);
        }

        .booking-info-item strong {
            font-weight: 500;
            margin-right: 0.5rem;
        }

        .card-footer-actions {
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
            padding: 1rem 1.5rem;
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .card-footer-actions .btn {
            border-radius: 6px;
            font-weight: 400;
            padding: 0.5rem 1rem;
            transition: all 0.3s ease;
            flex: 1;
            min-width: fit-content;
            font-size: 0.9rem;
        }

        .card-footer-actions .btn-success {
            background: var(--accent);
            border-color: var(--accent);
        }

        .card-footer-actions .btn-success:hover {
            background: #d69435;
            border-color: #d69435;
            transform: translateY(-1px);
        }

        .card-footer-actions .btn-danger {
            background: #dc3545;
            border-color: #dc3545;
        }

        .card-footer-actions .btn-danger:hover {
            background: #c82333;
            border-color: #c82333;
            transform: translateY(-1px);
        }

        .card-footer-actions .btn-primary {
            background: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        .card-footer-actions .btn-primary:hover {
            background: #1a2b2d;
            border-color: #1a2b2d;
            transform: translateY(-1px);
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: var(--primary-color);
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--muted-color);
            margin-bottom: 1.5rem;
        }

        .empty-state h4 {
            color: var(--secondary-color);
            font-weight: 400;
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            color: #6c757d;
            margin-bottom: 2rem;
        }

        .empty-state .btn {
            background: var(--accent);
            border-color: var(--accent);
            color: var(--primary-color);
            padding: 0.75rem 2rem;
            border-radius: 6px;
        }

        .empty-state .btn:hover {
            background: #d69435;
            border-color: #d69435;
        }

        .alert {
            border-radius: 6px;
            border: 1px solid;
            padding: 1rem 1.5rem;
            font-size: 0.95rem;
        }

        .alert-success {
            background: rgba(229, 161, 62, 0.1);
            border-color: var(--accent);
            color: var(--secondary-color);
        }

        .alert-danger {
            background: rgba(220, 53, 69, 0.1);
            border-color: #dc3545;
            color: var(--secondary-color);
        }

        .list-view .booking-card {
            margin-bottom: 1.5rem;
        }

        .grid-view .row {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }

        @media (max-width: 768px) {
            .calendar-container {
                padding: 1rem;
            }

            .stats-card {
                margin-bottom: 1rem;
            }

            .booking-list-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .grid-view .row {
                grid-template-columns: 1fr;
            }

            .page-header h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
<?php 
    require_once "includes/header.php"; 
    include_once "includes/header.php";
?>

<div class="page-header">
    <div class="container">
        <h2><i class="bi bi-calendar-check me-2"></i>My Bookings</h2>
        <p class="mb-0 mt-2">Manage and track all your tour bookings</p>
    </div>
</div>

<main>   
<div class="container py-4">
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

    <!-- Statistics Cards -->
    <?php 
    $pendingCount = 0;
    $approvedCount = 0;
    $progressCount = 0;
    foreach ($bookings as $booking) {
        if ($booking['booking_status'] == 'Pending for Payment' || $booking['booking_status'] == 'Pending for Approval') {
            $pendingCount++;
        } elseif ($booking['booking_status'] == 'Approved') {
            $approvedCount++;
        } elseif ($booking['booking_status'] == 'In Progress') {
            $progressCount++;
        }
    }
    ?>

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
        <div class="col-md-4">
            <div class="stats-card progress">
                <div class="icon">
                    <i class="bi bi-compass"></i>
                </div>
                <h3><?= $progressCount ?></h3>
                <p>In Progress</p>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="action-buttons">
        <a href="tour-packages-browse.php" class="btn btn-primary">
            <i class="bi bi-search me-2"></i>Browse Tour Packages
        </a>
        <a href="booking-history.php" class="btn btn-outline-secondary">
            <i class="bi bi-clock-history me-2"></i>View Booking History
        </a>
    </div>

    <!-- Calendar View -->
    <div class="calendar-container">
        <h4><i class="bi bi-calendar3 me-2"></i>Booking Calendar</h4>
        <div id="calendar"></div>
    </div>

    <!-- Booking List -->
    <div class="booking-list-header">
        <h4><i class="bi bi-list-ul me-2"></i>Active Bookings</h4>
        <div class="view-toggle">
            <button class="btn active" id="gridViewBtn">
                <i class="bi bi-grid-3x3-gap me-1"></i> Grid
            </button>
            <button class="btn" id="listViewBtn">
                <i class="bi bi-list me-1"></i> List
            </button>
        </div>
    </div>

    <?php if (!empty($bookings)): ?>
        <div id="bookingsContainer" class="grid-view">
            <div class="row g-4">
                <?php 
                $no = 1; 
                foreach ($bookings as $booking): 
                    if (!in_array($booking['booking_status'], ['Pending for Payment', 'Pending for Approval', 'Approved', 'In Progress'])) continue;
                    
                    $statusClass = '';
                    if ($booking['booking_status'] == 'Pending for Payment' || $booking['booking_status'] == 'Pending for Approval') {
                        $statusClass = 'status-pending';
                    } elseif ($booking['booking_status'] == 'Approved') {
                        $statusClass = 'status-approved';
                    } elseif ($booking['booking_status'] == 'In Progress') {
                        $statusClass = 'status-progress';
                    }
                ?>
                    <div class="col-12">
                        <div class="card booking-card <?= $statusClass ?>">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span>#<?= $no++ ?></span>
                                <span class="badge 
                                    <?= $booking['booking_status'] == 'Pending for Payment' ? 'bg-warning' : 
                                       ($booking['booking_status'] == 'Pending for Approval' ? 'bg-info' : 
                                       ($booking['booking_status'] == 'Approved' ? 'bg-success' : 'bg-secondary')) ?> 
                                    status-badge">
                                    <?= htmlspecialchars($booking['booking_status']) ?>
                                </span>
                            </div>

                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bi bi-geo-alt me-2" style="color: var(--accent);"></i>
                                    <?= htmlspecialchars($booking['tourpackage_name']) ?>
                                </h5>
                                <p class="card-text">
                                    <?= htmlspecialchars($booking['tourpackage_desc']) ?>
                                </p>

                                <div class="booking-info">
                                    <div class="booking-info-item">
                                        <i class="bi bi-clock"></i>
                                        <span><strong>Duration:</strong> <?= htmlspecialchars($booking['schedule_days']) ?> days</span>
                                    </div>
                                    <div class="booking-info-item">
                                        <i class="bi bi-person-badge"></i>
                                        <span><strong>Guide:</strong> <?= htmlspecialchars($booking['guide_name']) ?></span>
                                    </div>
                                    <div class="booking-info-item">
                                        <i class="bi bi-calendar-event"></i>
                                        <span><strong>Start:</strong> <?= htmlspecialchars($booking['booking_start_date']) ?></span>
                                    </div>
                                    <div class="booking-info-item">
                                        <i class="bi bi-calendar-check"></i>
                                        <span><strong>End:</strong> <?= htmlspecialchars($booking['booking_end_date']) ?></span>
                                    </div>
                                    <div class="booking-info-item">
                                        <i class="bi bi-map"></i>
                                        <span><strong>Spots:</strong> <?= htmlspecialchars($booking['tour_spots'] ?? '—') ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="card-footer-actions">
                                <?php if ($booking['booking_status'] == 'Pending for Payment'): ?>
                                    <a href="payment-form.php?id=<?= $booking['booking_ID'] ?>" 
                                       class="btn btn-success"> 
                                        <i class="bi bi-credit-card me-1"></i>Pay Now
                                    </a>
                                    <a href="booking-cancel.php?id=<?= $booking['booking_ID'] ?>" 
                                       class="btn btn-danger cancel-booking"
                                       data-name="<?= htmlspecialchars($booking['tourpackage_name']) ?>">
                                        <i class="bi bi-x-circle me-1"></i>Cancel
                                    </a>
                                <?php else: ?>
                                    <a href="booking-cancel.php?id=<?= $booking['booking_ID'] ?>" 
                                       class="btn btn-danger cancel-booking"
                                       data-name="<?= htmlspecialchars($booking['tourpackage_name']) ?>">
                                        <i class="bi bi-x-circle me-1"></i>Cancel
                                    </a>
                                <?php endif; ?>
                                <a href="booking-view.php?id=<?= $booking['booking_ID'] ?>" 
                                   class="btn btn-primary">
                                    <i class="bi bi-eye me-1"></i>View Details
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="bi bi-inbox"></i>
            <h4>No Active Bookings</h4>
            <p>You currently have no active bookings. Start exploring amazing tour packages!</p>
            <a href="tour-packages-browse.php" class="btn">
                <i class="bi bi-compass me-2"></i>Explore Tours
            </a>
        </div>
    <?php endif; ?>
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
<?php
session_start();
$current_page = basename($_SERVER['PHP_SELF']);

// Redirect if not logged in or not a Guide
if (!isset($_SESSION['user']) || $_SESSION['user']['role_name'] !== 'Tour Guide') {
    header('Location: ../../index.php');
    exit;
}

// Status-based redirects
if ($_SESSION['user']['account_status'] === 'Suspended') {
    header('Location: account-suspension.php');
    exit;
}
if ($_SESSION['user']['account_status'] === 'Pending') {
    header('Location: account-pending.php');
    exit;
}

require_once "../../classes/guide.php";
require_once "../../classes/booking.php";

$bookingObj = new Booking();
$guideObj = new Guide();

$guide_ID = $guideObj->getGuide_ID($_SESSION['user']['account_ID']);
$bookings = $bookingObj->getBookingByGuideID($guide_ID);

// Calculate statistics by time period
$today = new DateTime();
$thisWeekStart = (clone $today)->modify('Monday this week');
$thisMonthStart = (clone $today)->modify('first day of this month');
$thisYearStart = (clone $today)->modify('first day of January');

$statsThisWeek = 0;
$statsThisMonth = 0;
$statsThisYear = 0;
$earningsThisWeek = 0;
$earningsThisMonth = 0;
$earningsThisYear = 0;

if (!empty($bookings)) {
    foreach ($bookings as $booking) {
        $bookingDate = new DateTime($booking['booking_start_date']);
        $amount = floatval($booking['booking_amount'] ?? 0);
        
        // This Week
        if ($bookingDate >= $thisWeekStart && $bookingDate <= $today) {
            $statsThisWeek++;
            $earningsThisWeek += $amount;
        }
        
        // This Month
        if ($bookingDate >= $thisMonthStart && $bookingDate <= $today) {
            $statsThisMonth++;
            $earningsThisMonth += $amount;
        }
        
        // This Year
        if ($bookingDate >= $thisYearStart && $bookingDate <= $today) {
            $statsThisYear++;
            $earningsThisYear += $amount;
        }
    }
}

// Prepare calendar events
$calendarEvents = [];
if (!empty($bookings)) {
    foreach ($bookings as $booking) {
        $calendarEvents[] = [
            'title' => $booking['tourpackage_name'],
            'start' => $booking['booking_start_date'],
            'end' => $booking['booking_end_date'],
            'status' => $booking['booking_status'],
            'id' => $booking['booking_ID']
        ];
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Bookings | TourGuide PH</title>

    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="../../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/vendor/bootstrap-icons/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <!-- FullCalendar CSS -->
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css' rel='stylesheet' />

    <link rel="stylesheet" href="../../assets/css/guide/booking.css">
    <link rel="stylesheet" href="../../assets/css/guide/dashboard.css">

    
</head>
<body class="d-flex">

    <?php 
        require_once "includes/aside-dashboard.php"; 
    ?>

    <!-- Main Content -->
    <main class="main-content d-flex flex-column">
        <!-- Header -->
        <div class="header-card d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <div>
                <h3 class="mb-1 fw-bold">My Bookings</h3>
                <p class="text-muted mb-0">Manage and track all your tour bookings.</p>
            </div>
            <div class="text-md-end">
                <div class="d-flex align-items-center gap-3 flex-wrap justify-content-md-end">
                    <span class="badge bg-success status-badge">
                        <i class="bi bi-check-circle"></i> <?= ucfirst($_SESSION['user']['account_status']) ?>
                    </span>
                    <div class="clock" id="liveClock"></div>
                </div>
                <small class="text-muted d-block mt-1">Philippine Standard Time (PST)</small>
            </div>
        </div>

        <!-- Alerts -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert-custom alert-success p-3">
                <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert-custom alert-error p-3">
                <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Quick Links -->
        <div class="d-flex gap-2 mb-4 flex-wrap">
            <a href="tour-packages-browse.php" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-search"></i> Browse Packages
            </a>
            <a href="booking-history.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-clock-history"></i> Booking History
            </a>
        </div>

        <!-- Statistics by Time Period -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon" style="background: linear-gradient(135deg, #E5A13E, #f39c12);">
                                <i class="bi bi-calendar-week"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 text-muted">This Week</h6>
                                <h4 class="mb-1 fw-bold text-accent"><?= $statsThisWeek ?></h4>
                                <small class="text-muted">₱ <?= number_format($earningsThisWeek, 2) ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon" style="background: linear-gradient(135deg, #213638, #2e8b57);">
                                <i class="bi bi-calendar-month"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 text-muted">This Month</h6>
                                <h4 class="mb-1 fw-bold" style="color: var(--secondary-color);"><?= $statsThisMonth ?></h4>
                                <small class="text-muted">₱ <?= number_format($earningsThisMonth, 2) ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon" style="background: linear-gradient(135deg, #CFE7E5, #a8e6cf);">
                                <i class="bi bi-calendar-year"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 text-muted">This Year</h6>
                                <h4 class="mb-1 fw-bold" style="color: #27ae60;"><?= $statsThisYear ?></h4>
                                <small class="text-muted">₱ <?= number_format($earningsThisYear, 2) ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Calendar Section -->
        <div class="card mb-4" style="border: 1px solid #e9ecef;">
            <div class="card-header" style="background: #f8f9fa; border-bottom: 1px solid #e9ecef; padding: 1.5rem;">
                <h5 class="mb-0 fw-bold">Booking Calendar</h5>
            </div>
            <div class="card-body" style="padding: 1.5rem;">
                <div id="calendar"></div>
            </div>
        </div>

        <!-- Bookings Table -->
        <div class="table-container">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Package</th>
                            <th>Description</th>
                            <th>Days</th>
                            <th>Tourist</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Status</th>
                            <th>Spots</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($bookings)): ?>
                            <?php $no = 1; foreach ($bookings as $booking): ?>
                                <?php 
                                $status = $booking['booking_status'];
                                $isPending = in_array($status, ['Pending for Payment', 'Pending for Approval', 'Approved']);
                                if (!$isPending) continue;
                                ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><strong><?= htmlspecialchars($booking['tourpackage_name']) ?></strong></td>
                                    <td class="text-truncate" style="max-width: 180px;">
                                        <?= htmlspecialchars($booking['tourpackage_desc']) ?>
                                    </td>
                                    <td><?= htmlspecialchars($booking['schedule_days']) ?> days</td>
                                    <td><?= htmlspecialchars($booking['tourist_name']) ?></td>
                                    <td><?= date('M d, Y', strtotime($booking['booking_start_date'])) ?></td>
                                    <td><?= date('M d, Y', strtotime($booking['booking_end_date'])) ?></td>
                                    <td>
                                        <?php
                                        $badgeClass = match($status) {
                                            'Pending for Payment' => 'bg-warning text-dark',
                                            'Pending for Approval' => 'bg-info text-white',
                                            'Approved' => 'bg-success text-white',
                                            default => 'bg-secondary'
                                        };
                                        ?>
                                        <span class="badge <?= $badgeClass ?> status-badge">
                                            <?= htmlspecialchars($status) ?>
                                        </span>
                                    </td>
                                    <td class="text-truncate" style="max-width: 120px;">
                                        <?= htmlspecialchars($booking['tour_spots'] ?? '—') ?>
                                    </td>
                                    <td>
                                        <?php if ($status === 'Pending for Payment'): ?>
                                            <a href="booking-view.php?booking_ID=<?= $booking['booking_ID'] ?? ''; ?>&tourist_ID=<?= $booking['tourist_ID'] ?? ''; ?>" class="btn btn-sm btn-outline-primary">View</a>

                                        <?php elseif ($status === 'Pending for Approval'): ?>
                                            <a href="booking-approve.php?id=<?= $booking['booking_ID'] ?>" 
                                               class="btn btn-sm btn-success"
                                               onclick="return confirm('Approve this booking?')">
                                                Approve
                                            </a>
                                            <a href="booking-reject.php?id=<?= $booking['booking_ID'] ?>" 
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirm('Reject this booking?')">
                                                Reject
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                    You currently have no active bookings.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Bootstrap JS -->
    <script src="../../assets/vendor/twbs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <!-- FullCalendar JS -->
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>

    <!-- Live Clock (PH Time) -->
    <script>
        function updateClock() {
            const now = new Date();
            const options = {
                timeZone: 'Asia/Manila',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            };
            document.getElementById('liveClock').textContent = now.toLocaleTimeString('en-US', options);
        }
        updateClock();
        setInterval(updateClock, 1000);
    </script>

    <!-- Calendar Initialization -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('calendar');
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,dayGridWeek,listMonth'
                },
                events: <?= json_encode($calendarEvents) ?>,
                eventClick: function(info) {
                    const status = info.event.extendedProps.status;
                    const id = info.event.extendedProps.id;
                    alert('Booking: ' + info.event.title + '\nStatus: ' + status + '\nDate: ' + info.event.start.toDateString());
                },
                eventDidMount: function(info) {
                    const status = info.event.extendedProps.status;
                    let bgColor = '#6c757d';
                    
                    if (status === 'Approved') bgColor = '#28a745';
                    else if (status === 'Pending for Payment') bgColor = '#ffc107';
                    else if (status === 'Pending for Approval') bgColor = '#17a2b8';
                    else if (status === 'In Progress') bgColor = '#007bff';
                    else if (status === 'Completed') bgColor = '#20c997';
                    
                    info.el.style.backgroundColor = bgColor;
                    info.el.style.borderColor = bgColor;
                },
                height: 'auto',
                contentHeight: 'auto'
            });
            calendar.render();
        });
    </script>
</body>
</html>
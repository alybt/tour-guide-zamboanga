<?php
// Set current page if not already set
if (!isset($current_page)) {
    $current_page = basename($_SERVER['PHP_SELF']);
}

function isActive($page) {
    global $current_page;
    return ($current_page === $page) ? 'active' : '';
}

function isActiveMultiple($pages) {
    global $current_page;
    return in_array($current_page, $pages) ? 'active' : '';
}?>

    <link href="../../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/node_modules/@fortawesome/fontawesome-free/css/all.min.css">
<aside class="sidebar">
    <div class="logo px-3">
        <i class="fas fa-map-marked-alt"></i> <span> Tourismo Zamboanga</span>
    </div>
    <nav class="nav flex-column px-2">
        <a class="nav-link <?= isActive('dashboard.php') ?>" href="dashboard.php">
            <i class="bi bi-house-door"></i>
            <span class="nav-text">Dashboard</span>
        </a>
        <a class="nav-link <?= isActive('booking.php') ?>" href="booking.php">
            <i class="bi bi-calendar-check"></i>
            <span class="nav-text">Bookings</span>
        </a>
        <a class="nav-link <?= isActiveMultiple(['tour-packages.php', 'tour-packages-add.php', 'tour-packages-edit.php']) ?>" href="tour-packages.php">
            <i class="bi bi-box-seam"></i>
            <span class="nav-text">Tour Packages</span>
        </a>
        <!-- <a class="nav-link <?= isActive('schedules.php') ?>" href="schedules.php">
            <i class="bi bi-clock-history"></i>
            <span class="nav-text">Schedules</span>
        </a> -->
        <a class="nav-link <?= isActive('payments.php') ?>" href="payments.php">
            <i class="bi bi-credit-card"></i>
            <span class="nav-text">Payout</span>
        </a>
        <hr class="bg-white opacity-25 my-3">
        <a class="nav-link text-warning" href="includes/ajax/account-change.php">
            <i class="bi bi-person-walking"></i>
            <span class="nav-text">Switch to Tourist</span>
        </a>
        <a class="nav-link text-danger" href="logout.php" 
           onclick="return confirm('Logout now? Your last activity will be recorded.');">
            <i class="bi bi-box-arrow-right"></i>
            <span class="nav-text">Logout</span>
        </a>
    </nav>
</aside>
<?php
function isActive($page) {
    global $current_page;
    return ($current_page === $page) ? 'active' : '';
}?>
<aside class="sidebar">
    <div class="logo px-3">
        <span>Tourismo Zamboanga</span>
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
        <a class="nav-link <?= isActive('tour-packages.php') ?>" href="tour-packages.php">
            <i class="bi bi-box-seam"></i>
            <span class="nav-text">Tour Packages</span>
        </a>
        <a class="nav-link <?= isActive('schedules.php') ?>" href="schedules.php">
            <i class="bi bi-clock-history"></i>
            <span class="nav-text">Schedules</span>
        </a>
        <a class="nav-link <?= isActive('payments.php') ?>" href="payments.php">
            <i class="bi bi-credit-card"></i>
            <span class="nav-text">Payments</span>
        </a>
        <hr class="bg-white opacity-25 my-3">
        <a class="nav-link text-warning" href="account-change.php">
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
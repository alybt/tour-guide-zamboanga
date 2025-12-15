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
}
?>

<link href="../../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../../assets/node_modules/@fortawesome/fontawesome-free/css/all.min.css">
<link rel="stylesheet" href="../../assets/vendor/bootstrap-icons/bootstrap-icons.css">

<style>
    .sidebar {
        position: fixed;
        left: 0;
        top: 0;
        bottom: 0;
        width: 250px;
        background: linear-gradient(180deg, var(--secondary-color) 0%, #1a2829 100%);
        color: white;
        overflow-y: auto;
        z-index: 1000;
        box-shadow: 2px 0 10px rgba(0,0,0,0.1);
    }

    .sidebar .logo {
        padding: 20px 15px;
        font-size: 1.5rem;
        font-weight: bold;
        color: white;
        border-bottom: 1px solid rgba(255,255,255,0.1);
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .sidebar .logo i {
        color: var(--accent);
        font-size: 1.8rem;
    }

    .sidebar .nav {
        padding-top: 10px;
    }

    .sidebar .nav-link {
        color: rgba(255, 255, 255, 0.7);
        padding: 12px 15px;
        border-radius: 8px;
        margin: 5px 0;
        display: flex;
        align-items: center;
        gap: 12px;
        transition: all 0.3s ease;
        text-decoration: none;
    }

    .sidebar .nav-link i {
        font-size: 1.2rem;
        width: 24px;
        text-align: center;
    }

    .sidebar .nav-link:hover {
        background-color: rgba(229, 161, 62, 0.1);
        color: var(--accent);
        transform: translateX(5px);
    }

    .sidebar .nav-link.active {
        background-color: var(--accent);
        color: var(--secondary-color);
        font-weight: 600;
    }

    .sidebar .nav-link.active i {
        color: var(--secondary-color);
    }

    .sidebar .nav-link.text-warning {
        color: #ffc107 !important;
    }

    .sidebar .nav-link.text-warning:hover {
        background-color: rgba(255, 193, 7, 0.1);
        color: #ffc107 !important;
    }

    .sidebar .nav-link.text-danger {
        color: #dc3545 !important;
    }

    .sidebar .nav-link.text-danger:hover {
        background-color: rgba(220, 53, 69, 0.1);
        color: #dc3545 !important;
    }

    .sidebar hr {
        margin: 15px 0;
        opacity: 0.25;
    }

    .nav-text {
        font-size: 0.95rem;
    }

    /* Scrollbar styling */
    .sidebar::-webkit-scrollbar {
        width: 6px;
    }

    .sidebar::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.05);
    }

    .sidebar::-webkit-scrollbar-thumb {
        background: rgba(229, 161, 62, 0.5);
        border-radius: 3px;
    }

    .sidebar::-webkit-scrollbar-thumb:hover {
        background: var(--accent);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .sidebar {
            width: 70px;
        }

        .sidebar .logo span,
        .sidebar .nav-text {
            display: none;
        }

        .sidebar .nav-link {
            justify-content: center;
        }

        .sidebar .logo {
            justify-content: center;
        }

        .main-content {
            margin-left: 70px !important;
            width: calc(100% - 70px) !important;
        }
    }
</style>

<aside class="sidebar">
    <div class="logo">
        <i class="fas fa-map-marked-alt"></i>
        <span>Tourismo Zamboanga</span>
    </div>
    <nav class="nav flex-column px-2">
        <a class="nav-link <?= isActive('dashboard.php') ?>" href="dashboard.php">
            <i class="bi bi-house-door"></i>
            <span class="nav-text">Dashboard</span>
        </a>
        
        <a class="nav-link <?= isActiveMultiple(['booking.php', 'booking-view.php', 'booking-requests.php']) ?>" href="booking.php">
            <i class="bi bi-calendar-check"></i>
            <span class="nav-text">Bookings</span>
        </a>
        
        <a class="nav-link <?= isActiveMultiple(['tour-packages.php', 'tour-packages-add.php', 'tour-packages-edit.php', 'tour-packages-view.php']) ?>" href="tour-packages.php">
            <i class="bi bi-box-seam"></i>
            <span class="nav-text">Tour Packages</span>
        </a>
        
        <a class="nav-link <?= isActive('calendar.php') ?>" href="calendar.php">
            <i class="bi bi-calendar3"></i>
            <span class="nav-text">Calendar</span>
        </a>
        
        <a class="nav-link <?= isActiveMultiple(['payout-request.php', 'payout-history.php', 'earnings.php']) ?>" href="payout-request.php">
            <i class="bi bi-wallet2"></i>
            <span class="nav-text">Payout & Earnings</span>
        </a>
        
        <a class="nav-link <?= isActive('reviews.php') ?>" href="reviews.php">
            <i class="bi bi-star"></i>
            <span class="nav-text">Reviews</span>
        </a>
        
        <a class="nav-link <?= isActive('messages.php') ?>" href="messages.php">
            <i class="bi bi-chat-dots"></i>
            <span class="nav-text">Messages</span>
        </a>
        
        <a class="nav-link <?= isActiveMultiple(['profile.php', 'profile-settings.php']) ?>" href="profile-settings.php">
            <i class="bi bi-person-circle"></i>
            <span class="nav-text">Profile Settings</span>
        </a>
        
        <hr class="bg-white opacity-25 my-3">
        
        <!-- <a class="nav-link text-warning" href="includes/ajax/account-change.php">
            <i class="bi bi-person-walking"></i>
            <span class="nav-text">Switch to Tourist</span>
        </a> -->
        
        <a class="nav-link text-danger" href="logout.php" 
           onclick="return confirm('Logout now? Your last activity will be recorded.');">
            <i class="bi bi-box-arrow-right"></i>
            <span class="nav-text">Logout</span>
        </a>
    </nav>
</aside>
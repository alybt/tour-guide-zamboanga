<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role_name'] !== 'Tourist') {
    header('Location: ../../index.php');
    exit;
} else if ($_SESSION['user']['account_status'] == 'Suspended') {
    header('Location: account-suspension.php');
    exit;
} else if ($_SESSION['user']['account_status'] == 'Pending') {
    header('Location: account-pending.php');
    exit;
}
require_once "../../classes/tourist.php";
require_once "../../classes/tour-manager.php";

$tourist_ID = $_SESSION['account_ID'];
$touristObj = new Tourist();
$tourist = $touristObj->getTouristByAccountID($tourist_ID);
$tourmanagerObj = new TourManager();
$upcomingTours = $tourmanagerObj->upcomingToursCountForTourist($tourist_ID);
$tourspotexplored = $touristObj->tourSpotsExplored($tourist_ID);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tourist Dashboard - Tourismo Zamboanga</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #ffffff;
            --secondary-color: #213638;
            --accent: #E5A13E;
            --secondary-accent: #CFE7E5;
            --muted-color: gainsboro;
            --pending-for-payment: #F9A825;
            --pending-for-approval: #EF6C00;
            --approved: #3A8E5C;
            --in-progress: #009688;
            --completed: #1A6338;
            --cancelled: #F44336;
        }

        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin-top: 5rem;
        }

        .navbar {
            background-color: var(--secondary-color) !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .navbar-brand {
            color: var(--primary-color) !important;
            font-weight: bold;
            font-size: 1.5rem;
        }

        .navbar-brand i {
            color: var(--accent);
        }

        .nav-link {
            color: var(--secondary-accent) !important;
            margin: 0 10px;
            transition: color 0.3s;
        }

        .nav-link:hover {
            color: var(--accent) !important;
        }

        .hero-section {
            background: linear-gradient(135deg, var(--secondary-color) 0%, #2d4a4d 100%);
            color: var(--primary-color);
            padding: 40px 0;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--primary-color);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s;
            margin-bottom: 20px;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card i {
            font-size: 2.5rem;
            color: var(--accent);
            margin-bottom: 10px;
        }

        .stat-card h3 {
            color: var(--secondary-color);
            font-size: 2rem;
            margin: 10px 0;
        }

        .stat-card p {
            color: #6c757d;
            margin: 0;
        }

        .booking-card {
            background: var(--primary-color);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 4px solid var(--accent);
        }

        .booking-card .guide-img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
        }

        .status-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-approved {
            background-color: var(--approved);
            color: white;
        }

        .status-pending {
            background-color: var(--pending-for-approval);
            color: white;
        }

        .status-in-progress {
            background-color: var(--in-progress);
            color: white;
        }

        .guide-card {
            background: var(--primary-color);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s;
            margin-bottom: 20px;
        }

        .guide-card:hover {
            transform: translateY(-5px);
        }

        .guide-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .guide-card-body {
            padding: 15px;
        }

        .rating {
            color: var(--accent);
        }

        .btn-primary {
            background-color: var(--accent);
            border-color: var(--accent);
            color: var(--secondary-color);
            font-weight: 600;
        }

        .btn-primary:hover {
            background-color: #d89435;
            border-color: #d89435;
        }

        .btn-outline-primary {
            color: var(--accent);
            border-color: var(--accent);
        }

        .btn-outline-primary:hover {
            background-color: var(--accent);
            border-color: var(--accent);
            color: var(--secondary-color);
        }

        .section-title {
            color: var(--secondary-color);
            font-weight: bold;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid var(--accent);
            display: inline-block;
        }

        .search-bar {
            background: var(--primary-color);
            border-radius: 50px;
            padding: 10px 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .search-bar input {
            border: none;
            background: transparent;
        }

        .search-bar input:focus {
            outline: none;
            box-shadow: none;
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: var(--cancelled);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>
    <?php include_once 'includes/header.php'; 
    ?>

    <div class="container mt-4">
        <div class="hero-section">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1>Welcome back, <?= $tourist['name_first'] ?> 👋</h1>
                    <p class="lead">Ready for your next adventure? Let's find the perfect guide for you.</p>
                    <div class="search-bar mt-3">
                        <div class="input-group">
                            <span class="input-group-text bg-transparent border-0"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" placeholder="Search destinations, guides, or experiences...">
                            <button class="btn btn-primary" type="button">Search</button>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-center d-none d-md-block">
                    <i class="fas fa-globe-americas" style="font-size: 8rem; color: var(--secondary-accent); opacity: 0.3;"></i>
                </div>
            </div>
        </div>

        <!-- Stats Row -->
        <div class="row">
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <i class="fas fa-calendar-check"></i>
                    <h3><?= $upcomingTours ?></h3>
                    <p>Upcoming Tours</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <i class="fas fa-map-marked-alt"></i>
                    <h3><?= $tourspotexplored ?></h3>
                    <p>Cities Explored</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <i class="fas fa-award"></i>
                    <h3>8</h3>
                    <p>Badges Earned</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <i class="fas fa-star"></i>
                    <h3>4.9</h3>
                    <p>Avg. Experience</p>
                </div>
            </div>
        </div>

        'Tourist to Guide', 'Tourist To Tour Spots', 'Tourist to Tour Packages','Guide to Tourist'

        <!-- Upcoming Bookings -->
        <div class="row mt-4">
            <div class="col-12">
                <h2 class="section-title">Upcoming Bookings</h2>
            </div>
            
            <div class="col-md-6">
                <div class="booking-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="d-flex">
                            <img src="https://i.pravatar.cc/150?img=33" alt="Guide" class="guide-img me-3">
                            <div>
                                <h5 class="mb-1">Historic Rome Walking Tour</h5>
                                <p class="text-muted mb-1"><i class="fas fa-user"></i> Guide: Marco Rossi</p>
                                <p class="text-muted mb-1"><i class="fas fa-calendar"></i> Dec 15, 2025 at 9:00 AM</p>
                                <p class="text-muted mb-0"><i class="fas fa-map-marker-alt"></i> Rome, Italy</p>
                            </div>
                        </div>
                        <span class="status-badge status-approved">Approved</span>
                    </div>
                    <div class="mt-3">
                        <button class="btn btn-outline-primary btn-sm me-2"><i class="fas fa-comment"></i> Message</button>
                        <button class="btn btn-outline-primary btn-sm me-2"><i class="fas fa-map"></i> View Details</button>
                        <button class="btn btn-outline-primary btn-sm"><i class="fas fa-directions"></i> Directions</button>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="booking-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="d-flex">
                            <img src="https://i.pravatar.cc/150?img=45" alt="Guide" class="guide-img me-3">
                            <div>
                                <h5 class="mb-1">Street Food Adventure</h5>
                                <p class="text-muted mb-1"><i class="fas fa-user"></i> Guide: Maria Garcia</p>
                                <p class="text-muted mb-1"><i class="fas fa-calendar"></i> Dec 18, 2025 at 6:00 PM</p>
                                <p class="text-muted mb-0"><i class="fas fa-map-marker-alt"></i> Barcelona, Spain</p>
                            </div>
                        </div>
                        <span class="status-badge status-pending">Pending</span>
                    </div>
                    <div class="mt-3">
                        <button class="btn btn-outline-primary btn-sm me-2"><i class="fas fa-comment"></i> Message</button>
                        <button class="btn btn-outline-primary btn-sm me-2"><i class="fas fa-times"></i> Cancel</button>
                        <button class="btn btn-outline-primary btn-sm"><i class="fas fa-edit"></i> Modify</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recommended Guides -->
        <div class="row mt-5">
            <div class="col-12">
                <h2 class="section-title">Recommended Guides for You</h2>
            </div>

            <div class="col-md-3">
                <div class="guide-card">
                    <img src="https://i.pravatar.cc/300?img=12" alt="Guide">
                    <div class="guide-card-body">
                        <h5>Elena Petrova</h5>
                        <p class="text-muted mb-1"><i class="fas fa-map-marker-alt"></i> Paris, France</p>
                        <div class="rating mb-2">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <span class="text-muted">(127)</span>
                        </div>
                        <p class="mb-2"><small><i class="fas fa-language"></i> English, French, Russian</small></p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold" style="color: var(--accent);">$45/hr</span>
                            <button class="btn btn-primary btn-sm">View Profile</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="guide-card">
                    <img src="https://i.pravatar.cc/300?img=68" alt="Guide">
                    <div class="guide-card-body">
                        <h5>Kenji Tanaka</h5>
                        <p class="text-muted mb-1"><i class="fas fa-map-marker-alt"></i> Tokyo, Japan</p>
                        <div class="rating mb-2">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                            <span class="text-muted">(89)</span>
                        </div>
                        <p class="mb-2"><small><i class="fas fa-language"></i> English, Japanese</small></p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold" style="color: var(--accent);">$50/hr</span>
                            <button class="btn btn-primary btn-sm">View Profile</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="guide-card">
                    <img src="https://i.pravatar.cc/300?img=31" alt="Guide">
                    <div class="guide-card-body">
                        <h5>Ahmed Hassan</h5>
                        <p class="text-muted mb-1"><i class="fas fa-map-marker-alt"></i> Cairo, Egypt</p>
                        <div class="rating mb-2">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <span class="text-muted">(156)</span>
                        </div>
                        <p class="mb-2"><small><i class="fas fa-language"></i> English, Arabic</small></p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold" style="color: var(--accent);">$35/hr</span>
                            <button class="btn btn-primary btn-sm">View Profile</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="guide-card">
                    <img src="https://i.pravatar.cc/300?img=47" alt="Guide">
                    <div class="guide-card-body">
                        <h5>Sofia Martinez</h5>
                        <p class="text-muted mb-1"><i class="fas fa-map-marker-alt"></i> Mexico City</p>
                        <div class="rating mb-2">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                            <span class="text-muted">(93)</span>
                        </div>
                        <p class="mb-2"><small><i class="fas fa-language"></i> English, Spanish</small></p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold" style="color: var(--accent);">$40/hr</span>
                            <button class="btn btn-primary btn-sm">View Profile</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Animate stats on load
            $('.stat-card h3').each(function() {
                const $this = $(this);
                const countTo = parseInt($this.text());
                $({ countNum: 0 }).animate({
                    countNum: countTo
                }, {
                    duration: 1000,
                    easing: 'swing',
                    step: function() {
                        $this.text(Math.floor(this.countNum));
                    },
                    complete: function() {
                        $this.text(this.countNum);
                    }
                });
            });

            // View Profile button
            $('.guide-card .btn-primary').on('click', function() {
                alert('Navigating to guide profile...');
            });

            // Booking action buttons
            $('.booking-card button').on('click', function() {
                const action = $(this).text().trim();
                console.log('Action clicked:', action);
            });
        });
    </script>
</body>
</html>
<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role_name'] !== 'Tourist') {
    header('Location: ../../index.php');
    exit;
}

require_once "../../classes/guide.php";
require_once "../../classes/tour-manager.php";
require_once "../../classes/tourist.php";

// Get guide ID from URL
if (!isset($_GET['guide_id']) || empty($_GET['guide_id'])) {
    $_SESSION['error'] = "Invalid guide ID.";
    header("Location: index.php");
    exit();
}

$guide_ID = intval($_GET['guide_id']);
$guideObj = new Guide();
$touristObj = new Tourist();
$tourManager = new TourManager();

$guides = $guideObj->viewAllGuideInfo();
$guideData = null;

foreach ($guides as $guide) {
    if ($guide['guide_ID'] == $guide_ID) {
        $guideData = $guide;
        break;
    }
}

if (!$guideData) {
    $_SESSION['error'] = "Guide not found.";
    header("Location: index.php");
    exit();
}

// Get guide's tour packages
$allPackages = $tourManager->viewAllPackagesInfo();
$guidePackages = array_filter($allPackages, function($pkg) use ($guide_ID) {
    return $pkg['guide_ID'] == $guide_ID;
});

$accountInfo = $tourManager->guideAccountInfo();
$rating = $touristObj->getGuideRating($guide_ID);
$avgRating = $rating['avg'] ?? 0;
$ratingCount = $rating['count'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($guideData['guide_name']) ?> - Guide Profile</title>

    <link rel="stylesheet" href="../../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/vendor/bootstrap-icons/bootstrap-icons.css">
    <link rel="stylesheet" href="../../assets/vendor/components/font-awesome/css/all.min.css">
    <link rel="stylesheet" href="../../assets/node_modules/@fortawesome/fontawesome-free/css/all.min.css">

    <style>
        
        :root {
            --accent: #E5A13E; 
            --secondary-color: #213638; 
            --secondary-accent: #CFE7E5; 
        }
        
        body {
            margin-top: 3rem;
            background: #f8f9fa;
            font-family: 'Poppins', sans-serif;
            color: var(--secondary-color);
        }

        
        .profile-header {
            background: linear-gradient(135deg, var(--secondary-color) 0%, #2e5f5f 100%);
            padding: 60px 0;
            color: white;
            margin-bottom: 2rem; 
        }

        
        .profile-pic { 
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid white; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            margin-bottom: 0; 
        }
        
        
        .verified-badge {
            background: var(--accent);
            color: var(--secondary-color);
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 600;
            display: inline-block;
            margin-top: 10px; 
        }
        
        
        .badge-rating {
            background: var(--accent);
            color: var(--secondary-color);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
        }

        
        .content-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        
        .info-section {
            padding: 0; 
            border-bottom: 1px solid #e9ecef;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .info-section:last-child {
            border-bottom: none;
            padding-bottom: 0;
            margin-bottom: 0;
        }
        .info-label {
            font-weight: 600;
            color: var(--secondary-color);
            display: block;
            margin-bottom: 0.5rem;
            font-size: 1.1rem; 
        }

        
        .package-card { 
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }
        .package-card:hover {
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            border-color: var(--accent);
        }

        
        .btn-book {
            background: var(--accent);
            border: none;
            color: var(--secondary-color);
            padding: 15px 40px; 
            border-radius: 12px;
            font-weight: 600;
            font-size: 1.1rem;
        }
        .btn-book:hover {
            background: #d69435;
            color: var(--secondary-color);
        }
        
        
        .stat-box {
            text-align: center;
            padding: 20px;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--accent);
        }
        
        
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #6c757d;
        }
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <?php require_once "includes/header.php"; ?>

    <div class="profile-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-3 text-center text-md-start">
                    <?php if ($accountInfo && $accountInfo['account_profilepic']): ?>
                        <img src="<?= htmlspecialchars($accountInfo['account_profilepic']) ?>" 
                             alt="<?= htmlspecialchars($guideData['guide_name']) ?>" 
                             class="profile-pic">
                    <?php else: ?>
                        <div class="profile-pic bg-secondary d-flex align-items-center justify-content-center">
                            <i class="bi bi-person-fill text-white" style="font-size: 3rem;"></i>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mt-2">
                        <span class="verified-badge">
                            <i class="fas fa-check-circle me-1"></i> Verified Guide
                        </span>
                    </div>
                </div>
                
                <div class="col-md-6 mt-4 mt-md-0">
                    <h1 class="mb-2"><?= htmlspecialchars($guideData['guide_name']) ?></h1>
                    
                    <?php if ($accountInfo && $accountInfo['account_nickname']): ?>
                        <p class="mb-1 opacity-75">
                            <i class="bi bi-tag me-2"></i> <?= htmlspecialchars($accountInfo['account_nickname']) ?>
                        </p>
                    <?php endif; ?>

                    <div class="d-flex align-items-center gap-3 flex-wrap mb-3">
                        <span class="badge-rating">
                            <i class="bi bi-star-fill"></i> 
                            <?= number_format($avgRating, 1) ?> 
                            <small>(<?= $ratingCount ?> reviews)</small>
                        </span>
                        
                        </div>
                </div>
                
                <div class="col-md-3 text-center mt-4 mt-md-0">
                    <a href="booking-add.php?guide_id=<?= $guide_ID ?>" class="btn btn-book">
                        <i class="fas fa-calendar-plus me-2"></i>Book a Tour
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container mb-5">
        <div class="row">
            <div class="col-lg-8">
                <div class="content-card">
                    <div class="info-section">
                        <span class="info-label">
                            <i class="bi bi-info-circle"></i> About Me
                        </span>
                        <p class="mb-0">
                            <?php if ($accountInfo && $accountInfo['account_aboutme']): ?>
                                <?= nl2br(htmlspecialchars($accountInfo['account_aboutme'])) ?>
                            <?php else: ?>
                                <span class="text-muted">No information provided yet.</span>
                            <?php endif; ?>
                        </p>
                    </div>

                    <?php if ($accountInfo && $accountInfo['account_bio']): ?>
                        <div class="info-section">
                            <span class="info-label">
                                <i class="bi bi-file-text"></i> Bio / Quick Summary
                            </span>
                            <p class="mb-0">
                                <?= nl2br(htmlspecialchars($accountInfo['account_bio'])) ?>
                            </p>
                        </div>
                    <?php endif; ?>

                    <div class="info-section border-bottom-0 pb-0 mb-0">
                        <span class="info-label">
                            <i class="bi bi-telephone"></i> Contact Email
                        </span>
                        <p class="mb-0">
                            <i class="bi bi-envelope"></i> 
                            <a href="mailto:<?= htmlspecialchars($guideData['guide_email']) ?>" style="color: var(--accent);">
                                <?= htmlspecialchars($guideData['guide_email']) ?>
                            </a>
                        </p>
                    </div>
                </div>
                
                <div class="mt-5">
                    <h3 class="mb-4">
                        <i class="bi bi-map-fill" style="color: var(--accent);"></i> 
                        Tour Packages
                    </h3>
                        <?php 
                            include 'includes/components/guide-tour-packages.php';
                        ?>
                </div>
                
                <div class="content-card mt-5">
                    <h4 class="mb-4"><i class="fas fa-comments me-2"></i>Reviews (<?= $ratingCount ?>)</h4>
                    
                    

                    <button class="btn btn-outline-secondary mt-3">
                        Load More Reviews
                    </button>
                </div>
                
            </div>

            <div class="col-lg-4">
                <div class="content-card">
                    <div class="info-section border-bottom-0 pb-0 mb-0">
                        <span class="info-label">
                            <i class="bi bi-shield-check"></i> Guide Information
                        </span>
                        <div class="mb-3">
                            <small class="text-muted">License Number</small>
                            <p class="mb-0 fw-bold"><?= htmlspecialchars($guideData['guide_license'] ?? 'N/A') ?></p>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">Years of Experience</small>
                            <p class="mb-0 fw-bold"><?= htmlspecialchars($guideData['guide_yearsofexperience'] ?? 'N/A') ?> years</p>
                        </div>
                        <div>
                            <small class="text-muted">Total Packages</small>
                            <p class="mb-0 fw-bold"><?= count($guidePackages) ?></p>
                        </div>
                    </div>
                </div>

                <div class="content-card mt-4">
                    <div class="info-section border-bottom-0 pb-0 mb-0">
                        <span class="info-label">
                            <i class="bi bi-lightning"></i> Quick Actions
                        </span>
                        <a href="index.php" class="btn btn-outline-secondary w-100 mb-2">
                            <i class="bi bi-arrow-left"></i> Back to Guides
                        </a>
                        <a href="booking-add.php?guide_id=<?= $guide_ID ?>" class="btn w-100" style="background: var(--accent); color: var(--secondary-color);">
                            <i class="bi bi-calendar-check"></i> Book Now
                        </a>
                    </div>
                </div>
                
                <div class="content-card mt-4">
                    <h5 class="mb-3">Quick Facts</h5>
                    <div class="mb-2"><i class="fas fa-check text-success me-2"></i>Licensed Guide</div>
                    <div class="mb-2"><i class="fas fa-check text-success me-2"></i>Background Checked</div>
                    <div class="mb-2"><i class="fas fa-check text-success me-2"></i>Insurance Covered</div>
                    <div class="mb-2"><i class="fas fa-check text-success me-2"></i>Instant Booking</div>
                    <div class="mb-2"><i class="fas fa-check text-success me-2"></i>Free Cancellation</div>
                </div>
                
            </div>
        </div>
    </div>

    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
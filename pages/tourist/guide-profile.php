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

// Get guide details
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

    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="../../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/vendor/bootstrap-icons/bootstrap-icons.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="../../assets/vendor/components/font-awesome/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet"/>

    <style>
        :root {
            --primary-color: #ffffff;
            --secondary-color: #213638;
            --accent: #E5A13E;
            --secondary-accent: #CFE7E5;
        }

        body {
            background: #f8f9fa;
            font-family: 'Poppins', sans-serif;
            color: var(--secondary-color);
        }

        .profile-header {
            background: linear-gradient(135deg, var(--secondary-color), #2e5f5f);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
        }

        .profile-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .profile-pic {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--accent);
            margin-bottom: 1rem;
        }

        .rating-stars {
            color: #ffc107;
            font-size: 1.2rem;
        }

        .info-section {
            padding: 2rem;
            border-bottom: 1px solid #e9ecef;
        }

        .info-section:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: var(--secondary-color);
            display: block;
            margin-bottom: 0.5rem;
        }

        .package-card {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }

        .package-card:hover {
            box-shadow: 0 4px 12px rgba(229, 161, 62, 0.15);
            border-color: var(--accent);
        }

        .btn-book {
            background: var(--accent);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-book:hover {
            background: #d69435;
            color: white;
        }

        .badge-rating {
            background: var(--accent);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
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

    <!-- Profile Header -->
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
                </div>
                <div class="col-md-9">
                    <h1 class="mb-2"><?= htmlspecialchars($guideData['guide_name']) ?></h1>
                    <?php if ($accountInfo && $accountInfo['account_nickname']): ?>
                        <p class="mb-3 opacity-75">
                            <i class="bi bi-tag"></i> <?= htmlspecialchars($accountInfo['account_nickname']) ?>
                        </p>
                    <?php endif; ?>
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <span class="badge-rating">
                            <i class="bi bi-star-fill"></i> 
                            <?= number_format($avgRating, 1) ?> 
                            <small>(<?= $ratingCount ?> reviews)</small>
                        </span>
                        <span class="badge bg-success">
                            <i class="bi bi-check-circle"></i> Verified Guide
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Profile Content -->
    <div class="container mb-5">
        <div class="row">
            <div class="col-lg-8">
                <!-- About Section -->
                <div class="profile-card">
                    <div class="info-section">
                        <span class="info-label">
                            <i class="bi bi-info-circle"></i> About
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
                                <i class="bi bi-file-text"></i> Bio
                            </span>
                            <p class="mb-0">
                                <?= nl2br(htmlspecialchars($accountInfo['account_bio'])) ?>
                            </p>
                        </div>
                    <?php endif; ?>

                    <div class="info-section">
                        <span class="info-label">
                            <i class="bi bi-telephone"></i> Contact
                        </span>
                        <p class="mb-0">
                            <i class="bi bi-envelope"></i> 
                            <a href="mailto:<?= htmlspecialchars($guideData['guide_email']) ?>">
                                <?= htmlspecialchars($guideData['guide_email']) ?>
                            </a>
                        </p>
                    </div>
                </div>

                <!-- Tour Packages Section -->
                <div class="mt-5">
                    <h3 class="mb-4">
                        <i class="bi bi-map-fill" style="color: var(--accent);"></i> 
                        Tour Packages
                    </h3>

                    <?php if (!empty($guidePackages)): ?>
                        <?php foreach ($guidePackages as $package): ?>
                            <div class="package-card">
                                <div class="row align-items-start">
                                    <div class="col-md-8">
                                        <h5 class="mb-2">
                                            <a href="tour-packages-view.php?id=<?= $package['tourpackage_ID'] ?>" 
                                               class="text-decoration-none" style="color: var(--secondary-color);">
                                                <?= htmlspecialchars($package['tourpackage_name']) ?>
                                            </a>
                                        </h5>
                                        <p class="text-muted mb-2">
                                            <?= htmlspecialchars(substr($package['tourpackage_desc'], 0, 150)) ?>...
                                        </p>
                                        <div class="d-flex gap-3 flex-wrap">
                                            <span class="badge bg-light text-dark">
                                                <i class="bi bi-calendar"></i> <?= $package['schedule_days'] ?> days
                                            </span>
                                            <span class="badge bg-light text-dark">
                                                <i class="bi bi-people"></i> <?= $package['numberofpeople_based'] ?> - <?= $package['numberofpeople_maximum'] ?> pax
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-md-4 text-md-end">
                                        <h6 class="text-success fw-bold mb-3">
                                            <?= $package['pricing_currency'] ?> <?= number_format($package['pricing_foradult'], 2) ?>
                                        </h6>
                                        <a href="tour-packages-view.php?id=<?= $package['tourpackage_ID'] ?>" 
                                           class="btn btn-book btn-sm">
                                            View & Book
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="bi bi-inbox"></i>
                            <p>This guide hasn't created any tour packages yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <div class="profile-card">
                    <div class="info-section">
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
                            <small class="text-muted">Total Bookings</small>
                            <p class="mb-0 fw-bold"><?= count($guidePackages) ?> packages</p>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="profile-card mt-4">
                    <div class="info-section">
                        <span class="info-label">
                            <i class="bi bi-lightning"></i> Quick Actions
                        </span>
                        <a href="index.php" class="btn btn-outline-secondary w-100 mb-2">
                            <i class="bi bi-arrow-left"></i> Back to Guides
                        </a>
                        <a href="booking-add.php?guide_id=<?= $guide_ID ?>" class="btn btn-warning w-100">
                            <i class="bi bi-calendar-check"></i> Book a Tour
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>

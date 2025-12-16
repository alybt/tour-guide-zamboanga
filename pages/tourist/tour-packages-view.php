<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role_name'] !== 'Tourist') {
    header('Location: ../../index.php');
    exit;
}

require_once "../../classes/tour-manager.php";
require_once "../../classes/guide.php";
require_once "../../classes/tourist.php";
$tourist_ID = $_SESSION['user']['account_ID'];
$toristObj = new Tourist();

// Validate and get package ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "Invalid tour package ID.";
    header("Location: tour-packages.php");
    exit();
}

$tourpackage_ID = intval($_GET['id']);
$tourManager = new TourManager();
$guideObj = new Guide();

// Get package details
$package = $tourManager->getTourPackageDetailsByID($tourpackage_ID);

if (!$package) {
    $_SESSION['error'] = "Tour package not found.";
    header("Location: tour-packages.php");
    exit();
}

// Get guide name
$guides = $guideObj->viewAllGuide();
$guideName = "N/A";
$guideID = null;
if ($guides && !empty($package['guide_ID'])) {
    foreach ($guides as $guide) {
        if ($guide['guide_ID'] == $package['guide_ID']) {
            $guideName = htmlspecialchars($guide['guide_name']);
            $guideID = $guide['guide_ID'];
            break;
        }
    }
}

// Get associated tour spots
$spots = $tourManager->getSpotsByPackage($tourpackage_ID);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Tour Package</title>

    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="../../assets/vendor/bootstrap/css/bootstrap.min.css">
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="../../assets/vendor/components/font-awesome/css/all.min.css">
    
    <link rel="stylesheet" href="../../assets/css/tourist/tour-packages-view.css">
    
</head>
<body>
    <?php require_once "includes/header.php"; ?>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-xl-9">
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($_SESSION['error']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <div class="card package-card">
                    <div class="card-header">
                        <h2 class="mb-0 text-white">
                            <i class="fas fa-map-marked-alt me-2"></i>
                            <?= htmlspecialchars($package['tourpackage_name']) ?>
                        </h2>
                    </div>

                    <div class="card-body">
                        <div class="row g-4">
                            <!-- Package Info -->
                            <div class="col-md-6">
                                <p class="mb-3"><span class="info-label">Description:</span></p>
                                <p class="text-muted ms-1"><?= nl2br(htmlspecialchars($package['tourpackage_desc'] ?? 'No description available')) ?></p>
                            </div>

                            <div class="col-md-6">
                                <p class="mb-3"><span class="info-label">Tour Guide:</span></p>
                                <p class="ms-1">
                                    <i class="fas fa-user-tie text-primary"></i>
                                    <?php if ($guideID): ?>
                                        <a href="guide_profile.php?guide_id=<?= $guideID ?>" class="text-decoration-none fw-bold" style="color: #007bff;">
                                            <?= $guideName ?>
                                        </a>
                                    <?php else: ?>
                                        <strong><?= $guideName ?></strong>
                                    <?php endif; ?>
                                </p>
                            </div>

                            <div class="col-md-6">
                                <p class="mb-3"><span class="info-label">Duration:</span></p>
                                <p class="ms-1">
                                    <i class="fas fa-calendar-alt text-info"></i>
                                    <?= htmlspecialchars($package['schedule_days'] ?? 'N/A') ?> day(s)
                                </p>
                            </div>

                            <div class="col-md-6">
                                <p class="mb-3"><span class="info-label">Group Size:</span></p>
                                <p class="ms-1">
                                    <i class="fas fa-users text-success"></i>
                                    <?= htmlspecialchars($package['numberofpeople_based'] ?? 'N/A') ?> - <?= htmlspecialchars($package['numberofpeople_maximum'] ?? 'N/A') ?> people
                                </p>
                            </div>

                            <div class="col-md-6">
                                <p class="mb-3"><span class="info-label">Base Price:</span></p>
                                <h5 class="ms-1 text-success fw-bold">
                                    <?= htmlspecialchars($package['pricing_currency'] ?? 'PHP') ?>
                                    <?= number_format($package['pricing_foradult'] ?? 0, 2) ?>
                                </h5>
                            </div>

                            <div class="col-md-6">
                                <p class="mb-3"><span class="info-label">Discount:</span></p>
                                <h5 class="ms-1 text-danger fw-bold">
                                    - <?= htmlspecialchars($package['pricing_currency'] ?? 'PHP') ?>
                                    <?= number_format($package['pricing_discount'] ?? 0, 2) ?>
                                </h5>
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- Tour Spots -->
                        <h5 class="mb-3">
                            <i class="fas fa-map-marker-alt text-primary"></i> Tour Spots
                        </h5>
                        <?php if (!empty($spots)): ?>
                            <div class="row g-3">
                                <?php foreach ($spots as $spot): ?>
                                    <div class="col-12">
                                        <div class="spot-item">
                                            <h6 class="mb-1 fw-bold text-dark">
                                                <?= htmlspecialchars($spot['spots_name']) ?>
                                            </h6>
                                            <p class="mb-0 text-muted small">
                                                <?= nl2br(htmlspecialchars($spot['spots_description'])) ?>
                                            </p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted fst-italic">No tour spots associated with this package.</p>
                        <?php endif; ?>

                        <div class="d-flex flex-wrap gap-3 mt-5">
                            <a href="booking-add.php?id=<?= htmlspecialchars($package['tourpackage_ID'] ?? '') ?>" 
                               class="btn btn-success btn-book text-white">
                                <i class="fas fa-ticket-alt me-2"></i> Book Now
                            </a>

                            <!-- Back Button -->
                            <a href="tour-packages-browse.php" class="btn btn-outline-secondary btn-back">
                                <i class="fas fa-arrow-left me-2"></i> Back to Packages
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS + Popper -->
    <script src="../../assets/vendor/twbs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="../../assets/vendor/components/jquery/jquery.min.js"></script>

    <script>
        $(document).ready(function() {
            // Optional: Add loading effect on book button
            $('.btn-book').on('click', function(e) {
                const $btn = $(this);
                const originalText = $btn.html();
                $btn.html('<i class="fas fa-spinner fa-spin"></i> Processing...').addClass('disabled');
                
                // Re-enable after navigation (won't happen if redirect)
                setTimeout(() => {
                    $btn.html(originalText).removeClass('disabled');
                }, 3000);
            });
        });
    </script>
</body>
</html>
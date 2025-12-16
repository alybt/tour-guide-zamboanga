<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role_name'] !== 'Admin' || $_SESSION['user']['account_status'] == 'Suspended') {
    header('Location: ../../index.php');
    exit;
}

require_once "../../classes/tour-manager.php";
require_once "../../classes/guide.php";

$error = "";
$package = null;

// Get package ID from URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: tour-packages.php");
    exit();
}

$tourpackage_ID = intval($_GET['id']);
$tourManager = new TourManager();
$guideObj = new Guide();

// Fetch existing package data
$package = $tourManager->getTourPackageById($tourpackage_ID);

if (!$package) {
    $_SESSION['error'] = "Tour package not found.";
    header("Location: tour-packages.php");
    exit();
}

// Get all spots and guides for the form
$spots = $tourManager->getAllSpots();
$guides = $guideObj->viewAllGuide();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize input
    $guide_ID = trim($_POST['guide_ID']);
    $tourpackage_name = trim($_POST['tourpackage_name']);
    $tourpackage_desc = trim($_POST['tourpackage_desc']);
    $schedule_days = trim($_POST['schedule_days']);
    $numberofpeople_maximum = trim($_POST['numberofpeople_maximum']);
    $numberofpeople_based = trim($_POST['numberofpeople_based']);
    $basedAmount = trim($_POST['basedAmount']);
    $discount = trim($_POST['discount'] ?? '0');
    $currency = 'PHP';
    $spots_array = $_POST['spots'] ?? [];

    // Validation
    if (empty($guide_ID)) {
        $error = "Please select a guide";
    } elseif (empty($tourpackage_name)) {
        $error = "Tour package name is required";
    } elseif (empty($tourpackage_desc)) {
        $error = "Description is required";
    } elseif (empty($schedule_days) || !is_numeric($schedule_days) || $schedule_days < 1) {
        $error = "Number of days must be at least 1";
    } elseif (empty($numberofpeople_maximum) || !is_numeric($numberofpeople_maximum) || $numberofpeople_maximum < 1) {
        $error = "Maximum people must be at least 1";
    } elseif (empty($numberofpeople_based) || !is_numeric($numberofpeople_based) || $numberofpeople_based < 1) {
        $error = "Minimum people must be at least 1";
    } elseif (empty($basedAmount) || !is_numeric($basedAmount) || $basedAmount < 0) {
        $error = "Base amount must be a positive number";
    } elseif (!is_numeric($discount) || $discount < 0) {
        $error = "Discount must be 0 or more";
    } else {
        $result = $tourManager->updateTourPackage(
            $tourpackage_ID,
            $guide_ID,
            $tourpackage_name,
            $tourpackage_desc,
            $schedule_days,
            $numberofpeople_maximum,
            $numberofpeople_based,
            $currency,
            $basedAmount,
            $discount,
            $spots_array
        );

        if ($result) {
            $_SESSION['success'] = "Tour package updated successfully!";
            header("Location: tour-packages.php");
            exit();
        } else {
            $error = "Failed to update tour package. Please try again.";
        }
    }

    // Repopulate form with posted data on error
    $package['guide_ID'] = $guide_ID;
    $package['tourpackage_name'] = $tourpackage_name;
    $package['tourpackage_desc'] = $tourpackage_desc;
    $package['schedule_days'] = $schedule_days;
    $package['numberofpeople_maximum'] = $numberofpeople_maximum;
    $package['numberofpeople_based'] = $numberofpeople_based;
    $package['pricing_based'] = $basedAmount;
    $package['pricing_discount'] = $discount;
    $package['spots'] = $spots_array;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Tour Package | Tourismo Zamboanga</title>

    <link rel="stylesheet" href="../../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/vendor/bootstrap-icons/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet"/>

    <style>
        :root {
            --primary-color: #ffffff;
            --secondary-color: #213638;
            --accent: #E5A13E;
            --secondary-accent: #CFE7E5;
            --text-dark: #2d3436;
            --text-light: #636e72;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            color: var(--text-dark);
            min-height: 100vh;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            width: 260px;
            background: var(--secondary-color);
            color: var(--primary-color);
            padding-top: 1.5rem;
            transition: all 0.3s ease;
            z-index: 1000;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }

        .main-content {
            margin-left: 260px;
            padding: 2rem;
            transition: all 0.3s ease;
        }

        .header-card {
            background: var(--primary-color);
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(33, 54, 56, 0.08);
            padding: 1.75rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(207, 231, 229, 0.3);
        }

        .clock {
            font-family: 'Courier New', monospace;
            font-weight: 600;
            color: var(--secondary-color);
            font-size: 1.1rem;
        }

        .status-badge {
            font-size: 0.8rem;
            padding: 0.35rem 0.75rem;
            border-radius: 50px;
            font-weight: 600;
        }

        .form-card {
            background: var(--primary-color);
            border-radius: 14px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            border: 1px solid rgba(207, 231, 229, 0.4);
            padding: 2rem;
        }

        .form-label {
            font-weight: 600;
            color: var(--text-dark);
        }

        .form-control, .form-select {
            border-radius: 10px;
            padding: 0.75rem 1rem;
        }

        .form-check-label {
            font-weight: 500;
        }

        .spot-item {
            background: var(--secondary-accent);
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .alert-custom {
            border-radius: 12px;
            font-weight: 500;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        @media (max-width: 992px) {
            .sidebar {
                width: 80px;
            }
            .sidebar .nav-text,
            .sidebar .logo span {
                display: none;
            }
            .main-content {
                margin-left: 80px;
            }
        }

        @media (max-width: 576px) {
            .main-content {
                padding: 1rem;
            }
            .form-card {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>

    <?php include 'includes/dashboard.php'; ?>

    <main class="main-content">

        <!-- Header -->
        <div class="header-card d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <div>
                <h3 class="mb-1 fw-bold">Edit Tour Package</h3>
                <p class="text-muted mb-0">Update the details of "<?= htmlspecialchars($package['tourpackage_name']) ?>".</p>
            </div>
            <div class="text-md-end">
                <div class="d-flex align-items-center gap-3 flex-wrap justify-content-md-end">
                    <span class="badge bg-success status-badge">
                        <i class="bi bi-shield-check"></i> Active
                    </span>
                    <div class="clock" id="liveClock"></div>
                </div>
                <small class="text-muted d-block mt-1">Philippine Standard Time (PST)</small>
            </div>
        </div>

        <!-- Error Alert -->
        <?php if ($error): ?>
            <div class="alert-custom alert-error p-3">
                <?= htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Edit Form -->
        <div class="form-card">
            <form action="" method="post">

                <div class="row g-4">
                    <!-- Package Name -->
                    <div class="col-md-8">
                        <label for="tourpackage_name" class="form-label">Tour Package Name</label>
                        <input type="text" class="form-control" name="tourpackage_name" id="tourpackage_name"
                               value="<?= htmlspecialchars($package['tourpackage_name']) ?>" required>
                    </div>

                    <!-- Guide -->
                    <div class="col-md-4">
                        <label for="guide_ID" class="form-label">Assigned Guide</label>
                        <select class="form-select" name="guide_ID" id="guide_ID" required>
                            <option value="">-- Select Guide --</option>
                            <?php foreach ($guides as $guide): ?>
                                <option value="<?= $guide['guide_ID'] ?>"
                                    <?= ($package['guide_ID'] == $guide['guide_ID']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($guide['guide_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Description -->
                    <div class="col-12">
                        <label for="tourpackage_desc" class="form-label">Description</label>
                        <textarea class="form-control" name="tourpackage_desc" id="tourpackage_desc" rows="5" required><?= htmlspecialchars($package['tourpackage_desc']) ?></textarea>
                    </div>

                    <!-- Days, Max People, Min People -->
                    <div class="col-md-4">
                        <label for="schedule_days" class="form-label">Schedule Days</label>
                        <input type="number" class="form-control" name="schedule_days" id="schedule_days" min="1"
                               value="<?= htmlspecialchars($package['schedule_days']) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="numberofpeople_maximum" class="form-label">Maximum People</label>
                        <input type="number" class="form-control" name="numberofpeople_maximum" id="numberofpeople_maximum" min="1"
                               value="<?= htmlspecialchars($package['numberofpeople_maximum']) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="numberofpeople_based" class="form-label">Minimum People (Base)</label>
                        <input type="number" class="form-control" name="numberofpeople_based" id="numberofpeople_based" min="1"
                               value="<?= htmlspecialchars($package['numberofpeople_based']) ?>" required>
                    </div>

                    <!-- Pricing -->
                    <div class="col-md-6">
                        <label for="basedAmount" class="form-label">Base Amount (PHP)</label>
                        <input type="number" class="form-control" name="basedAmount" id="basedAmount" min="0" step="0.01"
                               value="<?= htmlspecialchars($package['pricing_based']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="discount" class="form-label">Discount Amount (PHP)</label>
                        <input type="number" class="form-control" name="discount" id="discount" min="0" step="0.01"
                               value="<?= htmlspecialchars($package['pricing_discount'] ?? 0) ?>">
                    </div>

                    <!-- Tourist Spots -->
                    <div class="col-12">
                        <label class="form-label">Select Tourist Spots</label>
                        <div class="row">
                            <?php foreach ($spots as $spot): ?>
                                <div class="col-lg-6">
                                    <div class="spot-item">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="spots[]"
                                                   value="<?= $spot['spots_ID'] ?>"
                                                <?= in_array($spot['spots_ID'], $package['spots']) ? 'checked' : '' ?>
                                                   id="spot_<?= $spot['spots_ID'] ?>">
                                            <label class="form-check-label" for="spot_<?= $spot['spots_ID'] ?>">
                                                <strong><?= htmlspecialchars($spot['spots_name']) ?></strong>
                                            </label>
                                        </div>
                                        <small class="text-muted d-block mt-1">
                                            <?= htmlspecialchars($spot['spots_description']) ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="col-12 text-end">
                        <a href="tour-packages.php" class="btn btn-outline-secondary me-3">
                            <i class="bi bi-x-circle me-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-check-circle me-2"></i> Update Package
                        </button>
                    </div>
                </div>
            </form>
        </div>

    </main>

    <script src="../../assets/vendor/twbs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>

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
</body>
</html>
<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role_name'] !== 'Tour Guide') {
    header('Location: ../../index.php');
    exit;
} else if ($_SESSION['user']['account_status'] == 'Suspended') {
    header('Location: account-suspension.php');
    exit;
} else if ($_SESSION['user']['account_status'] == 'Pending') {
    header('Location: account-pending.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

require_once "../../classes/guide.php";
require_once "../../classes/tour-manager.php";

$tourMgrObj = new TourManager();
$guideObj   = new Guide();

$guide_ID = $guideObj->getGuide_ID($_SESSION['user']['account_ID']);
$spots    = $guideObj->getAllSpots();

$tourpackage_ID = intval($_GET['id']);

// --- Load Related Data ---
$tourpackage       = $tourMgrObj->getTourPackageByID($tourpackage_ID);
$schedule          = $tourMgrObj->getScheduleByID($tourpackage['schedule_ID']);
$numberofpeople    = $tourMgrObj->getPeopleByID($schedule['numberofpeople_ID']);
$pricing           = $tourMgrObj->getPricingByID($numberofpeople['pricing_ID']);
$tourpackage_spots = $tourMgrObj->getSpotsByPackageID($tourpackage_ID);

if (!$tourpackage) {
    $_SESSION['error'] = "Package not found.";
    header("Location: tour-packages.php");
    exit;
}

/* -------------------------------------------------
   Flash Data
   ------------------------------------------------- */
$old      = $_SESSION['old_input'] ?? [];
$errors   = $_SESSION['errors']   ?? [];
$success  = $_SESSION['success']  ?? '';

unset($_SESSION['old_input'], $_SESSION['errors'], $_SESSION['success']);

/* -------------------------------------------------
   Populate fields from DB
   ------------------------------------------------- */
$pkg = [
    'tourpackage_name'       => $old['tourpackage_name']       ?? $tourpackage['tourpackage_name'] ?? '',
    'tourpackage_desc'       => $old['tourpackage_desc']       ?? $tourpackage['tourpackage_desc'] ?? '',
    'schedule_days'          => $old['schedule_days']          ?? $schedule['schedule_days'] ?? 1,
    'numberofpeople_maximum' => $old['numberofpeople_maximum'] ?? $numberofpeople['numberofpeople_maximum'] ?? '',
    'numberofpeople_based'   => $old['numberofpeople_based']   ?? $numberofpeople['numberofpeople_based'] ?? '',
    'pricing_foradult'       => $old['pricing_foradult']       ?? $pricing['pricing_foradult'] ?? '',
    'pricing_forchild'       => $old['pricing_forchild']       ?? $pricing['pricing_forchild'] ?? '',
    'pricing_foryoungadult'  => $old['pricing_foryoungadult']  ?? $pricing['pricing_foryoungadult'] ?? '',
    'pricing_forsenior'      => $old['pricing_forsenior']      ?? $pricing['pricing_forsenior'] ?? '',
    'pricing_forpwd'         => $old['pricing_forpwd']         ?? $pricing['pricing_forpwd'] ?? '',
    'include_meal'           => $old['include_meal']           ?? $pricing['include_meal'] ?? 0,
    'meal_fee'               => $old['meal_fee']               ?? $pricing['meal_fee'] ?? '0.00',
    'transport_fee'          => $old['transport_fee']          ?? $pricing['transport_fee'] ?? '0.00',
    'discount'               => $old['discount']               ?? $pricing['discount'] ?? '0.00',
];

/* -------------------------------------------------
   Form Submission
   ------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrfToken, $_POST['csrf_token'] ?? '')) {
        $errors['general'] = 'Invalid CSRF token. Please try again.';
    } else {
        $posted = [
            'tourpackage_name'       => trim($_POST['tourpackage_name'] ?? ''),
            'tourpackage_desc'       => trim($_POST['tourpackage_desc'] ?? ''),
            'schedule_days'          => $_POST['schedule_days'] ?? '',
            'numberofpeople_maximum' => $_POST['numberofpeople_maximum'] ?? '',
            'numberofpeople_based'   => $_POST['numberofpeople_based'] ?? '',
            'pricing_foradult'       => $_POST['pricing_foradult'] ?? '',
            'pricing_forchild'       => $_POST['pricing_forchild'] ?? '',
            'pricing_foryoungadult'  => $_POST['pricing_foryoungadult'] ?? '',
            'pricing_forsenior'      => $_POST['pricing_forsenior'] ?? '',
            'pricing_forpwd'         => $_POST['pricing_forpwd'] ?? '',
            'include_meal'           => isset($_POST['include_meal']) ? 1 : 0,
            'meal_fee'               => $_POST['meal_fee'] ?? '0.00',
            'transport_fee'          => $_POST['transport_fee'] ?? '0.00',
            'discount'               => $_POST['discount'] ?? '0.00',
            'itinerary'              => $_POST['itinerary'] ?? [],
        ];

        $v = new class {
            public $errors = [];
            public function required($val, $name) {
                if ($val === '') $this->errors[$name] = ucfirst(str_replace('_', ' ', $name)) . ' is required.';
            }
            public function numeric($val, $name, $min = null) {
                if (!is_numeric($val) || $val < 0) {
                    $this->errors[$name] = ucfirst(str_replace('_', ' ', $name)) . ' must be a positive number.';
                } elseif ($min !== null && $val < $min) {
                    $this->errors[$name] = ucfirst(str_replace('_', ' ', $name)) . " must be at least $min.";
                }
            }
        };

        $v->required($posted['tourpackage_name'], 'tourpackage_name');
        $v->required($posted['tourpackage_desc'], 'tourpackage_desc');
        $v->required($posted['schedule_days'], 'schedule_days');
        $v->numeric($posted['schedule_days'], 'schedule_days', 1);
        $v->required($posted['numberofpeople_maximum'], 'numberofpeople_maximum');
        $v->numeric($posted['numberofpeople_maximum'], 'numberofpeople_maximum', 1);
        $v->required($posted['numberofpeople_based'], 'numberofpeople_based');
        $v->numeric($posted['numberofpeople_based'], 'numberofpeople_based', 1);
        $v->required($posted['pricing_foradult'], 'pricing_foradult');
        $v->numeric($posted['pricing_foradult'], 'pricing_foradult', 0);
        if ($posted['include_meal']) $v->numeric($posted['meal_fee'], 'meal_fee', 0);
        $v->numeric($posted['transport_fee'], 'transport_fee', 0);
        $v->numeric($posted['discount'], 'discount', 0);

        $itinerary = $posted['itinerary'];
        if (!is_array($itinerary) || empty($itinerary)) {
            $v->errors['itinerary'] = 'At least one itinerary item is required.';
        } else {
            foreach ($itinerary as $idx => $item) {
                $spot     = $item['spot'] ?? '';
                $activity = trim($item['activity_name'] ?? '');
                $day      = $item['day'] ?? '';
                $start    = $item['start_time'] ?? '';
                $end      = $item['end_time'] ?? '';

                if ($spot === '' && $activity === '') {
                    $v->errors["itinerary_$idx"] = "Row " . ($idx + 1) . ": Select a spot or enter an activity.";
                }
                if ($day === '' || !ctype_digit((string)$day) || $day < 1 || $day > $posted['schedule_days']) {
                    $v->errors["itinerary_day_$idx"] = "Row " . ($idx + 1) . ": Invalid day.";
                }
                if ($start && $end) {
                    $startSec = strtotime($start);
                    $endSec   = strtotime($end);
                    if ($endSec <= $startSec) {
                        $v->errors["itinerary_time_$idx"] = "Row " . ($idx + 1) . ": Activity cannot go past midnight.";
                    }
                    foreach ($itinerary as $j => $other) {
                        if ($j === $idx) continue;
                        if (($other['day'] ?? '') == $day && !empty($other['start_time']) && !empty($other['end_time'])) {
                            $otherStart = strtotime($other['start_time']);
                            $otherEnd   = strtotime($other['end_time']);
                            if ($startSec < $otherEnd && $endSec > $otherStart) {
                                $v->errors["itinerary_overlap_$idx"] =
                                    "Row " . ($idx + 1) . ": Time overlaps with Row " . ($j + 1) . " on Day $day.";
                                break;
                            }
                        }
                    }
                }
            }
        }

        $errors = $v->errors;

        if (empty($errors)) {
            $tour_spots = $activities = $startTimes = $endTimes = $days = $packagespots_id = [];

            foreach ($itinerary as $item) {
                $packagespots_id[] = $item['packagespot_ID'] ?? null;
                $tour_spots[]      = $item['spot'] === '' ? null : $item['spot'];
                $activities[]      = trim($item['activity_name'] ?? '');
                $startTimes[]      = $item['start_time'] ?? null;
                $endTimes[]        = $item['end_time'] ?? null;
                $days[]            = $item['day'] ?? null;
            }

            $result = $tourMgrObj->updateTourPackagesAndItsSpots(
                $packagespots_id, $tour_spots, $activities, $startTimes, $endTimes, $days,
                $tourpackage_ID, $guide_ID, $posted['tourpackage_name'], $posted['tourpackage_desc'],
                $schedule['schedule_ID'], $posted['schedule_days'],
                $numberofpeople['numberofpeople_ID'], $posted['numberofpeople_maximum'], $posted['numberofpeople_based'],
                $pricing['pricing_ID'], 'PHP', $posted['pricing_foradult'], $posted['pricing_forchild'] ?? 0,
                $posted['pricing_foryoungadult'] ?? 0, $posted['pricing_forsenior'] ?? 0, $posted['pricing_forpwd'] ?? 0,
                $posted['include_meal'], $posted['meal_fee'], $posted['transport_fee'], $posted['discount']
            );

            if ($result) {
                $_SESSION['success'] = 'Tour package updated successfully!';
                header('Location: tour-packages.php');
                exit;
            } else {
                $errors['general'] = 'Failed to update package.';
            }
        }
    }

    $_SESSION['old_input'] = $posted;
    $pkg = $posted;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Edit Tour Package | TourGuide PH</title>

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

        /* Sidebar */
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

        .sidebar .logo {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--accent);
            text-align: center;
            margin-bottom: 2rem;
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.85);
            padding: 0.85rem 1.5rem;
            border-radius: 0;
            transition: all 0.2s;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(229, 161, 62, 0.15);
            color: var(--accent);
        }

        .sidebar .nav-link i {
            font-size: 1.2rem;
            width: 24px;
            text-align: center;
        }

        .sidebar .nav-text {
            white-space: nowrap;
        }

        /* Main Content */
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

        .status-badge {
            font-size: 0.8rem;
            padding: 0.35rem 0.75rem;
            border-radius: 50px;
            font-weight: 600;
        }

        .clock {
            font-family: 'Courier New', monospace;
            font-weight: 600;
            color: var(--secondary-color);
            font-size: 1.1rem;
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

        .itinerary-item {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 1rem;
            position: relative;
            transition: all 0.2s;
        }

        .itinerary-item.error {
            border-color: #dc3545;
            background-color: #fdf2f2;
        }

        .remove-btn {
            position: absolute;
            top: 0.75rem;
            right: 0.75rem;
            background: #dc3545;
            color: white;
            border: none;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            font-weight: bold;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
        }

        .time-error {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }

        .alert-custom {
            border-radius: 12px;
            font-weight: 500;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        /* Responsive */
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

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="logo px-3">
            <span>TourGuide PH</span>
        </div>
        <nav class="nav flex-column px-2">
            <a class="nav-link" href="dashboard.php">
                <i class="bi bi-house-door"></i>
                <span class="nav-text">Dashboard</span>
            </a>
            <a class="nav-link" href="booking.php">
                <i class="bi bi-calendar-check"></i>
                <span class="nav-text">Bookings</span>
            </a>
            <a class="nav-link active" href="tour-packages.php">
                <i class="bi bi-box-seam"></i>
                <span class="nav-text">Tour Packages</span>
            </a>
            <a class="nav-link" href="schedules.php">
                <i class="bi bi-clock-history"></i>
                <span class="nav-text">Schedules</span>
            </a>
            <a class="nav-link" href="payments.php">
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

    <!-- Main Content -->
    <main class="main-content">

        <!-- Header -->
        <div class="header-card d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <div>
                <h3 class="mb-1 fw-bold">Edit Tour Package</h3>
                <p class="text-muted mb-0">Update package details, pricing, and itinerary.</p>
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

        <!-- Back Button -->
        <div class="mb-3">
            <a href="tour-packages.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Back to Packages
            </a>
        </div>

        <!-- Alerts -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert-custom alert-success p-3">
                <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($errors['general'])): ?>
            <div class="alert-custom alert-error p-3">
                <?= htmlspecialchars($errors['general']) ?>
            </div>
        <?php endif; ?>

        <!-- Edit Form -->
        <div class="form-card">
            <form method="post" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label">Package Name <span class="text-danger">*</span></label>
                        <input type="text" name="tourpackage_name" class="form-control <?= isset($errors['tourpackage_name']) ? 'is-invalid' : '' ?>" 
                               value="<?= htmlspecialchars($pkg['tourpackage_name']) ?>" required>
                        <?php if (isset($errors['tourpackage_name'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['tourpackage_name']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Schedule Days <span class="text-danger">*</span></label>
                        <input type="number" id="schedule_days" name="schedule_days" min="1" class="form-control <?= isset($errors['schedule_days']) ? 'is-invalid' : '' ?>" 
                               value="<?= htmlspecialchars($pkg['schedule_days']) ?>" required>
                        <?php if (isset($errors['schedule_days'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['schedule_days']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea name="tourpackage_desc" rows="3" class="form-control <?= isset($errors['tourpackage_desc']) ? 'is-invalid' : '' ?>" 
                                  required><?= htmlspecialchars($pkg['tourpackage_desc']) ?></textarea>
                        <?php if (isset($errors['tourpackage_desc'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['tourpackage_desc']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Max People <span class="text-danger">*</span></label>
                        <input type="number" name="numberofpeople_maximum" min="1" class="form-control <?= isset($errors['numberofpeople_maximum']) ? 'is-invalid' : '' ?>" 
                               value="<?= htmlspecialchars($pkg['numberofpeople_maximum']) ?>" required>
                        <?php if (isset($errors['numberofpeople_maximum'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['numberofpeople_maximum']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Min People <span class="text-danger">*</span></label>
                        <input type="number" name="numberofpeople_based" min="1" class="form-control <?= isset($errors['numberofpeople_based']) ? 'is-invalid' : '' ?>" 
                               value="<?= htmlspecialchars($pkg['numberofpeople_based']) ?>" required>
                        <?php if (isset($errors['numberofpeople_based'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['numberofpeople_based']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <hr class="my-4">

                <h5 class="mb-3">Pricing (PHP)</h5>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Adult <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="pricing_foradult" class="form-control <?= isset($errors['pricing_foradult']) ? 'is-invalid' : '' ?>" 
                               value="<?= htmlspecialchars($pkg['pricing_foradult']) ?>" required>
                        <?php if (isset($errors['pricing_foradult'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['pricing_foradult']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Child</label>
                        <input type="number" step="0.01" name="pricing_forchild" class="form-control" value="<?= htmlspecialchars($pkg['pricing_forchild']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Young Adult</label>
                        <input type="number" step="0.01" name="pricing_foryoungadult" class="form-control" value="<?= htmlspecialchars($pkg['pricing_foryoungadult']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Senior</label>
                        <input type="number" step="0.01" name="pricing_forsenior" class="form-control" value="<?= htmlspecialchars($pkg['pricing_forsenior']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">PWD</label>
                        <input type="number" step="0.01" name="pricing_forpwd" class="form-control" value="<?= htmlspecialchars($pkg['pricing_forpwd']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Discount (%)</label>
                        <input type="number" step="0.01" name="discount" class="form-control" value="<?= htmlspecialchars($pkg['discount']) ?>">
                    </div>
                </div>

                <div class="row g-3 mt-2">
                    <div class="col-md-4">
                        <div class="form-check">
                            <input type="checkbox" name="include_meal" id="include_meal" class="form-check-input" <?= $pkg['include_meal'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="include_meal">Include Meal</label>
                        </div>
                    </div>
                    <div class="col-md-4" id="mealFeeContainer" style="<?= $pkg['include_meal'] ? '' : 'display:none;' ?>">
                        <label class="form-label">Meal Fee</label>
                        <input type="number" step="0.01" name="meal_fee" class="form-control" value="<?= htmlspecialchars($pkg['meal_fee']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Transport Fee</label>
                        <input type="number" step="0.01" name="transport_fee" class="form-control" value="<?= htmlspecialchars($pkg['transport_fee']) ?>">
                    </div>
                </div>

                <hr class="my-4">

                <h5 class="mb-3">Itinerary</h5>
                <div id="itinerary-container">
                    <?php if (!empty($tourpackage_spots)): ?>
                        <?php foreach ($tourpackage_spots as $idx => $spot): ?>
                            <div class="itinerary-item">
                                <button type="button" class="remove-btn" onclick="removeItinerary(this)">X</button>
                                <input type="hidden" name="itinerary[<?= $idx ?>][packagespot_ID]" value="<?= $spot['packagespot_ID'] ?? '' ?>">

                                <div class="row g-3">
                                    <div class="col-md-2">
                                        <label class="form-label">Day</label>
                                        <select name="itinerary[<?= $idx ?>][day]" class="form-select day-select">
                                            <?php for ($d = 1; $d <= $pkg['schedule_days']; $d++): ?>
                                                <option value="<?= $d ?>" <?= ($spot['packagespot_day'] ?? 1) == $d ? 'selected' : '' ?>><?= $d ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label">Spot</label>
                                        <select name="itinerary[<?= $idx ?>][spot]" class="form-select" onchange="toggleActivity(this)">
                                            <option value="">-- Custom Activity --</option>
                                            <?php foreach ($spots as $s): ?>
                                                <option value="<?= $s['spots_ID'] ?>" <?= $spot['spots_ID'] == $s['spots_ID'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($s['spots_name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label">Activity</label>
                                        <input type="text" name="itinerary[<?= $idx ?>][activity_name]" class="form-control" 
                                               value="<?= htmlspecialchars($spot['packagespot_activityname'] ?? '') ?>" 
                                               style="<?= !empty($spot['spots_ID']) ? 'display:none;' : '' ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Start Time</label>
                                        <input type="time" name="itinerary[<?= $idx ?>][start_time]" class="form-control" 
                                               value="<?= htmlspecialchars($spot['packagespot_starttime'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">End Time</label>
                                        <input type="time" name="itinerary[<?= $idx ?>][end_time]" class="form-control" 
                                               value="<?= htmlspecialchars($spot['packagespot_endtime'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">No itinerary items yet. Add one below.</p>
                    <?php endif; ?>
                </div>

                <button type="button" class="btn btn-outline-primary btn-sm mb-3" onclick="addItinerary()">
                    <i class="bi bi-plus-circle"></i> Add Stop/Activity
                </button>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle"></i> Save Changes
                    </button>
                    <a href="tour-packages.php" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </main>

    <!-- Bootstrap JS -->
    <script src="../../assets/vendor/twbs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Live Clock -->
    <script>
        function updateClock() {
            const now = new Date();
            const options = { timeZone: 'Asia/Manila', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true };
            document.getElementById('liveClock').textContent = now.toLocaleTimeString('en-US', options);
        }
        updateClock();
        setInterval(updateClock, 1000);
    </script>

    <!-- Form Logic -->
    <script>
        document.getElementById('include_meal').addEventListener('change', function () {
            document.getElementById('mealFeeContainer').style.display = this.checked ? '' : 'none';
        });

        function toggleActivity(select) {
            const row = select.closest('.row');
            const activityInput = row.querySelector('input[name$="[activity_name]"]');
            activityInput.style.display = select.value === '' ? '' : 'none';
        }

        function removeItinerary(btn) {
            btn.closest('.itinerary-item').remove();
            validateItineraryRealtime();
        }

        function updateDayDropdowns() {
            const days = parseInt(document.getElementById('schedule_days').value) || 1;
            document.querySelectorAll('.day-select').forEach(sel => {
                const val = sel.value;
                sel.innerHTML = '';
                for (let d = 1; d <= days; d++) {
                    const opt = new Option(d, d, false, val == d);
                    sel.add(opt);
                }
            });
            validateItineraryRealtime();
        }

        document.getElementById('schedule_days').addEventListener('input', updateDayDropdowns);

        function addItinerary() {
            const container = document.getElementById('itinerary-container');
            const idx = container.children.length;
            const days = <?= (int)$pkg['schedule_days'] ?>;
            let dayOptions = '';
            for (let d = 1; d <= days; d++) dayOptions += `<option value="${d}">${d}</option>`;

            const div = document.createElement('div');
            div.className = 'itinerary-item';
            div.innerHTML = `
                <button type="button" class="remove-btn" onclick="removeItinerary(this)">X</button>
                <input type="hidden" name="itinerary[${idx}][packagespot_ID]" value="">
                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label">Day</label>
                        <select name="itinerary[${idx}][day]" class="form-select day-select">${dayOptions}</select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Spot</label>
                        <select name="itinerary[${idx}][spot]" class="form-select" onchange="toggleActivity(this)">
                            <option value="">-- Custom Activity --</option>
                            <?php foreach ($spots as $s): ?>
                                <option value="<?= $s['spots_ID'] ?>"><?= htmlspecialchars($s['spots_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Activity</label>
                        <input type="text" name="it Schaefer[${idx}][activity_name]" class="form-control" placeholder="e.g. Beach Swimming">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Start Time</label>
                        <input type="time" name="itinerary[${idx}][start_time]" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">End Time</label>
                        <input type="time" name="itinerary[${idx}][end_time]" class="form-control">
                    </div>
                </div>
            `;
            container.appendChild(div);
            validateItineraryRealtime();
        }

        function parseTime(t) { const [h, m] = t.split(':').map(Number); return h * 60 + m; }

        function validateItineraryRealtime() {
            const items = document.querySelectorAll('.itinerary-item');
            items.forEach(item => {
                item.classList.remove('error');
                const err = item.querySelector('.time-error');
                if (err) err.remove();
            });

            const activities = [];
            const spotMap = new Map();

            items.forEach((item, i) => {
                const day = item.querySelector('select[name$="[day]"]')?.value;
                const spot = item.querySelector('select[name$="[spot]"]')?.value;
                const activity = item.querySelector('input[name$="[activity_name]"]')?.value.trim();
                const start = item.querySelector('input[name$="[start_time]"]')?.value;
                const end = item.querySelector('input[name$="[end_time]"]')?.value;

                if (spot) spotMap.set(spot, (spotMap.get(spot) || 0) + 1);
                if (start && end) {
                    const s = parseTime(start), e = parseTime(end);
                    if (e <= s) showError(item, "End time must be after start time.");
                    activities.push({ i, day, start: s, end: e, item });
                }
            });

            activities.forEach(a => {
                activities.forEach(b => {
                    if (a.i === b.i || a.day !== b.day) return;
                    if (a.start < b.end && a.end > b.start) {
                        showError(a.item, `Overlaps with Row ${b.i + 1}`);
                        showError(b.item, `Overlaps with Row ${a.i + 1}`);
                    }
                });
            });

            spotMap.forEach((count, spot) => {
                if (count > 1) {
                    items.forEach(item => {
                        if (item.querySelector(`select[name$="[spot]"]`)?.value === spot) {
                            showError(item, "This spot is used multiple times.");
                        }
                    });
                }
            });
        }

        function showError(item, msg) {
            item.classList.add('error');
            if (!item.querySelector('.time-error')) {
                const div = document.createElement('div');
                div.className = 'time-error';
                div.textContent = msg;
                item.appendChild(div);
            }
        }

        const container = document.getElementById('itinerary-container');
        const observer = new MutationObserver(() => setTimeout(validateItineraryRealtime, 50));
        observer.observe(container, { childList: true });

        document.addEventListener('input', e => {
            if (e.target.matches('input[name$="[start_time]"], input[name$="[end_time]"], select[name$="[day]"], select[name$="[spot]"]')) {
                validateItineraryRealtime();
            }
        });

        document.querySelector('form').addEventListener('submit', function (e) {
            document.querySelectorAll('.itinerary-item').forEach(item => {
                const spot = item.querySelector('select[name$="[spot]"]')?.value;
                const act = item.querySelector('input[name$="[activity_name]"]')?.value.trim();
                const start = item.querySelector('input[name$="[start_time]"]')?.value;
                const end = item.querySelector('input[name$="[end_time]"]')?.value;
                if (!spot && !act && !start && !end) item.remove();
            });
            validateItineraryRealtime();
            if (document.querySelector('.time-error')) {
                alert("Please fix all errors before submitting.");
                e.preventDefault();
            }
        });

        document.addEventListener('DOMContentLoaded', () => {
            validateItineraryRealtime();
            updateDayDropdowns();
        });
    </script>
</body>
</html>
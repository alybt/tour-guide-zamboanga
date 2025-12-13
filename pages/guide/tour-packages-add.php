<?php
session_start();
$current_page = basename($_SERVER['PHP_SELF']);
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
$guideObj = new Guide();
$guide_ID = $guideObj->getGuide_ID($_SESSION['user']['account_ID']);
$spots = $guideObj->getAllSpots();

/* Flash Messages */
$errors = $_SESSION['errors'] ?? [];
$success = $_SESSION['success'] ?? '';
unset($_SESSION['errors'], $_SESSION['success'], $_SESSION['old_input']);

$pkg = $_SESSION['old_input'] ?? [
    'tourpackage_name' => '',
    'tourpackage_desc' => '',
    'schedule_days' => 1,
    'numberofpeople_maximum' => '',
    'numberofpeople_based' => '',
    'pricing_foradult' => '',
    'pricing_forchild' => '',
    'pricing_foryoungadult' => '',
    'pricing_forsenior' => '',
    'pricing_forpwd' => '',
    'include_meal' => 0,
    'meal_fee' => '0.00',
    'transport_fee' => '0.00',
    'discount' => '0.00',
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Add Tour Package | TourGuide PH</title>
    <link rel="stylesheet" href="../../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/vendor/bootstrap-icons/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="../../assets/css/guide/dashboard.css">
    <link rel="stylesheet" href="../../assets/css/guide/tour-packages-add.css">
    <style>
        :root {
            --primary-color: #ffffff;
            --secondary-color: #213638;
            --accent: #E5A13E;
            --secondary-accent: #CFE7E5;
            --text-dark: #2d3436;
            --text-light: #636e72;
        }
        body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; color: var(--text-dark); min-height: 100vh; }
        .main-content { margin-left: 260px; padding: 2rem; transition: all 0.3s ease; }
        .header-card {
            background: var(--primary-color);
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(33, 54, 56, 0.08);
            padding: 1.75rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(207, 231, 229, 0.3);
        }
        .status-badge { font-size: 0.8rem; padding: 0.35rem 0.75rem; border-radius: 50px; font-weight: 600; }
        .clock { font-family: 'Courier New', monospace; font-weight: 600; color: var(--secondary-color); font-size: 1.1rem; }
        .form-card {
            background: var(--primary-color);
            border-radius: 14px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            border: 1px solid rgba(207, 231, 229, 0.4);
            padding: 2rem;
        }
        .form-label { font-weight: 600; color: var(--text-dark); }
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
            font-size: 0.9rem;
        }
        .time-error { color: #dc3545; font-size: 0.875rem; margin-top: 0.5rem; }
        .alert-custom {
            border-radius: 12px;
            font-weight: 500;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            padding: 1rem;
        }
        .alert-success { background-color: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .alert-error { background-color: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
        @media (max-width: 992px) {
            .main-content { margin-left: 80px; }
        }
        @media (max-width: 576px) {
            .main-content { padding: 1rem; }
            .form-card { padding: 1.5rem; }
        }
    </style>
</head>
<body>
    <?php require_once "includes/aside-dashboard.php"; ?>

    <main class="main-content">
        <!-- Header -->
        <div class="header-card d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <div>
                <h3 class="mb-1 fw-bold">Add New Tour Package</h3>
                <p class="text-muted mb-0">Create a new tour package with detailed itinerary and pricing.</p>
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

        <!-- Success / Error Alerts -->
        <?php if ($success): ?>
            <div class="alert-custom alert-success">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($errors['general'])): ?>
            <div class="alert-custom alert-error">
                <?= htmlspecialchars($errors['general']) ?>
            </div>
        <?php endif; ?>

        <!-- Add Form -->
        <div class="form-card">
            <form method="post" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                <!-- Basic Information -->
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
                        <textarea name="tourpackage_desc" rows="4" class="form-control <?= isset($errors['tourpackage_desc']) ? 'is-invalid' : '' ?>"
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

                <!-- Pricing -->
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

                <!-- Itinerary -->
                <h5 class="mb-3">Itinerary</h5>
                <div id="itinerary-container"></div>

                <button type="button" class="btn btn-outline-primary btn-sm mb-3" onclick="addItinerary()">
                    <i class="bi bi-plus-circle"></i> Add Stop/Activity
                </button>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle"></i> Create Package
                    </button>
                    <a href="tour-packages.php" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </main>

    <!-- Template for Itinerary Item -->
    <template id="itinerary-template">
        <div class="itinerary-item">
            <button type="button" class="remove-btn" onclick="removeItinerary(this)">X</button>
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">Day</label>
                    <select name="itinerary[{idx}][day]" class="form-select day-dropdown"></select>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Spot</label>
                    <select name="itinerary[{idx}][spot]" class="form-select spot-select" onchange="toggleActivity(this)">
                        <option value="">-- Custom Activity --</option>
                        <?php foreach ($spots as $s): ?>
                            <option value="<?= htmlspecialchars($s['spots_ID']) ?>"><?= htmlspecialchars($s['spots_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5 activity-container">
                    <label class="form-label">Activity Name</label>
                    <input type="text" name="itinerary[{idx}][activity_name]" class="form-control" placeholder="e.g. Lunch Break, Swimming">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Start Time</label>
                    <input type="time" name="itinerary[{idx}][start_time]" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">End Time</label>
                    <input type="time" name="itinerary[{idx}][end_time]" class="form-control">
                </div>
            </div>
            <div class="itinerary-error text-danger small mt-2"></div>
        </div>
    </template>

    <script src="../../assets/vendor/twbs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Live Clock
        function updateClock() {
            const now = new Date();
            const options = { timeZone: 'Asia/Manila', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true };
            document.getElementById('liveClock').textContent = now.toLocaleTimeString('en-US', options);
        }
        updateClock();
        setInterval(updateClock, 1000);

        // Itinerary Logic
        let itineraryCounter = 0;
        const container = document.getElementById('itinerary-container');
        const template = document.getElementById('itinerary-template').content;
        const daysInput = document.getElementById('schedule_days');

        function fillDayOptions(select, maxDays) {
            select.innerHTML = '';
            for (let i = 1; i <= maxDays; i++) {
                select.add(new Option(`Day ${i}`, i));
            }
        }

        function syncAllDayDropdowns() {
            const max = parseInt(daysInput.value, 10) || 1;
            document.querySelectorAll('.day-dropdown').forEach(sel => fillDayOptions(sel, max));
            validateItineraryRealtime();
        }

        function toggleActivity(select) {
            const container = select.closest('.row').querySelector('.activity-container');
            container.style.display = select.value === '' ? 'block' : 'none';
        }

        function addItinerary() {
            const clone = template.cloneNode(true);
            const idx = itineraryCounter++;
            clone.querySelectorAll('[name]').forEach(el => {
                el.name = el.name.replace('{idx}', idx);
            });
            const daySel = clone.querySelector('.day-dropdown');
            fillDayOptions(daySel, parseInt(daysInput.value, 10) || 1);
            daySel.value = 1;

            const spotSel = clone.querySelector('.spot-select');
            spotSel.addEventListener('change', () => toggleActivity(spotSel));
            toggleActivity(spotSel);

            container.appendChild(clone);
            validateItineraryRealtime();
        }

        function removeItinerary(btn) {
            if (container.children.length <= 1) {
                alert('You must have at least one itinerary item.');
                return;
            }
            btn.closest('.itinerary-item').remove();
            validateItineraryRealtime();
        }

        // Realtime Validation
        function parseTime(time) {
            if (!time) return null;
            const [h, m] = time.split(':').map(Number);
            return h * 60 + m;
        }

        function validateItineraryRealtime() {
            const items = document.querySelectorAll('.itinerary-item');
            items.forEach(item => {
                item.classList.remove('error');
                const err = item.querySelector('.itinerary-error');
                if (err) err.textContent = '';
            });

            const activities = [];
            const spotTracker = new Map();

            items.forEach((item, i) => {
                const day = item.querySelector('select[name$="[day]"]')?.value;
                const spot = item.querySelector('select[name$="[spot]"]')?.value;
                const activity = item.querySelector('input[name$="[activity_name]"]')?.value.trim();
                const start = item.querySelector('input[name$="[start_time]"]')?.value;
                const end = item.querySelector('input[name$="[end_time]"]')?.value;
                const startMins = parseTime(start);
                const endMins = parseTime(end);

                if (spot) {
                    spotTracker.set(spot, (spotTracker.get(spot) || 0) + 1);
                }

                if (!spot && !activity) {
                    item.querySelector('.itinerary-error').textContent = 'Select a spot or enter an activity.';
                    item.classList.add('error');
                }
                if (!day) {
                    item.querySelector('.itinerary-error').textContent = 'Select a day.';
                    item.classList.add('error');
                }
                if (start && end && endMins <= startMins) {
                    item.querySelector('.itinerary-error').textContent = 'End time must be after start time.';
                    item.classList.add('error');
                }

                if (start && end) {
                    activities.push({ i, day, start: startMins, end: endMins, item });
                }
            });

            // Check overlaps
            for (let a of activities) {
                for (let b of activities) {
                    if (a.i === b.i || a.day !== b.day) continue;
                    if (a.start < b.end && a.end > b.start) {
                        a.item.querySelector('.itinerary-error').textContent = `Overlaps with another activity on Day ${a.day}`;
                        a.item.classList.add('error');
                        b.item.classList.add('error');
                    }
                }
            }

            // Check duplicate spots
            for (const [spot, count] of spotTracker) {
                if (count > 1) {
                    items.forEach(item => {
                        if (item.querySelector('select[name$="[spot]"]')?.value === spot) {
                            item.querySelector('.itinerary-error').textContent = 'This spot is used multiple times.';
                            item.classList.add('error');
                        }
                    });
                }
            }
        }

        // Event Listeners
        daysInput.addEventListener('input', syncAllDayDropdowns);
        document.getElementById('include_meal').addEventListener('change', function () {
            document.getElementById('mealFeeContainer').style.display = this.checked ? '' : 'none';
        });

        document.addEventListener('input', e => {
            if (e.target.matches('input[name$="[start_time]"], input[name$="[end_time]"], select[name$="[day]"], select[name$="[spot]"], input[name$="[activity_name]"]')) {
                validateItineraryRealtime();
            }
        });

        const observer = new MutationObserver(() => setTimeout(validateItineraryRealtime, 50));
        observer.observe(container, { childList: true });

        document.querySelector('form').addEventListener('submit', function (e) {
            // Remove empty rows
            document.querySelectorAll('.itinerary-item').forEach(item => {
                const spot = item.querySelector('select[name$="[spot]"]')?.value;
                const act = item.querySelector('input[name$="[activity_name]"]')?.value.trim();
                const start = item.querySelector('input[name$="[start_time]"]')?.value;
                const end = item.querySelector('input[name$="[end_time]"]')?.value;
                if (!spot && !act && !start && !end) item.remove();
            });

            validateItineraryRealtime();
            if (document.querySelector('.itinerary-item.error')) {
                alert('Please fix all itinerary errors before submitting.');
                e.preventDefault();
            }
        });

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            addItinerary(); // Add first item
            validateItineraryRealtime();
        });
    </script>
</body>
</html>
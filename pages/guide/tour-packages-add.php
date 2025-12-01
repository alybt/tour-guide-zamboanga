<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role_name'] !== 'Tour Guide') {
    header('Location: ../../index.php');
    exit;
} else if ($_SESSION['user']['account_status'] == 'Suspended'){
    header('Location: account-suspension.php');
    exit;
} else if ($_SESSION['user']['account_status'] == 'Pending'){
    header('Location: account-pending.php');
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

require_once "../../classes/guide.php";
require_once "../../classes/tour-manager.php";

$tourMgr = new TourManager();
$guide   = new Guide();

$guide_ID = $guide->getGuide_ID($_SESSION['user']['account_ID']);
$spots    = $guide->getAllSpots();

/* -------------------------------------------------
   4. Flash Data (old input, errors, success)
   ------------------------------------------------- */
$old      = $_SESSION['old_input'] ?? [];
$errors   = $_SESSION['errors']   ?? [];
$success  = $_SESSION['success']  ?? '';

unset($_SESSION['old_input'], $_SESSION['errors'], $_SESSION['success']);

/* -------------------------------------------------
   5. Default View Model (for repopulating form)
   ------------------------------------------------- */
$pkg = [
    'tourpackage_name'       => $old['tourpackage_name']       ?? '',
    'tourpackage_desc'       => $old['tourpackage_desc']       ?? '',
    'schedule_days'          => $old['schedule_days']          ?? 1,
    'numberofpeople_maximum' => $old['numberofpeople_maximum'] ?? '',
    'numberofpeople_based'   => $old['numberofpeople_based']   ?? '',
    'pricing_foradult'       => $old['pricing_foradult']       ?? '',
    'pricing_forchild'       => $old['pricing_forchild']       ?? '',
    'pricing_foryoungadult'  => $old['pricing_foryoungadult']  ?? '',
    'pricing_forsenior'      => $old['pricing_forsenior']      ?? '',
    'pricing_forpwd'         => $old['pricing_forpwd']         ?? '',
    'include_meal'           => $old['include_meal']           ?? 0,
    'meal_fee'               => $old['meal_fee']               ?? '0.00',
    'transport_fee'          => $old['transport_fee']          ?? '0.00',
    'discount'               => $old['discount']               ?? '0.00',
];

/* -------------------------------------------------
   6. Form Submission
   ------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF Check
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

        // Validation Helper
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

        // Basic Fields
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

        if ($posted['include_meal']) {
            $v->numeric($posted['meal_fee'], 'meal_fee', 0);
        }
        $v->numeric($posted['transport_fee'], 'transport_fee', 0);
        $v->numeric($posted['discount'], 'discount', 0);

        // Itinerary Validation
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
                    // Convert times to seconds for easier comparison
                    $startSec = strtotime($start);
                    $endSec   = strtotime($end);

                    // 1️⃣ Must not cross midnight
                    if ($endSec <= $startSec) {
                        $v->errors["itinerary_time_$idx"] = "Row " . ($idx + 1) . ": Activity cannot go past midnight.";
                    }

                    // 2️⃣ Check for overlapping activities on the same day
                    foreach ($itinerary as $j => $other) {
                        if ($j === $idx) continue; // Skip same activity

                        if (($other['day'] ?? '') == $day && !empty($other['start_time']) && !empty($other['end_time'])) {
                            $otherStart = strtotime($other['start_time']);
                            $otherEnd   = strtotime($other['end_time']);

                            // Overlap condition
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

        // Save to DB if valid
        if (empty($errors)) {
            $tour_spots = $activities = $startTimes = $endTimes = $days = [];

            foreach ($itinerary as $item) {
                $tour_spots[]   = $item['spot'] === '' ? null : $item['spot'];
                $activities[]   = trim($item['activity_name'] ?? '');
                $startTimes[]   = $item['start_time'] ?? null;
                $endTimes[]     = $item['end_time'] ?? null;
                $days[]         = $item['day'] ?? null;
            }

            $result = $tourMgr->addTourPackagesAndItsSpots(
                $tour_spots,
                $activities,
                $startTimes,
                $endTimes,
                $days,
                $guide_ID,
                $posted['tourpackage_name'],
                $posted['tourpackage_desc'],
                $posted['schedule_days'],
                $posted['numberofpeople_maximum'],
                $posted['numberofpeople_based'],
                'PHP',
                $posted['pricing_foradult'],
                $posted['pricing_forchild'] ?? 0,
                $posted['pricing_foryoungadult'] ?? 0,
                $posted['pricing_forsenior'] ?? 0,
                $posted['pricing_forpwd'] ?? 0,
                $posted['include_meal'],
                $posted['meal_fee'],
                $posted['transport_fee'],
                $posted['discount']
            );

            if ($result) {
                $_SESSION['success'] = 'Tour package added successfully!';
                header('Location: tour-packages.php');
                exit;
            } else {
                $errors['general'] = 'Failed to save package. Check server logs.';
                error_log('TourManager error: ' . print_r($tourMgr->getLastError(), true));
            }
        }

        // Save input for repopulation
        $_SESSION['old_input'] = $posted;
        $pkg = $posted; // update view model
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Tour Packages Add</title>

    <link rel="stylesheet" href="../../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/vendor/bootstrap-icons/bootstrap-icons.css"> 

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet"/>

    <link rel="stylesheet" href="../../assets/css/guide/tour-packages-add.css">
    <link rel="stylesheet" href="../../assets/css/guide/dashboard.css">
    
</head>
<body>
<h1>Add Tour Package</h1>

<?php if ($success): ?>
    <p class="success"><?= htmlspecialchars($success) ?></p>
<?php endif; ?>

<?php if (!empty($errors['general'])): ?>
    <p class="error"><?= htmlspecialchars($errors['general']) ?></p>
<?php endif; ?>

<form method="post" onsubmit="return validateForm()">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

    <!-- Basic Information -->
    <div>
        <label for="tourpackage_name">Tour Package Name: *</label>
        <input type="text" name="tourpackage_name" id="tourpackage_name" value="<?= htmlspecialchars($pkg['tourpackage_name']) ?>">
        <?php if (isset($errors['tourpackage_name'])): ?>
            <span class="error"><?= htmlspecialchars($errors['tourpackage_name']) ?></span>
        <?php endif; ?>
    </div>

    <div>
        <label for="tourpackage_desc">Description: *</label><br>
        <textarea name="tourpackage_desc" id="tourpackage_desc"><?= htmlspecialchars($pkg['tourpackage_desc']) ?></textarea>
        <?php if (isset($errors['tourpackage_desc'])): ?>
            <span class="error"><?= htmlspecialchars($errors['tourpackage_desc']) ?></span>
        <?php endif; ?>
    </div>

    <div>
        <label for="schedule_days">Schedule Days: *</label>
        <input type="number" name="schedule_days" id="schedule_days" min="1" value="<?= htmlspecialchars($pkg['schedule_days']) ?>">
        <?php if (isset($errors['schedule_days'])): ?>
            <span class="error"><?= htmlspecialchars($errors['schedule_days']) ?></span>
        <?php endif; ?>
    </div>

    <div>
        <label for="numberofpeople_maximum">Maximum People: *</label>
        <input type="number" name="numberofpeople_maximum" id="numberofpeople_maximum" min="1" value="<?= htmlspecialchars($pkg['numberofpeople_maximum']) ?>">
        <?php if (isset($errors['numberofpeople_maximum'])): ?>
            <span class="error"><?= htmlspecialchars($errors['numberofpeople_maximum']) ?></span>
        <?php endif; ?>
    </div>

    <div>
        <label for="numberofpeople_based">Minimum People: *</label>
        <input type="number" name="numberofpeople_based" id="numberofpeople_based" min="1" value="<?= htmlspecialchars($pkg['numberofpeople_based']) ?>">
        <?php if (isset($errors['numberofpeople_based'])): ?>
            <span class="error"><?= htmlspecialchars($errors['numberofpeople_based']) ?></span>
        <?php endif; ?>
    </div>

    <!-- Pricing -->
    <h3>Pricing (PHP)</h3>
    <div>
        <label for="pricing_foradult">Adult: *</label>
        <input type="number" step="0.01" name="pricing_foradult" id="pricing_foradult" value="<?= htmlspecialchars($pkg['pricing_foradult']) ?>">
        <?php if (isset($errors['pricing_foradult'])): ?>
            <span class="error"><?= htmlspecialchars($errors['pricing_foradult']) ?></span>
        <?php endif; ?>
    </div>

    <div>
        <label for="pricing_forchild">Child:</label>
        <input type="number" step="0.01" name="pricing_forchild" id="pricing_forchild" value="<?= htmlspecialchars($pkg['pricing_forchild']) ?>">
    </div>

    <div>
        <label for="pricing_foryoungadult">Young Adult:</label>
        <input type="number" step="0.01" name="pricing_foryoungadult" id="pricing_foryoungadult" value="<?= htmlspecialchars($pkg['pricing_foryoungadult']) ?>">
    </div>

    <div>
        <label for="pricing_forsenior">Senior:</label>
        <input type="number" step="0.01" name="pricing_forsenior" id="pricing_forsenior" value="<?= htmlspecialchars($pkg['pricing_forsenior']) ?>">
    </div>

    <div>
        <label for="pricing_forpwd">PWD:</label>
        <input type="number" step="0.01" name="pricing_forpwd" id="pricing_forpwd" value="<?= htmlspecialchars($pkg['pricing_forpwd']) ?>">
    </div>

    <div>
        <label>
            <input type="checkbox" id="include_meal" name="include_meal" value="1" <?= $pkg['include_meal'] ? 'checked' : '' ?>>
            Include Meal
        </label>
    </div>

    <div id="mealFeeContainer" style="<?= $pkg['include_meal'] ? '' : 'display:none;' ?>">
        <label for="meal_fee">Meal Fee:</label>
        <input type="number" step="0.01" name="meal_fee" id="meal_fee" value="<?= htmlspecialchars($pkg['meal_fee']) ?>">
        <?php if (isset($errors['meal_fee'])): ?>
            <span class="error"><?= htmlspecialchars($errors['meal_fee']) ?></span>
        <?php endif; ?>
    </div>

    <div>
        <label for="transport_fee">Transport Fee:</label>
        <input type="number" step="0.01" name="transport_fee" id="transport_fee" value="<?= htmlspecialchars($pkg['transport_fee']) ?>">
    </div>

    <div>
        <label for="discount">Discount:</label>
        <input type="number" step="0.01" name="discount" id="discount" value="<?= htmlspecialchars($pkg['discount']) ?>">
        <?php if (isset($errors['discount'])): ?>
            <span class="error"><?= htmlspecialchars($errors['discount']) ?></span>
        <?php endif; ?>
    </div>

    <!-- Itinerary -->
    <h3>Itinerary</h3>
    <?php if (isset($errors['itinerary'])): ?>
        <p class="error"><?= htmlspecialchars($errors['itinerary']) ?></p>
    <?php endif; ?>

    <template id="itinerary-template">
        <div class="itinerary-item">
            <button type="button" class="remove-btn" onclick="removeItinerary(this)">X</button>

            <label>Day:</label>
            <select name="itinerary[{idx}][day]" class="day-dropdown"></select><br><br>

            <div class="spot-activity-pair">
                <label>Spot:</label>
                <select name="itinerary[{idx}][spot]" class="spot-select">
                    <option value="">-- None / Custom Activity --</option>
                    <?php foreach ($spots as $s): ?>
                        <option value="<?= htmlspecialchars($s['spots_ID']) ?>">
                            <?= htmlspecialchars($s['spots_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select><br><br>

                <div class="activity-container">
                    <label>Activity Name:</label>
                    <input type="text" name="itinerary[{idx}][activity_name]" placeholder="e.g. Lunch Break">
                </div><br><br>
            </div>

            <label>Start Time:</label>
            <input type="time" name="itinerary[{idx}][start_time]"><br><br>

            <label>End Time:</label>
            <input type="time" name="itinerary[{idx}][end_time]"><br><br>

            <div class="itinerary-error error"></div>
        </div>
    </template>

    <div id="itinerary-container"></div>

    <button type="button" onclick="addItinerary()">+ Add Another Stop/Activity</button><br><br>
    <button type="submit">Save Tour Package</button>
</form>

<script>
/* ---------- Global ---------- */
    let itineraryCounter = 0;
    const daysInput = document.getElementById('schedule_days');
    const container = document.getElementById('itinerary-container');
    const template = document.getElementById('itinerary-template').content;

    /* ---------- Helpers ---------- */
    function fillDayOptions(select, maxDays) {
        select.innerHTML = '';
        for (let i = 1; i <= maxDays; i++) {
            const opt = new Option(`Day ${i}`, i);
            select.add(opt);
        }
    }
    function syncAllDayDropdowns() {
        const max = parseInt(daysInput.value, 10) || 1;
        document.querySelectorAll('.day-dropdown').forEach(sel => fillDayOptions(sel, max));
    }
    function toggleActivity(select) {
        const act = select.closest('.spot-activity-pair').querySelector('.activity-container');
        act.style.display = select.value === '' ? '' : 'none';
    }

    /* ---------- Init ---------- */
    document.addEventListener('DOMContentLoaded', () => {
        addItinerary();
        syncAllDayDropdowns();

        const mealChk = document.getElementById('include_meal');
        const mealDiv = document.getElementById('mealFeeContainer');
        mealChk.addEventListener('change', () => {
            mealDiv.style.display = mealChk.checked ? '' : 'none';
        });
    });

    /* ---------- Events ---------- */
    daysInput.addEventListener('input', syncAllDayDropdowns);

    /* ---------- Add Row ---------- */
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
    }

    /* ---------- Remove Row ---------- */
    function removeItinerary(btn) {
        if (container.children.length <= 1) {
            alert('You must keep at least one itinerary item.');
            return;
        }
        btn.closest('.itinerary-item').remove();
    }

    /* ---------- Client Validation ---------- */
    function validateForm() {
        let ok = true;
        document.querySelectorAll('.itinerary-item').forEach(item => {
            const spot = item.querySelector('select[name$="[spot]"]').value;
            const act  = item.querySelector('input[name$="[activity_name]"]').value.trim();
            const day  = item.querySelector('select[name$="[day]"]').value;
            const err  = item.querySelector('.itinerary-error');
            err.textContent = '';

            if (!spot && !act) {
                err.textContent = 'Select a spot or enter an activity.';
                ok = false;
            }
            if (!day) {
                err.textContent = 'Select a day.';
                ok = false;
            }
        });
        if (!ok) alert('Please fix itinerary errors.');
        return ok;
    }

    // IDK
    function parseTime(time) {
        if (!time) return null;
        const [h, m] = time.split(':').map(Number);
        return h * 60 + m; // minutes from midnight
    }

    function validateItineraryRealtime() {
        const items = document.querySelectorAll('.itinerary-item');
        const activities = [];
        const spotTracker = new Map(); // {spotID -> [indexes]}
        
        // Clear previous highlights & errors
        items.forEach(item => {
            item.style.borderColor = "#ccc";
            item.style.backgroundColor = "#f9f9f9";
            const err = item.querySelector('.time-error');
            if (err) err.remove();
        });

        // Collect all item info
        items.forEach((item, i) => {
            const day = parseInt(item.querySelector('select[name$="[day]"]')?.value);
            const start = item.querySelector('input[name$="[start_time]"]')?.value;
            const end = item.querySelector('input[name$="[end_time]"]')?.value;
            const spot = item.querySelector('select[name$="[spot]"]')?.value || null;
            const startMins = parseTime(start);
            const endMins = parseTime(end);

            // Track spots for duplicate detection
            if (spot && spot !== "") {
                if (!spotTracker.has(spot)) spotTracker.set(spot, []);
                spotTracker.get(spot).push(i);
            }

            activities.push({ item, i, day, startMins, endMins, start, end });
        });

        // Helper for inline error message
        const showError = (item, msg) => {
            item.style.borderColor = "#e74c3c";
            item.style.backgroundColor = "#fff5f5";
            if (!item.querySelector('.time-error')) {
                const div = document.createElement('div');
                div.className = 'time-error';
                div.style.color = "red";
                div.style.fontSize = "0.9em";
                div.style.marginTop = "5px";
                div.textContent = msg;
                item.appendChild(div);
            }
        };

        // 1️⃣ Check invalid times
        activities.forEach(a => {
            if (a.start && a.end && a.endMins <= a.startMins) {
                showError(a.item, "Activity cannot go past midnight or end before it starts.");
            }
        });

        // 2️⃣ Check overlaps on same day
        for (let i = 0; i < activities.length; i++) {
            for (let j = i + 1; j < activities.length; j++) {
                const a = activities[i], b = activities[j];
                if (a.day && b.day && a.day === b.day &&
                    a.startMins !== null && a.endMins !== null &&
                    b.startMins !== null && b.endMins !== null) {

                    if (a.startMins < b.endMins && a.endMins > b.startMins) {
                        showError(a.item, `Overlaps with Row ${b.i + 1} on Day ${a.day}.`);
                        showError(b.item, `Overlaps with Row ${a.i + 1} on Day ${a.day}.`);
                    }
                }
            }
        }

        // 3️⃣ Check duplicate spots
        for (const [spotID, indexes] of spotTracker.entries()) {
            if (indexes.length > 1) {
                indexes.forEach(i => {
                    const item = items[i];
                    showError(item, "This spot is already chosen in another itinerary item.");
                });
            }
        }
    }

    // 🧩 Auto-run validation when user edits any field
    document.addEventListener('input', (e) => {
        if (
            e.target.matches('input[name$="[start_time]"], input[name$="[end_time]"], select[name$="[day]"], select[name$="[spot]"]')
        ) {
            validateItineraryRealtime();
        }
    });

    // 🧩 Auto revalidate when itinerary items are added or removed
    const observer = new MutationObserver(() => {
        setTimeout(validateItineraryRealtime, 50);
    });
    observer.observe(container, { childList: true });

    // Run validation on page load
    document.addEventListener('DOMContentLoaded', validateItineraryRealtime);

    // Final check on submit
    document.querySelector('form').addEventListener('submit', function (e) {
        validateItineraryRealtime();
        if (document.querySelector('.time-error')) {
            alert("Please fix all conflicts before submitting.");
            e.preventDefault();
        }
    });
    
// Validation when it itinerary doesnt have a activty/spots AND start AND end Time 
    document.querySelector('form').addEventListener('submit', function (e) {
        // 🧹 1️⃣ Remove completely empty itinerary rows
        document.querySelectorAll('.itinerary-item').forEach(item => {
            const spot = item.querySelector('select[name$="[spot]"]')?.value.trim();
            const activity = item.querySelector('input[name$="[activity_name]"]')?.value.trim();
            const start = item.querySelector('input[name$="[start_time]"]')?.value.trim();
            const end = item.querySelector('input[name$="[end_time]"]')?.value.trim();

            // If no spot, no activity, no start & no end → remove this block
            if (!spot && !activity && !start && !end) {
                item.remove();
            }
        });

        // 🧩 2️⃣ Run your normal validation after cleanup
        validateItineraryRealtime();

        // 🧩 3️⃣ Prevent submit if errors exist
        if (document.querySelector('.time-error')) {
            alert("Please fix all conflicts before submitting.");
            e.preventDefault();
        }
    });
</script>
</body>
</html>
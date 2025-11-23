<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role_name'] !== 'Admin' || $_SESSION['user']['account_status'] == 'Suspended') {
    header('Location: ../../index.php');
    exit;
}

require_once "../../classes/tour-manager.php";

$spotsObj = new TourManager();

$spots = [];
$success = "";
$error = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $spots["spots_name"]        = trim(htmlspecialchars($_POST['spots_name']));
    $spots["spots_description"] = trim(htmlspecialchars($_POST['spots_description']));
    $spots["spots_category"]    = trim(htmlspecialchars($_POST['spots_category']));
    $spots["spots_address"]     = trim(htmlspecialchars($_POST['spots_address']));
    $spots["spots_googlelink"]  = trim(htmlspecialchars($_POST['spots_googlelink']));

    // Validation
    if (empty($spots["spots_name"])) {
        $error["spots_name"] = "Tour spot name is required";
    }
    if (empty($spots["spots_description"])) {
        $error["spots_description"] = "Description is required";
    }
    if (empty($spots["spots_category"])) {
        $error["spots_category"] = "Category is required";
    }
    if (empty($spots["spots_address"])) {
        $error["spots_address"] = "Address is required";
    }

    if (empty($error)) {
        $results = $spotsObj->addTourSpots(
            $spots["spots_name"],
            $spots["spots_description"],
            $spots["spots_category"],
            $spots["spots_address"],
            $spots["spots_googlelink"]
        );

        if ($results !== false) {
            $_SESSION['success'] = "Tour spot added successfully.";
            header("Location: tour-spots.php");
            exit();
        } else {
            $error['general'] = "Failed to add tour spot. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Tour Spot | Tourismo Zamboanga Admin</title>

    <link rel="stylesheet" href="../../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/vendor/bootstrap-icons/bootstrap-icons.css">
    <!-- Google Fonts -->
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
            margin-bottom: 0.5rem;
        }

        .form-control, .form-select {
            border-radius: 10px;
            padding: 0.65rem 1rem;
            font-size: 0.95rem;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 0.2rem rgba(229, 161, 62, 0.25);
        }

        .char-counter {
            font-size: 0.8rem;
            color: var(--text-light);
            margin-top: 0.35rem;
            transition: color 0.2s;
        }

        .char-counter.warning { color: #ff9800; }
        .char-counter.error { color: #f44336; }

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

        .required {
            color: #dc3545;
        }

        .btn-primary {
            background-color: var(--accent);
            border: none;
            border-radius: 12px;
            padding: 0.65rem 1.5rem;
            font-weight: 600;
        }

        .btn-primary:hover {
            background-color: #d18f2c;
            transform: translateY(-1px);
        }

        .btn-secondary {
            border-radius: 12px;
            padding: 0.65rem 1.5rem;
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
            <span>Tourismo Zamboanga</span>
        </div>
        <nav class="nav flex-column px-2">
            <a class="nav-link" href="dashboard.php">
                <i class="bi bi-speedometer2"></i>
                <span class="nav-text">Dashboard</span>
            </a>
            <a class="nav-link active" href="tour-spots.php">
                <i class="bi bi-geo-alt"></i>
                <span class="nav-text">Manage Spots</span>
            </a>
            <a class="nav-link" href="manage-users.php">
                <i class="bi bi-people"></i>
                <span class="nav-text">Manage Users</span>
            </a>
            <a class="nav-link" href="reports.php">
                <i class="bi bi-graph-up"></i>
                <span class="nav-text">Reports</span>
            </a>
            <a class="nav-link" href="settings.php">
                <i class="bi bi-gear"></i>
                <span class="nav-text">Settings</span>
            </a>
            <hr class="bg-white opacity-25 my-3">
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
                <h3 class="mb-1 fw-bold">Add New Tour Spot</h3>
                <p class="text-muted mb-0">Create a new destination for tourists and guides.</p>
            </div>
            <div class="text-md-end">
                <div class="d-flex align-items-center gap-3 flex-wrap justify-content-md-end">
                    <span class="badge bg-success status-badge">
                        <i class="bi bi-shield-check"></i> Admin
                    </span>
                    <div class="clock" id="liveClock"></div>
                </div>
                <small class="text-muted d-block mt-1">Philippine Standard Time (PST)</small>
            </div>
        </div>

        <!-- Back Button -->
        <div class="mb-3">
            <a href="tour-spots.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Back to Tour Spots
            </a>
        </div>

        <!-- Alerts -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert-custom alert-success p-3">
                <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert-custom alert-error p-3">
                <?php foreach ($error as $msg): ?>
                    <div><?= htmlspecialchars($msg) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Add Form -->
        <div class="form-card">
            <form method="POST" novalidate>
                <div class="row g-4">

                    <!-- Spot Name -->
                    <div class="col-md-6">
                        <label class="form-label">Spot Name <span class="required">*</span></label>
                        <input type="text" name="spots_name" class="form-control" 
                               value="<?= $spots["spots_name"] ?? "" ?>" 
                               maxlength="225" required>
                        <div class="char-counter" id="name-counter">0 / 225 characters</div>
                    </div>

                    <!-- Category -->
                    <div class="col-md-6">
                        <label class="form-label">Category <span class="required">*</span></label>
                        <select name="spots_category" class="form-select" required>
                            <option value="">-- Select Category --</option>
                            <option value="Historical" <?= (isset($spots["spots_category"]) && $spots["spots_category"] == 'Historical') ? 'selected' : ''; ?>>Historical</option>
                            <option value="Beach" <?= (isset($spots["spots_category"]) && $spots["spots_category"] == 'Beach') ? 'selected' : ''; ?>>Beach</option>
                            <option value="Nature" <?= (isset($spots["spots_category"]) && $spots["spots_category"] == 'Nature') ? 'selected' : ''; ?>>Nature</option>
                            <option value="Cultural" <?= (isset($spots["spots_category"]) && $spots["spots_category"] == 'Cultural') ? 'selected' : ''; ?>>Cultural</option>
                            <option value="Religious" <?= (isset($spots["spots_category"]) && $spots["spots_category"] == 'Religious') ? 'selected' : ''; ?>>Religious</option>
                            <option value="Adventure" <?= (isset($spots["spots_category"]) && $spots["spots_category"] == 'Adventure') ? 'selected' : ''; ?>>Adventure</option>
                            <option value="Food & Dining" <?= (isset($spots["spots_category"]) && $spots["spots_category"] == 'Food & Dining') ? 'selected' : ''; ?>>Food & Dining</option>
                            <option value="Shopping" <?= (isset($spots["spots_category"]) && $spots["spots_category"] == 'Shopping') ? 'selected' : ''; ?>>Shopping</option>
                            <option value="Entertainment" <?= (isset($spots["spots_category"]) && $spots["spots_category"] == 'Entertainment') ? 'selected' : ''; ?>>Entertainment</option>
                            <option value="Other" <?= (isset($spots["spots_category"]) && $spots["spots_category"] == 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>

                    <!-- Description -->
                    <div class="col-12">
                        <label class="form-label">Description <span class="required">*</span></label>
                        <textarea name="spots_description" class="form-control" rows="4" 
                                  maxlength="225" required><?= $spots["spots_description"] ?? "" ?></textarea>
                        <div class="char-counter" id="description-counter">0 / 225 characters</div>
                    </div>

                    <!-- Address -->
                    <div class="col-md-8">
                        <label class="form-label">Address <span class="required">*</span></label>
                        <input type="text" name="spots_address" class="form-control" 
                               value="<?= $spots["spots_address"] ?? "" ?>" 
                               placeholder="e.g., Paseo del Mar, Zamboanga City"
                               maxlength="225" required>
                        <div class="char-counter" id="address-counter">0 / 225 characters</div>
                    </div>

                    <!-- Google Maps Link -->
                    <div class="col-md-4">
                        <label class="form-label">Google Maps Link (Optional)</label>
                        <input type="url" name="spots_googlelink" class="form-control" 
                               value="<?= $spots["spots_googlelink"] ?? "" ?>" 
                               placeholder="https://maps.google.com/..."
                               maxlength="500">
                        <div class="char-counter" id="link-counter">0 / 500 characters</div>
                    </div>

                </div>

                <!-- Buttons -->
                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Add Tour Spot
                    </button>
                    <a href="tour-spots.php" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </main>

    <script src="../../assets/vendor/twbs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Live Clock (PH Time) -->
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

    <!-- Character Counter -->
    <script>
        function updateCharCounter(inputId, counterId, maxLength) {
            const input = document.getElementById(inputId);
            const counter = document.getElementById(counterId);

            function update() {
                const length = input.value.length;
                counter.textContent = length + ' / ' + maxLength + ' characters';
                counter.classList.remove('warning', 'error');
                if (length > maxLength * 0.9) counter.classList.add('warning');
                if (length >= maxLength) counter.classList.add('error');
            }

            input.addEventListener('input', update);
            input.addEventListener('keyup', update);
            update();
        }

        document.addEventListener('DOMContentLoaded', function() {
            updateCharCounter('spots_name', 'name-counter', 225);
            updateCharCounter('spots_description', 'description-counter', 225);
            updateCharCounter('spots_address', 'address-counter', 225);
            updateCharCounter('spots_googlelink', 'link-counter', 500);
        });
    </script>
</body>
</html>
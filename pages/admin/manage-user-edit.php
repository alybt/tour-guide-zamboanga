<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role_name'] !== 'Admin' || $_SESSION['user']['account_status'] == 'Suspended') {
    header('Location: ../../index.php');
    exit;
}
require_once "../../classes/admin.php";

$adminObj = new Admin();

// Get user ID from URL
$user_ID = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($user_ID <= 0) {
    $_SESSION['error'] = "Invalid user ID.";
    header("Location: manage-users.php");
    exit;
}

$user = $adminObj->getUsersDetailsByID($user_ID);

if (!$user) {
    $_SESSION['error'] = "User not found.";
    header("Location: manage-users.php");
    exit;
}

// Fetch all roles + count
$allRoles   = $adminObj->getAllRoles();
$totalRoles = count($allRoles); // <-- For limiting "Add Role"

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstname = trim($_POST['firstname']);
    $lastname  = trim($_POST['lastname']);
    $username  = trim($_POST['username']);
    $role_ids  = $_POST['role_ID'] ?? [];
    $role_ids  = array_filter(array_map('intval', $role_ids));
    $status    = trim($_POST['status']);
    $password  = trim($_POST['password']);

    // Validation
    $errors = [];
    if (empty($firstname)) $errors[] = "First name is required.";
    if (empty($lastname)) $errors[] = "Last name is required.";
    if (empty($username)) $errors[] = "Username is required.";
    if (empty($role_ids)) $errors[] = "At least one role is required.";
    if (!in_array($status, ['Active', 'Inactive'])) $errors[] = "Invalid status.";

    if (empty($errors)) {
        try {
            // $pdo = $adminObj->connect();
            // $pdo->beginTransaction();

            // // 1. Update person
            // $stmt = $pdo->prepare("UPDATE person SET person_firstname = ?, person_lastname = ? 
            //                        WHERE person_ID = (SELECT person_ID FROM users WHERE user_ID = ?)");
            // $stmt->execute([$firstname, $lastname, $user_ID]);

            // // 2. Update users (username + password only)
            // $sql = "UPDATE users SET user_username = ? WHERE user_ID = ?";
            // $params = [$username, $user_ID];

            // if (!empty($password)) {
            //     $hashed = password_hash($password, PASSWORD_DEFAULT);
            //     $sql = "UPDATE users SET user_username = ?, user_password = ? WHERE user_ID = ?";
            //     $params = [$username, $hashed, $user_ID];
            // }
            // $pdo->prepare($sql)->execute($params);

            // // 3. Replace roles in Account_Info
            // $pdo->prepare("DELETE FROM Account_Info WHERE user_ID = ?")->execute([$user_ID]);
            // foreach ($role_ids as $rid) {
            //     if ($rid > 0) {
            //         $pdo->prepare("INSERT INTO Account_Info (user_ID, role_ID, account_status) VALUES (?, ?, ?)")
            //              ->execute([$user_ID, $rid, $status]);
            //     }
            // }

            // $pdo->commit();
            // $_SESSION['success'] = "User updated successfully.";
            // header("Location: manage-users.php");
            // exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("User update failed: " . $e->getMessage());
            $errors[] = "Update failed. Please try again.";
        }
    }

    if (!empty($errors)) {
        $_SESSION['error'] = implode("<br>", $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User | Tourismo Zamboanga Admin</title>

    <!-- Bootstrap 5 CSS -->
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

        /* Add Role Button Disabled Style */
        button[onclick="addRole()"]:disabled {
            opacity: 0.6;
            cursor: not-allowed;
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
            <a class="nav-link" href="tour-spots.php">
                <i class="bi bi-geo-alt"></i>
                <span class="nav-text">Manage Spots</span>
            </a>
            <a class="nav-link active" href="manage-users.php">
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
                <h3 class="mb-1 fw-bold">Edit User</h3>
                <p class="text-muted mb-0">Update user information, role, and account status.</p>
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
            <a href="manage-users.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Back to Users
            </a>
        </div>

        <!-- Alerts -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert-custom alert-success p-3">
                <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert-custom alert-error p-3">
                <?= $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Edit Form -->
        <div class="form-card">
            <form method="POST" novalidate>
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label">First Name <span class="text-danger">*</span></label>
                        <input type="text" name="firstname" class="form-control" 
                               value="<?= htmlspecialchars($user['name_first'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Last Name <span class="text-danger">*</span></label>
                        <input type="text" name="lastname" class="form-control" 
                               value="<?= htmlspecialchars($user['name_last'] ?? '') ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" name="username" class="form-control" 
                               value="<?= htmlspecialchars($user['user_username'] ?? '') ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Leave blank to keep current">
                        <div class="form-text">Only fill if you want to change the password.</div>
                    </div>

                    <!-- ROLES SECTION -->
                    <div class="col-md-6">
                        <label class="form-label">Roles <span class="text-danger">*</span></label>

                        <div id="roleContainer">
                            <?php 
                            $user_role_ids = [];
                            if (!empty($user['role_ID'])) {
                                $user_role_ids = array_map('intval', explode(',', $user['role_ID']));
                            }
                            if (empty($user_role_ids)) {
                                $user_role_ids = [0];
                            }

                            foreach ($user_role_ids as $index => $role_id):
                                $is_first = ($index === 0);
                            ?>
                            <div class="role-group mb-2 d-flex align-items-start">
                                <select name="role_ID[]" class="form-select" required>
                                    <option value="">-- SELECT ROLE --</option>
                                    <?php foreach ($allRoles as $r): ?>
                                        <option value="<?= $r['role_ID'] ?>" 
                                            <?= ($role_id == $r['role_ID']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($r['role_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (count($user_role_ids) > 1 || !$is_first): ?>
                                    <button type="button" class="btn btn-danger btn-sm ms-2 mt-1" 
                                            onclick="removeRole(this)">Remove</button>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <button type="button" class="btn btn-primary btn-sm mt-2" onclick="addRole()" id="addRoleBtn">
                            Add Role
                        </button>
                        <small id="addRoleLimitMsg" class="text-danger d-block mt-1"></small>
                    </div>

                    <!-- ACCOUNT STATUS -->
                    <div class="col-md-6">
                        <label class="form-label">Account Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="Active" <?= ($user['account_status'] ?? '') == 'Active' ? 'selected' : '' ?>>Active</option>
                            <option value="Inactive" <?= ($user['account_status'] ?? '') == 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-success">
                        Update User
                    </button>
                    <a href="manage-users.php" class="btn btn-secondary">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </main>

    <!-- Role Template -->
    <script type="text/template" id="roleTemplate">
        <div class="role-group mb-2 d-flex align-items-start">
            <select name="role_ID[]" class="form-select" required>
                <option value="">-- SELECT ROLE --</option>
                <?php foreach ($allRoles as $r): ?>
                    <option value="<?= $r['role_ID'] ?>">
                        <?= htmlspecialchars($r['role_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="button" class="btn btn-danger btn-sm ms-2 mt-1" 
                    onclick="removeRole(this)">Remove</button>
        </div>
    </script>

    <script src="../../assets/vendor/twbs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Live Clock & Role Management -->
    <script>
        const TOTAL_ROLES = <?= $totalRoles ?>;

        // Live Clock
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

        // Add Role
        function addRole() {
            const container = document.getElementById('roleContainer');
            const currentCount = container.querySelectorAll('.role-group').length;

            if (currentCount >= TOTAL_ROLES) {
                showLimitMessage(true);
                return;
            }

            const template = document.getElementById('roleTemplate').innerHTML;
            const div = document.createElement('div');
            div.innerHTML = template;
            container.appendChild(div.firstElementChild);
            updateRemoveButtons();
            showLimitMessage(false);
        }

        // Remove Role
        function removeRole(button) {
            button.parentElement.remove();
            updateRemoveButtons();
            showLimitMessage(false);
        }

        // Update Remove Buttons
        function updateRemoveButtons() {
            const groups = document.querySelectorAll('#roleContainer .role-group');
            groups.forEach((group, idx) => {
                let btn = group.querySelector('.btn-danger');
                if (groups.length <= 1) {
                    if (btn) btn.remove();
                } else {
                    if (!btn) {
                        btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'btn btn-danger btn-sm ms-2 mt-1';
                        btn.textContent = 'Remove';
                        btn.onclick = () => removeRole(btn);
                        group.appendChild(btn);
                    }
                }
            });
        }

        // Show Limit Message
        function showLimitMessage(show) {
            const msg = document.getElementById('addRoleLimitMsg');
            const btn = document.getElementById('addRoleBtn');
            if (show) {
                msg.textContent = `You cannot add more roles – only ${TOTAL_ROLES} role(s) exist.`;
                btn.disabled = true;
            } else {
                msg.textContent = '';
                btn.disabled = false;
            }
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            updateRemoveButtons();
            const currentCount = document.querySelectorAll('#roleContainer .role-group').length;
            if (currentCount >= TOTAL_ROLES) {
                showLimitMessage(true);
            }
        });
    </script>
</body>
</html>
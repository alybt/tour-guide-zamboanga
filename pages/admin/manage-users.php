<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role_name'] !== 'Admin' || $_SESSION['user']['account_status'] == 'Suspended') {
    header('Location: ../../index.php');
    exit;
}

require_once "../../config/database.php";
require_once "../../classes/auth.php";
require_once "../../classes/admin.php";
 

$adminObj = new Admin();
$admin = [];
$users = [];

$users = $adminObj->getAllUsersDetails(); 
$success_message = $_SESSION['success'] ?? '';
$error_message   = $_SESSION['error'] ?? '';

// Clear session messages
unset($_SESSION['success'], $_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users | Tourismo Zamboanga Admin</title>

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
            background-color: gainsboro;
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

        .table-container {
            background: var(--primary-color);
            border-radius: 14px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            overflow: hidden;
            border: 1px solid rgba(207, 231, 229, 0.4);
        }

        .table th {
            background-color: rgba(207, 231, 229, 0.3);
            color: var(--text-dark);
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table td {
            vertical-align: middle;
            font-size: 0.95rem;
        }

        .btn-sm {
            font-size: 0.8rem;
            padding: 0.25rem 0.6rem;
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

        .badge-role {
            font-size: 0.75rem;
            padding: 0.35em 0.65em;
        }

        .badge-status {
            font-size: 0.75rem;
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
            .header-card {
                padding: 1.25rem;
            }
            .table-responsive {
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>

    
    <?php include 'includes/dashboard.php';?>

    <!-- Main Content -->
    <main class="main-content">

        <!-- Header -->
        <div class="header-card d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <div>
                <h3 class="mb-1 fw-bold">Manage Users</h3>
                <p class="text-muted mb-0">View, edit, or delete user accounts. Total: <strong><?= count($users) ?></strong> users.</p>
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

        <!-- Alerts -->
        <?php if (!empty($success_message)): ?>
            <div class="alert-custom alert-success p-3">
                <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="alert-custom alert-error p-3">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <!-- Users Table -->
        <div class="table-container">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Full Name</th>
                            <th>Username</th>
                            <th>Password</th>
                            <th>Role</th>
                            <th>Tourist</th>
                            <th>Guide</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($users)): ?>
                            <?php $no = 1; foreach ($users as $u): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($u['full_name']) ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($u['username']) ?></td>
                                    <td><em>********</em></td>
                                    <td>
                                        <?php
                                        // Handle both ", " and "," separators safely
                                        $role_names  = !empty($u['role_name']) ? preg_split('/,\s*/', $u['role_name']) : [];
                                        $role_ids    = !empty($u['role_ID']) ? preg_split('/,\s*/', $u['role_ID']) : [];
                                        $is_Tourist = 0;
                                        $is_Guide = 0;
                                        $is_Admin = 0;

                                        // Loop through roles
                                        foreach ($role_ids as $i => $role_ID) {
                                            $role_ID = (int)$role_ID;
                                            $role_name = $role_names[$i] ?? 'Unknown Role';

                                            // Choose color
                                            $badgeClass = match ($role_ID) {
                                                1 => 'bg-danger',
                                                2 => 'bg-warning',
                                                3 => 'bg-info',
                                                default => 'bg-secondary',
                                            };

                                            if ($role_ID == 1){ $is_Admin = 1; } 
                                            else if ($role_ID == 2){ $is_Guide = 1; }
                                            else if ($role_ID == 3){ $is_Tourist = 1; }
                                        ?>
                                            <span class="badge <?= $badgeClass ?> badge-role">
                                                <?= htmlspecialchars($role_name) ?>
                                            </span>
                                        <?php } ?>
                                    </td>


                                    <td class="text-center">
                                        
                                        <?= $is_Tourist == 1 ? '<i class="bi bi-check-circle text-success"></i>' : '<i class="bi bi-x-circle text-muted"></i>' ?>
                                    </td>
                                    <td class="text-center">
                                        <?= $is_Guide == 1 ? '<i class="bi bi-check-circle text-success"></i>' : '<i class="bi bi-x-circle text-muted"></i>' ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= $u['status'] === 'Active' ? 'bg-success' : 'bg-secondary' ?> badge-status">
                                            <?= htmlspecialchars($u['status']) ?? '' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="manage-user-edit.php?id=<?= $u['user_ID'] ?>" 
                                           class="btn btn-sm btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="manage-user-delete.php?id=<?= $u['user_ID'] ?>" 
                                           class="btn btn-sm btn-outline-danger"
                                           onclick="return confirm('Delete this user? This action cannot be undone.')"
                                           title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                        <a href="user-details.php?id=<?= $u['user_ID'] ?>" 
                                           class="btn btn-sm btn-outline-info" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">
                                    <i class="bi bi-people fs-1 d-block mb-2"></i>
                                    No users found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mt-4 d-flex gap-2">
            <a href="add-user.php" class="btn btn-primary btn-sm">
                <i class="bi bi-person-plus"></i> Add New User
            </a>
        </div>
    </main>

    <!-- Bootstrap JS -->
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
</body>
</html>
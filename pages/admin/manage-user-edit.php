<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role_name'] !== 'Admin' || $_SESSION['user']['account_status'] == 'Suspended') {
    header('Location: ../../index.php');
    exit;
}
require_once "../../classes/admin.php";

$adminObj = new Admin();
 
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
$totalRoles = count($allRoles);
 
$userRoles = $adminObj->getUserRoleAssignments($user_ID);

// If no roles exist, create one empty slot
if (empty($userRoles)) {
    $userRoles = [
        ['account_ID' => 0, 'role_ID' => 0, 'account_status' => 'Active', 'is_deleted' => null, 'role_name' => '']
    ];
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstname = trim($_POST['firstname']);
    $lastname  = trim($_POST['lastname']);
    $username  = trim($_POST['username']);
    $password  = trim($_POST['password']);

    // Validation
    $errors = [];
    if (empty($firstname)) $errors[] = "First name is required.";
    if (empty($lastname)) $errors[] = "Last name is required.";
    if (empty($username)) $errors[] = "Username is required.";

    if (empty($errors)) {
        try {
            // Use the Admin class method to update
            $adminObj->updateUserDetails(
                $user_ID,
                $firstname,
                $lastname,
                $username,
                $password
            );

            $_SESSION['success'] = "User updated successfully.";
            header("Location: manage-users.php");
            exit;

        } catch (Exception $e) {
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

        /* Role Card Styling */
        .role-card {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 1rem;
            background: #f8f9fa;
            transition: all 0.2s;
        }

        .role-card:hover {
            border-color: var(--accent);
            box-shadow: 0 4px 12px rgba(229, 161, 62, 0.15);
        }

        .role-card.deleted {
            background: #fff3cd;
            border-color: #ffc107;
            opacity: 0.85;
        }

        .role-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .role-number {
            font-weight: 700;
            color: var(--accent);
            font-size: 0.9rem;
        }

        .status-indicator {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-active { background: #d4edda; color: #155724; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-suspended { background: #f8d7da; color: #721c24; }
        .status-deleted { background: #e2e3e5; color: #383d41; }

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
                <p class="text-muted mb-0">Update user information, roles, and individual account statuses.</p>
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
                <!-- User Basic Info -->
                <div class="row g-4 mb-4">
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
                </div>

                <hr class="my-4">

                <!-- ROLES SECTION -->
                <div class="mb-3">
                    <h5 class="mb-3">
                        <i class="bi bi-person-badge"></i> Role Assignments
                        <small class="text-muted">(Each role has its own status)</small>
                    </h5>
                </div>

                <div id="roleContainer">
                    <?php if (empty($userRoles)): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> No roles assigned yet.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Role Name</th>
                                        <th>Status</th>
                                        <th>Account Info ID</th>
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="roleTableBody">
                                    <?php foreach ($userRoles as $index => $roleData): 
                                        $isDeleted = !empty($roleData['is_deleted']);
                                        $currentStatus = $roleData['account_status'];
                                        $accountInfoId = $roleData['account_ID'];
                                        $roleName = htmlspecialchars($roleData['role_name'] ?? 'N/A');
                                    ?>
                                    <tr id="roleRow_<?= $accountInfoId ?>" class="<?= $isDeleted ? 'table-warning' : '' ?>">
                                        <td>
                                            <strong><?= $roleName ?></strong>
                                            <?php if ($isDeleted): ?>
                                                <span class="badge bg-secondary ms-2">Deleted</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="status-indicator status-<?= strtolower($currentStatus) ?>">
                                                <?= htmlspecialchars($currentStatus) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <code><?= $accountInfoId ?></code>
                                        </td>
                                        <td class="text-center">
                                            <?php if (!$isDeleted): ?>
                                                <button type="button" 
                                                        class="btn btn-sm btn-danger delete-role-btn" 
                                                        data-account-id="<?= $accountInfoId ?>"
                                                        data-role-name="<?= $roleName ?>"
                                                        onclick="deleteUserRole(<?= $accountInfoId ?>, '<?= $roleName ?>')">
                                                    <i class="bi bi-trash"></i> Delete
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted small">Already Deleted</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>



                <button type="button" class="btn btn-primary btn-sm mt-3" data-bs-toggle="modal" data-bs-target="#addRoleModal" id="addRoleBtn">
                    <i class="bi bi-plus-circle"></i> Add Another Role
                </button>
                <small id="addRoleLimitMsg" class="text-danger d-block mt-2"></small>

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle"></i> Update User
                    </button>
                    <a href="manage-users.php" class="btn btn-secondary">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </main>

    <!-- Add Role Modal -->
    <div class="modal fade" id="addRoleModal" tabindex="-1" aria-labelledby="addRoleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addRoleModalLabel">Add New Role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addRoleForm">
                        <input type="hidden" name="user_id" value="<?= $user_ID ?>">
                        <div class="mb-3">
                            <label class="form-label">Select Role <span class="text-danger">*</span></label>
                            <select name="role_ID" class="form-select" required>
                                <option value="">-- Select Role --</option>
                                <?php foreach ($allRoles as $r): ?>
                                    <option value="<?= $r['role_ID'] ?>"><?= htmlspecialchars($r['role_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="Active">Active</option>
                                <option value="Pending">Pending</option>
                                <option value="Suspended">Suspended</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitAddRole()">Add Role</button>
                </div>
            </div>
        </div>
    </div>
 

    <script src="../../assets/vendor/twbs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Scripts -->
    <script>
        const TOTAL_ROLES = <?= $totalRoles ?>;
        const USER_ID = <?= $user_ID ?>;

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

        // AJAX Delete User Role
        function deleteUserRole(accountInfoId, roleName) {
            // Confirmation dialog
            if (!confirm(`Are you sure you want to delete the role "${roleName}"?\n\nThis action will mark the account as deleted.`)) {
                return;
            }

            // Show loading state
            const btn = document.querySelector(`button[data-account-id="${accountInfoId}"]`);
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Deleting...';
            btn.disabled = true;

            // Create FormData
            const formData = new FormData();
            formData.append('account_ID', accountInfoId);
            formData.append('user_id', USER_ID);

            // Send AJAX request
            fetch('manage-user-delete.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    showAlert('success', data.message);
                    
                    // Remove or update the row
                    const row = document.getElementById(`roleRow_${accountInfoId}`);
                    if (row) {
                        // Add fade-out animation
                        row.style.transition = 'opacity 0.5s';
                        row.style.opacity = '0';
                        
                        setTimeout(() => {
                            row.remove();
                            
                            // Check if table is empty
                            const tbody = document.getElementById('roleTableBody');
                            if (tbody.children.length === 0) {
                                location.reload(); // Reload if no roles left
                            }
                        }, 500);
                    }
                } else {
                    // Show error message
                    showAlert('error', data.message);
                    
                    // Restore button
                    btn.innerHTML = originalHtml;
                    btn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('error', 'An error occurred while deleting the role. Please try again.');
                
                // Restore button
                btn.innerHTML = originalHtml;
                btn.disabled = false;
            });
        }

        // AJAX Add User Role
        function submitAddRole() {
            const form = document.getElementById('addRoleForm');
            const btn = event.target;
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Adding...';
            btn.disabled = true;

            const formData = new FormData(form);

            fetch('manage-user-add-role.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                btn.innerHTML = originalHtml;
                btn.disabled = false;

                if (data.success) {
                    showAlert('success', data.message);
                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('addRoleModal'));
                    modal.hide();
                    // Reload page to show new role
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showAlert('error', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                btn.innerHTML = originalHtml;
                btn.disabled = false;
                showAlert('error', 'An error occurred while adding the role. Please try again.');
            });
        }

        // Show Alert Function
        function showAlert(type, message) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert-custom alert-${type} p-3`;
            alertDiv.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
                    <span>${message}</span>
                </div>
            `;
            
            // Insert at the top of main content
            const mainContent = document.querySelector('.main-content');
            const headerCard = document.querySelector('.header-card');
            mainContent.insertBefore(alertDiv, headerCard.nextSibling);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                alertDiv.style.transition = 'opacity 0.5s';
                alertDiv.style.opacity = '0';
                setTimeout(() => alertDiv.remove(), 500);
            }, 5000);
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', () => {
            console.log('Edit user page loaded with AJAX delete functionality');
        }); 
    </script>
</body>
</html>
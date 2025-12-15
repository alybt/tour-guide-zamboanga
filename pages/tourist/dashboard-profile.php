<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role_name'] !== 'Tourist') {
    header('Location: ../../index.php');
    exit;
} else if ($_SESSION['user']['account_status'] == 'Suspended') {
    header('Location: account-suspension.php');
    exit;
} else if ($_SESSION['user']['account_status'] == 'Pending') {
    header('Location: account-pending.php');
    exit;
}

require_once "../../classes/tourist.php";
require_once "../../classes/account.php";

$accountObj = new Account();
$accountProfile = new Account();
$account_ID = $_SESSION['account_ID'];

$accountDetails = $accountObj->getInfobyAccountID($account_ID);
$upload_message = '';
$upload_success = false;
$active_tab = $_GET['tab'] ?? 'profile'; // Get active tab from URL

// Handle Profile Picture Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_picture'])) {
    $result = $accountProfile->updateProfilePicture($account_ID, $_FILES['profile_picture']);
    
    $upload_message = $result['message'];
    $upload_success = $result['success'];
    
    if ($result['success']) { 
        $_SESSION['user']['account_profilepic'] = $result['file_path'];
    }
}

// Handle Profile Picture Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_picture'])) {
    $result = $accountProfile->deleteProfilePicture($account_ID);
    
    $upload_message = $result['message'];
    $upload_success = $result['success'];
    
    if ($result['success']) {
        $_SESSION['user']['account_profilepic'] = null;
    }
}

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $profileData = [
        'name_first' => $_POST['name_first'] ?? '',
        'name_second' => $_POST['name_second'] ?? '',
        'name_middle' => $_POST['name_middle'] ?? '',
        'name_last' => $_POST['name_last'] ?? '',
        'name_suffix' => $_POST['name_suffix'] ?? '',
        'email' => $_POST['email'] ?? '',
        'phone_number' => $_POST['phone_number'] ?? '',
        'account_aboutme' => $_POST['account_aboutme'] ?? '',
        'account_bio' => $_POST['account_bio'] ?? '',
        'account_nickname' => $_POST['account_nickname'] ?? ''
    ];
    
    $result = $accountProfile->updateProfileInfo($account_ID, $profileData);
    
    $upload_message = $result['message'];
    $upload_success = $result['success'];
    
    if ($result['success']) {
        $accountDetails = $accountObj->getInfobyAccountID($account_ID);
    }
}

// Handle Security Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_security'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_username = $_POST['new_username'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $result = $accountProfile->updateSecurity($account_ID, $current_password, $new_username, $new_password, $confirm_password);
    
    $upload_message = $result['message'];
    $upload_success = $result['success'];
    $active_tab = 'security';
}

// Get Activity Logs for Notifications
$activityLogs = $accountProfile->getActivityLogs($account_ID, 20); // Get last 20 activities

$current_profile_pic = $accountProfile->getProfilePicture($account_ID);
$profile_pic_url = $current_profile_pic ?: 'https://via.placeholder.com/120';

$details = !empty($accountDetails) ? $accountDetails[0] : [];
$securityInfo = $accountProfile->getSecurityInfo($account_ID);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Profile Settings - Tourismo Zamboanga</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #ffffff;
            --secondary-color: #213638;
            --accent: #E5A13E;
            --secondary-accent: #CFE7E5;
            --muted-color: gainsboro;
        }
        body {
            background-color: var(--muted-color);
            margin-top: 5rem;
        }
        .settings-sidebar {
            background: white;
            border-radius: 15px;
            padding: 20px;
        }
        .settings-sidebar .nav-link {
            color: var(--secondary-color) !important;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 5px;
            transition: all 0.3s;
        }
        .settings-sidebar .nav-link:hover {
            background: var(--secondary-accent);
        }
        .settings-sidebar .nav-link.active {
            background: var(--accent);
            color: var(--secondary-color)!important;
            font-weight: 600;
        }
        .settings-content {
            background: white;
            border-radius: 15px;
            padding: 30px;
        }
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--accent);
        }
        .btn-primary {
            background: var(--accent);
            border: none;
            color: var(--secondary-color);
            font-weight: 600;
        }
        .btn-primary:hover {
            background: #d69330;
        }
        #profile_picture {
            display: none;
        }
        .activity-item {
            padding: 15px;
            border-left: 3px solid var(--accent);
            background: #f8f9fa;
            margin-bottom: 10px;
            border-radius: 5px;
        }
        .activity-time {
            color: #6c757d;
            font-size: 0.85rem;
        }
        .password-strength {
            height: 5px;
            border-radius: 3px;
            margin-top: 5px;
            transition: all 0.3s;
        }
        .content-section {
            display: none;
        }
        .content-section.active {
            display: block;
        }
    </style>
</head>
<body> 
    <?php include_once 'includes/header.php'; ?>
    
    <div class="container">
        <!-- Alert Messages -->
        <?php if ($upload_message): ?>
            <div class="alert alert-<?php echo $upload_success ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($upload_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-3">
                <div class="settings-sidebar">
                    <nav class="nav flex-column">
                        <a class="nav-link <?php echo $active_tab === 'profile' ? 'active' : ''; ?>" 
                           href="?tab=profile" data-tab="profile">
                            <i class="fas fa-user me-2"></i> Profile
                        </a>
                        <a class="nav-link <?php echo $active_tab === 'security' ? 'active' : ''; ?>" 
                           href="?tab=security" data-tab="security">
                            <i class="fas fa-lock me-2"></i> Security
                        </a>
                        <a class="nav-link <?php echo $active_tab === 'notifications' ? 'active' : ''; ?>" 
                           href="?tab=notifications" data-tab="notifications">
                            <i class="fas fa-bell me-2"></i> Notifications
                        </a>
                    </nav>
                </div>
            </div>
            <div class="col-md-9">
                <!-- PROFILE SECTION -->
                <div class="settings-content content-section <?php echo $active_tab === 'profile' ? 'active' : ''; ?>" id="profile-section">
                    <h3 class="mb-4">Personal Information</h3>
                    
                    <div class="text-center mb-4">
                        <img src="<?php echo htmlspecialchars($profile_pic_url); ?>" class="profile-avatar" alt="Profile" id="preview-image">
                        
                        <form method="POST" enctype="multipart/form-data" id="profile-pic-form">
                            <input type="file" name="profile_picture" id="profile_picture" accept="image/jpeg,image/png,image/gif,image/webp">
                            <div class="mt-3">
                                <button type="button" class="btn btn-primary btn-sm me-2" onclick="document.getElementById('profile_picture').click()">
                                    <i class="fas fa-camera me-2"></i>Change Photo
                                </button>
                                
                                <?php if ($current_profile_pic): ?>
                                    <button type="submit" name="delete_picture" class="btn btn-outline-danger btn-sm" 
                                            onclick="return confirm('Are you sure you want to remove your profile picture?')">
                                        Remove
                                    </button>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>

                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">First Name</label>
                                <input type="text" name="name_first" class="form-control" 
                                       value="<?php echo htmlspecialchars($details['name_first'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Second Name</label>
                                <input type="text" name="name_second" class="form-control" 
                                       value="<?php echo htmlspecialchars($details['name_second'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Middle Name</label>
                                <input type="text" name="name_middle" class="form-control" 
                                       value="<?php echo htmlspecialchars($details['name_middle'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Last Name</label>
                                <input type="text" name="name_last" class="form-control" 
                                       value="<?php echo htmlspecialchars($details['name_last'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Suffix</label>
                                <input type="text" name="name_suffix" class="form-control" 
                                       value="<?php echo htmlspecialchars($details['name_suffix'] ?? ''); ?>" 
                                       placeholder="Jr., Sr., III, etc.">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nickname</label>
                                <input type="text" name="account_nickname" class="form-control" 
                                       value="<?php echo htmlspecialchars($details['account_nickname'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" 
                                   value="<?php echo htmlspecialchars($details['email'] ?? ''); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" name="phone_number" class="form-control" 
                                   value="<?php echo htmlspecialchars($details['phone_number'] ?? ''); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">About Me</label>
                            <textarea name="account_aboutme" class="form-control" rows="4" 
                                      placeholder="Tell us about yourself..."><?php echo htmlspecialchars($details['account_aboutme'] ?? ''); ?></textarea>
                        </div> 

                        <div class="mb-3">
                            <label class="form-label">Bio</label>
                            <textarea name="account_bio" class="form-control" rows="4" 
                                      placeholder="Short bio..."><?php echo htmlspecialchars($details['account_bio'] ?? ''); ?></textarea>
                        </div> 

                        <hr class="my-4">

                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-secondary" onclick="location.reload()">Cancel</button>
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Save Changes
                            </button>
                        </div>
                    </form>
                </div>

                <!-- SECURITY SECTION -->
                <div class="settings-content content-section <?php echo $active_tab === 'security' ? 'active' : ''; ?>" id="security-section">
                    <h3 class="mb-4">Security Settings</h3>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Keep your account secure by using a strong password and updating your credentials regularly.
                    </div>

                    <form method="POST" id="security-form">
                        <div class="mb-4">
                            <h5>Current Credentials</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Current Username</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($securityInfo['username'] ?? ''); ?>" readonly>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Last Password Change</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($securityInfo['last_password_change'] ?? 'Never'); ?>" readonly>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <h5 class="mb-3">Change Credentials</h5>

                        <div class="mb-3">
                            <label class="form-label">Current Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" name="current_password" class="form-control" id="current-password" required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('current-password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <small class="text-muted">Required to make any changes</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">New Username</label>
                            <input type="text" name="new_username" class="form-control" 
                                   placeholder="Leave blank to keep current username">
                            <small class="text-muted">Username must be unique and at least 4 characters</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <div class="input-group">
                                <input type="password" name="new_password" class="form-control" id="new-password" 
                                       placeholder="Leave blank to keep current password">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('new-password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="password-strength" id="password-strength"></div>
                            <small class="text-muted">Password must be at least 8 characters with uppercase, lowercase, and numbers</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <div class="input-group">
                                <input type="password" name="confirm_password" class="form-control" id="confirm-password" 
                                       placeholder="Confirm new password">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm-password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-secondary" onclick="location.href='?tab=security'">Cancel</button>
                            <button type="submit" name="update_security" class="btn btn-primary">
                                <i class="fas fa-shield-alt me-2"></i>Update Security
                            </button>
                        </div>
                    </form>
                </div>

                <!-- NOTIFICATIONS SECTION -->
                <div class="settings-content content-section <?php echo $active_tab === 'notifications' ? 'active' : ''; ?>" id="notifications-section">
                    <h3 class="mb-4">Activity Log & Notifications</h3>
                    
                    <div class="mb-3">
                        <p class="text-muted">Review your recent account activities and system notifications</p>
                    </div>

                    <?php if (!empty($activityLogs)): ?>
                        <?php foreach ($activityLogs as $log): ?>
                            <div class="activity-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1">
                                            <i class="fas fa-circle-notch me-2" style="font-size: 0.7rem; color: var(--accent);"></i>
                                            <?php echo htmlspecialchars($log['action_name'] ?? 'Activity'); ?>
                                        </h6>
                                        <p class="mb-1"><?php echo htmlspecialchars($log['activity_description']); ?></p>
                                        <small class="activity-time">
                                            <i class="far fa-clock me-1"></i>
                                            <?php echo date('F d, Y - h:i A', strtotime($log['activity_timestamp'])); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            No recent activities found.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Profile picture preview and auto-submit
        document.getElementById('profile_picture').addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('preview-image').src = e.target.result;
                }
                reader.readAsDataURL(this.files[0]);
                document.getElementById('profile-pic-form').submit();
            }
        });

        // Toggle password visibility
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = event.currentTarget.querySelector('i');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Password strength indicator
        document.getElementById('new-password')?.addEventListener('input', function(e) {
            const password = e.target.value;
            const strengthBar = document.getElementById('password-strength');
            
            if (password.length === 0) {
                strengthBar.style.width = '0%';
                strengthBar.style.backgroundColor = '';
                return;
            }
            
            let strength = 0;
            if (password.length >= 8) strength += 25;
            if (password.match(/[a-z]/)) strength += 25;
            if (password.match(/[A-Z]/)) strength += 25;
            if (password.match(/[0-9]/)) strength += 25;
            
            strengthBar.style.width = strength + '%';
            
            if (strength <= 25) {
                strengthBar.style.backgroundColor = '#dc3545';
            } else if (strength <= 50) {
                strengthBar.style.backgroundColor = '#ffc107';
            } else if (strength <= 75) {
                strengthBar.style.backgroundColor = '#17a2b8';
            } else {
                strengthBar.style.backgroundColor = '#28a745';
            }
        });

        // Confirm password match validation
        document.getElementById('security-form')?.addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new-password').value;
            const confirmPassword = document.getElementById('confirm-password').value;
            
            if (newPassword && newPassword !== confirmPassword) {
                e.preventDefault();
                alert('New passwords do not match!');
            }
        });
    </script>
</body>
</html>
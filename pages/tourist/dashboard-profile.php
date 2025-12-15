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

$accountProfile = new Account();
$account_ID = $_SESSION['account_ID'];
 
$upload_message = '';
$upload_success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_picture'])) {
    $result = $accountProfile->updateProfilePicture($account_ID, $_FILES['profile_picture']);
    
    $upload_message = $result['message'];
    $upload_success = $result['success'];
    
    if ($result['success']) { 
        $_SESSION['user']['account_profilepic'] = $result['file_path'];
    }
}
 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_picture'])) {
    $result = $accountProfile->deleteProfilePicture($account_ID);
    
    $upload_message = $result['message'];
    $upload_success = $result['success'];
    
    if ($result['success']) {
        $_SESSION['user']['account_profilepic'] = null;
    }
}
 
$current_profile_pic = $accountProfile->getProfilePicture($account_ID);
$profile_pic_url = $current_profile_pic;

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
        #profile_picture {
            display: none;
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
                        <a class="nav-link active" href="#"><i class="fas fa-user me-2"></i> Profile</a>
                        <a class="nav-link" href="#"><i class="fas fa-lock me-2"></i> Security</a>
                        <a class="nav-link" href="#"><i class="fas fa-bell me-2"></i> Notifications</a>
                        <a class="nav-link" href="#"><i class="fas fa-credit-card me-2"></i> Payments</a>
                        <a class="nav-link" href="#"><i class="fas fa-globe me-2"></i> Preferences</a>
                        <a class="nav-link" href="#"><i class="fas fa-shield-alt me-2"></i> Privacy</a>
                    </nav>
                </div>
            </div>
            <div class="col-md-9">
                <div class="settings-content">
                    <h3 class="mb-4">Personal Information</h3>
                    
                    <div class="text-center mb-4">
                        <img src="<?php echo htmlspecialchars($profile_pic_url); ?>" class="profile-avatar" alt="Profile" id="preview-image" style= "height: 100px; width: 100px;">
                        
                        <!-- Profile Picture Upload Form -->
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

                    <form>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">First Name</label>
                                <input type="text" class="form-control" value="Sarah" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Last Name</label>
                                <input type="text" class="form-control" value="Johnson" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control" value="sarah.johnson@example.com" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" value="+1 234 567 8900" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Country</label>
                                <select class="form-select">
                                    <option selected>United States</option>
                                    <option>United Kingdom</option>
                                    <option>Canada</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">City</label>
                                <input type="text" class="form-control" value="New York" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Bio</label>
                            <textarea class="form-control" rows="4" placeholder="Tell us about yourself..."></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Languages</label>
                            <input type="text" class="form-control" value="English, Spanish" placeholder="Separate with commas">
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-secondary">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-submit form when file is selected and show preview
        document.getElementById('profile_picture').addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                // Show preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('preview-image').src = e.target.result;
                }
                reader.readAsDataURL(this.files[0]);
                
                // Auto-submit form
                document.getElementById('profile-pic-form').submit();
            }
        });
    </script>
</body>
</html>
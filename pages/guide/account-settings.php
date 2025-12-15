<?php
session_start();
$current_page = basename($_SERVER['PHP_SELF']);

// Redirect if not logged in or not a Guide
if (!isset($_SESSION['user']) || $_SESSION['user']['role_name'] !== 'Tour Guide') {
    header('Location: ../../index.php');
    exit;
}

// Status-based redirects
if ($_SESSION['user']['account_status'] === 'Suspended') {
    header('Location: account-suspension.php');
    exit;
}
if ($_SESSION['user']['account_status'] === 'Pending') {
    header('Location: account-pending.php');
    exit;
}

require_once "../../classes/guide.php";

$guideObj = new Guide();
$account_ID = $_SESSION['user']['account_ID'];

// Get current account info
$db = new PDO(
    'mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_NAME'),
    getenv('DB_USER'),
    getenv('DB_PASS')
);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sql = "SELECT account_profilepic, account_aboutme, account_bio, account_nickname 
        FROM Account_Info WHERE account_ID = :account_ID";
$stmt = $db->prepare($sql);
$stmt->bindParam(':account_ID', $account_ID, PDO::PARAM_INT);
$stmt->execute();
$accountInfo = $stmt->fetch(PDO::FETCH_ASSOC);

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nickname = $_POST['account_nickname'] ?? '';
        $aboutme = $_POST['account_aboutme'] ?? '';
        $bio = $_POST['account_bio'] ?? '';
        $profilepic = $accountInfo['account_profilepic'] ?? '';

        // Handle file upload
        if (!empty($_FILES['account_profilepic']['name'])) {
            $file = $_FILES['account_profilepic'];
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = basename($file['name']);
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (!in_array($ext, $allowed)) {
                throw new Exception("Invalid file type. Only JPG, PNG, and GIF are allowed.");
            }

            if ($file['size'] > 5 * 1024 * 1024) {
                throw new Exception("File size must be less than 5MB.");
            }

            $uploadDir = '../../assets/uploads/profiles/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $newFilename = 'guide_' . $account_ID . '_' . time() . '.' . $ext;
            $uploadPath = $uploadDir . $newFilename;

            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                $profilepic = 'assets/uploads/profiles/' . $newFilename;
            } else {
                throw new Exception("Failed to upload file.");
            }
        }

        // Update account info
        $sql = "UPDATE Account_Info 
                SET account_nickname = :nickname, 
                    account_aboutme = :aboutme, 
                    account_bio = :bio,
                    account_profilepic = :profilepic
                WHERE account_ID = :account_ID";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':nickname', $nickname);
        $stmt->bindParam(':aboutme', $aboutme);
        $stmt->bindParam(':bio', $bio);
        $stmt->bindParam(':profilepic', $profilepic);
        $stmt->bindParam(':account_ID', $account_ID, PDO::PARAM_INT);
        $stmt->execute();

        $success = "Profile updated successfully!";
        
        // Refresh account info
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':account_ID', $account_ID, PDO::PARAM_INT);
        $stmt->execute();
        $accountInfo = $stmt->fetch(PDO::FETCH_ASSOC);

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Account Settings | Tourismo Zamboanga</title>

    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="../../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/vendor/bootstrap-icons/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet"/>

    <link rel="stylesheet" href="../../assets/css/guide/dashboard.css">

    <style>
        .settings-container {
            max-width: 800px;
        }

        .form-section {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid #e9ecef;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .form-label {
            font-weight: 600;
            color: var(--secondary-color);
            margin-bottom: 0.75rem;
        }

        .form-control, .form-control:focus {
            border-radius: 6px;
            border: 1px solid #e9ecef;
        }

        .form-control:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 0.2rem rgba(229, 161, 62, 0.25);
        }

        .profile-preview {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--accent);
            margin-bottom: 1rem;
        }

        .btn-save {
            background: var(--accent);
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-save:hover {
            background: #d69435;
            color: white;
        }
    </style>
</head>
<body class="d-flex">

    <?php 
    require_once "includes/aside-dashboard.php";
    ?>

    <!-- Main Content -->
    <main class="main-content flex-grow-1">
        <!-- Header -->
        <div class="header-card d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <div>
                <h3 class="mb-1 fw-bold">Account Settings</h3>
                <p class="text-muted mb-0">Manage your profile and account information.</p>
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

        <!-- Alerts -->
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Settings Form -->
        <div class="settings-container">
            <form method="POST" enctype="multipart/form-data">
                <!-- Profile Picture Section -->
                <div class="form-section">
                    <h5 class="mb-4">
                        <i class="bi bi-image"></i> Profile Picture
                    </h5>

                    <div class="mb-3">
                        <?php if ($accountInfo && $accountInfo['account_profilepic']): ?>
                            <img src="<?= htmlspecialchars($accountInfo['account_profilepic']) ?>" 
                                 alt="Profile" class="profile-preview">
                        <?php else: ?>
                            <div class="profile-preview bg-secondary d-flex align-items-center justify-content-center">
                                <i class="bi bi-person-fill text-white" style="font-size: 2rem;"></i>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="account_profilepic" class="form-label">Upload New Picture</label>
                        <input type="file" class="form-control" id="account_profilepic" name="account_profilepic" 
                               accept="image/jpeg,image/png,image/gif">
                        <small class="text-muted">Max size: 5MB. Allowed formats: JPG, PNG, GIF</small>
                    </div>
                </div>

                <!-- Basic Information -->
                <div class="form-section">
                    <h5 class="mb-4">
                        <i class="bi bi-person"></i> Basic Information
                    </h5>

                    <div class="mb-3">
                        <label for="account_nickname" class="form-label">Nickname</label>
                        <input type="text" class="form-control" id="account_nickname" name="account_nickname" 
                               value="<?= htmlspecialchars($accountInfo['account_nickname'] ?? '') ?>"
                               placeholder="e.g., 'The Adventure Guide'">
                        <small class="text-muted">A short nickname that tourists will see</small>
                    </div>

                    <div class="mb-3">
                        <label for="account_aboutme" class="form-label">About Me</label>
                        <textarea class="form-control" id="account_aboutme" name="account_aboutme" 
                                  rows="4" placeholder="Tell tourists about yourself..."><?= htmlspecialchars($accountInfo['account_aboutme'] ?? '') ?></textarea>
                        <small class="text-muted">Share your experience, passion, and what makes you unique</small>
                    </div>

                    <div class="mb-3">
                        <label for="account_bio" class="form-label">Bio</label>
                        <textarea class="form-control" id="account_bio" name="account_bio" 
                                  rows="3" placeholder="A brief bio (max 255 characters)"><?= htmlspecialchars($accountInfo['account_bio'] ?? '') ?></textarea>
                        <small class="text-muted">A short professional bio</small>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="form-section d-flex gap-2">
                    <button type="submit" class="btn btn-save">
                        <i class="bi bi-check-lg"></i> Save Changes
                    </button>
                    <a href="dashboard.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Cancel
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

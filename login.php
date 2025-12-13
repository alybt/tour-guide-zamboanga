<?php
// git config --global core.autocrlf true

ob_start();
session_start();
require_once "config/database.php";
require_once "classes/auth.php";
require_once "classes/activity-log.php";

$activityObj = new ActivityLogs();
$authObj = new Auth();
$auth = [];
$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $auth["username"] = trim(htmlspecialchars($_POST["username"]));
    $auth["password"] = trim(htmlspecialchars($_POST["password"]));

    $user = $authObj->login($auth["username"], $auth["password"]);

    if ($user && isset($user["role_ID"])) {
        $_SESSION["user"] = $user;
        $_SESSION["account_ID"] = $user["account_ID"];  // ✅ add this line
        $_SESSION["role_ID"] = $user["role_ID"];
        $_SESSION["username"] = $user["user_username"];
        $role = $user["role_ID"];

        if ($role == 1) {
            $action = $activityObj->loginActivity($user["account_ID"]);
            header('Location: pages/admin/dashboard.php');
            exit();
        } elseif ($role == 2) {
            $action = $activityObj->loginActivity($user["account_ID"]);
            header('Location: pages/guide/dashboard.php');
            exit();
        } else {
            $action = $activityObj->loginActivity($user["account_ID"]);
            header('Location: pages/tourist/index.php');
            exit();
        }

    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - Tourismo Zamboanga</title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/node_modules/@fortawesome/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="assets/css/public-pages/login.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            
            <div class="logo">
                <i class="fas fa-map-marked-alt"></i>
                <h2>Tourismo Zamboanga</h2>
                <p>Please login to your account</p>
            </div>

            <?php 
            // NOTE: The $error and $auth variables must be defined 
            // and populated in the PHP script that includes this HTML.
            if (!empty($error)) {
                echo '<div class="alert alert-danger alert-custom" role="alert">';
                echo '<i class="fas fa-exclamation-triangle me-2"></i> ' . htmlspecialchars($error);
                echo '</div>';
            }
            ?>
            
            <form id="loginForm" method="POST" action="">
                
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" 
                               name="username" 
                               id="username" 
                               class="form-control" 
                               placeholder="Enter your username" 
                               value="<?= htmlspecialchars($auth["username"] ?? '') ?>" 
                               required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group position-relative">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" 
                               name="password" 
                               id="password" 
                               class="form-control" 
                               placeholder="Enter your password" 
                               required>
                        <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                    </div>
                </div>

                <div class="remember-forgot">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="rememberMe" name="rememberMe">
                        <label class="form-check-label" for="rememberMe">
                            Remember me
                        </label>
                    </div>
                    <a href="#" class="forgot-password">Forgot Password?</a> 
                </div>

                <button type="submit" class="btn btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i> Sign In
                </button>
            </form>

            <div class="divider">
                <span>or register</span>
            </div>

            <div class="signup-link">
                Don't have an account? <a href="registration/tourist-registration.php">Register as Tourist</a>
            </div>
             <div class="signup-link mt-3">
                Want to be a Local Guide? <a href="registration/guide-registration.php">Register as Guide</a>
            </div>
            
        </div>

        <div class="back-home">
            <a href="#"><i class="fas fa-arrow-left me-2"></i> Back to Home</a>
        </div>
    </div>

    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/node_modules/jquery/dist/jquery.min.js"></script>
    
    <script>
        $(document).ready(function() {
            
            // Password toggle
            $('#togglePassword').on('click', function() {
                const passwordField = $('#password');
                const type = passwordField.attr('type') === 'password' ? 'text' : 'password';
                passwordField.attr('type', type);
                
                $(this).toggleClass('fa-eye fa-eye-slash');
            });

            $('input').on('blur', function() {
                const formElement = $(this); 
                if (formElement.val().trim() === '' && formElement.prop('required')) {
                    formElement.addClass('is-invalid');
                } else {
                    formElement.removeClass('is-invalid').addClass('is-valid');
                }
            });

            $('input').on('focus', function() {
                $(this).removeClass('is-invalid is-valid');
            });
            
        });
    </script>
</body>
</html>
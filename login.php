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
    <title>Login</title>


    <!-- <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" rel="stylesheet"> -->
    <!-- <link href="https://cdnjs.cloudflare.com/ajaxs/libs/font-awesome/4.0.3/css/font-awesome.css" rel="stylesheet"> -->

    <!-- Bootstrap -->
    <link rel="stylesheet" href="assets/vendor/components/font-awesome/css/font-awesome.min.css">
    <!-- Bootstrap -->
    <link rel="stylesheet" href="assets/vendor/twbs/bootstrap/dist/css/bootstrap.min.css">
    
    <!-- In File CSS-->
    <link rel="stylesheet" href="assets/css/public-pages/login.css">
</head>
<body>
    <form method="POST" action="">
        <div class="container px-4 py-5 mx-auto">
            <div class="card card0">
                <div class="d-flex flex-lg-row flex-column-reverse">
                    <div class="card card1">
                        <div class="row justify-content-center my-auto">
                            <div class="col-md-8 col-10 my-5">
                                <div class="row justify-content-center px-3 mb-3">
                                    <!-- <img id="logo" src="https://i.imgur.com/PSXxjNY.png"> -->
                                </div>
                                <h3 class="mb-5 text-center heading">Tourismo Zamboanga</h3>

                                <h6 class="msg-info">Please login to your account</h6>
                                <?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>

                                <div class="form-group">
                                    <label for= "username"class="form-control-label" style = "color: ">Username</label>
                                    
                                    <input type="text" name="username" placeholder="Username" value="<?= htmlspecialchars($auth["username"] ?? '') ?>" class="form-control">
                                </div>

                                <div class="form-group">
                                    <label class="form-control-label">Password</label>
                                    <!-- <input type="password" id="psw" name="psw" placeholder="Password" class="form-control"> -->
                                    <input type="password" id="psw" name="password" placeholder="Password" class="form-control">
                                </div>

                                <div class="row justify-content-center my-3 px-3">
                                    <button type="submit" class="btn-block btn-color">Login</button>
                                </div>

                                <!-- <div class="row justify-content-center my-2">
                                    <a href="#"><small class="text-muted">Forgot Password?</small></a>
                                </div> -->
                            </div>
                        </div>
                        <div class="bottom text-center mb-51">
                            <p class="sm-text mx-auto mb-3">
                                Don't have an account?
                                <a href="registration/tourist-registration.php" class="btn btn-white ml-2">Create new</a>
                            </p>
                        </div>

                        <div class="bottom1 text-center mb-51">
                            <p class="sm-text mx-auto mb-3">
                                Want to be a Local Guide?
                                <a href="registration/guide-registration.php" class="btn btn-white ml-2">Register Now</a>
                            </p>
                        </div>

                    </div>
                    <div class="card card2" style = "">
                        <div class="my-auto mx-md-5 px-md-5 right">
                            <h3 class="text-white">We are more than just a company</h3>
                            <small class="text-white">Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- <h2>Login</h2>
        <?php //if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>
        <input type="text" name="username" placeholder="Username" value="<?php //= htmlspecialchars($auth["username"] ?? '') ?>"><br><br>
        <input type="password" name="password" placeholder="Password"><br><br>
        <button type="submit">Login</button> -->
    </form>
    
        <!-- <a href="registration/tourist-registration.php">Register as A Tourist</a> -->

        <!-- Local Jquery-->
        <script src="assets/vendor/components/jquery/jquery.min.js"></script>
        <script src="assets/vendor/twbs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
        <!-- <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.bundle.min.js"></script> -->
        <!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script> -->
</body>
</html>

<?php
session_start();

$username   = $_SESSION['new_username']   ?? 'Tourist';
$emailSent  = $_SESSION['email_sent']    ?? false;
$emailError = $_SESSION['email_error']   ?? '';

// Clean session
unset($_SESSION['new_username'], $_SESSION['email_sent'], $_SESSION['email_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Successful</title>
    <link rel="stylesheet" href="../assets/css/public-pages/tourist-registration.css">
    <link rel="stylesheet" href="assets/vendor/twbs/bootstrap/dist/css/bootstrap.min.css">
    <style>
        :root {
            --primary-color: #ffffff;
            --secondary-color: #213638;
            --accent: #E5A13E;
            --text--black: #130404;
        }
        html, body { height: 100%; margin: 0; overflow: hidden; }
        body {
            background: var(--secondary-color);
            display: flex; align-items: center; justify-content: center;
            font-family: 'Segoe UI', sans-serif;
        }
        .success-wrapper {
            width: 100%; max-width: 1400px; margin: 0 auto;
            display: flex; border-radius: 20px; overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2); background: var(--primary-color);
        }
        .success-left {
            flex: 1; padding: 60px 50px; text-align: center;
            display: flex; flex-direction: column; justify-content: center;
        }
        .success-icon {
            width: 90px; height: 90px; background: var(--accent); color: white;
            border-radius: 50%; margin: 0 auto 30px; font-size: 48px;
            display: flex; align-items: center; justify-content: center;
        }
        .success-title { font-size: 32px; font-weight: 700; color: var(--secondary-color); margin-bottom: 16px; }
        .success-text { font-size: 18px; color: var(--text--black); margin-bottom: 24px; line-height: 1.7; }
        .email-status {
            padding: 16px; border-radius: 14px; margin: 24px 0; font-size: 15px; font-weight: 500;
        }
        .email-sent { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .email-failed { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .btn-login {
            display: inline-block; background: var(--accent); color: var(--secondary-color);
            padding: 14px 50px; border-radius: 50px; text-decoration: none; font-weight: 600;
            font-size: 17px; margin-top: 20px; transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(229, 161, 62, 0.3);
        }
        .btn-login:hover {
            background: var(--secondary-color); color: white; transform: translateY(-2px);
        }
        .success-right {
            flex: 1; background: linear-gradient(to top, #213638c0, #e5a23ea0),
            url('../../img/tour-spots/great-santa-cruz-island/1.jpg');
            background-size: cover; background-position: center;
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 28px; font-weight: 700; text-align: center;
            padding: 40px; text-shadow: 0 2px 10px rgba(0,0,0,0.5);
        }
        @media (max-width: 992px) {
            .success-wrapper { flex-direction: column; }
            .success-left { padding: 50px 30px; }
            .success-right { min-height: 280px; font-size: 22px; }
        }
        @media (max-width: 768px) {
            .success-left { padding: 40px 20px; }
            .success-right { display: none; }
            .success-title { font-size: 28px; }
        }
    </style>
</head>
<body>

<div class="success-wrapper">
    <div class="success-left">
        <div class="success-icon">Check</div>
        <h1 class="success-title">Registration Successful!</h1>
        <p class="success-text">
            Hello <strong><?= htmlspecialchars($username) ?></strong>,<br>
            Your tourist account has been created successfully.
        </p>

        <!-- SMART EMAIL STATUS -->
        <?php if ($emailSent): ?>
            <div class="email-status email-sent">
                Success: A confirmation email has been sent to your inbox.
            </div>
        <?php else: ?>
            <div class="email-status email-failed">
                <strong>Failed:</strong>
                <?= $emailError ?: 'Unknown error occurred.' ?>
            </div>
        <?php endif; ?>

        <a href="../login.php" class="btn-login">Log In Now</a>
    </div>

    <div class="success-right">
        Welcome to<br>Zamboanga Adventures!
    </div>
</div>

</body>
</html>
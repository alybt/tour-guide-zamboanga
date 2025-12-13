<?php
// --- FIX: Composer Autoloader MUST be the first thing executed to ensure PHPMailer classes are available. ---
require_once __DIR__ . "/../../assets/vendor/autoload.php"; // Load PHPMailer and other dependencies first

require_once __DIR__ . "/../../config/mail-trap-config.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP; // For SMTP::DEBUG_SERVER
use Dotenv\Dotenv; // Add the Dotenv class use statement

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Email Notification Sender</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f8f9fa; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }
        h1 { color: #3b82f6; border-bottom: 2px solid #3b82f6; padding-bottom: 10px; }
        .success { color: #10b981; font-weight: bold; padding: 10px; background-color: #d1fae5; border: 1px solid #10b981; border-radius: 4px; }
        .error { color: #ef4444; font-weight: bold; padding: 10px; background-color: #fee2e2; border: 1px solid #ef4444; border-radius: 4px; }
        p { margin-bottom: 15px; }
        code { background-color: #eee; padding: 2px 4px; border-radius: 3px; }
    </style>
</head>
<body>
<div class='container'>";

echo "<h1>Mailtrap Test Notification</h1>";

// --- 1. Load Configuration ---
try {
    // Correct Dotenv for version 4.x
    // Ensure the Dotenv class is properly autoloaded before usage
    $dotenv = Dotenv::createImmutable(
        dirname(__DIR__, 2),
        'mailtrap.env'
    );
    $dotenv->load();

    // MailtrapConfig class is expected to be available via 'mail-trap-config.php' or autoload
    $config = new MailtrapConfig();

    echo "<p>Configuration Loaded Successfully:</p>";
    echo "<ul>";
    echo "<li><strong>Host:</strong> <code>" . $config->getHost() . "</code></li>";
    echo "<li><strong>Port:</strong> <code>" . $config->getPort() . "</code></li>";
    echo "<li><strong>Key:</strong> <code>" . substr($config->getApiKey(), 0, 5) . "...</code> (Used as SMTP Password)</li>";
    echo "</ul>";

} catch (\Throwable $e) {
    echo "<div class='error'>❌ Configuration Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "</div></body></html>";
    exit;
}

// --- 2. PHPMailer Setup and Sending ---
$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->SMTPDebug = 0; // Set to 2 or SMTP::DEBUG_SERVER for detailed debugging output
    $mail->isSMTP();
    $mail->Host       = $config->getHost();
    $mail->SMTPAuth   = true;
    
    // IMPORTANT: For Mailtrap's SMTP, the API Key is typically used as the Password,
    // and 'api' or the API key itself is used as the Username. 
    // We'll use the API Key for both for robust compatibility with modern Mailtrap settings.
    $apiKey = $config->getApiKey();
    $mail->Username   = $apiKey; // Using API Key as Username (works for many services including Mailtrap)
    $mail->Password   = $apiKey; // Using API Key as Password

    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Use TLS (StartTLS)
    $mail->Port       = $config->getPort();

    // Recipients
    // NOTE: For Mailtrap, the "TO" address doesn't matter, as it intercepts ALL mail. 
    // Use a placeholder or your test email to see the capture in Mailtrap's UI.
    $mail->setFrom('notifications@myapp.dev', 'App Notification System');
    $mail->addAddress('testuser@example.com', 'Test Recipient'); // The captured email will show this recipient.
    $mail->addReplyTo('support@myapp.dev', 'Support Team');

    // Content
    $mail->isHTML(true); // Set email format to HTML
    $mail->Subject = 'New Account Notification (Sandbox Test ' . date('H:i:s') . ')';
    $mail->Body    = '
        <div style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
            <div style="padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
                <h2 style="color: #3b82f6;">Your Notification Test is Complete!</h2>
                <p>This is a test email sent using PHPMailer and intercepted by your Mailtrap sandbox configuration.</p>
                <p><strong>Next Step:</strong> Log into your Mailtrap dashboard and check the inbox associated with the API key you are using. You should see this message appear immediately!</p>
                <p style="margin-top: 20px;">Best,<br>The PHP Developer</p>
            </div>
        </div>';
    $mail->AltBody = 'Your Notification Test is Complete! This is a test email sent using PHPMailer and captured by your Mailtrap sandbox.';

    $mail->send();
    echo "<div class='success'>✅ Email Sent to Sandbox Successfully!</div>";
    echo "<p>The email has been captured by Mailtrap. Please check your Mailtrap inbox to view the result.</p>";

} catch (Exception $e) {
    echo "<div class='error'>❌ Message could not be sent. Mailer Error: " . htmlspecialchars($mail->ErrorInfo) . "</div>";
    echo "<p>Detailed PHP Exception: <code>" . htmlspecialchars($e->getMessage()) . "</code></p>";
}

echo "</div></body></html>";
?>
<?php
require_once __DIR__ . "/../../assets/vendor/autoload.php";
require_once "../../classes/mailer.php"; // Make sure path is correct


// 1. Create instance and set provider/credentials
$mailer = new Mailer('gmail', 'my-gmail-app-username', 'my-gmail-app-password');
$mailer->setFrom('my-gmail-address@gmail.com', 'App Support');

// 2. Set content and recipient
$mailer->addRecipient('client@example.com', 'Client Name');
$mailer->setContent('Test Email (Gmail)', '<h1>Hello from Gmail!</h1>');

// 3. Send
if ($mailer->send()) {
    echo "Gmail email sent successfully!";
} else {
    echo "Failed to send via Gmail. Error: " . $mailer->getError();
}
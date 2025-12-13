<?php
require_once "../../classes/Mailer.php";

$mailer = new Mailer();

$result = $mailer->send(
    'customer@gmail.com',
    'Juan Dela Cruz',
    'Test Email from Tourismo Zamboanga',
    '<h1>Hi! This is a test email.</h1><p>It works!</p>'
);

if ($result['success']) {
    echo "Email sent successfully!";
} else {
    echo "Failed: " . $result['message'];
}
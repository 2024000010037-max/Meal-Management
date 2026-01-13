<?php
session_start();
include "../config/database.php";
if (!isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true) {
    echo "Unauthorized access.";
    exit;
}
$newpass = $_POST['newpass'] ?? '';
$email = $_SESSION['otp_email'] ?? '';
if (strlen($newpass) < 6) {
    echo "Password must be at least 6 characters.";
    exit;
}
?>

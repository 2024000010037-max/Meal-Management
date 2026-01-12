<?php
session_start();
$user_otp = $_POST['otp'] ?? '';
$session_otp = $_SESSION['otp'] ?? '';
$otp_time = $_SESSION['otp_time'] ?? 0;
if (!$session_otp) {
    echo "Session expired. Please request a new OTP.";
    exit;
}


?>

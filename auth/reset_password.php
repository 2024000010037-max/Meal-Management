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
    $pdo = (new Database())->connect();
$hashed_password = password_hash($newpass, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
if ($stmt->execute([$hashed_password, $email])) {
    session_destroy(); // Clear session after success
    echo "success";
} else {
    echo "Database error. Could not update password.";
}
?>

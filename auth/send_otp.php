<?php
session_start();
include "../config/database.php";

// Include PHPMailer from the 'sms' folder as requested
require '../sms/Exception.php';
require '../sms/PHPMailer.php';
require '../sms/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$email = $_POST['email'] ?? '';

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "Invalid email format";
    exit;
}

$pdo = (new Database())->connect();

// Assuming 'email' column exists in users table
$stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user) {
    $otp = rand(100000, 999999);
    $_SESSION['otp'] = $otp;
    $_SESSION['otp_email'] = $email;
    $_SESSION['otp_time'] = time();

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'remarkhb.herlanit@gmail.com'; // REPLACE WITH YOUR GMAIL
        $mail->Password = 'mutq ddwp qkyu hzgo';    // REPLACE WITH YOUR APP PASSWORD
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('remarkhb.herlanit@gmail.com', 'Hostel Mess');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Password Reset OTP';
        $mail->Body    = "



?>

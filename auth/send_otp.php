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
  <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden;'>
                <div style='background-color: #e15f41; padding: 20px; text-align: center;'>
                    <h2 style='color: #ffffff; margin: 0;'>Hostel Mess Recovery</h2>
                </div>
                <div style='padding: 30px; background-color: #ffffff;'>
                    <p style='color: #555; font-size: 16px;'>Hello,</p>
                    <p style='color: #555; font-size: 16px; line-height: 1.6;'>We received a request to reset your password. Use the code below to verify your identity:</p>
                    <div style='text-align: center; margin: 30px 0;'>
                        <span style='display: inline-block; font-size: 32px; font-weight: bold; color: #e15f41; letter-spacing: 4px; background: #fff0eb; padding: 15px 30px; border-radius: 8px; border: 1px dashed #e15f41;'>$otp</span>
                    </div>
                    <p style='color: #777; font-size: 14px;'>This code will expire in 10 minutes.</p>
                    <hr style='border: none; border-top: 1px solid #eee; margin: 20px 0;'>
                    <p style='color: #999; font-size: 12px; text-align: center;'>If you didn't request this, you can safely ignore this email.</p>
                </div>
            </div>
        ";




?>

<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['manager', 'admin'])) {
    header("Location: ../index.php");
    exit;
}
include "../config/database.php";
require '../sms/Exception.php';
require '../sms/PHPMailer.php';
require '../sms/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$pdo = (new Database())->connect();
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$selected_month = $_GET['month'] ?? date('Y-m');

if (!$user_id) {
    die("Invalid User ID");
}

// --- 1. FETCH USER DETAILS ---
$stmt = $pdo->prepare("SELECT full_name, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || empty($user['email'])) {
    echo "<script>alert('User email not found!'); window.location.href='monthly_report.php?month=$selected_month';</script>";
    exit;
}
// Global Stats for Rate
$stmt = $pdo->prepare("SELECT SUM(breakfast + lunch + dinner) FROM meals WHERE DATE_FORMAT(meal_date, '%Y-%m') = ?");
$stmt->execute([$selected_month]);
$total_mess_meals = $stmt->fetchColumn() ?: 0;

$stmt = $pdo->prepare("SELECT SUM(amount) FROM bazar WHERE status = 'approved' AND DATE_FORMAT(bazar_date, '%Y-%m') = ?");
$stmt->execute([$selected_month]);
$total_mess_bazar = $stmt->fetchColumn() ?: 0;

$meal_rate = ($total_mess_meals > 0) ? ($total_mess_bazar / $total_mess_meals) : 0;
// User Stats
$stmt = $pdo->prepare("SELECT SUM(breakfast + lunch + dinner) FROM meals WHERE user_id = ? AND DATE_FORMAT(meal_date, '%Y-%m') = ?");
$stmt->execute([$user_id, $selected_month]);
$user_meals = $stmt->fetchColumn() ?: 0;

$stmt = $pdo->prepare("SELECT SUM(amount) FROM deposits WHERE user_id = ? AND status = 'approved' AND DATE_FORMAT(deposit_date, '%Y-%m') = ?");
$stmt->execute([$user_id, $selected_month]);
$user_deposit = $stmt->fetchColumn() ?: 0;

$user_cost = $user_meals * $meal_rate;
$balance = $user_deposit - $user_cost;
$due_amount = abs($balance);

// Only send if there is a due
if ($balance >= 0) {
    if (isset($_GET['ajax'])) {
        echo json_encode(['status' => 'error', 'message' => 'User has no due amount to pay.']);
        exit;
    }
    echo "<script>alert('User has no due amount to pay.'); window.location.href='monthly_report.php?month=$selected_month';</script>";
    exit;
}
// --- 3. GENERATE INVOICE ---
$month_name = date('F Y', strtotime($selected_month));
$invoice_date = date('d M, Y');
$invoice_html = "
<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #ddd; padding: 20px; background: #fff;'>
    <div style='text-align: center; border-bottom: 2px solid #e15f41; padding-bottom: 10px; margin-bottom: 20px;'>
        <h2 style='color: #e15f41; margin: 0;'>HOSTEL MESS INVOICE</h2>
        <p style='color: #777; margin: 5px 0;'>Month: <strong>$month_name</strong></p>
    </div>
    
    <table style='width: 100%; margin-bottom: 20px;'>
        <tr>
            <td>
                <strong>To:</strong><br>
                {$user['full_name']}<br>
                {$user['email']}
            </td>
            <td style='text-align: right;'>
                <strong>Date:</strong> $invoice_date<br>
                <strong>Status:</strong> <span style='color: red;'>Unpaid</span>
            </td>
        </tr>
    </table>

    <table style='width: 100%; border-collapse: collapse; margin-bottom: 20px;'>
        <tr style='background: #f8f9fa;'>
            <th style='border: 1px solid #ddd; padding: 10px; text-align: left;'>Description</th>
            <th style='border: 1px solid #ddd; padding: 10px; text-align: right;'>Value</th>
        </tr>
        <tr>
            <td style='border: 1px solid #ddd; padding: 10px;'>Total Meals Consumed</td>
            <td style='border: 1px solid #ddd; padding: 10px; text-align: right;'>" . number_format($user_meals, 1) . "</td>
        </tr>
        <tr>
            <td style='border: 1px solid #ddd; padding: 10px;'>Meal Rate (Current)</td>
            <td style='border: 1px solid #ddd; padding: 10px; text-align: right;'>৳ " . number_format($meal_rate, 2) . "</td>
        </tr>
        <tr>
            <td style='border: 1px solid #ddd; padding: 10px;'><strong>Total Expense</strong></td>
            <td style='border: 1px solid #ddd; padding: 10px; text-align: right;'><strong>৳ " . number_format($user_cost, 2) . "</strong></td>
        </tr>
        <tr>
            <td style='border: 1px solid #ddd; padding: 10px;'>Less: Total Deposit</td>
            <td style='border: 1px solid #ddd; padding: 10px; text-align: right;'>(-) ৳ " . number_format($user_deposit, 2) . "</td>
        </tr>
        <tr style='background: #ffebee;'>
            <td style='border: 1px solid #ddd; padding: 10px; color: #c62828; font-weight: bold;'>TOTAL DUE AMOUNT</td>
            <td style='border: 1px solid #ddd; padding: 10px; text-align: right; color: #c62828; font-weight: bold; font-size: 1.2em;'>৳ " . number_format($due_amount, 2) . "</td>
        </tr>
    </table>

    <div style='text-align: center; font-size: 12px; color: #777; margin-top: 30px;'>
        <p>Please clear your dues as soon as possible to avoid meal cancellation.</p>
        <p>Thank you, <br>Hostel Manager</p>
    </div>
</div>
";

// --- 4. SEND EMAIL ---
$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'demo@gmail.com'; // Using credentials from context
    $mail->Password   = 'xxxxxxxx';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

 // Recipients
    $mail->setFrom('remarkhb.herlanit@gmail.com', 'Hostel Mess Manager');
    $mail->addAddress($user['email'], $user['full_name']);

    // Content
    $mail->isHTML(true);
    $mail->Subject = "Due Payment Invoice - $month_name";
    $mail->Body    = "Dear {$user['full_name']},<br><br>You have a due balance for the month of <strong>$month_name</strong>.<br>Please find the invoice details below:<br><br>" . $invoice_html;
    $mail->AltBody = "Dear {$user['full_name']}, You have a due balance of " . number_format($due_amount, 2) . " for $month_name. Please pay immediately.";

// Attach HTML Invoice (User can print this as PDF)
    $mail->addStringAttachment($invoice_html, "Invoice_{$selected_month}.html", 'base64', 'text/html');

    $mail->send();
    
    if (isset($_GET['ajax'])) {
        echo json_encode(['status' => 'success', 'message' => "Invoice sent successfully to {$user['email']}!"]);
        exit;
    }
// Success Redirect
    echo "<script>
        alert('Invoice sent successfully to {$user['email']}!');
        window.location.href='monthly_report.php?month=$selected_month';
    </script>";
} catch (Exception $e) {
    if (isset($_GET['ajax'])) {
        echo json_encode(['status' => 'error', 'message' => "Message could not be sent. Mailer Error: {$mail->ErrorInfo}"]);
        exit;
    }
    echo "<script>
        alert('Message could not be sent. Mailer Error: {$mail->ErrorInfo}');
        window.location.href='monthly_report.php?month=$selected_month';
    </script>";
}


    
?>

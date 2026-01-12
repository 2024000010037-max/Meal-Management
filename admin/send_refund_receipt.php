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

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$month = $_GET['month'] ?? date('Y-m');

if (!$id) {
    header("Location: deposit.php?month=$month");
    exit;
}
/ 1. Fetch Refund Details (it's already approved and amount is negative)
$stmt = $pdo->prepare("SELECT d.*, u.full_name, u.email FROM deposits d JOIN users u ON d.user_id = u.id WHERE d.id = ? AND d.amount < 0");
$stmt->execute([$id]);
$deposit = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$deposit) {
    header("Location: deposit.php?month=$month&error=not_found");
    exit;
}

// 2. Calculate Stats for Invoice (Total Deposit & Current Balance)
$user_id = $deposit['user_id'];
$current_month = date('Y-m', strtotime($deposit['deposit_date']));

// Global Stats for Rate
$stmt = $pdo->prepare("SELECT SUM(breakfast + lunch + dinner) FROM meals WHERE DATE_FORMAT(meal_date, '%Y-%m') = ?");
$stmt->execute([$current_month]);
$total_mess_meals = $stmt->fetchColumn() ?: 0;

$stmt = $pdo->prepare("SELECT SUM(amount) FROM bazar WHERE status = 'approved' AND DATE_FORMAT(bazar_date, '%Y-%m') = ?");
$stmt->execute([$current_month]);
$total_mess_bazar = $stmt->fetchColumn() ?: 0;

$meal_rate = ($total_mess_meals > 0) ? ($total_mess_bazar / $total_mess_meals) : 0;
// User Stats (This Month)
$stmt = $pdo->prepare("SELECT SUM(breakfast + lunch + dinner) FROM meals WHERE user_id = ? AND DATE_FORMAT(meal_date, '%Y-%m') = ?");
$stmt->execute([$user_id, $current_month]);
$user_meals = $stmt->fetchColumn() ?: 0;

// User Total Deposit (This Month, after this refund)
$stmt = $pdo->prepare("SELECT SUM(amount) FROM deposits WHERE user_id = ? AND status = 'approved' AND DATE_FORMAT(deposit_date, '%Y-%m') = ?");
$stmt->execute([$user_id, $current_month]);
$user_total_deposit = $stmt->fetchColumn() ?: 0;

$user_cost = $user_meals * $meal_rate;
$balance = $user_total_deposit - $user_cost;

// 3. Generate Invoice HTML
$invoice_no = "REF-" . date('Ymd') . "-" . $deposit['id'];
$date_time = date('d M, Y h:i A');

$invoice_html = "
<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #eee; padding: 20px; background: #fff;'>
    <div style='text-align: center; border-bottom: 2px solid #dc3545; padding-bottom: 10px; margin-bottom: 20px;'>
        <h2 style='color: #dc3545; margin: 0;'>REFUND RECEIPT</h2>
        <p style='color: #777; margin: 5px 0;'>Hostel Mess Management</p>
    </div>
     <table style='width: 100%; margin-bottom: 20px;'>
        <tr>
            <td>
                <strong>Refund To:</strong><br>
                {$deposit['full_name']}<br>
                {$deposit['email']}
            </td>
            <td style='text-align: right;'>
                <strong>Receipt No:</strong> $invoice_no<br>
                <strong>Date:</strong> $date_time<br>
                <strong>Status:</strong> <span style='color: #dc3545; font-weight: bold;'>Refunded</span>
            </td>
        </tr>
    </table>
   <table style='width: 100%; border-collapse: collapse; margin-bottom: 20px;'>
        <tr style='background: #f8f9fa;'>
            <th style='border: 1px solid #ddd; padding: 12px; text-align: left;'>Description</th>
            <th style='border: 1px solid #ddd; padding: 12px; text-align: right;'>Details</th>
        </tr>
        <tr>
            <td style='border: 1px solid #ddd; padding: 10px;'>Payment Type</td>
            <td style='border: 1px solid #ddd; padding: 10px; text-align: right;'>Cash Refund</td>
        </tr>
        <tr style='background: #fbe9e7;'>
            <td style='border: 1px solid #ddd; padding: 10px; color: #c62828; font-weight: bold;'>AMOUNT REFUNDED</td>
            <td style='border: 1px solid #ddd; padding: 10px; text-align: right; color: #c62828; font-weight: bold; font-size: 1.2em;'>৳ " . number_format(abs($deposit['amount']), 2) . "</td>
        </tr>
    </table>

    <div style='background: #f9f9f9; padding: 15px; border-radius: 5px; margin-top: 20px;'>
        <h4 style='margin: 0 0 10px 0; font-size: 14px; color: #555;'>Account Summary (For {$current_month})</h4>
        <table style='width: 100%; font-size: 13px;'>
            <tr>
                <td>Total Deposit (after refund):</td>
                <td style='text-align: right;'>৳ " . number_format($user_total_deposit, 2) . "</td>
            </tr>
            <tr>
                <td>Current Balance:</td>
                <td style='text-align: right; font-weight: bold; color: " . ($balance >= 0 ? 'green' : 'red') . ";'>
                    " . ($balance >= 0 ? '+' : '') . number_format($balance, 2) . "
                </td>
            </tr>
        </table>
    </div>
</div>
";

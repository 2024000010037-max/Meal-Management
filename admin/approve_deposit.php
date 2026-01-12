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
$manager_id = $_SESSION['user_id'];

if (!$id) {
    header("Location: deposit.php?month=$month");
    exit;
}
// 1. Fetch Deposit Details (Verify it exists and is pending)
$stmt = $pdo->prepare("SELECT d.*, u.full_name, u.email FROM deposits d JOIN users u ON d.user_id = u.id WHERE d.id = ? AND d.status = 'pending'");
$stmt->execute([$id]);
$deposit = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$deposit) {
    header("Location: deposit.php?month=$month&error=not_found");
    exit;
}
// 2. Approve the Deposit
$stmt = $pdo->prepare("UPDATE deposits SET status = 'approved', manager_id = ? WHERE id = ?");
$stmt->execute([$manager_id, $id]);

// 3. Calculate Stats for Invoice (Total Deposit & Current Balance)
$user_id = $deposit['user_id'];
$current_month = date('Y-m'); 

// Global Stats for Rate
$stmt = $pdo->prepare("SELECT SUM(breakfast + lunch + dinner) FROM meals WHERE DATE_FORMAT(meal_date, '%Y-%m') = ?");
$stmt->execute([$current_month]);
$total_mess_meals = $stmt->fetchColumn() ?: 0;

$stmt = $pdo->prepare("SELECT SUM(amount) FROM bazar WHERE status = 'approved' AND DATE_FORMAT(bazar_date, '%Y-%m') = ?");
$stmt->execute([$current_month]);
$total_mess_bazar = $stmt->fetchColumn() ?: 0;

$me

// User Stats (This Month)
$stmt = $pdo->prepare("SELECT SUM(breakfast + lunch + dinner) FROM meals WHERE user_id = ? AND DATE_FORMAT(meal_date, '%Y-%m') = ?");
$stmt->execute([$user_id, $current_month]);
$user_meals = $stmt->fetchColumn() ?: 0;

// User Total Deposit (This Month)
$stmt = $pdo->prepare("SELECT SUM(amount) FROM deposits WHERE user_id = ? AND status = 'approved' AND DATE_FORMAT(deposit_date, '%Y-%m') = ?");
$stmt->execute([$user_id, $current_month]);
$user_total_deposit = $stmt->fetchColumn() ?: 0;

$user_cost = $user_meals * $meal_rate;
$balance = $user_total_deposit - $user_cost;

// 4. Generate Invoice HTML
$invoice_no = "INV-" . date('Ymd') . "-" . $deposit['id'];
$date_time = date('d M, Y h:i A');

$invoice_html = "
<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #eee; padding: 20px; background: #fff;'>
    <div style='text-align: center; border-bottom: 2px solid #28a745; padding-bottom: 10px; margin-bottom: 20px;'>
        <h2 style='color: #28a745; margin: 0;'>DEPOSIT RECEIPT</h2>
        <p style='color: #777; margin: 5px 0;'>Hostel Mess Management</p>
    </div>
    
    <table style='width: 100%; margin-bottom: 20px;'>
        <tr>
            <td>
                <strong>Received From:</strong><br>
                {$deposit['full_name']}<br>
                {$deposit['email']}
            </td>

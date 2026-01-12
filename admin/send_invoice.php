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
// --- 2. CALCULATE STATS (Same logic as monthly_report.php) ---

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

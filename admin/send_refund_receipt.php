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

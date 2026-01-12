<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['manager', 'admin'])) {
    header("Location: ../index.php");
    exit;
}

include "../config/database.php";
$pdo = (new Database())->connect();

$selected_month = $_GET['month'] ?? date('Y-m');

// 1. Total Active Members
$total_members = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 1")->fetchColumn();

// 2. Total Meal (Month)
$stmt = $pdo->prepare("SELECT SUM(breakfast + lunch + dinner) FROM meals WHERE DATE_FORMAT(meal_date, '%Y-%m') = ?");
$stmt->execute([$selected_month]);
$total_meal = $stmt->fetchColumn() ?: 0;

// 3. Total Bazar (Month)
$stmt = $pdo->prepare("SELECT SUM(amount) FROM bazar WHERE status = 'approved' AND DATE_FORMAT(bazar_date, '%Y-%m') = ?");
$stmt->execute([$selected_month]);
$total_bazar = $stmt->fetchColumn() ?: 0;

// 4. Total Deposit (Month)
$stmt = $pdo->prepare("SELECT SUM(amount) FROM deposits WHERE status = 'approved' AND DATE_FORMAT(deposit_date, '%Y-%m') = ?");
$stmt->execute([$selected_month]);
$total_deposit = $stmt->fetchColumn() ?: 0;

// 5. Meal Rate
$meal_rate = ($total_meal > 0) ? ($total_bazar / $total_meal) : 0;

// 6. Calculate Net Balance
$net_balance = $total_deposit - $total_bazar;

$pageTitle = "Dashboard";
ob_start();
?>

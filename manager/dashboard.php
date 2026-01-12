<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['manager', 'admin'])) {
    header("Location: ../index.php");
    exit;
}

include "../config/database.php";
$pdo = (new Database())->connect();

$selected_month = $_GET['month'] ?? date('Y-m');
$view_as = $_GET['view_as'] ?? 'manager';

// 1. Total Active Members (Non-admin users with meals > 0 this month)
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT m.user_id) 
    FROM meals m 
    JOIN users u ON m.user_id = u.id 
    WHERE u.role != 'admin' 
    AND u.status = 1 
    AND DATE_FORMAT(m.meal_date, '%Y-%m') = ? 
    AND (m.breakfast + m.lunch + m.dinner) > 0
");
$stmt->execute([$selected_month]);
$total_members = $stmt->fetchColumn() ?: 0;

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

// 7. Personal Stats (if view_as is user)
if ($view_as === 'user') {
    $user_id = $_SESSION['user_id'];

    // Personal Meals
    $stmt = $pdo->prepare("SELECT SUM(breakfast + lunch + dinner) FROM meals WHERE user_id = ? AND DATE_FORMAT(meal_date, '%Y-%m') = ?");
    $stmt->execute([$user_id, $selected_month]);
    $my_total_meal = $stmt->fetchColumn() ?: 0;

     // Personal Deposit
    $stmt = $pdo->prepare("SELECT SUM(amount) FROM deposits WHERE user_id = ? AND status = 'approved' AND DATE_FORMAT(deposit_date, '%Y-%m') = ?");
    $stmt->execute([$user_id, $selected_month]);
    $my_total_deposit = $stmt->fetchColumn() ?: 0;

      // Personal Cost & Balance
    $my_total_cost = $my_total_meal * $meal_rate;
    $my_net_balance = $my_total_deposit - $my_total_cost;
}










?>

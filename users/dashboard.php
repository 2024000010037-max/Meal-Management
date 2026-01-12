<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../index.php");
    exit;
}
include "../config/database.php";
$pdo = (new Database())->connect();
$user_id = $_SESSION['user_id'];
$selected_month = $_GET['month'] ?? date('Y-m');
// --- MESS STATS ---

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

// 2. Total Meal (Mess - Month)
$stmt = $pdo->prepare("SELECT SUM(breakfast + lunch + dinner) FROM meals WHERE DATE_FORMAT(meal_date, '%Y-%m') = ?");
$stmt->execute([$selected_month]);
$total_mess_meal = $stmt->fetchColumn() ?: 0;
// 3. Total Bazar (Mess - Month)
$stmt = $pdo->prepare("SELECT SUM(amount) FROM bazar WHERE status = 'approved' AND DATE_FORMAT(bazar_date, '%Y-%m') = ?");
$stmt->execute([$selected_month]);
$total_mess_bazar = $stmt->fetchColumn() ?: 0;
// 4. Meal Rate
$meal_rate = ($total_mess_meal > 0) ? ($total_mess_bazar / $total_mess_meal) : 0;


// --- MY STATS ---

// 5. My Meals
$stmt = $pdo->prepare("SELECT SUM(breakfast + lunch + dinner) FROM meals WHERE user_id = ? AND DATE_FORMAT(meal_date, '%Y-%m') = ?");
$stmt->execute([$user_id, $selected_month]);
$my_meal = $stmt->fetchColumn() ?: 0;
// 6. My Deposit
$stmt = $pdo->prepare("SELECT SUM(amount) FROM deposits WHERE user_id = ? AND status = 'approved' AND DATE_FORMAT(deposit_date, '%Y-%m') = ?");
$stmt->execute([$user_id, $selected_month]);
$my_deposit = $stmt->fetchColumn() ?: 0;






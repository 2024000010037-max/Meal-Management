<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['manager', 'admin'])) {
    header("Location: ../index.php");
    exit;
}
    include "../config/database.php";
$pdo = (new Database())->connect();

$selected_month = $_GET['month'] ?? date('Y-m');

// --- 1. CALCULATE GLOBAL MESS STATS ---
// Total Mess Meals
$stmt = $pdo->prepare("SELECT SUM(breakfast + lunch + dinner) FROM meals WHERE DATE_FORMAT(meal_date, '%Y-%m') = ?");
$stmt->execute([$selected_month]);
$total_mess_meals = $stmt->fetchColumn() ?: 0;

// Total Mess Bazar
$stmt = $pdo->prepare("SELECT SUM(amount) FROM bazar WHERE status = 'approved' AND DATE_FORMAT(bazar_date, '%Y-%m') = ?");
$stmt->execute([$selected_month]);
$total_mess_bazar = $stmt->fetchColumn() ?: 0;

// Total Mess Deposit
$stmt = $pdo->prepare("SELECT SUM(amount) FROM deposits WHERE status = 'approved' AND DATE_FORMAT(deposit_date, '%Y-%m') = ?");
$stmt->execute([$selected_month]);
$total_mess_deposit = $stmt->fetchColumn() ?: 0;

// Meal Rate
$meal_rate = ($total_mess_meals > 0) ? ($total_mess_bazar / $total_mess_meals) : 0;

// --- 2. FETCH USER DATA ---
// Active Users (Non-Admin)
$users = $pdo->query("SELECT id, full_name, role FROM users WHERE status = 1 AND role != 'admin' ORDER BY full_name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Meals per User
$stmt = $pdo->prepare("SELECT user_id, SUM(breakfast + lunch + dinner) as meals FROM meals WHERE DATE_FORMAT(meal_date, '%Y-%m') = ? GROUP BY user_id");
$stmt->execute([$selected_month]);
$user_meals_map = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Deposits per User
$stmt = $pdo->prepare("SELECT user_id, SUM(amount) as deposit FROM deposits WHERE status = 'approved' AND DATE_FORMAT(deposit_date, '%Y-%m') = ? GROUP BY user_id");
$stmt->execute([$selected_month]);
$user_deposits_map = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// --- 3. BUILD REPORT DATA ---
$report_data = [];
foreach ($users as $u) {
    $uid = $u['id'];
    $meals = $user_meals_map[$uid] ?? 0;



?>

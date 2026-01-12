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



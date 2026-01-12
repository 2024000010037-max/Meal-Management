<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../index.php");
    exit;
}
include "../config/database.php";
$pdo = (new Database())->connect();

// Set Timezone
date_default_timezone_set('Asia/Dhaka');
$userId = $_SESSION['user_id'];
$selected_date = $_GET['date'] ?? date('Y-m-d');
$msg = "";
// --- TIME CONSTRAINTS LOGIC (MOVED UP) ---
$now = new DateTime();
$target = new DateTime($selected_date);
$today_str = $now->format('Y-m-d');
$target_str = $target->format('Y-m-d');
$hour = (int)$now->format('H');




<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    header("Location: ../index.php");
    exit;
}

include "../config/database.php";
$pdo = (new Database())->connect();

date_default_timezone_set('Asia/Dhaka');

$msg = "";
$selected_date = $_GET['date'] ?? date('Y-m-d');

if (isset($_GET['toggle_auto']) && isset($_GET['uid'])) {
    $uid = intval($_GET['uid']);
       $pdo->prepare("UPDATE users SET is_auto_meal = NOT is_auto_meal WHERE id = ?")->execute([$uid]);
    header("Location: meal_entry.php?date=" . $selected_date);
    exit;
}



?>

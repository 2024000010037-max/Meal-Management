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
    // --- HANDLE FORM SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['meals'])) {
    try {
        $pdo->beginTransaction();
        
        // Prepare statements for performance
        $stmtCheck = $pdo->prepare("SELECT id FROM meals WHERE user_id = ? AND meal_date = ?");
        $stmtInsert = $pdo->prepare("INSERT INTO meals (user_id, meal_date, breakfast, lunch, dinner) VALUES (?, ?, ?, ?, ?)");
        $stmtUpdate = $pdo->prepare("UPDATE meals SET breakfast = ?, lunch = ?, dinner = ? WHERE id = ?");

        foreach ($_POST['meals'] as $uid => $m) {
            // Sanitize inputs
            $b = isset($m['breakfast']) ? floatval($m['breakfast']) : 0;
            $l = isset($m['lunch']) ? floatval($m['lunch']) : 0;
            $d = isset($m['dinner']) ? floatval($m['dinner']) : 0;



?>

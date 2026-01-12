<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}
include "../config/database.php";
$pdo = (new Database())->connect();

// Set Timezone (Adjust if needed, e.g., 'Asia/Dhaka')
date_default_timezone_set('Asia/Dhaka');

$msg = "";
$selected_date = $_GET['date'] ?? date('Y-m-d');
// --- HANDLE AUTO TOGGLE (Manager Side) ---
if (isset($_GET['toggle_auto']) && isset($_GET['uid'])) {
    $uid = intval($_GET['uid']);
    // Toggle the is_auto_meal status
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

            // Check if entry exists
            $stmtCheck->execute([$uid, $selected_date]);
            $existing_id = $stmtCheck->fetchColumn();

            if ($existing_id) {
                // Update existing
                $stmtUpdate->execute([$b, $l, $d, $existing_id]);
            } else {
                // Insert new if any value is greater than 0
                if ($b > 0 || $l > 0 || $d > 0) {
                    $stmtInsert->execute([$uid, $selected_date, $b, $l, $d]);
                }
            }
        }

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
 $pdo->commit();
        $msg = "<div class='alert alert-success alert-dismissible fade show'>
                    <i class='bi bi-check-circle-fill me-2'></i> Meals updated successfully for <strong>" . date('d M, Y', strtotime($selected_date)) . "</strong>
                    <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                </div>";
    } catch (Exception $e) {
        $pdo->rollBack();
        $msg = "<div class='alert alert-danger'>Error saving meals: " . $e->getMessage() . "</div>";
    }
}
// --- FETCH DATA ---
// 1. Get all active members (Managers + Users)
$users = $pdo->query("SELECT id, full_name, role, is_auto_meal FROM users WHERE role IN ('manager', 'user') AND status = 1 ORDER BY role ASC, full_name ASC")->fetchAll(PDO::FETCH_ASSOC);

// 2. Get existing meals for the selected date
$stmt = $pdo->prepare("SELECT user_id, breakfast, lunch, dinner FROM meals WHERE meal_date = ?");
$stmt->execute([$selected_date]);
$current_meals = $stmt->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);
/ --- TIME CONSTRAINTS LOGIC ---
$now = new DateTime();
$target = new DateTime($selected_date);
$today_str = $now->format('Y-m-d');
$target_str = $target->format('Y-m-d');
$hour = (int)$now->format('H');

$lock_b = false; // Breakfast Lock
$lock_l = false; // Lunch Lock
$lock_d = false; // Dinner Lock
$global_lock_msg = "";

// Manager & Admin have no time restrictions ("All possible")

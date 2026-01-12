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

$lock_b = false; $lock_l = false; $lock_d = false;
$global_lock_msg = "";

if ($target_str < $today_str) {
    // Past dates: Locked
    $lock_b = $lock_l = $lock_d = true;
    $global_lock_msg = "Past dates cannot be updated.";
} elseif ($target_str == $today_str) {
    // Today: Time based locking
    if ($hour >= 8)  $lock_b = true;
    if ($hour >= 11) $lock_l = true;
    if ($hour >= 15) $lock_d = true;
} else {
    // Future
    $tomorrow = new DateTime('tomorrow');
    if ($target_str == $tomorrow->format('Y-m-d')) {
        // Tomorrow: Opens after 6 PM today
        if ($hour < 18) {
                   $lock_b = $lock_l = $lock_d = true;
            $global_lock_msg = "Tomorrow's meal entry opens at 6:00 PM today.";
        }
    } else {
     // Days after tomorrow: Locked (Prevent far future booking if not desired, or allow)
        // Based on "next tah 6PM por", usually implies strict next day control.
        // We will lock dates beyond tomorrow to keep it simple.
        $lock_b = $lock_l = $lock_d = true;
        $global_lock_msg = "Advance booking is only allowed for the next day.";
    }
}         
// --- HANDLE AUTO MEAL TOGGLE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_auto'])) {
    $new_status = $_POST['auto_status'] == 1 ? 0 : 1;
    $stmt = $pdo->prepare("UPDATE users SET is_auto_meal = ? WHERE id = ?");
    $stmt->execute([$new_status, $userId]);
    // Refresh to show correct state
    header("Location: meals.php?date=" . $selected_date);
    exit;
}

// --- HANDLE FORM SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['toggle_auto'])) {
    $b = floatval($_POST['breakfast']);
    $l = floatval($_POST['lunch']);
    $d = floatval($_POST['dinner']);
    $date = $_POST['meal_date'];
  // Check if entry exists
    $stmtCheck = $pdo->prepare("SELECT id FROM meals WHERE user_id = ? AND meal_date = ?");
    $stmtCheck->execute([$userId, $date]);
    $existing_id = $stmtCheck->fetchColumn();





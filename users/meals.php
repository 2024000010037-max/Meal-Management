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
 if ($existing_id) {
        $stmt = $pdo->prepare("UPDATE meals SET breakfast=?, lunch=?, dinner=? WHERE id=?");
        $stmt->execute([$b, $l, $d, $existing_id]);
  } else {
        if ($b > 0 || $l > 0 || $d > 0) {
            $stmt = $pdo->prepare("INSERT INTO meals (user_id, meal_date, breakfast, lunch, dinner) VALUES (?,?,?,?,?)");
            $stmt->execute([$userId, $date, $b, $l, $d]);
        }
    }
  $msg = "<div class='alert alert-success alert-dismissible fade show'>Meal updated successfully! <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
}
// --- FETCH DATA ---
// 1. Get today's/selected date meal
$stmt = $pdo->prepare("SELECT * FROM meals WHERE user_id = ? AND meal_date = ?");
$stmt->execute([$userId, $selected_date]);
$current_meal = $stmt->fetch(PDO::FETCH_ASSOC);
// Get User Auto Status
$userStmt = $pdo->prepare("SELECT is_auto_meal FROM users WHERE id = ?");
$userStmt->execute([$userId]);
$is_auto_meal = $userStmt->fetchColumn();
// Apply Auto Logic (Display 1s if Auto ON and no record exists for today/future)
if (!$current_meal && $is_auto_meal && $selected_date >= date('Y-m-d')) {
    $current_meal = ['breakfast' => 1, 'lunch' => 1, 'dinner' => 1];
   // Auto-save if Today and Breakfast is locked (Ensures record exists if Auto is ON)
    if ($target_str == $today_str && $lock_b) {
        $stmt = $pdo->prepare("INSERT INTO meals (user_id, meal_date, breakfast, lunch, dinner) VALUES (?,?,?,?,?)");
        $stmt->execute([$userId, $selected_date, 1, 1, 1]);
    }
}

// 2. Get History
$meals = $pdo->query("SELECT * FROM meals WHERE user_id = $userId ORDER BY meal_date DESC");

// --- TIME CONSTRAINTS LOGIC MOVED UP ---

$pageTitle = "My Meals";
ob_start();
?>
    <style>
        .card { border:none; border-radius:16px; box-shadow:0 10px 25px rgba(0,0,0,.08); }
        .btn-qty { width: 35px; height: 35px; border-radius: 50%; padding: 0; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.2rem; }
         .meal-input { text-align: center; font-weight: bold; font-size: 1.2rem; border: none; background: transparent; width: 60px; }
        .locked-section { opacity: 0.6; pointer-events: none; }
    </style>
 




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


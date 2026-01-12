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
     <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1">Meal Management</h3>
        </div>
  <form method="GET" class="d-flex gap-2">
            <input type="date" name="date" class="form-control" value="<?= $selected_date ?>" onchange="this.form.submit()">
        </form>
    </div>
 <?= $msg ?>

    <!-- ENTRY CARD -->
    <div class="card p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="mb-0 text-primary">Update Meal <small class="text-muted fs-6 ms-1">(<?= date('d M', strtotime($selected_date)) ?>)</small></h5   
        <!-- Auto Meal Toggle (Moved Here) -->
            <form method="POST" class="d-flex align-items-center">
                <input type="hidden" name="toggle_auto" value="1">
                <input type="hidden" name="auto_status" value="<?= $is_auto_meal ?>">
                <div class="form-check form-switch m-0">
<input class="form-check-input" type="checkbox" role="switch" id="autoSwitch" onchange="this.form.submit()" <?= $is_auto_meal ? 'checked' : '' ?>>
                    <label class="form-check-label small fw-bold <?= $is_auto_meal ? 'text-success' : 'text-muted' ?>" for="autoSwitch">
   Auto: <?= $is_auto_meal ? 'ON' : 'OFF' ?>
                    </label>
                </div>
            </form>
        </div>
 <?php if($global_lock_msg): ?>
            <div class="alert alert-warning py-2"><i class="bi bi-lock-fill me-2"></i> <?= $global_lock_msg ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="meal_date" value="<?= $selected_date ?>">
            <div class="row g-4 text-center">
                <!-- Breakfast -->
<div class="col-md-4 <?= $lock_b ? 'locked-section' : '' ?>">
                    <label class="form-label text-muted fw-bold small">BREAKFAST</label>
                    <div class="d-flex align-items-center justify-content-center gap-2 bg-light rounded-pill p-2">
                        <button type="button" class="btn btn-outline-danger btn-qty" onclick="updateQty('breakfast', -0.5)">-</button>
                        <input type="number" step="0.5" min="0" id="breakfast" name="breakfast" value="<?= $current_meal['breakfast'] ?? 0 ?>" class="meal-input" readonly>
                        <button type="button" class="btn btn-outline-success btn-qty" onclick="updateQty('breakfast', 0.5)">+</button>
                    </div>
                    <?php if($lock_b): ?><small class="text-danger d-block mt-1"><i class="bi bi-lock"></i> Locked</small><?php endif; ?>
                
                </div>

                <!-- Lunch -->
                <div class="col-md-4 <?= $lock_l ? 'locked-section' : '' ?>">
                    <label class="form-label text-muted fw-bold small">LUNCH</label>
                    <div class="d-flex align-items-center justify-content-center gap-2 bg-light rounded-pill p-2">
                        <button type="button" class="btn btn-outline-danger btn-qty" onclick="updateQty('lunch', -0.5)">-</button>
                        <input type="number" step="0.5" min="0" id="lunch" name="lunch" value="<?= $current_meal['lunch'] ?? 0 ?>" class="meal-input" readonly>
                        <button type="button" class="btn btn-outline-success btn-qty" onclick="updateQty('lunch', 0.5)">+</button>
                    </div>
                    <?php if($lock_l): ?><small class="text-danger d-block mt-1"><i class="bi bi-lock"></i> Locked</small><?php endif; ?>
                </div>





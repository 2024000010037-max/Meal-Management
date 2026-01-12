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

// --- TIME CONSTRAINTS LOGIC ---
$now = new DateTime();
$target = new DateTime($selected_date);
$today_str = $now->format('Y-m-d');
$target_str = $target->format('Y-m-d');
$hour = (int)$now->format('H');

// Check if Breakfast time has passed (for Auto-Save trigger)
$time_lock_b = false;
if ($target_str == $today_str && $hour >= 8) {
    $time_lock_b = true;
}

$lock_b = false; // Breakfast Lock
$lock_l = false; // Lunch Lock
$lock_d = false; // Dinner Lock
$global_lock_msg = "";

$pageTitle = "Meal Entry";
ob_start();
?>
    <style>
        .card { border:none; border-radius:16px; box-shadow:0 10px 25px rgba(0,0,0,.08); }
        .table-input-group { min-width: 120px; }
        .form-control-sm { text-align: center; font-weight: bold; }
        .btn-qty { width: 30px; padding: 0; display: flex; align-items: center; justify-content: center; font-weight: bold; }
        .locked-input { background-color: #f8f9fa; border-color: #e9ecef; color: #6c757d; }
        .user-role-badge { font-size: 0.7rem; padding: 2px 6px; border-radius: 4px; background: #e2e8f0; color: #475569; margin-left: 5px; }
    </style>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0">Daily Meal Entry</h3>
            <p class="text-muted small mb-0">Manage meals for all members</p>
        </div>
        <form method="GET" class="d-flex gap-2">
            <a href="meal_history.php" class="btn btn-outline-primary"><i class="bi bi-clock-history me-1"></i> History</a>
            <input type="date" name="date" class="form-control" value="<?= $selected_date ?>" onchange="this.form.submit()">
        </form>
    </div>

    <?= $msg ?>

    <?php if($global_lock_msg): ?>
        <div class="alert alert-warning"><i class="bi bi-lock-fill me-2"></i> <?= $global_lock_msg ?></div>
    <?php endif; ?>

    <div class="card p-4">
        <form method="POST" class="row g-3">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 25%">Member Name</th>
                            <th class="text-center">Breakfast <small class="text-muted d-block" style="font-size:10px">(< 8 AM)</small></th>
                            <th class="text-center">Lunch <small class="text-muted d-block" style="font-size:10px">(< 11 AM)</small></th>
                            <th class="text-center">Dinner <small class="text-muted d-block" style="font-size:10px">(< 3 PM)</small></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($users as $u): 
                            $uid = $u['id'];
                            // Auto Meal Logic: If no entry exists and auto is ON, default to 1 (only for today/future)
                            $default = ($u['is_auto_meal'] && $selected_date >= date('Y-m-d')) ? 1 : 0;
                            
                            // Auto-save if Today, Auto is ON, No Entry, and Breakfast time passed
                            if (!isset($current_meals[$uid]) && $u['is_auto_meal'] && $time_lock_b) {
                                $pdo->prepare("INSERT INTO meals (user_id, meal_date, breakfast, lunch, dinner) VALUES (?, ?, 1, 1, 1)")->execute([$uid, $selected_date]);
                                $current_meals[$uid] = ['breakfast' => 1, 'lunch' => 1, 'dinner' => 1];
                            }

                            $b = $current_meals[$uid]['breakfast'] ?? $default;
                            $l = $current_meals[$uid]['lunch'] ?? $default;
                            $d = $current_meals[$uid]['dinner'] ?? $default;
                        ?>
                        <tr>
                            <td>
                                <div class="fw-bold"><?= htmlspecialchars($u['full_name']) ?></div>
                                <?php if($u['role'] === 'manager'): ?>
                                    <span class="user-role-badge">Manager</span>
                                <?php endif; ?>
                                <!-- Auto Meal Toggle -->
                                <div class="mt-1">
                                    <a href="?date=<?= $selected_date ?>&toggle_auto=1&uid=<?= $uid ?>" class="text-decoration-none small">
                                        <i class="bi <?= $u['is_auto_meal'] ? 'bi-toggle-on text-success' : 'bi-toggle-off text-muted' ?> fs-5 align-middle"></i>
                                        <span class="text-muted align-middle" style="font-size: 11px;">Auto Meal</span>
                                    </a>
                                </div>
                            </td>

                             <!-- Breakfast -->
                            <td><?php renderMealInput($uid, 'breakfast', $b, $lock_b); ?></td>
                             <!-- Lunch -->
                            <td><?php renderMealInput($uid, 'lunch', $l, $lock_l); ?></td>
                             <!-- Dinner -->
                            <td><?php renderMealInput($uid, 'dinner', $d, $lock_d); ?></td>
                            </tr>
                         <?php endforeach; ?>
                             </tbody>
                                </table>
                                </div>
                                <div class="col-12 mt-3">
                <?php if(!$lock_b || !$lock_l || !$lock_d): ?>
                    <button class="btn btn-primary btn-lg w-100 shadow-sm"><i class="bi bi-save me-2"></i> Save All Changes</button>
                <?php else: ?>
                    <button class="btn btn-secondary btn-lg w-100" disabled>Modifications Locked</button>
                <?php endif; ?>
            </div>
        </form>
    </div>
 <script>
        // JavaScript for +/- buttons
        function updateQty(btn, change) {
            const input = btn.parentElement.querySelector('input');
            if (input.readOnly) return;
            
            let val = parseFloat(input.value) || 0;
            val += change;
            if (val < 0) val = 0;
            input.value = val;
        }
    </script>
    <?php

// Helper function to render inputs
function renderMealInput($uid, $type, $value, $isLocked) {
    $name = "meals[$uid][$type]";
    $readonly = $isLocked ? 'readonly class="form-control form-control-sm locked-input"' : 'class="form-control form-control-sm"';
    $btnClass = $isLocked ? 'btn-secondary disabled' : 'btn-outline-primary';
    $onclickMinus = $isLocked ? '' : 'onclick="updateQty(this, -0.5)"';
    $onclickPlus = $isLocked ? '' : 'onclick="updateQty(this, 0.5)"';
    
    echo '
    <div class="input-group input-group-sm table-input-group mx-auto" style="max-width: 140px;">
        <button type="button" class="btn '.$btnClass.' btn-qty" '.$onclickMinus.'>-</button>
        <input type="number" step="0.5" min="0" name="'.$name.'" value="'.$value.'" '.$readonly.'>
        <button type="button" class="btn '.$btnClass.' btn-qty" '.$onclickPlus.'>+</button>
    </div>
    ';
}













?>

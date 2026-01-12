<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['manager', 'admin'])) {
    header("Location: ../index.php");
    exit;
}

include "../config/database.php";
$pdo = (new Database())->connect();

$selected_month = $_GET['month'] ?? date('Y-m');
$view_as = $_GET['view_as'] ?? 'manager';

// 1. Total Active Members (Non-admin users with meals > 0 this month)
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT m.user_id) 
    FROM meals m 
    JOIN users u ON m.user_id = u.id 
    WHERE u.role != 'admin' 
    AND u.status = 1 
    AND DATE_FORMAT(m.meal_date, '%Y-%m') = ? 
    AND (m.breakfast + m.lunch + m.dinner) > 0
");
$stmt->execute([$selected_month]);
$total_members = $stmt->fetchColumn() ?: 0;

// 2. Total Meal (Month)
$stmt = $pdo->prepare("SELECT SUM(breakfast + lunch + dinner) FROM meals WHERE DATE_FORMAT(meal_date, '%Y-%m') = ?");
$stmt->execute([$selected_month]);
$total_meal = $stmt->fetchColumn() ?: 0;

// 3. Total Bazar (Month)
$stmt = $pdo->prepare("SELECT SUM(amount) FROM bazar WHERE status = 'approved' AND DATE_FORMAT(bazar_date, '%Y-%m') = ?");
$stmt->execute([$selected_month]);
$total_bazar = $stmt->fetchColumn() ?: 0;

// 4. Total Deposit (Month)
$stmt = $pdo->prepare("SELECT SUM(amount) FROM deposits WHERE status = 'approved' AND DATE_FORMAT(deposit_date, '%Y-%m') = ?");
$stmt->execute([$selected_month]);
$total_deposit = $stmt->fetchColumn() ?: 0;

// 5. Meal Rate
$meal_rate = ($total_meal > 0) ? ($total_bazar / $total_meal) : 0;

// 6. Calculate Net Balance
$net_balance = $total_deposit - $total_bazar;

// 7. Personal Stats (if view_as is user)
if ($view_as === 'user') {
    $user_id = $_SESSION['user_id'];

    // Personal Meals
    $stmt = $pdo->prepare("SELECT SUM(breakfast + lunch + dinner) FROM meals WHERE user_id = ? AND DATE_FORMAT(meal_date, '%Y-%m') = ?");
    $stmt->execute([$user_id, $selected_month]);
    $my_total_meal = $stmt->fetchColumn() ?: 0;

     // Personal Deposit
    $stmt = $pdo->prepare("SELECT SUM(amount) FROM deposits WHERE user_id = ? AND status = 'approved' AND DATE_FORMAT(deposit_date, '%Y-%m') = ?");
    $stmt->execute([$user_id, $selected_month]);
    $my_total_deposit = $stmt->fetchColumn() ?: 0;

      // Personal Cost & Balance
    $my_total_cost = $my_total_meal * $meal_rate;
    $my_net_balance = $my_total_deposit - $my_total_cost;
}




    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-center mb-4 gap-3">
    <div class="text-center text-lg-start">
        <h3 class="fw-bold text-dark mb-0"></h3>
        <p class="text-muted small mb-0"></p>
    </div>
    
    <form method="GET" class="bg-white p-1 rounded-4 shadow-sm d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-2 border">
        <!-- View Mode Selector -->
        <div class="d-flex align-items-center px-3 py-2 bg-light rounded-3">
            <i class="bi bi-person-gear text-primary me-2 fs-5"></i>
            <select name="view_as" class="form-select form-select-sm border-0 shadow-none bg-transparent fw-bold text-secondary p-0" style="width: auto; cursor: pointer;" onchange="this.form.submit()">
                <option value="manager" <?= $view_as == 'manager' ? 'selected' : '' ?>>Manager View</option>
                <option value="user" <?= $view_as == 'user' ? 'selected' : '' ?>>My Personal View</option>
            </select>
        </div>
<!-- Month Picker -->
        <div class="d-flex align-items-center px-3 py-2 bg-light rounded-3">
            <i class="bi bi-calendar-month text-primary me-2 fs-5"></i>
            <input type="month" name="month" class="form-control form-control-sm border-0 shadow-none bg-transparent fw-bold text-dark p-0" style="width: auto; cursor: pointer;" value="<?= $selected_month ?>" onchange="this.form.submit()">
        </div>
    </form>
</div>

<div class="row g-4">
    <?php if ($view_as === 'manager'): ?>
    <!-- Total Members -->
    <div class="col-sm-6 col-md-4 col-lg-3">
        <div class="card dashboard-card bg-white p-4 h-100 shadow-sm">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="card-label text-primary">Active Members</div>
                    <h2 class="card-value text-dark mt-2"><?= $total_members ?></h2>
                    <div class="card-sub text-muted mt-1">Total Users</div>
                </div>
                <div class="card-icon bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-people-fill"></i>
                </div>
            </div>
        </div>
    </div>
 <!-- Meal Rate -->
    <div class="col-sm-6 col-md-4 col-lg-3">
        <div class="card dashboard-card bg-white p-4 h-100 shadow-sm">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="card-label text-info">Meal Rate</div>
                    <h2 class="card-value text-dark mt-2">৳ <?= number_format($meal_rate, 2) ?></h2>
                    <div class="card-sub text-muted mt-1">Per Meal</div>
                </div>
                <div class="card-icon bg-info bg-opacity-10 text-info">
                    <i class="bi bi-calculator"></i>
                </div>
            </div>
        </div>
    </div>











?>

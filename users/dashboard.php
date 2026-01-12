<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../index.php");
    exit;
}
include "../config/database.php";
$pdo = (new Database())->connect();
$user_id = $_SESSION['user_id'];
$selected_month = $_GET['month'] ?? date('Y-m');
// --- MESS STATS ---

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

// 2. Total Meal (Mess - Month)
$stmt = $pdo->prepare("SELECT SUM(breakfast + lunch + dinner) FROM meals WHERE DATE_FORMAT(meal_date, '%Y-%m') = ?");
$stmt->execute([$selected_month]);
$total_mess_meal = $stmt->fetchColumn() ?: 0;
// 3. Total Bazar (Mess - Month)
$stmt = $pdo->prepare("SELECT SUM(amount) FROM bazar WHERE status = 'approved' AND DATE_FORMAT(bazar_date, '%Y-%m') = ?");
$stmt->execute([$selected_month]);
$total_mess_bazar = $stmt->fetchColumn() ?: 0;
// 4. Meal Rate
$meal_rate = ($total_mess_meal > 0) ? ($total_mess_bazar / $total_mess_meal) : 0;


// --- MY STATS ---

// 5. My Meals
$stmt = $pdo->prepare("SELECT SUM(breakfast + lunch + dinner) FROM meals WHERE user_id = ? AND DATE_FORMAT(meal_date, '%Y-%m') = ?");
$stmt->execute([$user_id, $selected_month]);
$my_meal = $stmt->fetchColumn() ?: 0;
// 6. My Deposit
$stmt = $pdo->prepare("SELECT SUM(amount) FROM deposits WHERE user_id = ? AND status = 'approved' AND DATE_FORMAT(deposit_date, '%Y-%m') = ?");
$stmt->execute([$user_id, $selected_month]);
$my_deposit = $stmt->fetchColumn() ?: 0;
// 7. My Expense (Estimated)
$my_expense = $my_meal * $meal_rate;

// 8. My Balance (Due / Advance)
$my_balance = $my_deposit - $my_expense;

$pageTitle = "User Dashboard";
ob_start();
?>
<!-- Styles (Same as Manager) -->
    <style>
 .dashboard-card {
        border: none;
        border-radius: 15px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        overflow: hidden;
        position: relative;
    }
.dashboard-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.1);
    }
    .card-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-bottom: 15px;
    }
   .bg-gradient-success { background: linear-gradient(45deg, #1cc88a, #13855c); color: white; }
    
    .card-label { font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.9; }
    .card-value { font-size: 1.8rem; font-weight: 700; margin-bottom: 0; }
    .card-sub { font-size: 0.8rem; opacity: 0.8; }
    </style>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold text-dark mb-0">My Dashboard</h3>
        <p class="text-muted small">Overview for <?= date('F Y', strtotime($selected_month)) ?></p>
    </div>
<form method="GET" class="d-flex align-items-center gap-2 bg-white p-2 rounded shadow-sm">
        <label class="small fw-bold text-muted mb-0"><i class="bi bi-calendar3 me-1"></i> Month:</label>
        <input type="month" name="month" class="form-control form-control-sm border-0 bg-light fw-bold" value="<?= $selected_month ?>" onchange="this.form.submit()">
    </form>
</div>
   <div class="row g-4">
    <!-- MESS OVERVIEW ROW -->
    
    <!-- Active Members -->
    <div class="col-md-3 col-6">
        <div class="card dashboard-card bg-white p-3 h-100 shadow-sm">
            <div class="card-label text-primary">Active Members</div>
            <h3 class="card-value text-dark mt-2"><?= $total_members ?></h3>
            <div class="card-sub text-muted">Total Mess</div>
        </div>
    </div>
    <!-- Meal Rate -->
    <div class="col-md-3 col-6">
        <div class="card dashboard-card bg-white p-3 h-100 shadow-sm">
            <div class="card-label text-info">Meal Rate</div>
            <h3 class="card-value text-dark mt-2">৳ <?= number_format($meal_rate, 2) ?></h3>
            <div class="card-sub text-muted">Current Rate</div>
        </div>
    </div>




 






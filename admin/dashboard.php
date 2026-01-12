<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['manager', 'admin'])) {
    header("Location: ../index.php");
    exit;
}

include "../config/database.php";
$pdo = (new Database())->connect();

$selected_month = $_GET['month'] ?? date('Y-m');

// 1. Total Active Members
$total_members = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 1")->fetchColumn();

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

$pageTitle = "Dashboard";
ob_start();
?>
<!-- Styles for Dashboard -->
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
        <h3 class="fw-bold text-dark mb-0">Dashboard</h3>
        <p class="text-muted small"></p>
    </div>
    <form method="GET" class="d-flex align-items-center gap-2 bg-white p-2 rounded shadow-sm">
        <label class="small fw-bold text-muted mb-0"><i class="bi bi-calendar3 me-1"></i> Month:</label>
        <input type="month" name="month" class="form-control form-control-sm border-0 bg-light fw-bold" value="<?= $selected_month ?>" onchange="this.form.submit()">
    </form>
</div>

    <div class="row g-4">
    <!-- Total Members -->
    <div class="col-md-4 col-lg-3">
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
    <div class="col-md-4 col-lg-3">
        <div class="card dashboard-card bg-white p-4 h-100 shadow-sm">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="card-label text-info">Meal Rate</div>
                    <h2 class="card-value text-dark mt-2">৳ <?= number_format($meal_rate, 2) ?></h2>
                    <div class="card-sub text-muted mt-1">Bazar / Total Meals</div>
                </div>
                <div class="card-icon bg-info bg-opacity-10 text-info">
                    <i class="bi bi-calculator"></i>
                </div>
            </div>
        </div>
    </div>
   <!-- Total Meals -->
    <div class="col-md-4 col-lg-3">
        <div class="card dashboard-card bg-white p-4 h-100 shadow-sm">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="card-label text-warning">Total Meals</div>
                    <h2 class="card-value text-dark mt-2"><?= number_format($total_meal, 1) ?></h2>
                    <div class="card-sub text-muted mt-1">This Month</div>
                </div>
                <div class="card-icon bg-warning bg-opacity-10 text-warning">
                    <i class="bi bi-egg-fried"></i>
                </div>
            </div>
        </div>
    </div>
  <!-- Total Bazar -->
    <div class="col-md-4 col-lg-3">
        <div class="card dashboard-card bg-white p-4 h-100 shadow-sm">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="card-label text-danger">Total Bazar</div>
                    <h2 class="card-value text-dark mt-2">৳ <?= number_format($total_bazar, 0) ?></h2>
                    <div class="card-sub text-muted mt-1">Expenses</div>
                </div>
                <div class="card-icon bg-danger bg-opacity-10 text-danger">
                    <i class="bi bi-cart3"></i>
                </div>
            </div>
        </div>
    </div>
    <!-- Total Deposit -->
    <div class="col-md-6 col-lg-6">
        <div class="card dashboard-card bg-gradient-success p-4 h-100 text-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="card-label text-white-50">Total Deposit</div>
                    <h2 class="card-value mt-2">৳ <?= number_format($total_deposit, 0) ?></h2>
                    <div class="card-sub text-white-50 mt-1">Collected Amount</div>
                </div>
                <div class="card-icon bg-white bg-opacity-25 text-white">
                    <i class="bi bi-wallet2"></i>
                </div>
            </div>
        </div>
    </div>

    <?php if ($net_balance >= 0): ?>
    <!-- Total Advance (Surplus) -->
    <div class="col-md-6 col-lg-6">
        <div class="card dashboard-card bg-white p-4 h-100 shadow-sm border-start border-5 border-success">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="card-label text-success">Total Advance</div>
                    <h2 class="card-value text-dark mt-2">+৳ <?= number_format($net_balance, 0) ?></h2>
                    <div class="card-sub text-muted mt-1">Current Surplus</div>
                </div>
                <div class="card-icon bg-success bg-opacity-10 text-success">
                    <i class="bi bi-graph-up-arrow"></i>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <!-- Total Due (Deficit) -->
    <div class="col-md-6 col-lg-6">
        <div class="card dashboard-card bg-white p-4 h-100 shadow-sm border-start border-5 border-danger">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="card-label text-danger">Total Due</div>
                    <h2 class="card-value text-dark mt-2">-৳ <?= number_format(abs($net_balance), 0) ?></h2>
                    <div class="card-sub text-muted mt-1">Current Deficit</div>
                </div>
                <div class="card-icon bg-danger bg-opacity-10 text-danger">
                    <i class="bi bi-graph-down-arrow"></i>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Quick Actions -->
<div class="row mt-5">
    <div class="col-12">
        <h5 class="fw-bold text-secondary mb-3">Quick Actions</h5>
    </div>
    <div class="col-6 col-md-3 mb-3">
        <a href="meal_entry.php" class="btn btn-outline-primary w-100 p-3 h-100 d-flex flex-column align-items-center justify-content-center gap-2 rounded-4">
            <i class="bi bi-egg-fried fs-3"></i>
            <span class="fw-bold">Meal Entry</span>
        </a>
    </div>
    <div class="col-6 col-md-3 mb-3">
        <a href="bazar_entry.php" class="btn btn-outline-danger w-100 p-3 h-100 d-flex flex-column align-items-center justify-content-center gap-2 rounded-4">
            <i class="bi bi-cart-plus fs-3"></i>
            <span class="fw-bold">Bazar Entry</span>
        </a>
    </div>
    <div class="col-6 col-md-3 mb-3">
        <a href="deposit.php" class="btn btn-outline-success w-100 p-3 h-100 d-flex flex-column align-items-center justify-content-center gap-2 rounded-4">
            <i class="bi bi-wallet2 fs-3"></i>
            <span class="fw-bold">Deposits</span>
        </a>
    </div>

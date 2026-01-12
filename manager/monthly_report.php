<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['manager', 'admin'])) {
    header("Location: ../index.php");
    exit;
}
    include "../config/database.php";
$pdo = (new Database())->connect();

$selected_month = $_GET['month'] ?? date('Y-m');

// --- 1. CALCULATE GLOBAL MESS STATS ---
// Total Mess Meals
$stmt = $pdo->prepare("SELECT SUM(breakfast + lunch + dinner) FROM meals WHERE DATE_FORMAT(meal_date, '%Y-%m') = ?");
$stmt->execute([$selected_month]);
$total_mess_meals = $stmt->fetchColumn() ?: 0;

// Total Mess Bazar
$stmt = $pdo->prepare("SELECT SUM(amount) FROM bazar WHERE status = 'approved' AND DATE_FORMAT(bazar_date, '%Y-%m') = ?");
$stmt->execute([$selected_month]);
$total_mess_bazar = $stmt->fetchColumn() ?: 0;

// Total Mess Deposit
$stmt = $pdo->prepare("SELECT SUM(amount) FROM deposits WHERE status = 'approved' AND DATE_FORMAT(deposit_date, '%Y-%m') = ?");
$stmt->execute([$selected_month]);
$total_mess_deposit = $stmt->fetchColumn() ?: 0;

// Meal Rate
$meal_rate = ($total_mess_meals > 0) ? ($total_mess_bazar / $total_mess_meals) : 0;

// --- 2. FETCH USER DATA ---
// Active Users (Non-Admin)
$users = $pdo->query("SELECT id, full_name, role FROM users WHERE status = 1 AND role != 'admin' ORDER BY full_name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Meals per User
$stmt = $pdo->prepare("SELECT user_id, SUM(breakfast + lunch + dinner) as meals FROM meals WHERE DATE_FORMAT(meal_date, '%Y-%m') = ? GROUP BY user_id");
$stmt->execute([$selected_month]);
$user_meals_map = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Deposits per User
$stmt = $pdo->prepare("SELECT user_id, SUM(amount) as deposit FROM deposits WHERE status = 'approved' AND DATE_FORMAT(deposit_date, '%Y-%m') = ? GROUP BY user_id");
$stmt->execute([$selected_month]);
$user_deposits_map = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// --- 3. BUILD REPORT DATA ---
$report_data = [];
foreach ($users as $u) {
    $uid = $u['id'];
    $meals = $user_meals_map[$uid] ?? 0;
// Filter: Only show users who have meal count > 0
    if ($meals > 0) {
        $deposit = $user_deposits_map[$uid] ?? 0;
        $cost = $meals * $meal_rate;
        $balance = $deposit - $cost; 
        
        $report_data[] = [
            'user_id' => $uid,
            'name' => $u['full_name'],
            'role' => ucfirst($u['role']),
            'meals' => $meals,
            'deposit' => $deposit,
            'cost' => $cost,
            'balance' => $balance
        ];
    }
}
// --- 4. HANDLE EXCEL EXPORT ---
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=monthly_report_{$selected_month}.xls");
    echo '<table border="1">';
    echo '<tr><th colspan="6" style="text-align:center; font-size:16px; background:#f0f0f0;">Monthly Mess Report - ' . date('F Y', strtotime($selected_month)) . '</th></tr>';
    echo '<tr><th>Member Name</th><th>Role</th><th>Total Meals</th><th>Total Deposit</th><th>Cost (' . number_format($meal_rate, 2) . '/meal)</th><th>Balance (Due/Adv)</th></tr>';
    foreach ($report_data as $row) {
        $bal_text = ($row['balance'] >= 0 ? "+" : "") . number_format($row['balance'], 2);
        $color = $row['balance'] < 0 ? 'color:red;' : 'color:green;';
        echo "<tr>
            <td>{$row['name']}</td>
            <td>{$row['role']}</td>
            <td>{$row['meals']}</td>
            <td>" . number_format($row['deposit'], 2) . "</td>
            <td>" . number_format($row['cost'], 2) . "</td>
            <td style='{$color}'>{$bal_text}</td>
        </tr>";
    }
    echo '</table>';
    exit;
}
$pageTitle = "Monthly Report";
ob_start();
?>
<style>
    .card { border: none; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,.05); }
    .summary-box { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); text-align: center; }
    .summary-label { font-size: 0.8rem; font-weight: 600; color: #6c757d; text-transform: uppercase; }
    .summary-value { font-size: 1.5rem; font-weight: 700; margin-top: 5px; color: #212529; }
    @media print {
        .no-print { display: none !important; }
        .card { box-shadow: none !important; border: 1px solid #ddd !important; }
        .sidebar, .top-header { display: none !important; }
        .main-content { margin-left: 0 !important; }
    }
 /* Loading Overlay */
    #loadingOverlay {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(255, 255, 255, 0.8);
        z-index: 9999;
        display: none;
        justify-content: center;
        align-items: center;
        flex-direction: column;
    }
.spinner-custom {
        width: 3rem; height: 3rem;
        border: 5px solid #f3f3f3;
        border-top: 5px solid #dc3545; /* Red for Pay Taka theme */
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
</style>

<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <div>
        <h3 class="fw-bold mb-0 text-primary"><i class="bi bi-file-earmark-bar-graph"></i> Monthly Report</h3>
        <p class="text-muted small mb-0">Overview for <?= date('F Y', strtotime($selected_month)) ?></p>
    </div>
    <form method="GET" class="d-flex align-items-center gap-2">
        <label class="small fw-bold text-muted">Month:</label>
        <input type="month" name="month" class="form-control form-control-sm fw-bold" value="<?= $selected_month ?>" onchange="this.form.submit()">
    </form>
</div>
<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="summary-box border-bottom border-4 border-warning">
            <div class="summary-label">Total Mess Meals</div>
            <div class="summary-value"><?= number_format($total_mess_meals, 1) ?></div>
        </div>
    </div>
<div class="col-md-3">
        <div class="summary-box border-bottom border-4 border-success">
            <div class="summary-label">Total Mess Deposit</div>
            <div class="summary-value">৳ <?= number_format($total_mess_deposit, 0) ?></div>
        </div>
    </div>
<div class="col-md-3">
        <div class="summary-box border-bottom border-4 border-danger">
            <div class="summary-label">Total Mess Bazar</div>
            <div class="summary-value">৳ <?= number_format($total_mess_bazar, 0) ?></div>
        </div>
    </div>
<div class="col-md-3">
        <div class="summary-box border-bottom border-4 border-info">
            <div class="summary-label">Meal Rate</div>
            <div class="summary-value">৳ <?= number_format($meal_rate, 2) ?></div>
        </div>
    </div>
</div>
<!-- Report Table -->
<div class="card p-4">
    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <input type="text" id="searchInput" class="form-control w-50" placeholder="Search member name...">
        <div>
            <button onclick="window.print()" class="btn btn-outline-danger me-2"><i class="bi bi-printer"></i> Print</button>
            <a href="?month=<?= $selected_month ?>&export=excel" class="btn btn-success"><i class="bi bi-file-earmark-excel"></i> Excel</a>
        </div>
    </div>
<div class="table-responsive">
        <table class="table table-hover align-middle table-bordered">
            <thead class="table-light text-center">
                <tr>
                    <th>Member Name</th>
                    <th>Role</th>
                    <th>Total Meals</th>
                    <th>Total Deposit</th>
                    <th>Total Expense <br><small class="text-muted">(Meals × Rate)</small></th>
                    <th>Balance <br><small class="text-muted">(Due / Advance)</small></th>
                    <th>Action</th>
                </tr>
            </thead>

<tbody id="reportTableBody">
                <?php if(empty($report_data)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No active members with meals found for this month.</td></tr>
                <?php else: ?>
                    <?php foreach($report_data as $row): ?>
 <tr>
                        <td class="fw-bold"><?= htmlspecialchars($row['name']) ?></td>
                        <td class="text-center"><span class="badge bg-secondary bg-opacity-10 text-secondary"><?= $row['role'] ?></span></td>
                        <td class="text-center fw-bold"><?= number_format($row['meals'], 1) ?></td>
                        <td class="text-end text-success">+<?= number_format($row['deposit'], 2) ?></td>
                        <td class="text-end text-danger">-<?= number_format($row['cost'], 2) ?></td>
                        <td class="text-end fw-bold <?= $row['balance'] < 0 ? 'text-danger' : 'text-success' ?>">
                            <?= ($row['balance'] >= 0 ? "+" : "") . number_format($row['balance'], 2) ?>
                        </td>
 <td class="text-center">
                            <?php if($row['balance'] < 0): ?>
                                <button type="button" class="btn btn-danger btn-sm fw-bold" style="font-size: 0.75rem;" onclick="sendInvoice(<?= $row['user_id'] ?>, '<?= $selected_month ?>', '<?= htmlspecialchars($row['name']) ?>')">
                                    <i class="bi bi-envelope-paper-fill"></i> Pay Taka
                                </button>
                            <?php else: ?>
<a href="deposit.php?user_id=<?= $row['user_id'] ?>&return_amount=<?= $row['balance'] ?>&month=<?= $selected_month ?>" class="btn btn-outline-danger btn-sm fw-bold" style="font-size: 0.75rem;">
                                    <i class="bi bi-arrow-return-left"></i> Return Cash
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<!-- Loading Overlay -->
<div id="loadingOverlay">
    <div class="spinner-custom mb-3"></div>
    <h5 class="fw-bold text-danger">Sending Invoice...</h5>
    <p class="text-muted">Please wait, do not close this window.</p>
</div>
<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content text-center p-4 border-0 shadow-lg" style="border-radius: 20px;">
      <div class="modal-body">
        <div class="mb-3 text-success" style="font-size: 4rem;">
            <i class="bi bi-check-circle-fill"></i>
        </div>
        <h4 class="fw-bold text-dark mb-2">Successfully Sent!</h4>
        <p class="text-muted">The invoice has been mailed to the user.</p>
        <button type="button" class="btn btn-success px-4 rounded-pill fw-bold mt-3" data-bs-dismiss="modal">Awesome</button>
      </div>
    </div>
  </div>
</div>

<script>
    document.getElementById('searchInput').addEventListener('keyup', function() {
        const filter = this.value.toLowerCase();
        const rows = document.querySelectorAll('#reportTableBody tr');
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });

    

?>

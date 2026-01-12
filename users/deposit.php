<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
  header("Location: ../index.php");
    exit;
}
include "../config/database.php";
$pdo = (new Database())->connect();
$msg = "";
$selected_month = $_GET['month'] ?? date('Y-m');
$user_id = $_SESSION['user_id'];
// --- HANDLE ACTIONS (Delete Pending) ---
if (isset($_GET['action']) && isset($_GET['id'])) {
 $action = $_GET['action'];
$did = intval($_GET['id']);
    if ($action === 'delete') {
        // Users can only delete their own PENDING requests
        $stmt = $pdo->prepare("DELETE FROM deposits WHERE id = ? AND user_id = ? AND status = 'pending'");
        if ($stmt->execute([$did, $user_id])) {
   $msg = "<div class='alert alert-warning alert-dismissible fade show'>Request withdrawn successfully. <button            
type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        }
    }
}
// --- HANDLE FORM SUBMISSION (New Request) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_deposit'])) {
    $date = $_POST['deposit_date'];
  $amount = $_POST['amount'];
    $method = $_POST['payment_method'];
    $tnx_id = $_POST['transaction_id'];
    $remarks = $_POST['remarks'];
 // Insert with status = pending
    $stmt = $pdo->prepare("INSERT INTO deposits (user_id, amount, payment_method, transaction_id, deposit_date, remarks, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
 if ($stmt->execute([$user_id, $amount, $method, $tnx_id, $date, $remarks])) {
 $msg = "<div class='alert alert-success alert-dismissible fade show'>Deposit request submitted! Waiting for approval. <button 
type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    } else {
        $msg = "<div class='alert alert-danger'>Error submitting request.</div>";
    }
}
// --- FETCH DATA ---
// 1. My Pending Requests
$stmt = $pdo->prepare("SELECT * FROM deposits WHERE user_id = ? AND status = 'pending' ORDER BY deposit_date DESC");
$stmt->execute([$user_id]);
$my_pending = $stmt->fetchAll(PDO::FETCH_ASSOC);
// 2. My History (Approved/Rejected) for Selected Month
$stmt = $pdo->prepare("
SELECT d.*, m.full_name as manager_name
    FROM deposits d
  LEFT JOIN users m ON d.manager_id = m.id
    WHERE d.user_id = ? AND d.status != 'pending' AND DATE_FORMAT(d.deposit_date, '%Y-%m') = ? 
    ORDER BY d.deposit_date DESC
");
$stmt->execute([$user_id, $selected_month]);
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);
$pageTitle = "My Deposits";
ob_start();
?>
 <style>
        .card { border: none; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,.05); }
.nav-pills .nav-link.active { background-color: #198754; } /* Green theme for money */
        .nav-pills .nav-link { color: #198754; }
 .method-icon { width: 24px; text-align: center; display: inline-block; margin-right: 5px; }
        .badge-bkash { background-color: #e2136e; color: white; }
        .badge-nagad { background-color: #f7941d; color: white; }
        .badge-bank { background-color: #0056b3; color: white; }
        .badge-cash { background-color: #198754; color: white;
  </style>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold mb-0 text-success"><i class="bi bi-wallet2"></i> My Deposits</h3>
 <form method="GET" class="d-flex align-items-center gap-2">
            <label class="small fw-bold text-muted">Month:</label>
       <input type="month" name="month" class="form-control form-control-sm" value="<?= $selected_month ?>" onchange="this.form.submit()">
        </form>
    </div>

    <?= $msg ?>
 <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
        <li class="nav-item">
            <button class="nav-link active fw-bold" id="pills-add-tab" data-bs-toggle="pill" data-bs-target="#pills-add" type="button">
 New Request
            </button>
        </li>
        <li class="nav-item"><button class="nav-link fw-bold" id="pills-history-tab" data-bs-toggle="pill" data-bs-target="#pills-history" type="button">History</button></li>
    </ul>
 <div class="tab-content" id="pills-tabContent">
        
        <!-- ADD NEW REQUEST -->
        <div class="tab-pane fade show active" id="pills-add">
            <div class="row g-4"
   <!-- Form -->
                <div class="col-md-5">
                    <div class="card p-4 h-100">
                        <h5 class="fw-bold mb-3 text-success">Submit Deposit Request</h5>
                        <form method="POST" action="deposit.php?month=<?= $selected_month ?>">
                            <input type="hidden" name="save_deposit" value="1">
                            <div class="row g-3"> 
div class="col-12">
                                    <label class="form-label small fw-bold">Date</label>
                                    <input type="date" name="deposit_date" class="form-control" value="<?= date('Y-m-d') ?>" readonly required>
 </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold">Payment Method</label>
                                    <select name="payment_method" class="form-select" required>
 <?php $methods = ['cash', 'bkash', 'nagad', 'bank', 'other']; ?>
                                        <?php foreach($methods as $m): ?>
                                            <option value="<?= $m ?>"><?= ucfirst($m) ?></option>
                                        <?php endforeach; ?>
                                    </select>
  </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold">Amount (৳)</label>
                                    <input type="number" step="0.01" name="amount" class="form-control" placeholder="0.00" required>
                                </div>
div class="col-12">
                                    <label class="form-label small fw-bold">Transaction ID (Optional)</label>
                                    <input type="text" name="transaction_id" class="form-control" placeholder="e.g. TrxID123456">
                                </div>
<div class="col-12">
                                    <label class="form-label small fw-bold">Remarks</label>
                                    <textarea name="remarks" class="form-control" rows="2" placeholder="Any notes..."></textarea>
                                </div>
<div class="col-12 mt-3">
                                    <button class="btn btn-success w-100 fw-bold">Submit Request</button>
                                </div>
             </div>
                        </form>
                    </div>
                </div>

!-- My Pending Requests -->
                <div class="col-md-7">
  <div class="card p-4 h-100">
                        <h5 class="fw-bold mb-3">My Pending Requests</h5>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead class="table-light">
                                    <tr
 <th>Date</th>
                                        <th>Details</th>
                                        <th>Amount</th>
                                        <th class="text-end">Action</th>
                                    </tr>
 </thead>
                                <tbody>
                                    <?php if(empty($my_pending)): ?>
                                        <tr><td colspan="4" class="text-center text-muted py-4">No pending requests.</td></tr>
                                    <?php else: ?>
<?php foreach($my_pending as $req): ?>
                                        <tr>
                                            <td><?= date('d M', strtotime($req['deposit_date'])) ?></td>
                                            <td>
  <span class="badge badge-<?= strtolower($req['payment_method']) ?> text-uppercase"><?= $req['payment_method'] ?></span>
                                                <?php if($req['transaction_id']): ?><br><small class="text-muted"><?= htmlspecialchars($req['transaction_id']) ?></small><?php endif; ?>
                                            </td>
   <td class="fw-bold text-success">+<?= number_format($req['amount'], 2) ?></td>
                                            <td class="text-end">
  <a href="?action=delete&id=<?= $req['id'] ?>&month=<?= $selected_month ?>" class="btn btn-outline-danger btn-sm" title="Withdraw" onclick="return confirm('Withdraw this request?')"><i class="bi bi-trash"></i></a>
    </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <!-- HISTORY -->
        <div class="tab-pane fade" id="pills-history">
            <div class="card p-4">
<div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
          <th>Date</th>
                                <th>Details</th>
                                <th>Approved By</th>
                                <th>Status</th>
                                <th class="text-end">Amount</th>
                            </tr>
         </thead>
                        <tbody>
                            <?php if(empty($history)): ?>
                                <tr><td colspan="5" class="text-center text-muted py-4">No records found for this month.</td></tr>
                            <?php else: ?                           
 <?php foreach($history as $h): ?>
                                <tr>
                                    <td><?= date('d M', strtotime($h['deposit_date'])) ?></td>
                                    <td>
   <span class="badge badge-<?= strtolower($h['payment_method']) ?> text-uppercase"><?= $h['payment_method'] ?></span>
                                        <?php if($h['transaction_id']): ?><br><small class="text-muted"><?= htmlspecialchars($h['transaction_id']) ?></small><?php endif; ?>
                                    </td>
 <td><small class="text-muted"><?= htmlspecialchars($h['manager_name'] ?? 'N/A') ?></small></td>
                                    <td>
                                        <?php if($h['status'] === 'approved'): ?>
   <span class="badge bg-success">Approved</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Rejected</span>
                                        <?php endif; ?>
                                    </td>
 <td class="fw-bold text-end <?= $h['amount'] < 0 ? 'text-danger' : 'text-success' ?>">
                                        <?= $h['amount'] > 0 ? '+' : '' ?><?= number_format($h['amount'], 2) ?>
                                    </td>

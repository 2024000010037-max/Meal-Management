<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../index.php");
    exit;
}
include "../config/database.php";
$pdo = (new Database())->connect();

$userId = $_SESSION['user_id'];
$selected_month = $_GET['month'] ?? date('Y-m');
$msg = "";
// --- HANDLE DELETE REJECTED ---
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $del_id = intval($_GET['id']);
    $stmt = $pdo->prepare("DELETE FROM bazar WHERE id = ? AND user_id = ? AND status = 'rejected'");
    if ($stmt->execute([$del_id, $userId])) {
         header("Location: bazar.php?month=" . $selected_month);
         exit;
    }
}
// --- HANDLE FORM SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_bazar'])) {
    $date = $_POST['bazar_date'];
    $amount = $_POST['amount'];
    $details = $_POST['details'];
    $remarks = $_POST['remarks'];
    $shopper_ids = isset($_POST['shopper_ids']) ? implode(',', $_POST['shopper_ids']) : '';
if ($amount > 0 && !empty($details)) {
        $stmt = $pdo->prepare("INSERT INTO bazar (user_id, shopper_ids, bazar_date, amount, details, remarks, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
 if ($stmt->execute([$userId, $shopper_ids, $date, $amount, $details, $remarks])) {
            $msg = "<div class='alert alert-success alert-dismissible fade show'>Request submitted successfully! Waiting for approval. <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        } else {
 $msg = "<div class='alert alert-danger'>Failed to submit request.</div>";
        }
    } else {
        $msg = "<div class='alert alert-warning'>Please fill in all required fields.</div>";
    }
}
// --- FETCH DATA ---
// 1. Get Active Users (Moved up for Export/Mapping)
$users = $pdo->query("SELECT id, full_name FROM users WHERE status = 1 AND role IN ('manager', 'user') ORDER BY full_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$userMap = array_column($users, 'full_name', 'id');
// 2. Approved Bazar List (For History Tab)
$stmt = $pdo->prepare("
    SELECT b.* 
    FROM bazar b 
    WHERE b.status = 'approved' AND DATE_FORMAT(b.bazar_date, '%Y-%m') = ? 
    ORDER BY b.bazar_date DESC
");
$stmt->execute([$selected_month]);
$approved_bazars = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- HANDLE EXCEL EXPORT ---
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=bazar_list_{$selected_month}.xls");
    echo '<table border="1"><tr><th>Date</th><th>Shopper</th><th>Details</th><th>Amount</th></tr>';
    foreach($approved_bazars as $b) {
        $s_ids = explode(',', $b['shopper_ids'] ?? '');
        $s_names = array_map(fn($id) => $userMap[$id] ?? '', $s_ids);
        $shopper = implode(', ', array_filter($s_names));
        echo "<tr><td>{$b['bazar_date']}</td><td>{$shopper}</td><td>{$b['details']} " . ($b['remarks'] ? "({$b['remarks']})" : "") . "</td><td>{$b['amount']}</td></tr>";
    }
 echo '</table>';
    exit;
}
// 3. My Pending/Recent Requests (For Add Tab)
$stmt = $pdo->prepare("SELECT * FROM bazar WHERE user_id = ? ORDER BY id DESC LIMIT 10");
$stmt->execute([$userId]);
$my_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Bazar List";
ob_start();
?>
<!-- Select2 & jQuery for Advanced Dropdown -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <style>
        .card{border:none;border-radius:16px;box-shadow:0 10px 25px rgba(0,0,0,.08)}
        .nav-pills .nav-link.active { background-color: #0d6efd; }
        .status-badge { font-size: 0.75rem; padding: 4px 8px; border-radius: 12px; }
        @media print {
  .no-print { display: none !important; }
            .card { box-shadow: none !important; border: 1px solid #ddd !important; }
            .sidebar, .top-header { display: none !important; }
            .main-content { margin-left: 0 !important; }
        }
    </style>
 <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <h3 class="fw-bold mb-0"></h3>
        <form method="GET" class="d-flex align-items-center gap-2">
            <label class="small fw-bold text-muted">Month:</label>
            <input type="month" name="month" class="form-control form-control-sm" value="<?= $selected_month ?>" onchange="this.form.submit()">
        </form>
    </div>

    <?= $msg ?>
 <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold" id="pills-history-tab" data-bs-toggle="pill" data-bs-target="#pills-history" type="button">Bazar History</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold" id="pills-add-tab" data-bs-toggle="pill" data-bs-target="#pills-add" type="button">Add New / My Status</button>
        </li>
    </ul>
 <div class="tab-content" id="pills-tabContent">
        
        <!-- HISTORY TAB -->
        <div class="tab-pane fade show active" id="pills-history">
            <!-- Toolbar -->
            <div class="row g-3 mb-3 no-print">
                <div class="col-md-6">
                    <input type="text" id="searchInput" class="form-control" placeholder="Search details, shopper...">
                </div>
                <div class="col-md-6 text-md-end">
                    <button onclick="window.print()" class="btn btn-outline-danger me-2"><i class="bi bi-printer"></i> Print</button>
                    <a href="?month=<?= $selected_month ?>&export=excel" class="btn btn-success"><i class="bi bi-file-earmark-excel"></i> Excel</a>
                </div>
            </div>

            <div class="card p-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Shopper</th>
                                <th>Details</th>
                                <th class="text-end">Amount (৳)</th>
                            </tr>
                         </thead>
                        <tbody id="bazarTableBody">
                            <?php if(empty($approved_bazars)): ?>
                                <tr><td colspan="4" class="text-center text-muted py-4">No bazar records found for this month.</td></tr>
 <?php else: ?>
                                <?php foreach($approved_bazars as $b): ?>
                                <tr>
                                    <td><?= date('d M', strtotime($b['bazar_date'])) ?></td>
                                    <td>
                                        <div class="fw-bold small">
                                            <?php 
        $s_ids = explode(',', $b['shopper_ids'] ?? '');
                                                $s_names = array_map(fn($id) => $userMap[$id] ?? '', $s_ids);
                                                echo htmlspecialchars(implode(', ', array_filter($s_names)));
                                            ?>
                                        </div>
                                    </td>
                                    <td>
  <span class="d-block text-dark"><?= htmlspecialchars($b['details']) ?></span>
                                        <?php if($b['remarks']): ?><small class="text-muted">Note: <?= htmlspecialchars($b['remarks']) ?></small><?php endif; ?>
                                    </td>
                                    <td class="fw-bold text-success text-end amount-cell" data-amount="<?= $b['amount'] ?>"><?= number_format($b['amount'], 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                                             <td colspan="3" class="text-end fw-bold">Total Amount:</td>
                                <td class="text-end fw-bold text-primary" id="totalAmount">0.00</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
 <!-- ADD NEW TAB -->
        <div class="tab-pane fade" id="pills-add">
            <div class="row g-4">
                <!-- Form -->
                <div class="col-md-5">
                    <div class="card p-4 h-100">
                        <h5 class="fw-bold mb-3 text-primary">Add Bazar Expense</h5>
                        <form method="POST">
                            <input type="hidden" name="add_bazar" value="1">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Date</label>
                                <input type="date" name="bazar_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
  <div class="mb-3">
                                <label class="form-label small fw-bold">Shopper (Attached Person)</label>
                                <select name="shopper_ids[]" class="form-select select2" multiple required>
                                    <?php foreach($users as $u): ?>
                                        <option value="<?= $u['id'] ?>" <?= $u['id'] == $userId ? 'selected' : '' ?>><?= htmlspecialchars($u['full_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
 <div class="mb-3">
                                <label class="form-label small fw-bold">Items / Details</label>
                                <textarea name="details" class="form-control" rows="2" placeholder="e.g. Rice, Oil, Chicken" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Amount (৳)</label>
                                <input type="number" step="0.01" name="amount" class="form-control" placeholder="0.00" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Remarks (Optional)</label>
                                <input type="text" name="remarks" class="form-control" placeholder="Any extra note...">
                            </div>
                            <button class="btn btn-primary w-100 fw-bold"><i class="bi bi-send me-2"></i> Submit for Approval</button>
                        </form>
                    </div>
                </div>

                <!-- My Recent Requests -->
                <div class="col-md-7">
                    <div class="card p-4 h-100">
                        <h5 class="fw-bold mb-3">My Recent Requests</h5>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead>
                                   tr>
                                        <th>Da<te</th>
                                        <th>Details</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
    <?php foreach($my_requests as $req): ?>
                                    <tr>
                                        <td><?= date('d M', strtotime($req['bazar_date'])) ?></td>
                                        <td><small><?= htmlspecialchars(substr($req['details'], 0, 20)) ?>...</small></td>
                                        <td><?= $req['amount'] ?></td>
                                        <td>
                                            <?php if($req['status'] == 'approved'): ?>
                                                <span class="badge bg-success status-badge">Approved</span>
                                            <?php elseif($req['status'] == 'rejected'): ?>
                                                <span class="badge bg-danger status-badge">Rejected</span>
                                                <a href="?action=delete&id=<?= $req['id'] ?>&month=<?= $selected_month ?>" class="text-danger ms-2" onclick="return confirm('Permanently delete this rejected request?')" title="Delete"><i class="bi bi-trash"></i></a>
     <?php else: ?>
                                                <span class="badge bg-warning text-dark status-badge">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
 <script>
        $(document).ready(function() {
            $('.select2').select2({
                theme: 'bootstrap-5',
                placeholder: "Select shoppers",
                width: '100%'
            });

            // Live Search & Total Calculation
            const searchInput = document.getElementById('searchInput');
            const tableBody = document.getElementById('bazarTableBody');
            const rows = tableBody.getElementsByTagName('tr');
            const totalDisplay = document.getElementById('totalAmount');
function calculateTotal() {
                let total = 0;
                Array.from(rows).forEach(row => {
                    if (row.style.display !== 'none') {
                        const cell = row.querySelector('.amount-cell');
                        if (cell) total += parseFloat(cell.getAttribute('data-amount')) || 0;
                    }
                });
                totalDisplay.textContent = total.toFixed(2);
            }
 searchInput.addEventListener('keyup', function() {
                const filter = searchInput.value.toLowerCase();
                Array.from(rows).forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(filter) ? '' : 'none';
                });
                calculateTotal();
            });

            // Initial Calc
            calculateTotal();
        });
    </script>

<?php
$content = ob_get_clean();
include "layout.php";
?>


   

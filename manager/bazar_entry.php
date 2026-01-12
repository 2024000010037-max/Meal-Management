<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    header("Location: ../index.php");
    exit;
}

include "../config/database.php";

$pdo = (new Database())->connect();

$msg = "";
$selected_month = $_GET['month'] ?? date('Y-m');

// --- HANDLE APPROVAL / REJECTION ---
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $bid = intval($_GET['id']);
    $new_status = ($action === 'approve') ? 'approved' : 'rejected';
    $manager_id = $_SESSION['user_id'];
    
    $stmt = $pdo->prepare("UPDATE bazar SET status = ?, manager_id = ? WHERE id = ?");
    $stmt->execute([$new_status, $manager_id, $bid]);
    
    header("Location: bazar_entry.php?month=" . $selected_month);
    exit;
}
    // --- HANDLE NEW ENTRY (Manager - Auto Approved) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_bazar'])) {
    $date = $_POST['bazar_date'];
    $amount = $_POST['amount'];
    $details = $_POST['details'];
    $remarks = $_POST['remarks'];
    $shopper_ids = isset($_POST['shopper_ids']) ? implode(',', $_POST['shopper_ids']) : '';
    $manager_id = $_SESSION['user_id'];

    $stmt = $pdo->prepare("INSERT INTO bazar (user_id, manager_id, shopper_ids, bazar_date, amount, details, remarks, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'approved')");
    if ($stmt->execute([$manager_id, $manager_id, $shopper_ids, $date, $amount, $details, $remarks])) {
        $msg = "<div class='alert alert-success alert-dismissible fade show'>Bazar added successfully! <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    } else {
        $msg = "<div class='alert alert-danger'>Error adding bazar.</div>";
    }
}

    // --- FETCH DATA ---
// 1. Pending Requests
$pending_reqs = $pdo->query("
    SELECT b.*, u.full_name as submitter_name 
    FROM bazar b 
    JOIN users u ON b.user_id = u.id 
    WHERE b.status = 'pending' 
    ORDER BY b.bazar_date ASC
")->fetchAll(PDO::FETCH_ASSOC);

// 2. Approved History
$stmt = $pdo->prepare("
    SELECT b.* 
    FROM bazar b 
    WHERE b.status = 'approved' AND DATE_FORMAT(b.bazar_date, '%Y-%m') = ? 
    ORDER BY b.bazar_date DESC
");
$stmt->execute([$selected_month]);
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Users List
$users = $pdo->query("SELECT id, full_name FROM users WHERE status = 1 AND role IN ('manager', 'user') ORDER BY full_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$userMap = array_column($users, 'full_name', 'id');

// --- HANDLE EXCEL EXPORT (Manager) ---
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=manager_bazar_list_{$selected_month}.xls");
    echo '<table border="1"><tr><th>Date</th><th>Shopper</th><th>Details</th><th>Amount</th></tr>';
    foreach($history as $h) {
        $s_ids = explode(',', $h['shopper_ids'] ?? '');
        $s_names = array_map(fn($id) => $userMap[$id] ?? '', $s_ids);
        $shopper = implode(', ', array_filter($s_names));
        echo "<tr><td>{$h['bazar_date']}</td><td>{$shopper}</td><td>{$h['details']} " . ($h['remarks'] ? "({$h['remarks']})" : "") . "</td><td>{$h['amount']}</td></tr>";
    }
    echo '</table>';
    exit;
}

$pageTitle = "Bazar Entry";
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
        .action-btn { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; }
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
        <li class="nav-item">
            <button class="nav-link active fw-bold position-relative" id="pills-pending-tab" data-bs-toggle="pill" data-bs-target="#pills-pending" type="button">
                Pending Requests
                <?php if(count($pending_reqs) > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        <?= count($pending_reqs) ?>
                    </span>
                <?php endif; ?>
            </button>
        </li>
        <li class="nav-item"><button class="nav-link fw-bold" id="pills-add-tab" data-bs-toggle="pill" data-bs-target="#pills-add" type="button">Add New Entry</button></li>
        <li class="nav-item"><button class="nav-link fw-bold" id="pills-history-tab" data-bs-toggle="pill" data-bs-target="#pills-history" type="button">History</button></li>
    </ul>

    <div class="tab-content" id="pills-tabContent">

<!-- PENDING REQUESTS -->
        <div class="tab-pane fade show active" id="pills-pending">
            <div class="card p-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Submitted By</th>
                                <th>Shopper</th>
                                <th>Details</th>
                                <th>Amount</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($pending_reqs)): ?>
                                <tr><td colspan="6" class="text-center text-muted py-4">No pending requests.</td></tr>
                            <?php else: ?>
                                <?php foreach($pending_reqs as $req): ?>
                                <tr>
                                    <td><?= date('d M', strtotime($req['bazar_date'])) ?></td>
                                    <td><?= htmlspecialchars($req['submitter_name']) ?></td>
                                    <td>
                                        <?php 
                                            $s_ids = explode(',', $req['shopper_ids'] ?? '');
                                            $s_names = array_map(fn($id) => $userMap[$id] ?? '', $s_ids);
                                            echo '<span class="badge bg-info text-dark">' . htmlspecialchars(implode(', ', array_filter($s_names))) . '</span>';
                                        ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($req['details']) ?>
                                        <?php if($req['remarks']): ?><br><small class="text-muted"><?= htmlspecialchars($req['remarks']) ?></small><?php endif; ?>
                                    </td>
                                    <td class="fw-bold"><?= number_format($req['amount'], 2) ?></td>
                                    <td class="text-end">
                                        <a href="?action=approve&id=<?= $req['id'] ?>&month=<?= $selected_month ?>" class="btn btn-success action-btn" title="Approve"><i class="bi bi-check-lg"></i></a>
                                        <a href="?action=reject&id=<?= $req['id'] ?>&month=<?= $selected_month ?>" class="btn btn-outline-danger action-btn" title="Reject" onclick="return confirm('Reject this request?')"><i class="bi bi-x-lg"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ADD NEW ENTRY -->
        <div class="tab-pane fade" id="pills-add">
            <div class="card p-4" style="max-width: 600px; margin: 0 auto;">
                <h5 class="fw-bold mb-3 text-primary">Add Direct Bazar Entry</h5>
                <form method="POST">
                    <input type="hidden" name="add_bazar" value="1">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Date</label>
                            <input type="date" name="bazar_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Shopper</label>
                            <select name="shopper_ids[]" class="form-select select2" multiple required>
                                <?php foreach($users as $u): ?>
                                    <option value="<?= $u['id'] ?>" <?= $u['id'] == $_SESSION['user_id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Items / Details</label>
                            <textarea name="details" class="form-control" rows="2" required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Amount (৳)</label>
                            <input type="number" step="0.01" name="amount" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Remarks</label>
                            <input type="text" name="remarks" class="form-control">
                        </div>
                        <div class="col-12 mt-4">
                            <button class="btn btn-success w-100 fw-bold">Save & Approve</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
<!-- HISTORY -->
        <div class="tab-pane fade" id="pills-history">

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

?>

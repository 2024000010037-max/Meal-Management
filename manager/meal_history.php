<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    header("Location: ../index.php");
    exit;
}
    include "../config/database.php";
$pdo = (new Database())->connect();
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date   = $_GET['end_date'] ?? date('Y-m-t');
$search     = $_GET['search'] ?? '';

// Build Query
$sql = "SELECT m.*, u.full_name 
        FROM meals m 
        JOIN users u ON m.user_id = u.id 
        WHERE m.meal_date BETWEEN ? AND ?";
$params = [$start_date, $end_date];
if (!empty($search)) {
    $sql .= " AND u.full_name LIKE ?";
    $params[] = "%$search%";
}
$sql .= " ORDER BY m.meal_date DESC, u.full_name ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$meals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle Excel Export
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=meal_history_{$start_date}_to_{$end_date}.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

echo '<table border="1">';
    echo '<tr><th>Date</th><th>Member Name</th><th>Breakfast</th><th>Lunch</th><th>Dinner</th><th>Total</th></tr>';
    foreach ($meals as $m) {
        $total = $m['breakfast'] + $m['lunch'] + $m['dinner'];
         echo "<tr>
                <td>{$m['meal_date']}</td>
                <td>{$m['full_name']}</td>
                <td>{$m['breakfast']}</td>
                <td>{$m['lunch']}</td>
                <td>{$m['dinner']}</td>
                <td>{$total}</td>
              </tr>";
    }
    echo '</table>';
    exit;
}
    $pageTitle = "Meal History";
ob_start();
?>





<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <div>
        <h3 class="fw-bold mb-0">Meal History</h3>
        <p class="text-muted small mb-0">View and export meal records</p>
    </div>
    <div>
        <a href="meal_entry.php" class="btn btn-outline-secondary me-2"><i class="bi bi-arrow-left"></i> Back</a>
        <button onclick="window.print()" class="btn btn-outline-danger me-2"><i class="bi bi-file-pdf"></i> PDF / Print</button>
        <button onclick="exportExcel()" class="btn btn-success"><i class="bi bi-file-earmark-excel"></i> Excel</button>
    </div>
</div>
<div class="card p-4 mb-4 no-print">
    <form method="GET" class="row g-3">
        <div class="col-md-3">
            <label class="form-label small fw-bold text-muted">Start Date</label>
            <input type="date" name="start_date" class="form-control" value="<?= $start_date ?>">
        </div>

<div class="col-md-3">
            <label class="form-label small fw-bold text-muted">End Date</label>
            <input type="date" name="end_date" class="form-control" value="<?= $end_date ?>">
        </div>
<div class="col-md-4">
            <label class="form-label small fw-bold text-muted">Search Member</label>
            <input type="text" name="search" class="form-control" placeholder="Enter name..." value="<?= htmlspecialchars($search) ?>">
        </div>
 <div class="col-md-2 d-flex align-items-end">
            <button class="btn btn-primary w-100"><i class="bi bi-filter"></i> Filter</button>
        </div>
    </form>
</div>
<div class="card p-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">

 <tr>
                    <th>Date</th>
                    <th>Member Name</th>
                    <th class="text-center">Breakfast</th>
                    <th class="text-center">Lunch</th>
                    <th class="text-center">Dinner</th>
                    <th class="text-center fw-bold">Total</th>
                </tr>
            </thead>
 <tbody>
                <?php if(empty($meals)): ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted">No records found</td></tr>
                <?php else: ?>
                    <?php foreach($meals as $m): 
                        $total = $m['breakfast'] + $m['lunch'] + $m['dinner'];
                    ?>

      <tr>
                        <td><?= date('d M, Y', strtotime($m['meal_date'])) ?></td>
                        <td class="fw-bold"><?= htmlspecialchars($m['full_name']) ?></td>
                        <td class="text-center"><?= $m['breakfast'] ?></td>
                        <td class="text-center"><?= $m['lunch'] ?></td>
                        <td class="text-center"><?= $m['dinner'] ?></td>
                        <td class="text-center fw-bold"><?= $total ?></td>
                    </tr>
     <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<script>
function exportExcel() {
    const url = new URL(window.location.href);
    url.searchParams.set('export', 'excel');
    window.location.href = url.toString();
}
</script>
<?php



?>

<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

include "../config/database.php";
$pdo = (new Database())->connect();

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date   = $_GET['end_date'] ?? date('Y-m-t');
$search     = $_GET['search'] ?? '';
/ Build Query
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
/ Handle Excel Export
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
<style>
    .card { border:none; border-radius:16px; box-shadow:0 10px 25px rgba(0,0,0,.08); }
    @media print {
        .no-print { display: none !important; }
        .card { box-shadow: none !important; border: 1px solid #ddd !important; }
        .sidebar, .top-header { display: none !important; }
        .main-content { margin-left: 0 !important; }
    }
</style>
<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <div>
        <h3 class="fw-bold mb-0">Meal History</h3>
        <p class="text-muted small mb-0">View and export meal records</p>
    </div>
    <div>

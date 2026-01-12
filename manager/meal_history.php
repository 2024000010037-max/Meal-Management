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




?>

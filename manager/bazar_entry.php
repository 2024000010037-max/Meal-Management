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


?>

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



?>

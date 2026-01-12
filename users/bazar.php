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


    

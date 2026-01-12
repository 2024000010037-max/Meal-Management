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


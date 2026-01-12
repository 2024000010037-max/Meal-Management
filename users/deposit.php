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

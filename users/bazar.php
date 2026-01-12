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


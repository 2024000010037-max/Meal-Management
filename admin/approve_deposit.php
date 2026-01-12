<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['manager', 'admin'])) {
    header("Location: ../index.php");
    exit;
}

include "../config/database.php";
require '../sms/Exception.php';
require '../sms/PHPMailer.php';
require '../sms/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
$pdo = (new Database())->connect();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$month = $_GET['month'] ?? date('Y-m');
$manager_id = $_SESSION['user_id'];

if (!$id) {
    header("Location: deposit.php?month=$month");
    exit;
}
// 1. Fetch Deposit Details (Verify it exists and is pending)
$stmt = $pdo->prepare("SELECT d.*, u.full_name, u.email FROM deposits d JOIN users u ON d.user_id = u.id WHERE d.id = ? AND d.status = 'pending'");
$stmt->execute([$id]);
$deposit = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$deposit) {
    header("Location: deposit.php?month=$month&error=not_found");
    exit;
}
// 2. Approve the Deposit
$stmt = $pdo->prepare("UPDATE deposits SET status = 'approved', manager_id = ? WHERE id = ?");
$stmt->execute([$manager_id, $id]);

// 3. Calculate Stats for Invoice (Total Deposit & Current Balance)
$user_id = $deposit['user_id'];
$current_month = date('Y-m'); 

// Global Stats for Rate
$stmt = $pdo->prepare("SELECT SUM(breakfast + lunch + dinner) FROM meals WHERE DATE_FORMAT(meal_date, '%Y-%m') = ?");
$stmt->execute([$current_month]);
$total_mess_meals = $stmt->fetchColumn() ?: 0;

$stmt = $pdo->prepare("SELECT SUM(amount) FROM bazar WHERE status = 'approved' AND DATE_FORMAT(bazar_date, '%Y-%m') = ?");
$stmt->execute([$current_month]);
$total_mess_bazar = $stmt->fetchColumn() ?: 0;

$me

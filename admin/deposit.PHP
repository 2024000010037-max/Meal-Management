<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['manager', 'admin'])) {
    header("Location: ../index.php");
    exit;
}
include "../config/database.php";
$pdo = (new Database())->connect();

require '../sms/Exception.php';
require '../sms/PHPMailer.php';
require '../sms/SMTP.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$msg = "";
$selected_month = $_GET['month'] ?? date('Y-m');
$manager_id = $_SESSION['user_id'];
// Success Message from Approve Page
if (isset($_GET['msg']) && $_GET['msg'] === 'approved') {
    $msg = "<div class='alert alert-success alert-dismissible fade show'>Deposit Approved & Invoice Sent! <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
}

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

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$selected_month = $_GET['month'] ?? date('Y-m');

if (!$user_id) {
    die("Invalid User ID");
}

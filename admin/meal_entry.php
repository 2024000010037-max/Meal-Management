<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}
include "../config/database.php";
$pdo = (new Database())->connect();

// Set Timezone (Adjust if needed, e.g., 'Asia/Dhaka')
date_default_timezone_set('Asia/Dhaka');

$msg = "";
$selected_date = $_GET['date'] ?? date('Y-m-d');

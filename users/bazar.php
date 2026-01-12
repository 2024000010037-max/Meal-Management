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


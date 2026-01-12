<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

include "../config/database.php";
$pdo = (new Database())->connect();

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date   = $_GET['end_date'] ?? date('Y-m-t');
$search     = $_GET['search'] ?? '';

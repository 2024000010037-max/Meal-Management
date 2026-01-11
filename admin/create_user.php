<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}
include "../config/database.php";

$pdo = (new Database())->connect();

$msg = "";
$editUser = null;



</php>  

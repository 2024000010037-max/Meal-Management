<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
 header("Location: ../index.php")
    exit;
}
include "../config/database.php";
$pdo = (new Database())->connect();
$user_id = $_SESSION['user_id'];
// Fetch User Info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
// Profile Photo Logic (Same as create_user.php)
$profile_img = !empty($user['photo']) ? "../uploads/" . $user['photo'] : "https://ui-avatars.com/api/?name=" . 
urlencode($user['full_name']) . "&background=fff&color=667eea&size=128";

$pageTitle = "My Account";
ob_start();
?>
<div class="row justify-content-center">
<div class="col-md-8 col-lg-6">

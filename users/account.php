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
 <div class="card border-0 shadow-lg rounded-4 overflow-hid
 <!-- Profile Header -->
            <div class="card-header text-white p-4 text-center border-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
<img src="<?= $profile_img ?>" class="rounded-circle mx-auto d-block mb-3 shadow" width="90" height="90" 
alt="Profile" style="object-fit: cover;">
<h4 class="fw-bold mb-1"><?= htmlspecialchars($user['full_name']) ?></h4>
<span class="badge bg-white text-primary rounded-pill px-3"><?= ucfirst($user['role']) ?></s
  </div>
            
            <!-- Profile Details -->
            <div class="card-body p-4">
 <h6 class="fw-bold text-muted text-uppercase small mb-4"><i class="bi bi-info-circle me-1"></i> Personal 
Information</h6>
                
                <div class="mb-3 border-bottom pb-2">
 <label class="small text-muted d-block mb-1">Username</label>
                    <span class="fw-bold text-dark fs-5"><?= htmlspecialchars($user['username']) ?></span>
                </div>

<?php
session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: ../admin/dashboard.php");
        exit;
    } elseif ($_SESSION['role'] === 'manager') {
        header("Location: ../manager/dashboard.php");
        exit;
    }
}
msg = "";

// Only run login logic if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include "../config/database.php";
    $pdo = (new Database())->connect();

    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        if ($user['status'] == 1) {
            // Set Session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];

            // Redirect based on role
            if ($user['role'] === 'admin') {
                header("Location: ../admin/dashboard.php");
            } elseif ($user['role'] === 'manager') {
                header("Location: ../manager/dashboard.php");
            } else {
                header("Location: ../user/dashboard.php");
            }
            exit;
        } else {
            $msg = "<div class='alert alert-danger'>User inactive, contact hostel manager</div>";
        }
    } else {
        $msg = "<div class='alert alert-danger'>Invalid username or password</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login | Hostel Mess</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
body {
            font-family: 'Poppins', sans-serif;
            /* Warm, cute gradient background */
            background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
            min-height: 100vh;
            overflow-x: hidden;
            position: relative;
        }
</style>
</head>
<body class="d-flex align-items-center justify-content-center">

    <!-- Floating Food Icons -->
    <div class="food-icon" style="top: 15%; left: 10%; animation-delay: 0s;">🍔</div>
    <div class="food-icon" style="top: 25%; right: 15%; animation-delay: 1s;">🍕</div>
    <div class="food-icon" style="bottom: 20%; left: 15%; animation-delay: 2s;">🍚</div>
    <div class="food-icon" style="bottom: 15%; right: 10%; animation-delay: 3s;">🍗</div>
    <div class="food-icon" style="top: 50%; left: 5%; animation-delay: 1.5s;">🥗</div>
    <div class="food-icon" style="top: 10%; left: 50%; animation-delay: 0.5s;">🍳</div>

   <div class="login-card text-center">
        <div class="mb-4">
            <span style="font-size: 3.5rem;">🍱</span>
            <h3 class="fw-bold mt-2 text-dark">Welcome Back!</h3>
            <p class="text-muted small">Hostel Meal management portal </p>
        </div>

        <?= $msg ?>
<form method="POST" class="text-start">
            <div class="mb-3">
                <label class="form-label text-secondary small fw-bold">USERNAME</label>
                <input type="text" name="username" class="form-control" placeholder="Enter username" required>
            </div>
            <div class="mb-4">
                <label class="form-label text-secondary small fw-bold">PASSWORD</label>
                <input type="password" name="password" class="form-control" placeholder="Enter password" required>
                <div class="mt-2">
                    <a href="forgot_password.php" class="forgot-link">Forgot Password?</a>
                </div>
            </div>
            <button class="btn btn-primary w-100">Let's Eat!</button>
        </form>
    </div>
      <!-- Floating Developer Button -->
    <div class="dev-fab-container">
        <button class="dev-fab" onclick="toggleDevPopup()">
            <i class="bi bi-bell-fill"></i>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="border: 2px solid white;">
                1
            </span>
        </button>
        <span class="dev-tooltip">Need a Custom System?</span>
    </div>
    <!-- Developer Contact Popup -->
    <div id="devPopup" class="dev-popup-overlay">
        <div class="dev-popup-card">
            <button class="dev-close-btn" style="width: 30px; height: 30px; font-size: 1rem; top: 15px; right: 15px;" onclick="toggleDevPopup()"><i class="bi bi-x-lg"></i></button>
            
            <div class="mb-3">
                <div style="width: 60px; height: 60px; background: #f3e8ff; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto; color: #9333ea; font-size: 1.8rem;">
                    <i class="bi bi-laptop"></i>
                </div>
            </div>
            <h5 class="fw-bold text-dark mb-2">Build Your Own Panel!</h5>
            <p class="text-muted small mb-3" style="font-size: 0.8rem;">Looking for a custom Admin Panel? Contact us to build professional software.</p>
            
            <div class="d-flex flex-column gap-2 text-start bg-light p-3 rounded-4 mb-3">
                <div class="d-flex align-items-center">
                    <div class="bg-white p-2 rounded-circle shadow-sm me-3 text-primary"><i class="bi bi-telephone-fill"></i></div>
                    <div><small class="text-muted d-block" style="font-size: 10px;">CALL US</small><span class="fw-bold text-dark">+8801780767020</span></div>
                </div>
                <div class="d-flex align-items-center">
                    <div class="bg-white p-2 rounded-circle shadow-sm me-3 text-danger"><i class="bi bi-envelope-fill"></i></div>
                    <div><small class="text-muted d-block" style="font-size: 10px;">EMAIL US</small><span class="fw-bold text-dark">1towfiq12@gmail.com</span></div>
                </div>
                <div class="d-flex align-items-center">
                    <div class="bg-white p-2 rounded-circle shadow-sm me-3 text-info"><i class="bi bi-globe"></i></div>
                    <div><small class="text-muted d-block" style="font-size: 10px;">WEBSITE</small><span class="fw-bold text-dark">www.edicut.com</span></div>
                </div>
            </div>
        </div>
    </div>
     <script>
        function toggleDevPopup() {
            document.getElementById('devPopup').classList.toggle('active');
        }
    </script>
</body>

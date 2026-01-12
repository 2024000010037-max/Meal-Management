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
</body>

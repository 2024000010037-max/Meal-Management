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

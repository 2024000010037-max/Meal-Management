<?php
session_start();
include "../config/database.php";
if (!isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true) {
    echo "Unauthorized access.";
    exit;
}

?>

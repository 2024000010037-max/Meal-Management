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

// Helper Function for File Upload
function uploadFile($file, $dir, $prefix) {
    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = $prefix . '_' . time() . '_' . uniqid() . '.' . $ext;
        if (move_uploaded_file($file['tmp_name'], $dir . $filename)) {
            return $filename;
        }
    }
    return null;
}

if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = $_GET['id'];



}
</php>  

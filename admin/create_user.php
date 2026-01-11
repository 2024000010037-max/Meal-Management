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
 if ($_GET['action'] === 'promote') {
        try {
            $pdo->beginTransaction();
            // Demote all existing managers to user
            $pdo->exec("UPDATE users SET role = 'user' WHERE role = 'manager'");
            // Promote the selected user
            $stmt = $pdo->prepare("UPDATE users SET role = 'manager' WHERE id = ?");
            $stmt->execute([$id]);
             $pdo->commit();
            $msg = "<div class='alert alert-success alert-dismissible fade show'>Manager updated successfully! Only 1 manager is allowed.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        } catch (Exception $e) {
            $pdo->rollBack();
            $msg = "<div class='alert alert-danger'>Error updating manager.</div>";
        }
    }

       // 2. Toggle Status (Active/Inactive)
    if ($_GET['action'] === 'status') {
        $stmt = $pdo->prepare("UPDATE users SET status = NOT status WHERE id = ?");
        $stmt->execute([$id]);
        $msg = "<div class='alert alert-success alert-dismissible fade show'>User status updated!<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    }

    // 3. Delete User
    if ($_GET['action'] === 'delete' && $id != $_SESSION['user_id']) {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            if ($stmt->execute([$id])) {
                $msg = "<div class='alert alert-success alert-dismissible fade show'>User deleted successfully!<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
            }
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') {
                $msg = "<div class='alert alert-warning alert-dismissible fade show'>Cannot delete user with existing records. Please make them <strong>Inactive</strong> instead.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
            } else {
                $msg = "<div class='alert alert-danger'>Error deleting user.</div>";
            }
        }
    }

}
</php>  

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

    // 4. Edit User (Fetch Data)
    if ($_GET['action'] === 'edit') {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $editUser = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

    /* UPDATE USER */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $id       = $_POST['user_id'];
    $name     = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $phone    = trim($_POST['phone']);
    
if (empty($name) || empty($username) || empty($email) || empty($phone)) {
        $msg = "<div class='alert alert-danger'>All text fields are required.</div>";
    } else {
        // Check username uniqueness (excluding current user)
        $check = $pdo->prepare("SELECT id FROM users WHERE username=? AND id != ?");
        $check->execute([$username, $id]);

    if ($check->rowCount() > 0) {
            $msg = "<div class='alert alert-danger'>Username already exists</div>";
        } else {
            $sql = "UPDATE users SET full_name=?, username=?, email=?, phone=?";
            $params = [$name, $username, $email, $phone];

            if (!empty($_POST['password'])) {
                $sql .= ", password=?";
                $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
            }

            $uploadDir = "../uploads/";
            if (!empty($_FILES['photo']['name']) && $path = uploadFile($_FILES['photo'], $uploadDir, 'avatar')) {
                $sql .= ", photo=?"; $params[] = $path;
            }
            if (!empty($_FILES['nid_photo']['name']) && $path = uploadFile($_FILES['nid_photo'], $uploadDir, 'nid')) {
                $sql .= ", nid_photo=?"; $params[] = $path;
            }

            $sql .= " WHERE id=?";
            $params[] = $id;

            if ($pdo->prepare($sql)->execute($params)) {
                $msg = "<div class='alert alert-success'>User updated successfully!</div>";
                echo "<script>setTimeout(()=>window.location.href='create_user.php', 1000)</script>";
            } else {
                $msg = "<div class='alert alert-danger'>Update failed.</div>";
            }
        }
    }
}

    /* CREATE USER */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    $name     = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $phone    = trim($_POST['phone']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
</php>  

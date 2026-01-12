<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['manager', 'admin'])) {
    header("Location: ../index.php");
    exit;
}
include "../config/database.php";
$pdo = (new Database())->connect();

require '../sms/Exception.php';
require '../sms/PHPMailer.php';
require '../sms/SMTP.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$msg = "";
$selected_month = $_GET['month'] ?? date('Y-m');
$manager_id = $_SESSION['user_id'];
// Success Message from Approve Page
if (isset($_GET['msg']) && $_GET['msg'] === 'approved') {
    $msg = "<div class='alert alert-success alert-dismissible fade show'>Deposit Approved & Invoice Sent! <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
}
if (isset($_GET['msg']) && $_GET['msg'] === 'refund_sent') {
    $msg = "<div class='alert alert-success alert-dismissible fade show'>Refund processed and receipt sent successfully! <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
}
// --- HANDLE ACTIONS (Approve, Reject, Delete) ---
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $did = intval($_GET['id']);

    if ($action === 'delete') {
        // Check permission: Admin (anytime) or Manager (within 10 mins)
        $stmt = $pdo->prepare("SELECT created_at, TIMESTAMPDIFF(SECOND, created_at, NOW()) as time_diff FROM deposits WHERE id = ?");
        $stmt->execute([$did]);
        $dep = $stmt->fetch(PDO::FETCH_ASSOC);
       $can_delete = false;
        if ($_SESSION['role'] === 'admin') {
            $can_delete = true;
        } elseif ($_SESSION['role'] === 'manager' && isset($dep['time_diff']) && $dep['time_diff'] >= 0 && $dep['time_diff'] <= 600) {
            $can_delete = true;
        }
       if ($can_delete) {
            $stmt = $pdo->prepare("DELETE FROM deposits WHERE id = ?");
            $stmt->execute([$did]);
            $msg = "<div class='alert alert-warning alert-dismissible fade show'>Deposit deleted permanently. <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        } else {
            $msg = "<div class='alert alert-danger alert-dismissible fade show'>Permission denied or time limit exceeded. <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        }
   } elseif ($action === 'reject') {
        $new_status = 'rejected';
        // Update status AND manager_id
        $stmt = $pdo->prepare("UPDATE deposits SET status = ?, manager_id = ? WHERE id = ?");
        $stmt->execute([$new_status, $manager_id, $did]);
        $msg = "<div class='alert alert-success alert-dismissible fade show'>Request Rejected! <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    }
}
// --- HANDLE FORM SUBMISSION (Add New / Edit) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_deposit'])) {
    $user_id = $_POST['user_id'];
    $date = $_POST['deposit_date'];
    $amount = $_POST['amount'];
    $method = $_POST['payment_method'];
    $tnx_id = $_POST['transaction_id'];
    $remarks = $_POST['remarks'];
    $edit_id = $_POST['edit_id'] ?? null;
  if ($edit_id) {
        // EDIT EXISTING
        // Check permission: Admin (anytime) or Manager (within 10 mins)
        $stmt = $pdo->prepare("SELECT created_at, TIMESTAMPDIFF(SECOND, created_at, NOW()) as time_diff FROM deposits WHERE id = ?");
        $stmt->execute([$edit_id]);
        $dep = $stmt->fetch(PDO::FETCH_ASSOC);
        $can_edit = false;
        if ($_SESSION['role'] === 'admin') {
            $can_edit = true;
        } elseif ($_SESSION['role'] === 'manager' && isset($dep['time_diff']) && $dep['time_diff'] >= 0 && $dep['time_diff'] <= 600) {
            $can_edit = true;
        }
      if ($can_edit) {
            $stmt = $pdo->prepare("UPDATE deposits SET user_id=?, manager_id=?, amount=?, payment_method=?, transaction_id=?, deposit_date=?, remarks=? WHERE id=?");
            if ($stmt->execute([$user_id, $manager_id, $amount, $method, $tnx_id, $date, $remarks, $edit_id])) {
                $msg = "<div class='alert alert-success alert-dismissible fade show'>Deposit updated successfully! <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
            }
        } else {
            $msg = "<div class='alert alert-danger alert-dismissible fade show'>Permission denied or time limit exceeded. <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        }

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
   } else {
        // ADD NEW (Direct Entry - Auto Approved)
        $stmt = $pdo->prepare("INSERT INTO deposits (user_id, manager_id, amount, payment_method, transaction_id, deposit_date, remarks, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'approved')");
        if ($stmt->execute([$user_id, $manager_id, $amount, $method, $tnx_id, $date, $remarks])) {
            $deposit_id = $pdo->lastInsertId();            

           // If it's a refund, redirect to the refund receipt sender
            if ($amount < 0) {
                header("Location: send_refund_receipt.php?id=$deposit_id&month=$selected_month");
                exit;
            }
         // --- START: SEND INVOICE EMAIL ---
            $stmtUser = $pdo->prepare("SELECT full_name, email FROM users WHERE id = ?");
            $stmtUser->execute([$user_id]);
            $user_info = $stmtUser->fetch(PDO::FETCH_ASSOC);

            if ($user_info && !empty($user_info['email'])) {
                $current_month = date('Y-m', strtotime($date));
              // Global Stats for Rate
                $stmt_rate = $pdo->prepare("SELECT SUM(breakfast + lunch + dinner) FROM meals WHERE DATE_FORMAT(meal_date, '%Y-%m') = ?");
                $stmt_rate->execute([$current_month]);
                $total_mess_meals = $stmt_rate->fetchColumn() ?: 0;
               $stmt_rate = $pdo->prepare("SELECT SUM(amount) FROM bazar WHERE status = 'approved' AND DATE_FORMAT(bazar_date, '%Y-%m') = ?");
                $stmt_rate->execute([$current_month]);
                $total_mess_bazar = $stmt_rate->fetchColumn() ?: 0;

                $meal_rate = ($total_mess_meals > 0) ? ($total_mess_bazar / $total_mess_meals) : 0;

                // User Stats (This Month)
                $stmt_user_stats = $pdo->prepare("SELECT SUM(breakfast + lunch + dinner) FROM meals WHERE user_id = ? AND DATE_FORMAT(meal_date, '%Y-%m') = ?");
                $stmt_user_stats->execute([$user_id, $current_month]);
                $user_meals = $stmt_user_stats->fetchColumn() ?: 0;
               // User Total Deposit (This Month)
                $stmt_user_stats = $pdo->prepare("SELECT SUM(amount) FROM deposits WHERE user_id = ? AND status = 'approved' AND DATE_FORMAT(deposit_date, '%Y-%m') = ?");
                $stmt_user_stats->execute([$user_id, $current_month]);
                $user_total_deposit = $stmt_user_stats->fetchColumn() ?: 0;

                $user_cost = $user_meals * $meal_rate;
                $balance = $user_total_deposit - $user_cost;
               // Generate Invoice HTML
                $invoice_no = "INV-" . date('Ymd') . "-" . $deposit_id;
                $date_time = date('d M, Y h:i A');

                $invoice_html = "
               <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #eee; padding: 20px; background: #fff;'>
                    <div style='text-align: center; border-bottom: 2px solid #28a745; padding-bottom: 10px; margin-bottom: 20px;'>
                        <h2 style='color: #28a745; margin: 0;'>DEPOSIT RECEIPT</h2>
                        <p style='color: #777; margin: 5px 0;'>Hostel Mess Management</p>
                    </div>
<table style='width: 100%; margin-bottom: 20px;'>
                        <tr><td><strong>Received From:</strong><br>{$user_info['full_name']}<br>{$user_info['email']}</td>
                        <td style='text-align: right;'><strong>Receipt No:</strong> $invoice_no<br><strong>Date:</strong> $date_time<br><strong>Status:</strong> <span style='color: green; font-weight: bold;'>Approved</span></td></tr>
                    </table>
                    <table style='width: 100%; border-collapse: collapse; margin-bottom: 20px;'>
                        <tr style='background: #f8f9fa;'><th style='border: 1px solid #ddd; padding: 12px; text-align: left;'>Description</th><th style='border: 1px solid #ddd; padding: 12px; text-align: right;'>Details</th></tr>
                        <tr><td style='border: 1px solid #ddd; padding: 10px;'>Payment Type</td><td style='border: 1px solid #ddd; padding: 10px; text-align: right;'>Advance / Deposit</td></tr>
                        <tr><td style='border: 1px solid #ddd; padding: 10px;'>Payment Method</td><td style='border: 1px solid #ddd; padding: 10px; text-align: right; text-transform: capitalize;'>" . htmlspecialchars(ucfirst($method)) . "</td></tr>
                        <tr><td style='border: 1px solid #ddd; padding: 10px;'>Transaction ID</td><td style='border: 1px solid #ddd; padding: 10px; text-align: right;'>" . htmlspecialchars($tnx_id) . "</td></tr>
                        <tr style='background: #e8f5e9;'><td style='border: 1px solid #ddd; padding: 10px; color: #2e7d32; font-weight: bold;'>AMOUNT RECEIVED</td><td style='border: 1px solid #ddd; padding: 10px; text-align: right; color: #2e7d32; font-weight: bold; font-size: 1.2em;'>৳ " . number_format($amount, 2) . "</td></tr>
                    </table>
<div style='background: #f9f9f9; padding: 15px; border-radius: 5px; margin-top: 20px;'>
                        <h4 style='margin: 0 0 10px 0; font-size: 14px; color: #555;'>Account Summary (Current Month)</h4>
                        <table style='width: 100%; font-size: 13px;'>
                            <tr><td>Total Deposit:</td><td style='text-align: right;'>৳ " . number_format($user_total_deposit, 2) . "</td></tr>
                            <tr><td>Current Balance:</td><td style='text-align: right; font-weight: bold; color: " . ($balance >= 0 ? 'green' : 'red') . ";'>" . ($balance >= 0 ? '+' : '') . number_format($balance, 2) . "</td></tr>
                        </table>
                    </div>
                </div>";
 // Send Email
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'youremail@gmail.com';
                    $mail->Password   = 'your app pass';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;
                   $mail->setFrom('youremail@gmail.com', 'Hostel Mess Manager');
                    $mail->addAddress($user_info['email'], $user_info['full_name']);

                    $mail->isHTML(true);
                    $mail->Subject = "Deposit Received - ৳ " . number_format($amount, 2);
                    $mail->Body    = "Dear {$user_info['full_name']},<br><br>We have received your deposit. Here is your receipt.<br><br>" . $invoice_html;
                    $mail->addStringAttachment($invoice_html, "Receipt_{$invoice_no}.html", 'base64', 'text/html');
                    
                    $mail->send();
                    $msg = "<div class='alert alert-success alert-dismissible fade show'>Deposit added and invoice sent successfully! <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
                } catch (Exception $e) {
                    $msg = "<div class='alert alert-warning alert-dismissible fade show'>Deposit added, but failed to send invoice email. Error: {$mail->ErrorInfo} <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
                }
            }
            // --- END: SEND INVOICE EMAIL ---
        }
    }
}
/ --- FETCH DATA ---
// 1. Pending Requests
$pending_reqs = $pdo->query("
    SELECT d.*, u.full_name 
    FROM deposits d 
    JOIN users u ON d.user_id = u.id 
    WHERE d.status = 'pending' 
    ORDER BY d.deposit_date ASC
")->fetchAll(PDO::FETCH_ASSOC);
// 2. History (Approved/Rejected) for Selected Month
$stmt = $pdo->prepare("
    SELECT d.*, u.full_name, m.full_name as manager_name,
           TIMESTAMPDIFF(SECOND, d.created_at, NOW()) as time_diff
    FROM deposits d 
    JOIN users u ON d.user_id = u.id 
    LEFT JOIN users m ON d.manager_id = m.id
    WHERE d.status != 'pending' AND DATE_FORMAT(d.deposit_date, '%Y-%m') = ? 
    ORDER BY d.deposit_date DESC
");

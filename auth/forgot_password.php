<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>



  
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
        <span style="font-size: 3rem;">🔐</span>
        <h3 class="fw-bold mt-2 text-dark">Recovery</h3>
    </div>

    <div id="alertMsg"></div>
<!-- Step 1: Email -->
    <div class="step active" id="step-1">
        <p class="text-muted small mb-3">Enter your email to receive a verification code.</p>
        <div class="mb-3 text-start">
            <label class="form-label text-secondary small fw-bold">EMAIL ADDRESS</label>
            <input type="email" id="email" class="form-control" placeholder="you@example.com" required>
        </div>
        <button id="send-otp-btn" class="btn btn-primary w-100" onclick="sendOTP()">Send OTP</button>
    </div>
<!-- Step 2: OTP -->
    <div class="step" id="step-2">
        <p class="text-muted small mb-3">Enter the 6-digit code sent to <strong id="user-email-display"></strong>.</p>
        <div class="mb-3 text-start">
            <label class="form-label text-secondary small fw-bold">OTP CODE</label>
            <input type="text" id="otp" class="form-control" placeholder="123456" required>
        </div>
        <button id="verify-otp-btn" class="btn btn-primary w-100" onclick="verifyOTP()">Verify Code</button>
    </div>
  <!-- Step 3: New Password -->
    <div class="step" id="step-3">
        <p class="text-muted small mb-3">Create a new password for your account.</p>
        <div class="mb-3 text-start">
            <label class="form-label text-secondary small fw-bold">NEW PASSWORD</label>
            <input type="password" id="newpass" class="form-control" placeholder="Enter new password" required>
        </div>
        <button id="change-pass-btn" class="btn btn-primary w-100" onclick="changePassword()">Reset Password</button>
    </div>
<!-- Step 4: Success Message -->
    <div class="step" id="final-success">
        <div class="text-success mb-3" style="font-size: 3rem;">🎉</div>
        <h5 class="fw-bold text-dark">Password Reset!</h5>
        <p class="text-muted small mb-4">Your password has been changed successfully.</p>
        <a href="login.php" class="btn btn-primary w-100">Go to Login</a>
    </div>
 <div class="mt-4">
        <a href="login.php" class="back-link">← Back to Login</a>
    </div>
  </div>
<script>
    const step1 = document.getElementById('step-1');
    const step2 = document.getElementById('step-2');
    const step3 = document.getElementById('step-3');
    const finalSuccess = document.getElementById('final-success');
    const alertMsg = document.getElementById('alertMsg');
  
        function showStep(stepNum) {
        step1.classList.remove('active');
        step2.classList.remove('active');
        step3.classList.remove('active');
        finalSuccess.classList.remove('active');
          if (stepNum === 1) step1.classList.add('active');
        if (stepNum === 2) step2.classList.add('active');
        if (stepNum === 3) step3.classList.add('active');
        if (stepNum === 4) finalSuccess.classList.add('active');
    }
 function toggleButtonLoading(btnId, isLoading) {
        const btn = document.getElementById(btnId);
        if (isLoading) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
        } else {
            btn.disabled = false;
            // Reset text based on ID
            if(btnId === 'send-otp-btn') btn.innerText = 'Send OTP';
            if(btnId === 'verify-otp-btn') btn.innerText = 'Verify Code';
            if(btnId === 'change-pass-btn') btn.innerText = 'Reset Password';
        }
    }
function showMessage(message, type) {
        alertMsg.innerHTML = `<div class="alert alert-${type} alert-dismissible fade show" role="alert">${message}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>`;
    }
function sendOTP() {
        const email = document.getElementById("email").value;
        if (!email) {
            showMessage("Please enter your email address.", "warning");
            return;
        }
 toggleButtonLoading('send-otp-btn', true);
        alertMsg.innerHTML = '';
fetch("send_otp.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `email=${encodeURIComponent(email)}`
        })
 .then(res => res.text())
        .then(data => {
            if (data.trim() === "success") {
                document.getElementById('user-email-display').textContent = email;
                showStep(2);
            } else {
                showMessage(data, "danger");
            }
        })

.catch(() => showMessage("An error occurred. Please try again.", "danger"))
        .finally(() => toggleButtonLoading('send-otp-btn', false));
    }
function verifyOTP() {
        const otp = document.getElementById("otp").value;
        if (!otp) {
            showMessage("Please enter the OTP.", "warning");
            return;
        }




<?php
require_once "db.php";
require_once "admin/utils_mailer.php";
session_start();

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$success = "";
$error = "";

// Ensure the OTP table exists
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    otp VARCHAR(6) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$step = $_SESSION['reset_step'] ?? 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step == 1 && isset($_POST['send_otp'])) {
        $username = trim($_POST['username'] ?? '');

        if ($username === '') {
            $error = "Please enter your username.";
        } else {
            $stmt = mysqli_prepare($conn, "SELECT id, name, email FROM users WHERE username = ?");
            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($result && mysqli_num_rows($result) === 1) {
                $user = mysqli_fetch_assoc($result);
                $email = $user['email'];

                if (empty($email)) {
                    $error = "No email address linked to this account. Contact the administrator.";
                } else {
                    $otp = sprintf("%06d", mt_rand(100000, 999999));
                    $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));

                    // Remove old otps
                    $del_stmt = mysqli_prepare($conn, "DELETE FROM password_resets WHERE email = ?");
                    mysqli_stmt_bind_param($del_stmt, "s", $email);
                    mysqli_stmt_execute($del_stmt);
                    mysqli_stmt_close($del_stmt);

                    // Insert new otp
                    $ins_stmt = mysqli_prepare($conn, "INSERT INTO password_resets (email, otp, expires_at) VALUES (?, ?, ?)");
                    mysqli_stmt_bind_param($ins_stmt, "sss", $email, $otp, $expires_at);
                    if (mysqli_stmt_execute($ins_stmt)) {
                        if (send_password_reset_otp($email, $user['name'] ?: $username, $otp)) {
                            $_SESSION['reset_step'] = 2;
                            $_SESSION['reset_email'] = $email;
                            $_SESSION['reset_user_id'] = $user['id'];
                            $step = 2;
                            $success = "Verification code sent to your email!";
                        } else {
                            $error = "Failed to send email. Mail server configuration issue.";
                        }
                    } else {
                        $error = "Database error. Could not generate OTP.";
                    }
                    mysqli_stmt_close($ins_stmt);
                }
            } else {
                $error = "Username not found.";
            }
            mysqli_stmt_close($stmt);
        }
    } elseif ($step == 2 && isset($_POST['verify_otp'])) {
        $otp_entered = trim($_POST['otp'] ?? '');
        $email = $_SESSION['reset_email'];

        if (empty($otp_entered)) {
            $error = "Please enter the verification code.";
        } else {
            $stmt = mysqli_prepare($conn, "SELECT otp, expires_at FROM password_resets WHERE email = ? ORDER BY created_at DESC LIMIT 1");
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);

            if ($res && mysqli_num_rows($res) == 1) {
                $row = mysqli_fetch_assoc($res);
                if (strtotime($row['expires_at']) < time()) {
                    $error = "Verification code has expired. Please request a new one.";
                    $_SESSION['reset_step'] = 1;
                    $step = 1;
                } elseif ($row['otp'] !== $otp_entered) {
                    $error = "Invalid verification code.";
                } else {
                    // Success! Allow password reset
                    $_SESSION['reset_step'] = 3;
                    $step = 3;
                    $success = "Code verified! Please enter your new password.";
                    
                    // Clean up OTP
                    mysqli_query($conn, "DELETE FROM password_resets WHERE email = '$email'");
                }
            } else {
                $error = "Invalid request or code expired.";
                $_SESSION['reset_step'] = 1;
                $step = 1;
            }
            mysqli_stmt_close($stmt);
        }
    } elseif ($step == 3 && isset($_POST['reset_password'])) {
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $user_id = $_SESSION['reset_user_id'];

        if ($new_password === '' || $confirm_password === '') {
            $error = "Please fill in all fields.";
        } elseif ($new_password !== $confirm_password) {
            $error = "Passwords do not match.";
        } elseif (strlen($new_password) < 6) {
            $error = "Password must be at least 6 characters long.";
        } else {
            $hashed_new = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = mysqli_prepare($conn, "UPDATE users SET password = ?, must_change_password = 0 WHERE id = ?");
            mysqli_stmt_bind_param($update_stmt, "si", $hashed_new, $user_id);
            if (mysqli_stmt_execute($update_stmt)) {
                $success = "Password updated successfully! You can now log in.";
                unset($_SESSION['reset_step']);
                unset($_SESSION['reset_email']);
                unset($_SESSION['reset_user_id']);
                $step = 4; // Complete state
            } else {
                $error = "Failed to update password. System error.";
            }
            mysqli_stmt_close($update_stmt);
        }
    } elseif (isset($_POST['cancel'])) {
         unset($_SESSION['reset_step']);
         unset($_SESSION['reset_email']);
         unset($_SESSION['reset_user_id']);
         header("Location: login.php");
         exit;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Reset Password | Rent Manager</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <link rel="icon" type="image/png" href="assets/img/favicon.png">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/main.css">
  <style>
      .otp-inputs { display: flex; gap: 10px; justify-content: center; margin-bottom: 20px; }
      .otp-inputs input { width: 45px; height: 55px; text-align: center; font-size: 24px; font-weight: 700; border-radius: 12px; border: 1.5px solid #e2e8f0; background: rgba(255,255,255,0.8); }
      .otp-inputs input:focus { border-color: #6366f1; outline: none; background: white; }
  </style>
</head>
<body class="bg-mesh-glass centered-layout">

<!-- Background Shapes -->
<div class="bg-shape shape-1"></div>
<div class="bg-shape shape-2"></div>

<div style="width: 100%; max-width: 440px;">
    <!-- Main Glass Card -->
    <div class="glass-panel animate-fade-in" style="margin-top: 40px; margin-bottom: 40px;">
        <div style="text-align: center; margin-bottom: 32px;">
            <img src="assets/img/logo.png" alt="Logo" style="width: 64px; height: 64px; border-radius: 16px; margin-bottom: 16px; box-shadow: 0 8px 16px rgba(0,0,0,0.1);">
            <h1 style="font-size: 26px; font-weight: 700; color: var(--text-main); margin-bottom: 8px;">Reset Password</h1>
            <?php if ($step == 1): ?><p style="color: var(--text-muted); font-size: 15px;">Enter your username to receive an OTP</p><?php endif; ?>
            <?php if ($step == 2): ?><p style="color: var(--text-muted); font-size: 15px;">Check your email for the verification code.</p><?php endif; ?>
            <?php if ($step == 3): ?><p style="color: var(--text-muted); font-size: 15px;">Create a new secure password.</p><?php endif; ?>
        </div>

        <?php if ($error !== ""): ?>
            <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: #dc2626; padding: 12px; border-radius: 8px; font-size: 14px; margin-bottom: 24px; font-weight: 500;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success !== ""): ?>
            <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); color: #10B981; padding: 12px; border-radius: 8px; font-size: 14px; margin-bottom: 24px; font-weight: 500;">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if ($step == 4): ?>
            <!-- Success fully complete -->
            <a href="login.php" class="btn btn-primary hover-lift" style="width: 100%; display: flex; justify-content: center; text-decoration: none; padding: 14px;">Back to Login</a>
        <?php else: ?>
            <form method="POST" autocomplete="off" id="resetForm">
                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf']); ?>">
                
                <?php if ($step == 1): ?>
                    <div class="form-group hover-lift" style="margin-bottom: 28px;">
                        <label class="form-label" style="margin-left: 2px;">Username</label>
                        <input name="username" class="glass-input" placeholder="john.doe" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required autofocus>
                    </div>
                    <button type="submit" name="send_otp" class="btn btn-primary hover-lift" style="width: 100%; padding: 14px;">Send Verification OTP</button>
                    
                <?php elseif ($step == 2): ?>
                    <div style="text-align: center; margin-bottom: 24px;">
                        <p style="font-size: 14px; color: var(--text-main); font-weight: 500;">Code sent to: <span style="font-weight: 700; color: #6366f1;"><?php echo htmlspecialchars($_SESSION['reset_email']); ?></span></p>
                    </div>
                    
                    <input type="hidden" name="otp" id="hiddenOtp" required>
                    <div class="otp-inputs">
                        <input type="text" maxlength="1" class="otp-box" autofocus>
                        <input type="text" maxlength="1" class="otp-box">
                        <input type="text" maxlength="1" class="otp-box">
                        <input type="text" maxlength="1" class="otp-box">
                        <input type="text" maxlength="1" class="otp-box">
                        <input type="text" maxlength="1" class="otp-box">
                    </div>
                    
                    <button type="submit" name="verify_otp" class="btn btn-primary hover-lift" style="width: 100%; padding: 14px; margin-top: 10px;">Verify Code</button>
                    <button type="submit" name="cancel" class="btn btn-outline hover-lift" formnovalidate style="width: 100%; justify-content: center; padding: 14px; margin-top: 12px; font-size: 14px; background: transparent;">Cancel Reset</button>
                    
                <?php elseif ($step == 3): ?>
                    <div class="form-group hover-lift">
                        <label class="form-label" style="margin-left: 2px;">New Password</label>
                        <div style="position: relative;">
                            <input type="password" name="new_password" class="glass-input pwd-input" placeholder="Min 6 chars" required minlength="6" autofocus style="padding-right: 40px;">
                            <i class='bx bx-hide pwd-toggle' style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-muted); font-size: 20px;"></i>
                        </div>
                    </div>

                    <div class="form-group hover-lift" style="margin-bottom: 28px;">
                        <label class="form-label" style="margin-left: 2px;">Confirm Password</label>
                        <div style="position: relative;">
                            <input type="password" name="confirm_password" class="glass-input pwd-input" placeholder="Re-type new password" required style="padding-right: 40px;">
                            <i class='bx bx-hide pwd-toggle' style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-muted); font-size: 20px;"></i>
                        </div>
                    </div>

                    <button type="submit" name="reset_password" class="btn btn-primary hover-lift" style="width: 100%; padding: 14px;">Save New Password</button>
                <?php endif; ?>
            </form>
        <?php endif; ?>

        <?php if($step == 1): ?>
        <div style="text-align:center; margin-top:24px; font-size:14px;">
            <a href="login.php" style="color:var(--text-muted); font-weight:600; text-decoration: none; transition: 0.2s;">← Back to Resident Login</a>
        </div>
        <?php endif; ?>
    </div>
    
    <div style="text-align: center; margin-top: 24px; font-size: 13px; color: var(--text-muted); opacity: 0.8; margin-bottom: 20px;">
        © <?php echo date("Y"); ?> Rent Manager System
    </div>
</div>

<script>
    // OTP Box Logic
    const otpBoxes = document.querySelectorAll('.otp-box');
    const hiddenOtp = document.getElementById('hiddenOtp');
    const form = document.getElementById('resetForm');

    if (otpBoxes.length > 0) {
        otpBoxes[0].focus();

        otpBoxes.forEach((box, index) => {
            box.addEventListener('input', (e) => {
                if (e.target.value.length === 1) {
                    if (index < otpBoxes.length - 1) {
                        otpBoxes[index + 1].focus();
                    }
                }
                updateHiddenOtp();
            });

            box.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !e.target.value) {
                    if (index > 0) {
                        otpBoxes[index - 1].focus();
                    }
                }
            });
            
            box.addEventListener('paste', (e) => {
                e.preventDefault();
                const pasteData = e.clipboardData.getData('text').slice(0, 6).replace(/[^0-9]/g, '');
                if (pasteData) {
                    for (let i = 0; i < pasteData.length; i++) {
                         if(otpBoxes[i]) {
                             otpBoxes[i].value = pasteData[i];
                         }
                    }
                    if(pasteData.length < 6) otpBoxes[pasteData.length].focus();
                    else otpBoxes[5].focus();
                    updateHiddenOtp();
                }
            });
        });

        function updateHiddenOtp() {
            let otpVal = '';
            otpBoxes.forEach(b => otpVal += b.value);
            hiddenOtp.value = otpVal;
        }
        
        form.addEventListener('submit', (e) => {
            if(document.activeElement.name !== 'cancel' && !hiddenOtp.value) {
                updateHiddenOtp();
            }
        });
    }

    // Password Toggle
    document.querySelectorAll('.pwd-toggle').forEach(icon => {
        icon.addEventListener('click', function() {
            const input = this.previousElementSibling;
            if(input) {
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                this.classList.toggle('bx-show');
                this.classList.toggle('bx-hide');
            }
        });
    });
</script>

</body>
</html>

<?php
// renter/force-password.php
require_once "../db.php";
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['must_change_password'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_pass = $_POST['new_password'] ?? '';
    $conf_pass = $_POST['confirm_password'] ?? '';

    if (strlen($new_pass) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($new_pass !== $conf_pass) {
        $error = "Passwords do not match.";
    } else {
        $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($conn, "UPDATE users SET password = ?, must_change_password = 0 WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $hashed, $user_id);
        
        if (mysqli_stmt_execute($stmt)) {
            unset($_SESSION['must_change_password']);
            $success = "Password updated! Redirecting to dashboard...";
            header("refresh:2;url=dashboard.php");
        } else {
            $error = "System error. Please try again.";
        }
        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Change Password | <?php echo HOUSE_NAME; ?></title>
    <!-- Immediate Theme Setter -->
    <script>
        (function() {
            if (localStorage.getItem('theme') === 'dark') {
                document.documentElement.classList.add('dark-theme');
            }
        })();
    </script>
    
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-design-system.css?v=<?php echo time(); ?>">
    <style>
        body {
            background: #0F172A;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        
        /* Modern Premium Styling */
        .panel {
            background: #1E293B !important;
            border: 1px solid rgba(255, 255, 255, 0.05) !important;
            border-radius: 20px !important;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2) !important;
            padding: 40px !important;
        }
        .form-group label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #94A3B8 !important;
            font-weight: 600;
            margin-bottom: 10px;
            display: block;
        }
        .form-group input {
            background: #0F172A !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            border-radius: 12px !important;
            color: #F8FAFC !important;
            height: 52px !important;
            transition: all 0.3s ease !important;
        }
        .form-group input:focus {
            background: #131E32 !important;
            border-color: #6366F1 !important;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15) !important;
            outline: none !important;
        }
        .form-group i {
            color: #64748B !important;
            transition: color 0.3s ease;
        }
        .form-group input:focus ~ i, .form-group input:focus + i, .form-group:focus-within i.bx {
            color: #6366F1 !important;
        }
        .btn-primary.submit-btn {
            background: linear-gradient(135deg, #6366F1, #8B5CF6) !important;
            border: none !important;
            color: white !important;
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.3) !important;
            transition: transform 0.2s, box-shadow 0.2s !important;
            font-weight: 600 !important;
            letter-spacing: 0.5px;
        }
        .btn-primary.submit-btn:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 12px 25px rgba(99, 102, 241, 0.4) !important;
        }
        
        .mb-nav-center {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background: #624BFF;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            box-shadow: 0 6px 16px rgba(98, 75, 255, 0.4);
            cursor: pointer;
            margin-top: -24px;
            border: 4px solid var(--white, #FFFFFF);
            transition: transform 0.2s;
        }
        .dark-theme .mb-nav-center {
            border-color: #111827;
        }
    </style>
</head>
<body>
    <div style="width: 100%; max-width: 440px;">
        <div class="panel animate-up" style="padding: 40px;">
            <div style="text-align: center; margin-bottom: 35px;">
                <div style="width: 72px; height: 72px; background: rgba(99, 102, 241, 0.1); border: 1px solid rgba(99, 102, 241, 0.2); box-shadow: 0 8px 20px rgba(99, 102, 241, 0.15); border-radius: 20px; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px;">
                    <i class='bx bx-shield-quarter' style="font-size: 36px; color: #818cf8;"></i>
                </div>
                <h1 style="font-size: 28px; font-weight: 800; background: linear-gradient(135deg, #ffffff 30%, #a5b4fc); -webkit-background-clip: text; -webkit-text-fill-color: transparent; letter-spacing: -0.5px; margin-bottom: 12px;">Secure Your Account</h1>
                <p style="color: #94a3b8; font-size: 14px; line-height: 1.6; max-width: 320px; margin: 0 auto;">First-time login detected. Please set a new password to continue.</p>
            </div>

            <?php if($error): ?>
                <div style="background: #FEF2F2; color: #EF4444; padding: 12px; border-radius: 12px; font-size: 13px; margin-bottom: 24px; border: 1px solid #FEE2E2; display: flex; align-items: center; gap: 2px;">
                    <i class='bx bx-error-circle'></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if($success): ?>
                <div style="background: #F0FDF4; color: #10B981; padding: 12px; border-radius: 12px; font-size: 13px; margin-bottom: 24px; border: 1px solid #DCFCE7; display: flex; align-items: center; gap: 2px;">
                    <i class='bx bx-check-circle'></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                  <input type="hidden" name="csrf" value="<?php echo getCsrfToken(); ?>">
                <div class="form-group">
                    <label>New Password</label>
                    <div style="position: relative;">
                        <input type="password" name="new_password" placeholder="Min 6 characters" class="pwd-input" required style="padding-right: 40px;">
                        <i class='bx bx-hide pwd-toggle' style="position: absolute; right: 16px; top: 14px; color: var(--text-gray); cursor: pointer; font-size: 20px;"></i>
                    </div>
                </div>
                <div class="form-group" style="margin-bottom: 32px;">
                    <label>Confirm Password</label>
                    <div style="position: relative;">
                        <input type="password" name="confirm_password" placeholder="Repeat new password" class="pwd-input" required style="padding-right: 40px;">
                        <i class='bx bx-hide pwd-toggle' style="position: absolute; right: 16px; top: 14px; color: var(--text-gray); cursor: pointer; font-size: 20px;"></i>
                    </div>
                </div>
                <button type="submit" class="btn-primary submit-btn" style="width: 100%; justify-content: center; padding: 16px; border-radius: 14px; font-size: 15px;">
                    <i class='bx bx-lock-open-alt' style="font-size: 20px; margin-right: 8px;"></i> Update & Continue
                </button>
            </form>
        </div>
        
        <p style="text-align: center; margin-top: 24px; color: var(--text-gray); font-size: 13px;">
            &copy; <?php echo date('Y'); ?> <?php echo HOUSE_NAME; ?>. All rights reserved.
        </p>
    </div>

<script>
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
<!-- Universal Mobile Bottom Navigation Bar (Visible only on mobile <= 768px) -->
<nav class="mobile-bottom-nav">
    <a href="dashboard.php" class="mb-nav-item "><i class='bx bx-home'></i><span>Dashboard</span></a>
    <a href="my-payments.php" class="mb-nav-item "><i class='bx bx-credit-card'></i><span>Payments</span></a>
    <div class="mb-nav-center" onclick="if(typeof openPaymentModal === 'function') openPaymentModal(0, 'Quick Payment', 'general'); else window.location.href='my-payments.php';">
        <i class='bx bx-plus'></i>
    </div>
    <a href="payment-history.php" class="mb-nav-item "><i class='bx bx-history'></i><span>History</span></a>
    <a href="profile.php" class="mb-nav-item "><i class='bx bx-user'></i><span>Profile</span></a>
</nav>
</body>
</html>

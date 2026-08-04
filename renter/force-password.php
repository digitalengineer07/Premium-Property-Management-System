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
            background: #F4F7FF;
            background-image: radial-gradient(circle at 15% 50%, rgba(224, 231, 255, 0.6), transparent 30%),
                              radial-gradient(circle at 85% 30%, rgba(224, 231, 255, 0.6), transparent 30%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            font-family: 'Inter', sans-serif;
        }
        
        .logo-container {
            position: absolute;
            top: 40px;
            left: 40px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        @media (max-width: 768px) {
            .logo-container {
                position: relative;
                top: 0;
                left: 0;
                margin-bottom: 30px;
                justify-content: center;
            }
            body {
                flex-direction: column;
                justify-content: flex-start;
                padding-top: 40px;
            }
        }
        
        /* Modern Light Premium Styling */
        .panel {
            background: #FFFFFF !important;
            border: 1px solid rgba(0, 0, 0, 0.03) !important;
            border-radius: 24px !important;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.08) !important;
            padding: 48px !important;
        }
        
        .form-group label {
            font-size: 14px;
            color: #1E293B !important;
            font-weight: 700;
            margin-bottom: 12px;
            display: block;
            text-transform: none;
            letter-spacing: normal;
        }
        
        .form-group input {
            background: #FFFFFF !important;
            border: 1px solid #E2E8F0 !important;
            border-radius: 12px !important;
            color: #1E293B !important;
            height: 52px !important;
            transition: all 0.3s ease !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02) !important;
            font-weight: 500;
        }
        
        .form-group input::placeholder {
            color: #94A3B8;
            font-weight: 400;
        }
        
        .form-group input:focus {
            border-color: #6366F1 !important;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1) !important;
            outline: none !important;
        }
        
        .form-group i {
            color: #94A3B8 !important;
            transition: color 0.3s ease;
        }
        
        .form-group input:focus ~ i, .form-group input:focus + i, .form-group:focus-within i.bx {
            color: #6366F1 !important;
        }
        
        .btn-primary.submit-btn {
            background: linear-gradient(135deg, #818cf8, #4f46e5) !important;
            border: none !important;
            color: white !important;
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.3) !important;
            transition: transform 0.2s, box-shadow 0.2s !important;
            font-weight: 600 !important;
            letter-spacing: 0.5px;
            height: 54px;
        }
        
        .btn-primary.submit-btn:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 12px 25px rgba(99, 102, 241, 0.4) !important;
        }
        
        /* Security Shield Icon Animation */
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-5px); }
            100% { transform: translateY(0px); }
        }
        .shield-icon-container {
            animation: float 4s ease-in-out infinite;
        }
    </style>
</head>
<body>
    <div class="logo-container">
        <i class='bx bxs-building-house' style="color: #6366F1; font-size: 40px;"></i>
        <div style="line-height: 1.1;">
            <div style="font-weight: 800; color: #1E293B; font-size: 22px;">Madhav Kunj</div>
            <div style="font-weight: 700; color: #6366F1; font-size: 11px; letter-spacing: 3px;">RESIDENCE</div>
        </div>
    </div>

    <div style="width: 100%; max-width: 520px;">
        <div class="panel animate-up">
            <div style="text-align: center; margin-bottom: 40px;">
                <div class="shield-icon-container" style="position: relative; width: 90px; height: 90px; margin: 0 auto 30px;">
                    <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #818cf8, #4f46e5); box-shadow: 0 15px 35px rgba(79, 70, 229, 0.35); border-radius: 30px; display: flex; align-items: center; justify-content: center;">
                        <i class='bx bxs-lock-alt' style="font-size: 44px; color: #ffffff;"></i>
                    </div>
                    <!-- Decorative checkmark badge -->
                    <div style="position: absolute; width: 28px; height: 28px; background: #10B981; border: 3px solid #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; top: -5px; left: -10px;">
                        <i class='bx bx-check' style="color: white; font-size: 18px; font-weight: bold;"></i>
                    </div>
                </div>
                
                <h1 style="font-size: 30px; font-weight: 800; color: #0F172A; margin-bottom: 12px;">Secure Your Account</h1>
                <p style="color: #64748B; font-size: 14.5px; line-height: 1.6; max-width: 360px; margin: 0 auto;">First-time login detected. For your security, please set a new password to continue.</p>
            </div>

            <?php if($error): ?>
                <div style="background: #FEF2F2; color: #EF4444; padding: 14px; border-radius: 12px; font-size: 13.5px; margin-bottom: 24px; border: 1px solid #FEE2E2; display: flex; align-items: center; gap: 8px;">
                    <i class='bx bx-error-circle' style="font-size: 18px;"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="csrf" value="<?php echo getCsrfToken(); ?>">
                
                <div class="form-group" style="margin-bottom: 24px;">
                    <label>New Password</label>
                    <div style="position: relative;">
                        <i class='bx bx-lock-alt' style="position: absolute; left: 16px; top: 16px; color: #94A3B8; font-size: 20px; z-index: 2;"></i>
                        <input type="password" name="new_password" placeholder="Enter new password" class="pwd-input" required style="padding-left: 48px; padding-right: 48px;">
                        <i class='bx bx-hide pwd-toggle' style="position: absolute; right: 16px; top: 16px; color: #94A3B8; cursor: pointer; font-size: 20px; z-index: 2;"></i>
                    </div>
                    
                    <div style="display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 16px; font-size: 12px; color: #64748B;">
                        Password Strength: <span style="color: #EF4444; font-weight: 600;">Weak</span>
                        <div style="display: flex; gap: 4px; margin-left: 4px;">
                            <div style="height: 4px; width: 24px; background: #EF4444; border-radius: 2px;"></div>
                            <div style="height: 4px; width: 24px; background: #E2E8F0; border-radius: 2px;"></div>
                            <div style="height: 4px; width: 24px; background: #E2E8F0; border-radius: 2px;"></div>
                            <div style="height: 4px; width: 24px; background: #E2E8F0; border-radius: 2px;"></div>
                        </div>
                    </div>
                </div>
                
                <div class="form-group" style="margin-bottom: 32px;">
                    <label>Confirm Password</label>
                    <div style="position: relative;">
                        <i class='bx bx-lock-alt' style="position: absolute; left: 16px; top: 16px; color: #94A3B8; font-size: 20px; z-index: 2;"></i>
                        <input type="password" name="confirm_password" placeholder="Confirm new password" class="pwd-input" required style="padding-left: 48px; padding-right: 48px;">
                        <i class='bx bx-hide pwd-toggle' style="position: absolute; right: 16px; top: 16px; color: #94A3B8; cursor: pointer; font-size: 20px; z-index: 2;"></i>
                    </div>
                </div>
                
                <button type="submit" class="btn-primary submit-btn" style="width: 100%; justify-content: center; border-radius: 14px; font-size: 15px;">
                    <i class='bx bx-lock-open-alt' style="font-size: 20px; margin-right: 8px;"></i> Update & Continue
                </button>
            </form>

            <!-- Security Priority Box -->
            <div style="margin-top: 36px; background: #F0FDF4; border-radius: 16px; padding: 16px 20px; display: flex; align-items: flex-start; gap: 16px;">
                <div style="background: #DCFCE7; width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-top: 2px;">
                    <i class='bx bx-check-shield' style="color: #10B981; font-size: 24px;"></i>
                </div>
                <div>
                    <div style="font-weight: 700; color: #0F172A; font-size: 14px; margin-bottom: 4px;">Your security is our priority</div>
                    <div style="font-size: 12.5px; color: #64748B; line-height: 1.5;">Use a strong password that's different from previously used passwords.</div>
                </div>
            </div>
        </div>
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

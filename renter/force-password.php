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
                margin-bottom: 20px;
                justify-content: center;
            }
            body {
                flex-direction: column;
                justify-content: flex-start;
                padding-top: 20px;
            }
        }
        
        /* Dark Theme Fixes */
        .dark-theme body {
            background: #0F172A;
            background-image: none;
        }
        .dark-theme .panel {
            background: #1E293B !important;
            border-color: rgba(255,255,255,0.05) !important;
        }
        .dark-theme h1 { color: #F8FAFC !important; }
        .dark-theme p { color: #94A3B8 !important; }
        .dark-theme .form-group label { color: #F8FAFC !important; }
        .dark-theme .form-group input {
            background: #0F172A !important;
            color: #F8FAFC !important;
            border-color: rgba(255,255,255,0.1) !important;
        }
        .dark-theme .input-icon-box { background: #152033 !important; }
        .dark-theme .security-priority-box {
            background: rgba(16, 185, 129, 0.05) !important;
        }
        .dark-theme .security-priority-box .title { color: #F8FAFC !important; }
        .dark-theme .security-priority-box .desc { color: #94A3B8 !important; }
        
        /* Pixel-perfect Panel Styling */
        .panel {
            background: #FFFFFF !important;
            border: 1px solid rgba(0, 0, 0, 0.03) !important;
            border-radius: 24px !important;
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.05) !important;
            padding: 35px 30px !important;
        }
        
        .form-group label {
            font-size: 13.5px;
            color: #0F172A !important;
            font-weight: 700;
            margin-bottom: 8px;
            display: block;
            text-transform: none;
            letter-spacing: normal;
        }
        
        .form-group input {
            background: #FFFFFF !important;
            border: 1px solid #E2E8F0 !important;
            border-radius: 12px !important;
            color: #0F172A !important;
            height: 48px !important;
            transition: all 0.3s ease !important;
            box-shadow: none !important;
            font-weight: 500;
            font-size: 14.5px;
            padding-left: 54px !important;
            padding-right: 48px !important;
        }
        
        .form-group input::placeholder {
            color: #94A3B8;
            font-weight: 400;
        }
        
        .form-group input:focus {
            border-color: #818CF8 !important;
            box-shadow: 0 0 0 4px rgba(129, 140, 248, 0.15) !important;
            outline: none !important;
        }
        
        .input-icon-box {
            position: absolute;
            left: 6px;
            top: 6px;
            width: 36px;
            height: 36px;
            background: #EEF2FF;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2;
        }
        
        .input-icon-box i {
            color: #6366F1;
            font-size: 20px;
        }
        
        .pwd-toggle {
            position: absolute;
            right: 16px;
            top: 14px;
            color: #94A3B8;
            cursor: pointer;
            font-size: 20px;
            z-index: 2;
            transition: color 0.3s;
        }
        
        .pwd-toggle:hover {
            color: #64748B;
        }
        
        .btn-primary.submit-btn {
            background: linear-gradient(135deg, #8B5CF6, #3B82F6) !important;
            border: none !important;
            color: white !important;
            box-shadow: 0 8px 16px rgba(99, 102, 241, 0.25) !important;
            transition: transform 0.2s, box-shadow 0.2s !important;
            font-weight: 600 !important;
            font-size: 15px !important;
            letter-spacing: 0.3px;
            height: 48px;
            border-radius: 12px !important;
        }
        
        .btn-primary.submit-btn:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 14px 28px rgba(99, 102, 241, 0.35) !important;
        }
        
        /* Graphic Background for Shield */
        .shield-graphic-area {
            position: relative;
            width: 140px;
            height: 140px;
            margin: 0 auto 5px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .orbit-circle {
            position: absolute;
            border: 1px dashed rgba(99, 102, 241, 0.2);
            border-radius: 50%;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        
        .shield-core {
            width: 70px;
            height: 80px;
            background: linear-gradient(135deg, #8B5CF6, #3B82F6);
            border-radius: 16px 16px 36px 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 12px 24px rgba(99, 102, 241, 0.3);
            position: relative;
            z-index: 10;
        }
        
        .floating-badge {
            position: absolute;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: white;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            z-index: 11;
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

    <div style="width: 100%; max-width: 480px;">
        <div class="panel animate-up">
            <div style="text-align: center; margin-bottom: 25px;">
                <div class="shield-graphic-area">
                    <!-- Orbit Rings -->
                    <div class="orbit-circle" style="width: 100px; height: 100px;"></div>
                    <div class="orbit-circle" style="width: 140px; height: 140px;"></div>
                    
                    <!-- Floating Elements -->
                    <div class="floating-badge" style="width: 24px; height: 24px; top: 15px; left: 15px; background: #DCFCE7;">
                        <i class='bx bx-check' style="color: #10B981; font-size: 16px; font-weight: bold;"></i>
                    </div>
                    <div class="floating-badge" style="width: 20px; height: 20px; bottom: 25px; right: 15px; background: #EEF2FF;">
                        <i class='bx bx-key' style="color: #818CF8; font-size: 12px;"></i>
                    </div>
                    
                    <!-- Tiny pluses -->
                    <i class='bx bx-plus' style="position: absolute; top: 10px; right: 30px; color: #CBD5E1; font-size: 10px;"></i>
                    <i class='bx bx-plus' style="position: absolute; bottom: 30px; left: 20px; color: #CBD5E1; font-size: 14px;"></i>
                    
                    <!-- Main Shield -->
                    <div class="shield-core">
                        <i class='bx bxs-lock-alt' style="font-size: 32px; color: #ffffff;"></i>
                    </div>
                </div>
                
                <h1 style="font-size: 26px; font-weight: 800; color: #0F172A; margin-bottom: 8px;">Secure Your Account</h1>
                <p style="color: #64748B; font-size: 14px; line-height: 1.5; max-width: 360px; margin: 0 auto;">First-time login detected. For your security, please set a new password to continue.</p>
            </div>

            <?php if($error): ?>
                <div style="background: #FEF2F2; color: #EF4444; padding: 14px; border-radius: 12px; font-size: 13.5px; margin-bottom: 24px; border: 1px solid #FEE2E2; display: flex; align-items: center; gap: 8px;">
                    <i class='bx bx-error-circle' style="font-size: 18px;"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="csrf" value="<?php echo getCsrfToken(); ?>">
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label>New Password</label>
                    <div style="position: relative;">
                        <div class="input-icon-box">
                            <i class='bx bx-lock-alt'></i>
                        </div>
                        <input type="password" name="new_password" placeholder="Enter new password" class="pwd-input" required>
                        <i class='bx bx-hide pwd-toggle'></i>
                    </div>
                    
                    <div style="display: flex; align-items: center; gap: 8px; margin-top: 10px; font-size: 12px; color: #64748B; padding-left: 6px;">
                        Password Strength: <span style="color: #F43F5E; font-weight: 600;">Weak</span>
                        <div style="display: flex; gap: 6px; margin-left: 8px;">
                            <div style="height: 4px; width: 24px; background: #F43F5E; border-radius: 2px;"></div>
                            <div style="height: 4px; width: 24px; background: #E2E8F0; border-radius: 2px;"></div>
                            <div style="height: 4px; width: 24px; background: #E2E8F0; border-radius: 2px;"></div>
                            <div style="height: 4px; width: 24px; background: #E2E8F0; border-radius: 2px;"></div>
                        </div>
                    </div>
                </div>
                
                <div class="form-group" style="margin-bottom: 24px;">
                    <label>Confirm Password</label>
                    <div style="position: relative;">
                        <div class="input-icon-box">
                            <i class='bx bx-lock-alt'></i>
                        </div>
                        <input type="password" name="confirm_password" placeholder="Confirm new password" class="pwd-input" required>
                        <i class='bx bx-hide pwd-toggle'></i>
                    </div>
                </div>
                
                <button type="submit" class="btn-primary submit-btn" style="width: 100%; justify-content: center;">
                    <i class='bx bx-lock-open-alt' style="font-size: 20px; margin-right: 8px;"></i> Update & Continue
                </button>
            </form>

            <!-- Security Priority Box -->
            <div class="security-priority-box" style="margin-top: 24px; background: #F8FAFC; border-radius: 12px; padding: 14px 16px; display: flex; align-items: center; gap: 12px; position: relative; overflow: hidden;">
                <div style="background: rgba(16, 185, 129, 0.15); width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i class='bx bx-check-shield' style="color: #10B981; font-size: 20px;"></i>
                </div>
                <div style="position: relative; z-index: 2;">
                    <div class="title" style="font-weight: 700; color: #0F172A; font-size: 13px; margin-bottom: 2px;">Your security is our priority</div>
                    <div class="desc" style="font-size: 12px; color: #64748B; line-height: 1.4; max-width: 250px;">Use a strong password that's different from previously used passwords.</div>
                </div>
                <!-- Faint lock graphic on the right -->
                <i class='bx bxs-lock-alt' style="position: absolute; right: -10px; bottom: -15px; font-size: 70px; color: rgba(148, 163, 184, 0.08); z-index: 1; transform: rotate(-10deg);"></i>
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

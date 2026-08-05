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
            background: transparent;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            padding-top: 20px;
            padding-bottom: 20px;
            font-family: 'Inter', sans-serif;
            box-sizing: border-box;
        }
        
        .logo-container {
            position: absolute;
            top: 20px;
            left: 20px;
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
        
        /* Advanced Background Graphic Styling */
        .bg-elements {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            z-index: -1;
            overflow: hidden;
            background: #F8FAFC;
        }
        .bg-blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.6;
        }
        .blob-top-right {
            top: -15%; right: -10%;
            width: 60%; height: 70%;
            background: radial-gradient(circle, #E0E7FF, rgba(243, 232, 255, 0.5));
        }
        .blob-bottom-left {
            bottom: -20%; left: -15%;
            width: 50%; height: 60%;
            background: rgba(243, 232, 255, 0.7);
        }
        .blob-bottom-right {
            bottom: -10%; right: 10%;
            width: 50%; height: 60%;
            background: rgba(224, 231, 255, 0.6);
        }
        .bg-pattern {
            position: absolute;
        }
        .pattern-dots {
            top: 5%; right: 15%;
            width: 250px; height: 250px;
            background-image: radial-gradient(#C7D2FE 2px, transparent 2px);
            background-size: 24px 24px;
            opacity: 0.4;
        }
        .pattern-waves {
            top: 25%; right: -10%;
            width: 500px; height: 600px;
            background-image: repeating-radial-gradient(circle at 100% 50%, transparent, transparent 15px, rgba(199, 210, 254, 0.15) 16px, rgba(199, 210, 254, 0.15) 17px);
            opacity: 0.8;
        }
        .bg-ring {
            position: absolute;
            border: 2px solid rgba(199, 210, 254, 0.4);
            border-radius: 50%;
        }
        .ring-1 {
            top: 15%; left: 15%;
            width: 40px; height: 40px;
        }
        .ring-2 {
            bottom: 30%; left: 10%;
            width: 80px; height: 80px;
        }
        .bg-shield {
            position: absolute;
            bottom: -5%; right: 5%;
            font-size: 350px;
            color: rgba(199, 210, 254, 0.15);
            transform: rotate(15deg);
        }
        
        /* Dark Theme Fixes */
        .dark-theme .bg-elements { background: #0F172A; }
        .dark-theme .blob-top-right { background: radial-gradient(circle, #1E293B, rgba(30, 41, 59, 0.5)); opacity: 0.4;}
        .dark-theme .blob-bottom-left { background: #1E293B; opacity: 0.4; }
        .dark-theme .blob-bottom-right { background: #1E293B; opacity: 0.4;}
        .dark-theme .pattern-dots { background-image: radial-gradient(#334155 2px, transparent 2px); }
        .dark-theme .pattern-waves { background-image: repeating-radial-gradient(circle at 100% 50%, transparent, transparent 15px, rgba(51, 65, 85, 0.2) 16px, rgba(51, 65, 85, 0.2) 17px); }
        .dark-theme .bg-ring { border-color: rgba(51, 65, 85, 0.5); }
        .dark-theme .bg-shield { color: rgba(51, 65, 85, 0.2); }
        .dark-theme body { background: transparent; }
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
            padding: 20px 24px !important;
        }
        
        .form-group label {
            font-size: 13px;
            color: #0F172A !important;
            font-weight: 700;
            margin-bottom: 6px;
            display: block;
            text-transform: none;
            letter-spacing: normal;
        }
        
        .form-group input {
            background: #FFFFFF !important;
            border: 1px solid #E2E8F0 !important;
            border-radius: 10px !important;
            color: #0F172A !important;
            height: 40px !important;
            transition: all 0.3s ease !important;
            box-shadow: none !important;
            font-weight: 500;
            font-size: 13.5px;
            padding-left: 44px !important;
            padding-right: 44px !important;
        }
        
        .form-group input::placeholder {
            color: #94A3B8;
            font-weight: 400;
        }
        
        .form-group input:focus {
            border-color: #818CF8 !important;
            box-shadow: 0 0 0 3px rgba(129, 140, 248, 0.15) !important;
            outline: none !important;
        }
        
        .input-icon-box {
            position: absolute;
            left: 4px;
            top: 4px;
            width: 32px;
            height: 32px;
            background: #EEF2FF;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2;
        }
        
        .input-icon-box i {
            color: #6366F1;
            font-size: 18px;
        }
        
        .pwd-toggle {
            position: absolute;
            right: 14px;
            top: 12px;
            color: #94A3B8;
            cursor: pointer;
            font-size: 18px;
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
            box-shadow: 0 6px 12px rgba(99, 102, 241, 0.25) !important;
            transition: transform 0.2s, box-shadow 0.2s !important;
            font-weight: 600 !important;
            font-size: 14px !important;
            letter-spacing: 0.2px;
            height: 40px;
            border-radius: 10px !important;
        }
        
        .btn-primary.submit-btn:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.35) !important;
        }
        
        /* Graphic Background for Shield */
        .shield-graphic-area {
            position: relative;
            width: 110px;
            height: 110px;
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
            width: 58px;
            height: 66px;
            background: linear-gradient(135deg, #8B5CF6, #3B82F6);
            border-radius: 14px 14px 30px 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3);
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
    <div class="bg-elements">
        <div class="bg-blob blob-top-right"></div>
        <div class="bg-blob blob-bottom-left"></div>
        <div class="bg-blob blob-bottom-right"></div>
        <div class="bg-pattern pattern-dots"></div>
        <div class="bg-pattern pattern-waves"></div>
        <div class="bg-ring ring-1"></div>
        <div class="bg-ring ring-2"></div>
        <i class='bx bx-shield bg-shield'></i>
    </div>

    <div class="logo-container">
        <div style="position: relative; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; background: #EEF2FF; border-radius: 12px; border: 1.5px solid #C7D2FE;">
            <i class='bx bxs-building-house' style="color: #6366F1; font-size: 26px;"></i>
        </div>
        <div style="line-height: 1.1;">
            <div style="font-weight: 800; color: #1E293B; font-size: 20px;">Madhav Kunj</div>
            <div style="font-weight: 700; color: #6366F1; font-size: 10px; letter-spacing: 3.5px;">RESIDENCE</div>
        </div>
    </div>

    <div style="width: 100%; max-width: 450px;">
        <div class="panel animate-up">
            <div style="text-align: center; margin-bottom: 12px;">
                <div class="shield-graphic-area">
                    <!-- Orbit Rings -->
                    <div class="orbit-circle" style="width: 80px; height: 80px;"></div>
                    <div class="orbit-circle" style="width: 110px; height: 110px;"></div>
                    
                    <!-- Floating Elements -->
                    <div class="floating-badge" style="width: 20px; height: 20px; top: 12px; left: 12px; background: #DCFCE7;">
                        <i class='bx bx-check' style="color: #10B981; font-size: 14px; font-weight: bold;"></i>
                    </div>
                    <div class="floating-badge" style="width: 18px; height: 18px; bottom: 20px; right: 10px; background: #EEF2FF;">
                        <i class='bx bx-key' style="color: #818CF8; font-size: 10px;"></i>
                    </div>
                    
                    <!-- Tiny pluses -->
                    <i class='bx bx-plus' style="position: absolute; top: 10px; right: 20px; color: #CBD5E1; font-size: 10px;"></i>
                    <i class='bx bx-plus' style="position: absolute; bottom: 25px; left: 15px; color: #CBD5E1; font-size: 12px;"></i>
                    
                    <!-- Main Shield -->
                    <div class="shield-core">
                        <i class='bx bxs-lock-alt' style="font-size: 26px; color: #ffffff;"></i>
                    </div>
                </div>
                
                <h1 style="font-size: 22px; font-weight: 800; color: #0F172A; margin-bottom: 4px;">Secure Your Account</h1>
                <p style="color: #64748B; font-size: 13px; line-height: 1.4; max-width: 320px; margin: 0 auto;">First-time login detected. For your security, please set a new password to continue.</p>
            </div>

            <?php if($error): ?>
                <div style="background: #FEF2F2; color: #EF4444; padding: 14px; border-radius: 12px; font-size: 13.5px; margin-bottom: 24px; border: 1px solid #FEE2E2; display: flex; align-items: center; gap: 8px;">
                    <i class='bx bx-error-circle' style="font-size: 18px;"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="csrf" value="<?php echo getCsrfToken(); ?>">
                
                <div class="form-group" style="margin-bottom: 12px;">
                    <label>New Password</label>
                    <div style="position: relative;">
                        <div class="input-icon-box">
                            <i class='bx bx-lock-alt'></i>
                        </div>
                        <input type="password" name="new_password" placeholder="Enter new password" class="pwd-input" required>
                        <i class='bx bx-hide pwd-toggle'></i>
                    </div>
                    
                    <div style="display: flex; align-items: center; gap: 8px; margin-top: 6px; font-size: 11px; color: #64748B; padding-left: 6px;">
                        Password Strength: <span style="color: #F43F5E; font-weight: 600;">Weak</span>
                        <div style="display: flex; gap: 4px; margin-left: 6px;">
                            <div style="height: 4px; width: 20px; background: #F43F5E; border-radius: 2px;"></div>
                            <div style="height: 4px; width: 20px; background: #E2E8F0; border-radius: 2px;"></div>
                            <div style="height: 4px; width: 20px; background: #E2E8F0; border-radius: 2px;"></div>
                            <div style="height: 4px; width: 20px; background: #E2E8F0; border-radius: 2px;"></div>
                        </div>
                    </div>
                </div>
                
                <div class="form-group" style="margin-bottom: 16px;">
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
                    <i class='bx bx-lock-open-alt' style="font-size: 18px; margin-right: 6px;"></i> Update & Continue
                </button>
            </form>
            
            <div class="security-priority-box" style="margin-top: 16px; background: #F8FAFC; border-radius: 12px; padding: 10px 14px; display: flex; align-items: center; gap: 10px; position: relative; overflow: hidden;">
                <div style="background: rgba(16, 185, 129, 0.15); width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i class='bx bx-check-shield' style="color: #10B981; font-size: 18px;"></i>
                </div>
                <div style="position: relative; z-index: 2;">
                    <div class="title" style="font-weight: 700; color: #0F172A; font-size: 12px; margin-bottom: 2px;">Your security is our priority</div>
                    <div class="desc" style="font-size: 11px; color: #64748B; line-height: 1.4; max-width: 220px;">Use a strong password that's different from previously used passwords.</div>
                </div>
                <!-- Faint lock graphic on the right -->
                <i class='bx bxs-lock-alt' style="position: absolute; right: -8px; bottom: -12px; font-size: 60px; color: rgba(148, 163, 184, 0.08); z-index: 1; transform: rotate(-10deg);"></i>
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
<?php include_once __DIR__ . '/views/mobile/mobile_bottom_nav.php'; ?>
</body>
</html>

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
            background: var(--bg-main);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
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
            <div style="text-align: center; margin-bottom: 32px;">
                <div style="width: 64px; height: 64px; background: rgba(98, 75, 255, 0.1); border-radius: 16px; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                    <i class='bx bx-shield-quarter' style="font-size: 32px; color: var(--primary-purple);"></i>
                </div>
                <h1 style="font-size: 24px; font-weight: 800; color: var(--text-dark); margin-bottom: 8px;">Secure Your Account</h1>
                <p style="color: var(--text-gray); font-size: 14px; line-height: 1.5;">First-time login detected. Please set a new password to continue.</p>
            </div>

            <?php if($error): ?>
                <div style="background: #FEF2F2; color: #EF4444; padding: 12px; border-radius: 12px; font-size: 13px; margin-bottom: 24px; border: 1px solid #FEE2E2; display: flex; align-items: center; gap: 8px;">
                    <i class='bx bx-error-circle'></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if($success): ?>
                <div style="background: #F0FDF4; color: #10B981; padding: 12px; border-radius: 12px; font-size: 13px; margin-bottom: 24px; border: 1px solid #DCFCE7; display: flex; align-items: center; gap: 8px;">
                    <i class='bx bx-check-circle'></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
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
                <button type="submit" class="btn-primary" style="width: 100%; justify-content: center; padding: 14px; border-radius: 12px;">
                    <i class='bx bx-lock-open-alt'></i> Update & Continue
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
</body>
</html>

<?php
// admin/login.php - Robust Admin Login with Premium Design
require_once "../db.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isset($_SESSION['admin'])) {
    header("Location: dashboard.php");
    exit;
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if ($username === '' || $password === '') {
        $error = "Please provide both username and password.";
    } else {
        // Use the singular 'admin' table as confirmed by the user
        $stmt = mysqli_prepare($conn, "SELECT id, username, password FROM admin WHERE username = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $username);
            if (mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt);
                
                if ($result && mysqli_num_rows($result) === 1) {
                    $admin = mysqli_fetch_assoc($result);
                    
                    if (password_verify($password, $admin['password'])) {
                        // SUCCESS
                        @session_regenerate_id(true);
                        
                        $_SESSION['admin'] = $admin['username'];
                        $_SESSION['admin_id'] = (int)$admin['id'];
                        $_SESSION['login_time'] = time();

                        // Track Login
                        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
                        $ip_esc = mysqli_real_escape_string($conn, $ip);
                        $admin_id = (int)$admin['id'];
                        @mysqli_query($conn, "INSERT INTO login_logs (user_id, user_type, ip_address, login_time) VALUES ($admin_id, 'admin', '$ip_esc', NOW())");
                        $_SESSION['login_log_id'] = mysqli_insert_id($conn);

                        header("Location: dashboard.php");
                        exit;
                    } else {
                        $error = "Invalid admin username or password.";
                    }
                } else {
                    $error = "Invalid admin username or password.";
                }
            } else {
                $error = "Execution failed: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        } else {
            $error = "Database preparation failed. The 'admin' table might be missing or corrupted.";
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Admin Login - Property Administration</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <link rel="icon" type="image/png" href="../assets/img/favicon.png">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
  <style>
    :root {
        --primary-purple: #624BFF;
        --primary-hover: #5038E6;
        --text-dark: #0F172A;
        --text-gray: #64748B;
        --border: #E2E8F0;
        --white: #FFFFFF;
        --bg: #F8FAFC;
    }
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
    body {
        background-color: var(--bg);
        color: var(--text-dark);
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 100vh;
        position: relative;
        overflow: hidden;
    }
    
    /* Decorative Background Elements */
    .bg-circle-1 {
        position: absolute; width: 600px; height: 600px; background: rgba(98, 75, 255, 0.05);
        border-radius: 50%; top: -200px; left: -150px; filter: blur(60px); z-index: 1;
    }
    .bg-circle-2 {
        position: absolute; width: 500px; height: 500px; background: rgba(16, 185, 129, 0.05);
        border-radius: 50%; bottom: -100px; right: -100px; filter: blur(60px); z-index: 1;
    }

    .login-container {
        width: 100%; max-width: 420px; z-index: 2; padding: 20px;
    }
    
    .login-card {
        background: var(--white);
        border: 1px solid var(--border);
        border-radius: 24px;
        padding: 40px 32px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.04);
        position: relative;
    }
    
    .back-btn {
        position: absolute; top: 20px; left: 24px; display: inline-flex; align-items: center; gap: 6px;
        color: var(--text-gray); font-size: 13px; font-weight: 600; text-decoration: none;
        transition: color 0.2s ease;
    }
    .back-btn:hover { color: var(--primary-purple); }

    .logo-box {
        width: 64px; height: 64px; background: #F5F3FF; border-radius: 16px;
        display: flex; align-items: center; justify-content: center;
        color: var(--primary-purple); font-size: 32px; margin: 0 auto 24px auto;
        box-shadow: 0 8px 16px rgba(98, 75, 255, 0.15);
    }

    .login-header { text-align: center; margin-bottom: 32px; }
    .login-header h1 { font-size: 24px; font-weight: 800; color: var(--text-dark); margin-bottom: 8px; letter-spacing: -0.5px; }
    .login-header p { font-size: 14px; color: var(--text-gray); font-weight: 500; }

    .form-group { margin-bottom: 20px; }
    .form-label { display: block; font-size: 13px; font-weight: 600; color: var(--text-dark); margin-bottom: 8px; margin-left: 4px; }
    
    .input-wrapper { position: relative; }
    .input-wrapper i.icon-left { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-gray); font-size: 18px; }
    .input-wrapper i.icon-right { position: absolute; right: 16px; top: 50%; transform: translateY(-50%); color: var(--text-gray); font-size: 18px; cursor: pointer; transition: color 0.2s; }
    .input-wrapper i.icon-right:hover { color: var(--primary-purple); }

    .form-input {
        width: 100%; padding: 14px 16px 14px 44px; font-size: 14px; color: var(--text-dark);
        background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px;
        outline: none; transition: all 0.2s ease; font-weight: 500;
    }
    .form-input:focus { background: var(--white); border-color: var(--primary-purple); box-shadow: 0 0 0 4px rgba(98, 75, 255, 0.1); }
    
    .btn-submit {
        width: 100%; padding: 14px; background: var(--primary-purple); color: var(--white);
        border: none; border-radius: 12px; font-size: 14px; font-weight: 700; cursor: pointer;
        transition: all 0.2s ease; box-shadow: 0 4px 12px rgba(98, 75, 255, 0.25);
        display: flex; align-items: center; justify-content: center; gap: 8px;
        margin-top: 24px;
    }
    .btn-submit:hover { background: var(--primary-hover); box-shadow: 0 6px 16px rgba(98, 75, 255, 0.3); transform: translateY(-1px); }

    .error-box {
        background: #FEF2F2; border: 1px solid #FCA5A5; color: #DC2626;
        padding: 12px 16px; border-radius: 12px; font-size: 13px; font-weight: 600;
        margin-bottom: 24px; display: flex; align-items: center; gap: 8px;
    }

    .footer-link {
        text-align: center; margin-top: 24px; font-size: 13px; color: var(--text-gray); font-weight: 500;
    }
    .footer-link a { color: var(--primary-purple); font-weight: 700; text-decoration: none; transition: color 0.2s; }
    .footer-link a:hover { color: var(--primary-hover); }
  </style>
</head>
<body>

<div class="bg-circle-1"></div>
<div class="bg-circle-2"></div>

<div class="login-container">
    <div class="login-card">
        <a href="../index.php" class="back-btn"><i class='bx bx-left-arrow-alt'></i> Home</a>
        
        <div class="login-header">
            <div class="logo-box">
                <i class='bx bx-building-house'></i>
            </div>
            <h1>Admin Login</h1>
            <p>Property Administration Panel</p>
        </div>

        <?php if ($error !== ""): ?>
            <div class="error-box">
                <i class='bx bx-error-circle'></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <div class="form-group">
                <label class="form-label">Username</label>
                <div class="input-wrapper">
                    <i class='bx bx-user icon-left'></i>
                    <input type="text" name="username" class="form-input" placeholder="Enter admin username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required autofocus>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Password</label>
                <div class="input-wrapper">
                    <i class='bx bx-lock-alt icon-left'></i>
                    <input type="password" name="password" id="loginPassword" class="form-input" placeholder="••••••••" required style="padding-right: 44px;">
                    <i class='bx bx-hide icon-right' id="togglePassword"></i>
                </div>
            </div>

            <button type="submit" name="login" class="btn-submit">Sign In <i class='bx bx-right-arrow-alt' style="font-size: 18px;"></i></button>
        </form>

        <div class="footer-link">
            Not an admin? <a href="../login.php">Resident Login</a>
        </div>
    </div>
    
    <div style="text-align: center; margin-top: 24px; font-size: 13px; color: var(--text-gray); font-weight: 500;">
        © <?php echo date("Y"); ?> Rent Manager System
    </div>
</div>

<script>
    const togglePassword = document.querySelector('#togglePassword');
    const password = document.querySelector('#loginPassword');

    togglePassword.addEventListener('click', function () {
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
        this.classList.toggle('bx-show');
        this.classList.toggle('bx-hide');
    });
</script>
</body>
</html>

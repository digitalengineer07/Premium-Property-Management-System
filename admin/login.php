<?php
// admin/login.php - Split-layout Admin Login
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
        $stmt = mysqli_prepare($conn, "SELECT id, username, password FROM admin WHERE username = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $username);
            if (mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt);
                
                if ($result && mysqli_num_rows($result) === 1) {
                    $admin = mysqli_fetch_assoc($result);
                    
                    if (password_verify($password, $admin['password'])) {
                        @session_regenerate_id(true);
                        
                        $_SESSION['admin'] = $admin['username'];
                        $_SESSION['admin_id'] = (int)$admin['id'];
                        $_SESSION['login_time'] = time();

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
            $error = "Database preparation failed.";
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
    }
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
    
    body {
        background: linear-gradient(135deg, #F8FAFC 0%, #EBF4FF 50%, #E0E7FF 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    .split-layout {
        display: flex;
        width: 100%;
        max-width: 1200px;
        height: 100vh;
        max-height: 800px;
        background: transparent;
        margin: 0 auto;
        padding: 20px;
        gap: 20px;
    }

    /* Left Panel Styles */
    .left-panel {
        flex: 1.1;
        display: flex;
        flex-direction: column;
        justify-content: center;
        position: relative;
        padding: 20px;
    }

    .brand {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 24px;
    }
    .brand-logo {
        width: 42px; height: 42px; background: transparent; 
        color: var(--primary-purple); font-size: 46px;
        display: flex; align-items: center; justify-content: center;
    }
    .brand-text h2 { font-size: 18px; font-weight: 800; color: var(--text-dark); line-height: 1.2; }
    .brand-text p { font-size: 12px; color: var(--text-gray); font-weight: 500; }

    .hero-title {
        font-size: 36px; font-weight: 800; color: var(--text-dark);
        line-height: 1.1; margin-bottom: 12px; letter-spacing: -1px;
    }
    .hero-title span { color: var(--primary-purple); }
    .hero-subtitle {
        font-size: 15px; color: var(--text-gray); font-weight: 500; line-height: 1.4;
        margin-bottom: 32px; max-width: 380px;
    }

    .feature-list { display: flex; flex-direction: column; gap: 16px; z-index: 2; position: relative; }
    .feature-item { display: flex; align-items: flex-start; gap: 12px; }
    .feature-icon {
        width: 36px; height: 36px; background: linear-gradient(135deg, #8B5CF6 0%, #6D28D9 100%); color: var(--white);
        border-radius: 10px; display: flex; align-items: center; justify-content: center;
        font-size: 18px; flex-shrink: 0; box-shadow: 0 4px 12px rgba(109, 40, 217, 0.25);
    }
    .feature-text h4 { font-size: 14px; font-weight: 700; color: var(--text-dark); margin-bottom: 2px; }
    .feature-text p { font-size: 12px; color: var(--text-gray); font-weight: 500; max-width: 300px; line-height: 1.3; }

    .bg-illustration {
        position: absolute;
        bottom: -40px;
        left: 0;
        width: 90%;
        max-width: 520px;
        height: auto;
        z-index: 1;
        pointer-events: none;
    }
    .bg-circle {
        position: absolute; width: 480px; height: 480px;
        border-radius: 50%; bottom: 20px; right: -40px; z-index: 0;
        background: radial-gradient(circle, rgba(167,139,250,0.3) 0%, rgba(139,92,246,0.15) 60%, rgba(139,92,246,0.02) 100%);
    }
    .bg-rings {
        position: absolute; width: 620px; height: 620px;
        border-radius: 50%; bottom: -50px; right: -110px; z-index: 0;
        border: 1px solid rgba(139,92,246,0.1);
        box-shadow: inset 0 0 0 20px transparent, inset 0 0 0 21px rgba(139,92,246,0.08), inset 0 0 0 40px transparent, inset 0 0 0 41px rgba(139,92,246,0.05), inset 0 0 0 60px transparent, inset 0 0 0 61px rgba(139,92,246,0.03);
    }
    .dot-grid {
        position: absolute;
        top: 35%; left: 30px;
        width: 120px; height: 180px;
        background-image: radial-gradient(rgba(139,92,246,0.15) 2.5px, transparent 2.5px);
        background-size: 16px 16px;
        z-index: 0;
    }
    .birds {
        position: absolute;
        top: 10%; right: 60px;
        width: 120px; height: auto;
        opacity: 0.7;
        z-index: 0;
    }

    /* Right Panel Styles (Login Card) */
    .right-panel {
        flex: 0.9;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        z-index: 2;
    }

    .login-card {
        background: var(--white);
        width: 100%; max-width: 440px;
        border-radius: 24px;
        padding: 24px 32px;
        box-shadow: 0 20px 50px rgba(0,0,0,0.06);
        position: relative;
    }

    .card-logo {
        width: 48px; height: 48px; background: #F8FAFC; border-radius: 50%;
        margin: 0 auto 12px auto; display: flex; align-items: center; justify-content: center;
        color: var(--primary-purple); font-size: 24px; box-shadow: inset 0 2px 10px rgba(0,0,0,0.02);
    }

    .login-header { text-align: center; margin-bottom: 16px; }
    .login-header h1 { font-size: 22px; font-weight: 800; color: var(--text-dark); margin-bottom: 4px; letter-spacing: -0.5px; }
    .login-header p { font-size: 12px; color: var(--text-gray); font-weight: 500; }
    .header-line { width: 32px; height: 3px; background: var(--primary-purple); margin: 12px auto 0 auto; border-radius: 4px; }

    .form-group { margin-bottom: 12px; }
    .form-label { display: block; font-size: 12px; font-weight: 700; color: var(--text-dark); margin-bottom: 8px; margin-left: 4px; }
    
    .input-wrapper { position: relative; }
    .input-wrapper i.icon-left { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--primary-purple); font-size: 18px; }
    .input-wrapper i.icon-right { position: absolute; right: 14px; top: 50%; transform: translateY(-50%); color: var(--text-gray); font-size: 18px; cursor: pointer; transition: color 0.2s; }
    .input-wrapper i.icon-right:hover { color: var(--primary-purple); }

    .form-input {
        width: 100%; padding: 10px 14px 10px 42px; font-size: 14px; color: var(--text-dark);
        background: #ffffff; border: 1.5px solid #E2E8F0; border-radius: 10px;
        outline: none; transition: all 0.2s ease; font-weight: 500;
    }
    .form-input:focus { border-color: var(--primary-purple); box-shadow: 0 0 0 3px rgba(98, 75, 255, 0.1); background: #ffffff; }

    .form-options {
        display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; margin-top: -2px;
    }
    .remember-me { display: flex; align-items: center; gap: 6px; cursor: pointer; }
    .remember-me input[type="checkbox"] { 
        appearance: none; width: 16px; height: 16px; border: 1.5px solid var(--primary-purple); border-radius: 4px;
        outline: none; cursor: pointer; position: relative; background: var(--primary-purple);
        display: flex; align-items: center; justify-content: center;
    }
    .remember-me input[type="checkbox"]::after {
        content: '\eb7b'; font-family: 'boxicons'; color: white; font-size: 12px; font-weight: bold;
    }
    .remember-me span { font-size: 12px; color: var(--text-gray); font-weight: 600; }
    
    .forgot-link { font-size: 12px; color: var(--primary-purple); font-weight: 700; text-decoration: none; }
    .forgot-link:hover { text-decoration: underline; }

    .btn-submit {
        width: 100%; padding: 12px; background: var(--primary-purple); color: var(--white);
        border: none; border-radius: 10px; font-size: 14px; font-weight: 700; cursor: pointer;
        transition: all 0.2s ease; box-shadow: 0 6px 16px rgba(98, 75, 255, 0.25);
        display: flex; align-items: center; justify-content: center; gap: 8px;
    }
    .btn-submit:hover { background: var(--primary-hover); transform: translateY(-1px); box-shadow: 0 8px 20px rgba(98, 75, 255, 0.35); }

    .divider {
        display: flex; align-items: center; margin: 16px 0; color: #94A3B8; font-size: 11px; font-weight: 600; text-transform: uppercase;
    }
    .divider::before, .divider::after { content: ''; flex: 1; height: 1px; background: #E2E8F0; }
    .divider::before { margin-right: 12px; }
    .divider::after { margin-left: 12px; }

    .btn-resident {
        width: 100%; padding: 12px; background: transparent; color: var(--primary-purple);
        border: 1.5px solid var(--primary-purple); border-radius: 10px; font-size: 14px; font-weight: 700; 
        cursor: pointer; text-decoration: none; transition: all 0.2s ease;
        display: flex; align-items: center; justify-content: center; gap: 8px;
    }
    .btn-resident:hover { background: rgba(98,75,255,0.05); }

    .secure-footer {
        text-align: center; margin-top: 16px; display: flex; align-items: center; justify-content: center; gap: 6px;
        color: var(--text-gray); font-size: 11px; font-weight: 600;
    }

    .error-box {
        background: #FEF2F2; border: 1px solid #FCA5A5; color: #DC2626;
        padding: 12px 16px; border-radius: 12px; font-size: 13px; font-weight: 600;
        margin-bottom: 24px; display: flex; align-items: center; gap: 8px;
    }

    @media (max-width: 992px) {
        .split-layout { flex-direction: column; padding: 20px; }
        .left-panel { padding: 20px; display: none; }
        .right-panel { padding: 0; }
    }
  </style>
</head>
<body>

<div class="split-layout">
    
    <!-- Left Promotional Panel -->
    <div class="left-panel">
        <div class="brand">
            <div class="brand-logo"><i class='bx bx-building-house'></i></div>
            <div class="brand-text">
                <h2>Madhav Kunj</h2>
                <p>Utility Management</p>
            </div>
        </div>

        <h1 class="hero-title">Smart Property<br><span>Management</span></h1>
        <p class="hero-subtitle">Manage your properties, residents, bills and payments with ease.</p>

        <div class="feature-list">
            <div class="feature-item">
                <div class="feature-icon"><i class='bx bx-user'></i></div>
                <div class="feature-text">
                    <h4>Resident Management</h4>
                    <p>Add, manage & communicate with residents</p>
                </div>
            </div>
            <div class="feature-item">
                <div class="feature-icon"><i class='bx bx-receipt'></i></div>
                <div class="feature-text">
                    <h4>Smart Billing</h4>
                    <p>Generate bills & track payments easily</p>
                </div>
            </div>
            <div class="feature-item">
                <div class="feature-icon"><i class='bx bx-bolt-circle'></i></div>
                <div class="feature-text">
                    <h4>Electricity Tracking</h4>
                    <p>Monitor usage, records & electricity bills</p>
                </div>
            </div>
            <div class="feature-item">
                <div class="feature-icon"><i class='bx bx-bar-chart-alt-2'></i></div>
                <div class="feature-text">
                    <h4>Reports & Analytics</h4>
                    <p>Real-time insights & financial reports</p>
                </div>
            </div>
        </div>
        
        <div class="dot-grid"></div>
        <svg class="birds" viewBox="0 0 100 50" xmlns="http://www.w3.org/2000/svg">
            <path d="M10,20 Q15,10 20,18 Q25,10 30,20" fill="none" stroke="#64748B" stroke-width="1.5" stroke-linecap="round"/>
            <path d="M45,8 Q50,-2 55,6 Q60,-2 65,8" fill="none" stroke="#64748B" stroke-width="1.5" stroke-linecap="round"/>
            <path d="M75,25 Q80,15 85,23 Q90,15 95,25" fill="none" stroke="#64748B" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
        <div class="bg-circle"></div>
        <div class="bg-rings"></div>
        <img src="../assets/img/login_building.png" class="bg-illustration" alt="Building Illustration">
    </div>

    <!-- Right Login Card Panel -->
    <div class="right-panel">
        <div class="login-card">
            
            <div class="card-logo">
                <i class='bx bx-building-house'></i>
            </div>
            
            <div class="login-header">
                <h1>Welcome Back!</h1>
                <p>Sign in to your admin account</p>
                <div class="header-line"></div>
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
                        <input type="text" name="username" class="form-input" placeholder="admin" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required autofocus>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div class="input-wrapper">
                        <i class='bx bx-lock-alt icon-left'></i>
                        <input type="password" name="password" id="loginPassword" class="form-input" placeholder="••••••••••••" required style="padding-right: 48px;">
                        <i class='bx bx-hide icon-right' id="togglePassword"></i>
                    </div>
                </div>

                <div class="form-options">
                    <label class="remember-me">
                        <input type="checkbox" checked>
                        <span>Remember me</span>
                    </label>
                    <a href="#" class="forgot-link">Forgot Password?</a>
                </div>

                <button type="submit" name="login" class="btn-submit">
                    <i class='bx bx-log-in-circle' style="font-size: 20px;"></i> Sign In
                </button>
            </form>

            <div class="divider">or</div>

            <a href="../login.php" class="btn-resident">
                <i class='bx bx-shield-quarter'></i> Resident Login
            </a>

            <div class="secure-footer">
                <i class='bx bx-check-shield' style="font-size: 16px;"></i> Secure & Protected Login
            </div>
        </div>
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

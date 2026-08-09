<?php
// login.php (renter) — Clean Professional Split Design
require_once "db.php";
session_start();

/* CSRF token */
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: renter/dashboard.php");
    exit;
}
// Removed undefined updateLastSeen() call

$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
        $error = "Session expired or invalid form submission. Please refresh the page.";
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if ($username === '' || $password === '') {
            $error = "Please provide both username and password.";
        } else {
            $stmt = mysqli_prepare($conn, "SELECT id, username, password, status FROM users WHERE username = ?");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "s", $username);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                
                if ($result && mysqli_num_rows($result) === 1) {
                    $user = mysqli_fetch_assoc($result);
                    if ($user['status'] === 'moved_out') {
                        $error = "This account has been archived (Moved Out). Access is restricted.";
                    } else if (password_verify($password, $user['password'])) {
                        // Success!
                        session_regenerate_id(true);
                        $_SESSION['user_id'] = (int)$user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['csrf'] = bin2hex(random_bytes(32));

                        // --- START LOGIN TRACKING ---
                        $user_id_log = (int)$user['id'];
                        $ip = $_SERVER['REMOTE_ADDR'];
                        $ip_esc = mysqli_real_escape_string($conn, $ip);
                        
                        // Use @ to suppress if table doesn't exist yet
                        @mysqli_query($conn, "INSERT INTO login_logs (user_id, user_type, ip_address, login_time) VALUES ($user_id_log, 'renter', '$ip_esc', NOW())");
                        $_SESSION['login_log_id'] = mysqli_insert_id($conn);
                        @mysqli_query($conn, "UPDATE users SET last_login = NOW() WHERE id = $user_id_log");
                        // --- END LOGIN TRACKING ---

                        // Check for mandatory password change (graceful if column missing)
                        $force_row = ['must_change_password' => 0];
                        $check_col = @mysqli_query($conn, "SELECT must_change_password FROM users WHERE id = $user_id_log");
                        if ($check_col) {
                            $force_row = mysqli_fetch_assoc($check_col);
                        }
                        
                        if ($force_row && $force_row['must_change_password']) {
                            $_SESSION['must_change_password'] = true;
                            header("Location: renter/force-password.php");
                        } else {
                            header("Location: renter/dashboard.php");
                        }
                        exit;
                    } else {
                        $error = "Invalid username or password.";
                    }
                } else {
                    $error = "Invalid username or password.";
                }
                mysqli_stmt_close($stmt);
            } else {
                $error = "System error. Please contact administrator.";
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Resident Login - Property Administration</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <link rel="icon" type="image/png" href="assets/img/favicon.png">
  <link rel="manifest" href="manifest.json">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
  
  <script>
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', () => {
        navigator.serviceWorker.register('sw.js').then(reg => {
          console.log('SW registered');
        }).catch(err => {
          console.log('SW failed', err);
        });
      });
    }
  </script>
  <script src="assets/js/pwa.js" defer></script>
  
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
        background: linear-gradient(135deg, #F0F4FF 0%, #E2E8F0 50%, #DBEAFE 100%);
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
    .feature-item { display: flex; align-items: flex-start; gap: 12px; position: relative; z-index: 3; }
    .feature-icon {
        width: 36px; height: 36px; background: linear-gradient(135deg, #7C3AED 0%, #5B21B6 100%); color: var(--white);
        border-radius: 12px; display: flex; align-items: center; justify-content: center;
        font-size: 18px; flex-shrink: 0; box-shadow: 0 6px 16px rgba(124, 58, 237, 0.25);
    }
    .feature-text {
        /* Text wrapper */
    }
    .feature-text h4 { font-size: 14px; font-weight: 800; color: var(--text-dark); margin-bottom: 2px; text-shadow: 0 0 15px rgba(255,255,255,1); }
    .feature-text p { font-size: 12px; color: var(--text-gray); font-weight: 600; max-width: 300px; line-height: 1.3; text-shadow: 0 0 15px rgba(255,255,255,1); }

    .bg-illustration {
        position: absolute;
        bottom: -30px;
        right: -40px;
        left: auto;
        width: 100%;
        max-width: 420px;
        height: auto;
        z-index: 1;
        pointer-events: none;
    }
    .bg-circle {
        position: absolute; width: 480px; height: 480px;
        border-radius: 50%; bottom: 20px; right: -40px; z-index: 0;
        background: radial-gradient(circle, rgba(124, 58, 237, 0.15) 0%, rgba(124, 58, 237, 0.05) 60%, transparent 100%);
    }
    .bg-rings {
        position: absolute; width: 620px; height: 620px;
        border-radius: 50%; bottom: -50px; right: -110px; z-index: 0;
        border: 1px solid rgba(124, 58, 237, 0.08);
        box-shadow: inset 0 0 0 20px transparent, inset 0 0 0 21px rgba(124, 58, 237, 0.06), inset 0 0 0 40px transparent, inset 0 0 0 41px rgba(124, 58, 237, 0.04), inset 0 0 0 60px transparent, inset 0 0 0 61px rgba(124, 58, 237, 0.02);
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
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(24px);
        -webkit-backdrop-filter: blur(24px);
        border: 1px solid rgba(255, 255, 255, 0.9);
        width: 100%; max-width: 440px;
        border-radius: 28px;
        padding: 36px 40px;
        box-shadow: 0 24px 60px rgba(98, 75, 255, 0.08), 0 0 0 1px rgba(255, 255, 255, 0.8) inset;
        position: relative;
        overflow: hidden;
    }

    .login-card > * {
        position: relative;
        z-index: 1;
    }

    .login-card::before {
        content: '';
        position: absolute;
        top: -10px;
        left: -10px;
        width: 160px;
        height: 160px;
        background-image: radial-gradient(rgba(98, 75, 255, 0.25) 2px, transparent 2px);
        background-size: 16px 16px;
        pointer-events: none;
        z-index: 0;
        -webkit-mask-image: radial-gradient(circle at top left, black 30%, transparent 80%);
        mask-image: radial-gradient(circle at top left, black 30%, transparent 80%);
    }

    .login-card::after {
        content: '';
        position: absolute;
        bottom: -50px;
        right: -50px;
        width: 220px;
        height: 220px;
        border-radius: 50%;
        border: 36px solid rgba(98, 75, 255, 0.12);
        pointer-events: none;
        z-index: 0;
    }

    .card-logo {
        width: 48px; height: 48px; background: linear-gradient(135deg, #F4F0FF 0%, #EAE0FF 100%); border-radius: 12px;
        margin: 0 auto 12px auto; display: flex; align-items: center; justify-content: center;
        color: var(--primary-purple); font-size: 24px; box-shadow: 0 4px 12px rgba(98, 75, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.9);
    }

    .login-header { text-align: center; margin-bottom: 12px; }
    .login-header h1 { font-size: 22px; font-weight: 800; color: var(--text-dark); margin-bottom: 4px; letter-spacing: -0.5px; }
    .login-header p { font-size: 12px; color: var(--text-gray); font-weight: 500; }
    .header-line { width: 32px; height: 3px; background: var(--primary-purple); margin: 8px auto 0 auto; border-radius: 4px; }

    .form-group { margin-bottom: 10px; }
    .form-label { display: block; font-size: 12px; font-weight: 700; color: var(--text-dark); margin-bottom: 6px; margin-left: 4px; }
    
    .input-wrapper { position: relative; }
    .input-wrapper i.icon-left { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--primary-purple); font-size: 16px; }
    .input-wrapper i.icon-right { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: var(--text-gray); font-size: 16px; cursor: pointer; transition: color 0.2s; }
    .input-wrapper i.icon-right:hover { color: var(--primary-purple); }

    .form-input {
        width: 100%; padding: 10px 14px 10px 40px; font-size: 13.5px; color: var(--text-dark);
        background: rgba(255, 255, 255, 0.8); border: 1.5px solid rgba(98, 75, 255, 0.15); border-radius: 12px;
        outline: none; transition: all 0.2s ease; font-weight: 500; box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
    }
    .form-input:focus { border-color: var(--primary-purple); box-shadow: 0 0 0 4px rgba(98, 75, 255, 0.12); background: #ffffff; }

    .form-options {
        display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; margin-top: -2px;
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
        width: 100%; padding: 12px; background: linear-gradient(135deg, #624BFF 0%, #4F39F6 100%); color: var(--white);
        border: none; border-radius: 12px; font-size: 14px; font-weight: 700; cursor: pointer;
        transition: all 0.2s ease; box-shadow: 0 8px 20px rgba(98, 75, 255, 0.25);
        display: flex; align-items: center; justify-content: center; gap: 8px;
    }
    .btn-submit:hover { background: linear-gradient(135deg, #5038E6 0%, #3B28C5 100%); transform: translateY(-2px); box-shadow: 0 12px 24px rgba(98, 75, 255, 0.35); }
    .btn-submit:active { transform: scale(0.98) translateY(0); box-shadow: 0 4px 10px rgba(98, 75, 255, 0.2); }

    .divider {
        display: flex; align-items: center; margin: 12px 0; color: #94A3B8; font-size: 11px; font-weight: 600; text-transform: uppercase;
    }
    .divider::before, .divider::after { content: ''; flex: 1; height: 1px; background: #E2E8F0; }
    .divider::before { margin-right: 12px; }
    .divider::after { margin-left: 12px; }

    .btn-resident {
        width: 100%; padding: 10px; background: transparent; color: var(--primary-purple);
        border: 1.5px solid var(--primary-purple); border-radius: 10px; font-size: 13.5px; font-weight: 700; 
        cursor: pointer; text-decoration: none; transition: all 0.2s ease;
        display: flex; align-items: center; justify-content: center; gap: 8px;
    }
    .btn-resident:hover { background: rgba(98,75,255,0.05); }
    .btn-resident:active { transform: scale(0.98); }

    .secure-footer {
        text-align: center; margin-top: 12px; display: flex; align-items: center; justify-content: center; gap: 6px;
        color: var(--text-gray); font-size: 11px; font-weight: 600;
    }

    .back-home-btn {
        display: inline-flex;
        align-self: flex-start;
        margin-bottom: 32px;
        align-items: center;
        gap: 6px;
        color: var(--text-gray);
        font-size: 13px;
        font-weight: 700;
        text-decoration: none;
        transition: all 0.2s ease;
        z-index: 50;
        padding: 8px 16px;
        border-radius: 12px;
        background: rgba(255,255,255,0.4);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        border: 1px solid rgba(255,255,255,0.4);
        box-shadow: 0 4px 15px rgba(0,0,0,0.03);
    }
    .back-home-btn:hover {
        color: var(--primary-purple);
        background: rgba(255,255,255,0.9);
        box-shadow: 0 6px 20px rgba(0,0,0,0.06);
        transform: translateY(-1px);
    }
    .back-home-btn:active {
        transform: scale(0.96) translateY(0);
        box-shadow: 0 2px 10px rgba(0,0,0,0.02);
    }

    .error-box {
        background: #FEF2F2; border: 1px solid #FCA5A5; color: #DC2626;
        padding: 10px 16px; border-radius: 12px; font-size: 12.5px; font-weight: 600;
        margin-bottom: 16px; display: flex; align-items: center; gap: 8px;
    }

    .mobile-home-btn {
        display: none;
    }

    @media (max-width: 992px) {
        body {
            background: #f4f7f6;
            background-image: 
                url('assets/img/vector_house_bg.png');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            position: relative;
        }
        body::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.8) 0%, rgba(15, 23, 42, 0.95) 100%);
            z-index: -1;
        }
        .split-layout { 
            flex-direction: column; 
            padding: 20px; 
            min-height: 100vh; 
            position: relative;
            z-index: 1;
        }
        .left-panel { display: none; }
        .right-panel { 
            padding: 0; 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            width: 100%; 
            flex: 1;
        }
        
        .mobile-home-btn {
            align-self: flex-start;
            margin-bottom: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 8px 16px 8px 12px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-radius: 100px;
            text-decoration: none;
            color: #ffffff;
            font-weight: 600;
            font-size: 13px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
            z-index: 10;
        }
        .mobile-home-btn:active {
            transform: scale(0.96);
            background: rgba(255, 255, 255, 0.25);
        }
        .mobile-home-btn i {
            font-size: 18px;
        }

        .login-card {
            width: 100%;
            max-width: 400px;
            padding: 36px 24px;
            box-shadow: 0 24px 48px rgba(0, 0, 0, 0.2);
            border-radius: 28px;
            border: 1px solid rgba(255, 255, 255, 0.4);
            background-color: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            margin-top: auto;
            margin-bottom: auto;
            position: relative;
            overflow: hidden;
        }

        .login-card > * {
            position: relative;
            z-index: 1;
        }

        .login-card::before {
            content: '';
            position: absolute;
            top: -10px;
            left: -10px;
            width: 150px;
            height: 150px;
            background-image: radial-gradient(rgba(98, 75, 255, 0.25) 2px, transparent 2px);
            background-size: 16px 16px;
            pointer-events: none;
            z-index: 0;
            -webkit-mask-image: radial-gradient(circle at top left, black 30%, transparent 80%);
            mask-image: radial-gradient(circle at top left, black 30%, transparent 80%);
        }

        .login-card::after {
            content: '';
            position: absolute;
            bottom: -50px;
            right: -50px;
            width: 180px;
            height: 180px;
            border-radius: 50%;
            border: 28px solid rgba(98, 75, 255, 0.05);
            pointer-events: none;
            z-index: 0;
        }
    }
  </style>
</head>
<body>

<div class="split-layout">
    
    <!-- Left Promotional Panel -->
    <div class="left-panel">
        <a href="index.php" class="back-home-btn">
            <i class='bx bx-left-arrow-alt' style="font-size: 18px;"></i> Back to Home
        </a>
        
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
        <img src="assets/img/login_building.png" class="bg-illustration" alt="Building Illustration">
    </div>

    <!-- Right Login Card Panel -->
    <div class="right-panel">
        
        <a href="index.php" class="mobile-home-btn">
            <i class='bx bx-home-alt-2'></i> Back to Home
        </a>

        <div class="login-card">
            
            <div class="card-logo" style="color: var(--primary-purple);">
                <i class='bx bx-user-circle'></i>
            </div>
            
            <div class="login-header">
                <h1>Resident Portal</h1>
                <p>Welcome back! Please sign in to continue.</p>
                <div class="header-line"></div>
            </div>

            <?php if ($error !== ""): ?>
                <div class="error-box">
                    <i class='bx bx-error-circle'></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" autocomplete="off">
                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf']); ?>">
                
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <div class="input-wrapper">
                        <i class='bx bx-user icon-left'></i>
                        <input type="text" name="username" class="form-input" placeholder="john.doe" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required autofocus>
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
                    <a href="forgot-password.php" class="forgot-link">Forgot Password?</a>
                </div>

                <button type="submit" name="login" class="btn-submit">
                    <i class='bx bx-log-in-circle' style="font-size: 20px;"></i> Sign In
                </button>
            </form>

            <div class="divider">or</div>

            <a href="admin/login.php" class="btn-resident">
                <i class='bx bx-shield-quarter'></i> Admin Login
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

<?php
// index.php - Landing page with Admin / Resident selection
require_once "db.php";
session_start();

// If logged in, redirect to their dashboard
if (isset($_SESSION['admin_id'])) {
    header("Location: admin/dashboard.php");
    exit;
}
if (isset($_SESSION['user_id'])) {
    header("Location: renter/dashboard.php");
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
  <title><?php echo HOUSE_NAME; ?> - Premium Property Management</title>
  <meta name="description" content="Manage your stay at <?php echo HOUSE_NAME; ?>. Access rent records, electricity bills, and support queries in one place.">
  <link rel="icon" type="image/png" href="assets/img/favicon.png">

  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

  <!-- Fonts + Icons -->
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">

  <style>
    :root {
      --primary: #10875c;      
      --primary-hover: #0a6b47;
      --text-dark: #121829;
      --text-gray: #64748b;
      --bg-color: #f7fafc;
      --border-light: #e2e8f0;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body { 
      font-family: "Inter", system-ui, sans-serif; 
      background-color: var(--bg-color);
      color: var(--text-dark);
      min-height: 100vh;
      overflow-x: hidden;
      display: flex;
      flex-direction: column;
      /* Complex background with subtle abstract lines */
      background-image: 
        radial-gradient(circle at 10% 20%, rgba(16, 135, 92, 0.05) 0%, transparent 40%),
        radial-gradient(circle at 90% 80%, rgba(59, 130, 246, 0.05) 0%, transparent 40%);
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
      position: relative;
    }

    /* Simulate the abstract wave lines in background using SVGs or CSS shapes */
    .bg-wave {
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 500px;
        z-index: -1;
        overflow: hidden;
        opacity: 0.5;
    }
    .bg-wave::before {
        content: '';
        position: absolute;
        top: -100px;
        left: 20%;
        width: 1000px;
        height: 300px;
        border: 1px solid rgba(16, 135, 92, 0.1);
        border-radius: 50%;
        transform: rotate(-15deg);
    }
    .bg-wave::after {
        content: '';
        position: absolute;
        top: 50px;
        left: 10%;
        width: 1200px;
        height: 400px;
        border: 1px solid rgba(16, 135, 92, 0.08);
        border-radius: 50%;
        transform: rotate(-10deg);
    }

    /* Top Header */
    body::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 6px;
        background: linear-gradient(90deg, #10875c 0%, #1e3a8a 100%);
        z-index: 20;
    }

    .top-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.5rem 4rem;
        max-width: 1400px;
        margin: 0 auto;
        width: 100%;
        z-index: 10;
        position: relative;
        margin-top: 6px; /* Space for the top border */
    }

    .brand-logo {
        display: flex;
        align-items: center;
        gap: 14px;
    }
    .brand-logo-icon {
        color: #10875c;
        font-size: 42px;
    }
    .brand-logo-text {
        display: flex;
        flex-direction: column;
    }
    .brand-logo-title {
        font-family: 'Outfit', sans-serif;
        font-weight: 800;
        font-size: 24px;
        color: #0f172a;
        line-height: 1;
        letter-spacing: -0.5px;
    }
    .brand-logo-sub {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 5px;
        color: #10875c; /* Changed to green to match image */
        text-transform: uppercase;
        margin-top: 5px;
    }

    .header-links {
        display: flex;
        align-items: center;
        gap: 32px;
        font-size: 14px;
        font-weight: 600;
        color: #475569;
    }
    .header-links .link-item {
        display: flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
        color: #475569;
        transition: color 0.2s;
    }
    .header-links .link-item:hover { color: #10875c; }
    .header-links .link-item i { font-size: 20px; color: #10875c; }
    
    .header-divider { 
        width: 1px; 
        height: 18px; 
        background: #e2e8f0; 
    }
    
    .btn-help {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 24px;
        border-radius: 100px;
        border: 1px solid #6ee7b7; /* Light green border */
        background: #f8fafc;
        color: #10875c;
        text-decoration: none;
        font-weight: 700;
        font-size: 14px;
        transition: all 0.2s;
        box-shadow: 0 4px 10px rgba(16, 135, 92, 0.05);
        margin-left: 10px;
    }
    .btn-help:hover {
        background: #ecfdf5;
        box-shadow: 0 4px 12px rgba(16, 135, 92, 0.1);
    }

    /* Main Container */
    .main-wrap {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      width: 100%;
      max-width: 1400px;
      margin: 0 auto;
      padding: 0 4rem;
      position: relative;
    }

    .content-grid {
        display: grid;
        grid-template-columns: 1.1fr 1fr;
        gap: 40px;
        width: 100%;
        margin-top: 1rem;
    }

    /* Left side - Hero */
    .hero-section {
      display: flex;
      flex-direction: column;
      justify-content: flex-start;
      position: relative;
      padding-top: 1rem;
    }

    .welcome-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 14px;
      background: #e6f6f0;
      color: #0b704b;
      font-weight: 600;
      font-size: 12px;
      border-radius: 100px;
      margin-bottom: 1.25rem;
      width: fit-content;
    }
    .welcome-badge i { font-size: 14px; }

    .hero-section h1 { 
      font-family: "Outfit", sans-serif;
      margin: 0; 
      font-size: 52px; 
      font-weight: 800;
      color: #0b1c3c;
      line-height: 1.15;
      letter-spacing: -1px;
    }
    
    .hero-section h1 span {
      background: linear-gradient(90deg, #10875c 0%, #3b82f6 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      display: block;
    }

    .deco-dash {
        display: flex;
        gap: 6px;
        margin-top: 1rem;
        margin-bottom: 1.5rem;
    }
    .deco-dash span:first-child { width: 30px; height: 4px; border-radius: 2px; background: var(--primary); }
    .deco-dash span:last-child { width: 10px; height: 4px; border-radius: 2px; background: #e2e8f0; }

    .hero-section p.hero-desc { 
      color: var(--text-gray); 
      font-size: 16px; 
      line-height: 1.6;
      max-width: 480px;
      font-weight: 500;
      margin-bottom: 2rem;
    }

    /* Hero mini features */
    .hero-mini-features {
        display: flex;
        gap: 16px;
        margin-bottom: 2rem;
        flex-wrap: wrap;
    }
    .mini-feat {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .mini-feat-icon {
        width: 40px; height: 40px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 20px;
    }
    .mf-1 .mini-feat-icon { background: #e6f6f0; color: #10875c; }
    .mf-2 .mini-feat-icon { background: #eff6ff; color: #3b82f6; }
    .mf-3 .mini-feat-icon { background: #faf5ff; color: #a855f7; }
    
    .mini-feat-text {
        font-size: 12px;
        font-weight: 700;
        color: #0b1c3c;
        line-height: 1.3;
        width: 80px;
    }

    .hero-image-wrapper {
      position: relative;
      width: 100%;
      max-width: 480px;
    }

    .hero-image-wrapper img {
      width: 100%;
      height: auto;
      filter: drop-shadow(0 20px 40px rgba(0,0,0,0.06));
    }

    /* Right side - Login Card */
    .login-section {
      display: flex;
      align-items: flex-start;
      justify-content: flex-end;
      padding-top: 1rem;
    }

    .login-card {
      background: #ffffff;
      border-radius: 30px;
      padding: 3rem;
      width: 100%;
      max-width: 460px;
      box-shadow: 0 24px 60px rgba(15, 23, 42, 0.06), 0 0 0 1px rgba(15, 23, 42, 0.02);
      position: relative;
    }

    .login-header-wrap {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 2rem;
    }

    .login-header h4 {
        font-size: 14px;
        color: var(--primary);
        font-weight: 700;
        margin-bottom: 4px;
    }
    .login-header h2 {
      font-family: "Outfit", sans-serif;
      font-weight: 800;
      font-size: 32px;
      color: #0b1c3c;
      margin: 0 0 4px 0;
    }
    .login-header p {
      color: var(--text-gray); 
      font-size: 13px; 
      font-weight: 500;
      margin: 0;
    }

    .shield-badge {
        width: 50px;
        height: 50px;
        background: #e6f6f0;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        color: var(--primary);
        font-size: 24px;
        position: relative;
    }
    /* Dotted background effect around shield */
    .shield-badge::after {
        content: '';
        position: absolute;
        inset: -10px;
        border: 2px dotted #cbd5e1;
        border-radius: 50%;
        opacity: 0.5;
    }

    /* Ultra-modern Role Toggle */
    .role-toggle {
      display: flex;
      background: #f1f5f9;
      padding: 6px; 
      border-radius: 14px;
      position: relative;
      margin-bottom: 1.5rem;
    }
    
    .toggle-glider {
        position: absolute;
        top: 6px;
        left: 6px;
        width: calc(50% - 6px);
        height: calc(100% - 12px);
        background: #ffffff;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        transition: transform 0.3s cubic-bezier(0.4, 0.0, 0.2, 1);
        z-index: 1;
    }
    
    .role-toggle.admin-active .toggle-glider {
        transform: translateX(100%);
    }

    .role-toggle button {
      flex: 1;
      border: 0;
      padding: 12px;
      font-weight: 600;
      font-size: 14px;
      background: transparent;
      cursor: pointer;
      color: #64748b;
      position: relative;
      z-index: 2;
      transition: color 0.3s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }
    .role-toggle button i { font-size: 18px; }
    
    .role-toggle button.active {
      color: var(--primary);
    }

    /* Role Info Box */
    .role-info-box {
        background: #e6f6f0;
        border-radius: 12px;
        padding: 16px;
        display: flex;
        align-items: center;
        gap: 16px;
        margin-bottom: 1.5rem;
    }
    .role-info-box.admin-mode { background: #eff6ff; }
    .role-info-icon {
        width: 36px; height: 36px;
        background: rgba(16, 135, 92, 0.15);
        color: var(--primary);
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
    }
    .admin-mode .role-info-icon { background: rgba(59, 130, 246, 0.15); color: #3b82f6; }
    
    .role-info-text {
        font-size: 13px;
        color: #0b1c3c;
        font-weight: 500;
        line-height: 1.4;
    }

    /* Buttons */
    .btn-proceed {
      background: var(--primary);
      color: white;
      border: none;
      width: 100%;
      padding: 16px;
      border-radius: 12px;
      font-weight: 600;
      font-size: 15px;
      cursor: pointer;
      transition: all 0.2s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      text-decoration: none;
    }
    .btn-proceed:hover {
      background: var(--primary-hover);
      color: white;
      box-shadow: 0 10px 20px rgba(16, 135, 92, 0.2);
    }

    .divider {
        display: flex;
        align-items: center;
        text-align: center;
        margin: 1.5rem 0;
        color: #94a3b8;
        font-size: 12px;
        font-weight: 500;
    }
    .divider::before, .divider::after {
        content: '';
        flex: 1;
        border-bottom: 1px solid var(--border-light);
    }
    .divider:not(:empty)::before { margin-right: 1em; }
    .divider:not(:empty)::after { margin-left: 1em; }

    .btn-switch {
      background: transparent;
      color: #475569;
      border: 1px solid var(--border-light);
      width: 100%;
      padding: 14px;
      border-radius: 12px;
      font-weight: 600;
      font-size: 14px;
      cursor: pointer;
      transition: all 0.2s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      text-decoration: none;
    }
    .btn-switch:hover {
        background: #f8fafc;
        color: #0f172a;
    }

    .terms-note {
      text-align: center;
      margin-top: 1.5rem;
      font-size: 12px;
      color: var(--text-gray);
      line-height: 1.5;
    }
    .terms-note a { color: var(--primary); text-decoration: none; font-weight: 600; }
    .terms-note a:hover { text-decoration: underline; }

    /* Bottom Features Strip */
    .bottom-features {
        width: 100%;
        background: #ffffff;
        border-radius: 100px;
        padding: 24px 40px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.03);
        margin-top: 2rem;
        margin-bottom: 2rem;
    }

    .bf-item {
        display: flex;
        align-items: center;
        gap: 16px;
    }
    .bf-icon {
        width: 44px; height: 44px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 22px;
    }
    .bf-item:nth-child(1) .bf-icon { background: #e6f6f0; color: #10875c; }
    .bf-item:nth-child(2) .bf-icon { background: #eff6ff; color: #3b82f6; }
    .bf-item:nth-child(3) .bf-icon { background: #faf5ff; color: #a855f7; }
    .bf-item:nth-child(4) .bf-icon { background: #fff7ed; color: #f97316; }

    .bf-text h5 {
        font-size: 14px;
        font-weight: 700;
        color: #0b1c3c;
        margin: 0 0 2px 0;
    }
    .bf-text p {
        font-size: 11px;
        color: var(--text-gray);
        margin: 0;
        font-weight: 500;
        line-height: 1.4;
    }
    .bf-divider {
        width: 1px; height: 30px; background: var(--border-light);
    }

    /* Page Footer */
    footer {
      padding: 0 0 24px 0;
      text-align: center;
    }
    footer .copyright {
      font-size: 12px;
      color: #94a3b8;
      font-weight: 500;
    }

    /* Responsive Design */
    @media (max-width: 1200px) {
        .content-grid { gap: 20px; }
        .hero-section h1 { font-size: 42px; }
        .bottom-features { flex-wrap: wrap; border-radius: 24px; gap: 20px; justify-content: flex-start; padding: 24px; }
        .bf-divider { display: none; }
        .bf-item { width: calc(50% - 10px); }
    }
    @media (max-width: 992px) {
      .content-grid { grid-template-columns: 1fr; gap: 40px; }
      .login-section { justify-content: center; }
      .hero-image-wrapper { display: none; }
      .top-header { padding: 1.5rem 2rem; }
      .main-wrap { padding: 0 2rem; }
    }
    @media (max-width: 768px) {
      .top-header { flex-direction: column; gap: 16px; }
      .header-links { width: 100%; justify-content: center; flex-wrap: wrap; }
      .hero-section { align-items: center; text-align: center; }
      .hero-section h1 { font-size: 36px; }
      .hero-section p.hero-desc { text-align: center; }
      .hero-mini-features { justify-content: center; }
      .deco-dash { justify-content: center; }
      .login-card { padding: 2rem; }
      .bf-item { width: 100%; }
    }
    
    /* Decorative blurry leaf overlay */
    .leaf-overlay {
        position: fixed;
        bottom: -50px;
        left: -50px;
        width: 300px;
        height: 300px;
        background-image: url('assets/img/leaf-blur.png'); /* Fallback if missing, won't break anything */
        background-size: contain;
        background-repeat: no-repeat;
        filter: blur(8px);
        opacity: 0.3;
        z-index: 0;
        pointer-events: none;
    }
  </style>
</head>
<body>

<div class="bg-wave"></div>
<!-- <div class="leaf-overlay"></div> -->

<!-- Top Header -->
<header class="top-header">
    <div class="brand-logo">
        <i class='bx bxs-city brand-logo-icon'></i>
        <div class="brand-logo-text">
            <span class="brand-logo-title">Madhav Kunj</span>
            <span class="brand-logo-sub">RESIDENCE</span>
        </div>
    </div>
    
    <div class="header-links">
        <div class="link-item">
            <i class='bx bx-check-shield'></i> Secure & Trusted
        </div>
        <div class="header-divider"></div>
        <div class="link-item">
            <i class='bx bx-phone-call'></i> Support
        </div>
        <a href="#" class="btn-help">
            <i class='bx bx-headphone'></i> Need Help?
        </a>
    </div>
</header>

<div class="main-wrap">
    <div class="content-grid">
        
        <!-- Left Hero Section -->
        <div class="hero-section">
            <div class="welcome-badge">
                <i class='bx bxs-magic-wand'></i> Welcome to <?php echo HOUSE_NAME; ?>
            </div>
            
            <h1>Smart Property<br><span>Management</span></h1>
            
            <div class="deco-dash">
                <span></span><span></span>
            </div>
            
            <p class="hero-desc">A unified digital experience to manage your property, view bills, track payments, and access important records &ndash; all in one secure place.</p>
            
            <div class="hero-mini-features">
                <div class="mini-feat mf-1">
                    <div class="mini-feat-icon"><i class='bx bx-receipt'></i></div>
                    <div class="mini-feat-text">Easy Bill<br>Management</div>
                </div>
                <div class="mini-feat mf-2">
                    <div class="mini-feat-icon"><i class='bx bx-credit-card-front'></i></div>
                    <div class="mini-feat-text">Secure<br>Payments</div>
                </div>
                <div class="mini-feat mf-3">
                    <div class="mini-feat-icon"><i class='bx bx-bar-chart-alt-2'></i></div>
                    <div class="mini-feat-text">Real-time<br>Updates</div>
                </div>
            </div>
            
            <div class="hero-image-wrapper">
                <img src="assets/img/hero_property_3d.png" alt="Smart Building Illustration">
            </div>
        </div>

        <!-- Right Login Section -->
        <div class="login-section">
            <div class="login-card">
                
                <div class="login-header-wrap">
                    <div class="login-header">
                        <h4>Welcome Back! 👋</h4>
                        <h2>Sign In</h2>
                        <p>Choose your role to continue</p>
                    </div>
                    <div class="shield-badge">
                        <i class='bx bx-check-shield'></i>
                    </div>
                </div>

                <div class="role-toggle" id="role-toggle-container">
                    <div class="toggle-glider"></div>
                    <button id="btn-renter" class="active" data-role="renter">
                        <i class='bx bx-group'></i> Resident
                    </button>
                    <button id="btn-admin" data-role="admin">
                        <i class='bx bx-user-circle'></i> Admin
                    </button>
                </div>

                <div class="role-info-box" id="role-info-box">
                    <div class="role-info-icon">
                        <i class='bx bxs-user-detail'></i>
                    </div>
                    <div class="role-info-text" id="role-info-text">
                        Access your resident portal to<br>view and pay bills, and more.
                    </div>
                </div>

                <a id="proceed-btn" href="login.php" class="btn-proceed">
                    Proceed to Login <i class='bx bx-right-arrow-alt' style="font-size: 20px;"></i>
                </a>
                
                <div class="divider">or</div>
                
                <a id="switch-btn" href="admin/login.php" class="btn-switch">
                    <i class='bx bx-refresh'></i> Switch to Admin Login
                </a>
                
                <div class="terms-note">
                    By continuing, you agree to our <a href="terms-and-conditions.php">Terms of Use</a><br>and <a href="privacy-policy.php">Privacy Policy</a>.
                </div>

            </div>
        </div>

    </div>

    <!-- Bottom Features Strip -->
    <div class="bottom-features">
        <div class="bf-item">
            <div class="bf-icon"><i class='bx bx-shield-quarter'></i></div>
            <div class="bf-text">
                <h5>Secure & Reliable</h5>
                <p>Your data is protected<br>with advanced security</p>
            </div>
        </div>
        <div class="bf-divider"></div>
        <div class="bf-item">
            <div class="bf-icon"><i class='bx bx-time-five'></i></div>
            <div class="bf-text">
                <h5>24/7 Access</h5>
                <p>Access your account<br>anytime, anywhere</p>
            </div>
        </div>
        <div class="bf-divider"></div>
        <div class="bf-item">
            <div class="bf-icon"><i class='bx bx-bell'></i></div>
            <div class="bf-text">
                <h5>Instant Notifications</h5>
                <p>Get real-time updates<br>on bills and payments</p>
            </div>
        </div>
        <div class="bf-divider"></div>
        <div class="bf-item">
            <div class="bf-icon"><i class='bx bx-headphone'></i></div>
            <div class="bf-text">
                <h5>Dedicated Support</h5>
                <p>We're here to help you<br>whenever you need</p>
            </div>
        </div>
    </div>
    
    <footer>
        <div class="copyright">
            &copy; <?php echo date('Y'); ?> <?php echo SYSTEM_NAME; ?>. All rights reserved.
        </div>
    </footer>
</div>

<script>
  const toggleContainer = document.getElementById('role-toggle-container');
  const btnRenter = document.getElementById('btn-renter');
  const btnAdmin = document.getElementById('btn-admin');
  const proceedBtn = document.getElementById('proceed-btn');
  const switchBtn = document.getElementById('switch-btn');
  const infoBox = document.getElementById('role-info-box');
  const infoText = document.getElementById('role-info-text');

  function setRole(role) {
    if (role === 'renter') {
      toggleContainer.classList.remove('admin-active');
      btnRenter.classList.add('active');
      btnAdmin.classList.remove('active');
      
      infoBox.classList.remove('admin-mode');
      infoBox.querySelector('i').className = 'bx bxs-user-detail';
      infoText.innerHTML = 'Access your resident portal to<br>view and pay bills, and more.';
      
      proceedBtn.href = 'login.php';
      
      switchBtn.href = 'admin/login.php';
      switchBtn.innerHTML = "<i class='bx bx-refresh'></i> Switch to Admin Login";
      
    } else {
      toggleContainer.classList.add('admin-active');
      btnAdmin.classList.add('active');
      btnRenter.classList.remove('active');
      
      infoBox.classList.add('admin-mode');
      infoBox.querySelector('i').className = 'bx bxs-dashboard';
      infoText.innerHTML = 'Access the property management<br>dashboard to manage everything.';
      
      proceedBtn.href = 'admin/login.php';
      
      switchBtn.href = 'login.php';
      switchBtn.innerHTML = "<i class='bx bx-refresh'></i> Switch to Resident Login";
    }
  }

  btnRenter.addEventListener('click', ()=> setRole('renter'));
  btnAdmin.addEventListener('click', ()=> setRole('admin'));
</script>

</body>
</html>

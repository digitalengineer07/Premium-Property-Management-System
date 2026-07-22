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
      --primary-gradient: linear-gradient(90deg, #10875c 0%, #3b82f6 100%);
      --text-main: #0b1c3c;
      --text-gray: #64748b;
      --bg-light: #f8fafc;
      --border-light: #e2e8f0;
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
      font-family: 'Inter', sans-serif;
      background: #f8fafc;
      color: var(--text-main);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      overflow-x: hidden;
      scroll-behavior: smooth;
      background-image: 
        radial-gradient(circle at 10% 30%, rgba(16, 185, 129, 0.04) 0%, transparent 40%),
        radial-gradient(circle at 90% 70%, rgba(59, 130, 246, 0.03) 0%, transparent 40%),
        url('data:image/svg+xml;utf8,<svg width="100%" height="100%" viewBox="0 0 1440 600" xmlns="http://www.w3.org/2000/svg"><path d="M-100,500 C300,50 500,250 1540,-100" fill="none" stroke="rgba(16,185,129,0.15)" stroke-width="1"/><path d="M-100,530 C300,80 500,280 1540,-70" fill="none" stroke="rgba(16,185,129,0.05)" stroke-width="1"/></svg>');
      background-size: cover;
      background-position: top center;
      background-repeat: no-repeat;
    }

    .top-header {
        display: flex; justify-content: space-between; align-items: center; 
        padding: 2.5rem 4rem; max-width: 1400px; margin: 0 auto; width: 100%; 
        z-index: 10; position: relative; flex-shrink: 0;
    }

    .brand-logo { display: flex; align-items: center; gap: 8px; cursor: pointer; }
    .logo-icon-wrapper { position: relative; display: flex; align-items: center; justify-content: center; }
    .brand-logo-icon { color: #10875c; font-size: 46px; }
    .brand-leaf { position: absolute; bottom: -2px; right: -4px; font-size: 22px; color: #10875c; transform: rotate(15deg); text-shadow: -2px -2px 0 #f8fafc; }
    
    .brand-logo-text { display: flex; flex-direction: column; margin-left: 2px; }
    .brand-logo-title { font-family: 'Outfit', sans-serif; font-weight: 800; font-size: 24px; color: #0b1c3c; line-height: 1.1; letter-spacing: -0.5px; }
    .brand-logo-sub { font-size: 10px; font-weight: 700; letter-spacing: 5px; color: #64748b; margin-top: 1px; }

    .header-links { display: flex; align-items: center; gap: 24px; font-size: 14px; font-weight: 500; color: #64748b; }
    .header-links .link-item { display: flex; align-items: center; gap: 8px; text-decoration: none; color: #64748b; transition: color 0.2s; }
    .header-links .link-item:hover { color: var(--primary); }
    .header-links .link-item i { font-size: 20px; color: #10875c; }
    .header-divider { width: 1px; height: 16px; background: #cbd5e1; }
    
    .btn-help {
        display: flex; align-items: center; gap: 8px; padding: 10px 24px; border-radius: 100px; 
        border: 1px solid #a7f3d0; background: #ecfdf5; color: #059669; text-decoration: none; 
        font-weight: 600; font-size: 14px; transition: all 0.3s ease; margin-left: 8px;
    }
    .btn-help:hover { background: #d1fae5; box-shadow: 0 4px 10px rgba(5, 150, 105, 0.1); transform: translateY(-1px); }

    /* Main Container */
    .main-wrap {
      flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center;
      width: 100%; max-width: 1350px; margin: 0 auto; padding: 1rem 4rem 3rem 4rem; position: relative; z-index: 1;
    }

    .content-grid {
        display: grid; grid-template-columns: 55% 45%; gap: 60px; width: 100%; align-items: center;
    }

    /* Left side - Hero */
    .hero-section { display: flex; flex-direction: column; justify-content: center; position: relative; animation: fadeUp 0.8s ease-out forwards; opacity: 0; }

    .welcome-badge {
      display: inline-flex; align-items: center; gap: 6px; padding: 6px 16px; background: #ecfdf5; color: #059669; 
      font-weight: 600; font-size: 13px; border-radius: 100px; margin-bottom: 1.5rem; width: fit-content;
    }
    .welcome-badge i { font-size: 14px; animation: pulseIcon 2s infinite; }

    .hero-section h1 { font-family: "Outfit", sans-serif; font-size: 58px; font-weight: 800; color: #0b1c3c; line-height: 1.15; letter-spacing: -1px; }
    .hero-section h1 span { background: var(--primary-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent; display: block; }

    .deco-dash { display: flex; gap: 6px; margin-top: 1.25rem; margin-bottom: 1.25rem; align-items: center; }
    .deco-dash span:first-child { width: 44px; height: 5px; border-radius: 3px; background: #10875c; }
    .deco-dash span:last-child { width: 8px; height: 5px; border-radius: 3px; background: #cbd5e1; }

    .hero-section p.hero-desc { color: #64748b; font-size: 15px; line-height: 1.6; max-width: 480px; font-weight: 400; margin-bottom: 2.5rem; }

    /* Hero mini features */
    .hero-mini-features { display: flex; gap: 16px; margin-bottom: 1rem; flex-wrap: wrap; }
    .mini-feat { display: flex; align-items: center; gap: 12px; transition: transform 0.3s ease; }
    .mini-feat:hover { transform: translateY(-2px); }
    .mini-feat-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; transition: box-shadow 0.3s ease; }
    .mini-feat:hover .mini-feat-icon { box-shadow: 0 8px 16px rgba(0,0,0,0.06); }
    .mf-1 .mini-feat-icon { background: #ecfdf5; color: #059669; }
    .mf-2 .mini-feat-icon { background: #eff6ff; color: #2563eb; }
    .mf-3 .mini-feat-icon { background: #faf5ff; color: #9333ea; }
    .mini-feat-text { font-size: 13px; font-weight: 700; color: #0f172a; line-height: 1.3; }

    .hero-image-wrapper { position: relative; width: 100%; max-width: 750px; margin-top: 20px; animation: floatImg 6s ease-in-out infinite; margin-left: -20px; }
    .hero-image-wrapper img { width: 100%; height: auto; object-fit: contain; mix-blend-mode: multiply; transform: scale(1.1); transform-origin: left center; }

    /* Right side - Login Card */
    .login-section { display: flex; align-items: center; justify-content: flex-end; animation: fadeLeft 0.8s ease-out 0.2s forwards; opacity: 0; }
    .login-card {
      background: #ffffff; border-radius: 32px; padding: 2rem 2.5rem; width: 100%; max-width: 480px; 
      box-shadow: 0 20px 40px rgba(15, 23, 42, 0.04), 0 0 0 1px rgba(15, 23, 42, 0.02); position: relative;
      transition: box-shadow 0.4s ease, transform 0.4s ease;
    }
    .login-card:hover { box-shadow: 0 30px 60px rgba(15, 23, 42, 0.08), 0 0 0 1px rgba(15, 23, 42, 0.02); transform: translateY(-4px); }
    
    .login-header-wrap { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.25rem; position: relative; }
    .login-header h4 { font-size: 14px; color: #059669; font-weight: 700; margin-bottom: 4px; display: flex; align-items: center; gap: 4px; }
    .login-header h2 { font-family: "Outfit", sans-serif; font-weight: 800; font-size: 32px; color: #0f172a; margin: 0 0 4px 0; letter-spacing: -0.5px; }
    .login-header p { color: #64748b; font-size: 14px; font-weight: 500; margin: 0; }
    
    .shield-badge-wrapper { position: relative; width: 68px; height: 68px; display: flex; align-items: center; justify-content: center; }
    .shield-badge-dots { position: absolute; inset: 0; background-image: radial-gradient(#cbd5e1 1.5px, transparent 1.5px); background-size: 8px 8px; border-radius: 50%; opacity: 0.8; animation: spinSlow 30s linear infinite; }
    .shield-badge { width: 44px; height: 44px; background: #ecfdf5; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #10b981; font-size: 24px; position: relative; z-index: 2; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.1); border: 2px solid #ffffff; }

    /* Ultra-modern Role Toggle */
    .role-toggle { display: flex; background: #f8fafc; padding: 6px; border-radius: 16px; position: relative; margin-bottom: 1rem; border: 1px solid #f1f5f9; }
    .toggle-glider { position: absolute; top: 6px; left: 6px; width: calc(50% - 6px); height: calc(100% - 12px); background: #ffffff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); transition: transform 0.4s cubic-bezier(0.4, 0.0, 0.2, 1); z-index: 1; }
    .role-toggle.admin-active .toggle-glider { transform: translateX(100%); }
    .role-toggle button { flex: 1; border: 0; padding: 12px; font-weight: 600; font-size: 14px; background: transparent; cursor: pointer; color: #64748b; position: relative; z-index: 2; transition: color 0.3s ease; display: flex; align-items: center; justify-content: center; gap: 8px; }
    .role-toggle button i { font-size: 18px; }
    .role-toggle button.active { color: #10875c; }

    /* Role Info Box */
    .role-info-box { background: #ecfdf5; border-radius: 12px; padding: 12px 16px; display: flex; align-items: center; gap: 14px; margin-bottom: 1.25rem; transition: all 0.3s ease; }
    .role-info-box.admin-mode { background: #f8fafc; border: 1px solid #e2e8f0; padding: 11px 15px; }
    .role-info-icon { width: 40px; height: 40px; background: #d1fae5; color: #059669; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; transition: all 0.3s ease; }
    .admin-mode .role-info-icon { background: #e2e8f0; color: #475569; }
    .role-info-text { font-size: 13px; color: #064e3b; font-weight: 500; line-height: 1.4; transition: all 0.3s ease; }
    .admin-mode .role-info-text { color: #475569; }

    /* Buttons */
    .btn-proceed { background: linear-gradient(90deg, #166534 0%, #0d9488 100%); color: white; border: none; width: 100%; padding: 14px; border-radius: 12px; font-weight: 600; font-size: 15px; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; gap: 10px; text-decoration: none; box-shadow: 0 4px 15px rgba(13, 148, 136, 0.2); }
    .btn-proceed:hover { background: linear-gradient(90deg, #14532d 0%, #0f766e 100%); box-shadow: 0 8px 20px rgba(13, 148, 136, 0.3); transform: translateY(-2px); }

    .divider { display: flex; align-items: center; text-align: center; margin: 1rem 0; color: #94a3b8; font-size: 12px; font-weight: 500; }
    .divider::before, .divider::after { content: ''; flex: 1; border-bottom: 1px solid #e2e8f0; }
    .divider:not(:empty)::before { margin-right: 1em; }
    .divider:not(:empty)::after { margin-left: 1em; }

    .btn-switch { background: #ffffff; color: #475569; border: 1px solid #e2e8f0; width: 100%; padding: 14px; border-radius: 12px; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none; }
    .btn-switch:hover { background: #f8fafc; color: #0f172a; border-color: #cbd5e1; }

    .terms-note { text-align: center; margin-top: 1.25rem; font-size: 12px; color: #64748b; line-height: 1.5; }
    .terms-note a { color: #0d9488; text-decoration: none; font-weight: 600; transition: color 0.2s; }
    .terms-note a:hover { color: #0f766e; text-decoration: underline; }

    /* Bottom Features Strip */
    .bottom-features {
        width: 100%; max-width: 1250px; margin: -80px auto 1rem auto; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); 
        border-radius: 24px; padding: 24px 40px; display: flex; justify-content: space-between; align-items: center; 
        box-shadow: 0 20px 40px rgba(15, 23, 42, 0.06), 0 0 0 1px rgba(15, 23, 42, 0.02); flex-shrink: 0; position: relative; z-index: 20;
        animation: fadeUp 0.8s ease-out 0.4s forwards; opacity: 0;
    }
    .bf-item { display: flex; align-items: center; gap: 16px; transition: transform 0.3s ease; }
    .bf-item:hover { transform: translateY(-2px); }
    .bf-icon { width: 56px; height: 56px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 26px; transition: box-shadow 0.3s ease; }
    .bf-item:hover .bf-icon { box-shadow: 0 8px 16px rgba(0,0,0,0.06); }
    .bf-item.secure .bf-icon { background: #ecfdf5; color: #059669; border: 2px solid #a7f3d0; }
    .bf-item.access .bf-icon { background: #eff6ff; color: #2563eb; border: 2px solid #bfdbfe; }
    .bf-item.notif .bf-icon { background: #faf5ff; color: #9333ea; border: 2px solid #e9d5ff; }
    .bf-item.support .bf-icon { background: #fff7ed; color: #ea580c; border: 2px solid #fed7aa; }
    .bf-text h5 { font-size: 14px; font-weight: 700; color: #0b1c3c; margin: 0 0 4px 0; }
    .bf-text p { font-size: 12px; color: var(--text-gray); margin: 0; font-weight: 500; line-height: 1.4; max-width: 180px; }
    .bf-divider { width: 1px; height: 40px; background: #e2e8f0; }

    /* Abstract Glowing Orbs */
    .premium-glow-left {
        position: fixed; bottom: -10vh; left: -10vw; width: 60vw; height: 60vw;
        background: radial-gradient(circle, rgba(16,185,129,0.08) 0%, transparent 70%);
        z-index: 0; pointer-events: none; border-radius: 50%;
    }
    .premium-glow-right {
        position: fixed; top: -10vh; right: -10vw; width: 50vw; height: 50vw;
        background: radial-gradient(circle, rgba(59,130,246,0.08) 0%, transparent 70%);
        z-index: 0; pointer-events: none; border-radius: 50%;
    }

    /* Page Footer */
    footer { padding: 0 0 10px 0; text-align: center; flex-shrink: 0; z-index: 5; position: relative; }
    footer .copyright { font-size: 11px; color: #94a3b8; font-weight: 500; }

    /* Animations */
    @keyframes floatImg { 0% { transform: translateY(0px); } 50% { transform: translateY(-12px); } 100% { transform: translateY(0px); } }
    @keyframes fadeUp { to { opacity: 1; transform: translateY(0); } }
    @keyframes fadeLeft { to { opacity: 1; transform: translateX(0); } }
    @keyframes pulseIcon { 0% { opacity: 1; transform: scale(1); } 50% { opacity: 0.6; transform: scale(1.1); } 100% { opacity: 1; transform: scale(1); } }
    @keyframes spinSlow { 100% { transform: rotate(360deg); } }

    /* Responsive Design */
    @media (max-width: 1440px) {
        .content-grid { gap: 30px; grid-template-columns: 50% 50%; }
        .hero-section h1 { font-size: 46px; }
        .hero-image-wrapper { max-width: 480px; }
        .top-header { padding: 1.5rem 3rem; }
    }
    @media (max-width: 1024px) {
      body { height: auto; overflow: visible; overflow-x: hidden; }
      .main-wrap { padding: 2rem; }
      .content-grid { grid-template-columns: 1fr; gap: 40px; margin-bottom: 2rem; }
      .login-section { justify-content: center; }
      .hero-section { align-items: center; text-align: center; }
      .hero-section h1 { font-size: 42px; }
      .hero-section p.hero-desc { text-align: center; margin: 0 auto 2rem auto; }
      .hero-mini-features, .deco-dash { justify-content: center; }
      .top-header { padding: 1rem 2rem; }
    }
    @media (max-width: 768px) {
      .top-header { flex-direction: column; gap: 16px; padding-bottom: 1.5rem; }
      .header-links { width: 100%; justify-content: center; flex-wrap: wrap; }
      .login-card { padding: 2rem; }
    }
  </style>
</head>
<body>

<!-- Abstract Premium Glows -->
<div class="premium-glow-left"></div>
<div class="premium-glow-right"></div>

<!-- Top Header -->
<header class="top-header">
    <div class="brand-logo">
        <div class="logo-icon-wrapper">
            <i class='bx bx-buildings brand-logo-icon'></i>
            <i class='bx bxs-leaf brand-leaf'></i>
        </div>
        <div class="brand-logo-text">
            <span class="brand-logo-title">Madhav Kunj</span>
            <span class="brand-logo-sub">R E S I D E N C E</span>
        </div>
    </div>
    
    <div class="header-links">
        <div class="link-item">
            <i class='bx bx-check-shield'></i> Secure
        </div>
        <div class="header-divider"></div>
        <div class="link-item">
            <i class='bx bx-phone-call'></i> Support
        </div>
        <a href="#" class="btn-help">
            <i class='bx bx-headphone'></i> Help
        </a>
    </div>
</header>

<div class="main-wrap">
    <div class="content-grid">
        
        <!-- Left Hero Section -->
        <div class="hero-section">
            <div class="welcome-badge">
                <i class='bx bx-sparkles'></i> Welcome to <?php echo HOUSE_NAME; ?>
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
                    <div class="shield-badge-wrapper">
                        <div class="shield-badge-dots"></div>
                        <div class="shield-badge">
                            <i class='bx bx-check'></i>
                        </div>
                    </div>
                </div>

                <div class="role-toggle" id="role-toggle-container">
                    <div class="toggle-glider"></div>
                    <button id="btn-renter" class="active" data-role="renter">
                        <i class='bx bx-group'></i> Resident
                    </button>
                    <button id="btn-admin" data-role="admin">
                        <i class='bx bx-shield-quarter'></i> Admin
                    </button>
                </div>

                <div class="role-info-box" id="role-info-box">
                    <div class="role-info-icon" id="role-info-icon-container">
                        <i class='bx bxs-user-circle' id="role-info-icon"></i>
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
                    <i class='bx bx-user'></i> Switch to Admin Login
                </a>
                
                <div class="terms-note">
                    By continuing, you agree to our <a href="terms-and-conditions.php">Terms of Use</a><br>and <a href="privacy-policy.php">Privacy Policy</a>.
                </div>

            </div>
        </div>

    </div>

    <!-- Bottom Features Strip -->
    <div class="bottom-features">
        <div class="bf-item secure">
            <div class="bf-icon"><i class='bx bx-check-shield'></i></div>
            <div class="bf-text">
                <h5>Secure & Reliable</h5>
                <p>Your data is protected<br>with advanced security</p>
            </div>
        </div>
        <div class="bf-divider"></div>
        <div class="bf-item access">
            <div class="bf-icon"><i class='bx bx-time-five'></i></div>
            <div class="bf-text">
                <h5>24/7 Access</h5>
                <p>Access your account<br>anytime, anywhere</p>
            </div>
        </div>
        <div class="bf-divider"></div>
        <div class="bf-item notif">
            <div class="bf-icon"><i class='bx bx-bell'></i></div>
            <div class="bf-text">
                <h5>Instant Notifications</h5>
                <p>Get real-time updates<br>on bills and payments</p>
            </div>
        </div>
        <div class="bf-divider"></div>
        <div class="bf-item support">
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
      infoBox.querySelector('i').className = 'bx bxs-user-circle';
      infoText.innerHTML = 'Access your resident portal to<br>view and pay bills, and more.';
      
      proceedBtn.href = 'login.php';
      
      switchBtn.href = 'admin/login.php';
      switchBtn.innerHTML = "<i class='bx bx-user'></i> Switch to Admin Login";
      
    } else {
      toggleContainer.classList.add('admin-active');
      btnAdmin.classList.add('active');
      btnRenter.classList.remove('active');
      
      infoBox.classList.add('admin-mode');
      infoBox.querySelector('i').className = 'bx bxs-dashboard';
      infoText.innerHTML = 'Access the property management<br>dashboard to manage everything.';
      
      proceedBtn.href = 'admin/login.php';
      
      switchBtn.href = 'login.php';
      switchBtn.innerHTML = "<i class='bx bx-user'></i> Switch to Resident Login";
    }
  }

  btnRenter.addEventListener('click', ()=> setRole('renter'));
  btnAdmin.addEventListener('click', ()=> setRole('admin'));
</script>

</body>
</html>

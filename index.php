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
      position: fixed;
      top: 0; left: 0; right: 0; bottom: 0;
      background: 
        radial-gradient(circle at 10% 40%, rgba(16, 185, 129, 0.03), transparent 60%),
        radial-gradient(circle at 90% 20%, rgba(59, 130, 246, 0.03), transparent 60%),
        url('data:image/svg+xml;utf8,<svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg"><path d="M0,100 C150,200 350,0 500,100 C650,200 850,0 1000,100 L1000,0 L0,0 Z" fill="rgba(16, 185, 129, 0.01)"/></svg>') no-repeat top center;
      background-size: cover;
      z-index: 0;
      pointer-events: none;
    }

    /* Top Header - Glassmorphism */
    body::before {
        content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: var(--primary-gradient); z-index: 20;
    }

    .top-header {
        display: flex; justify-content: space-between; align-items: center; 
        padding: 1rem 3rem; max-width: 1300px; margin: 0 auto; width: 100%; 
        z-index: 10; position: relative; margin-top: 4px; flex-shrink: 0;
        background: rgba(248, 250, 252, 0.6);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border-radius: 0 0 24px 24px;
    }

    .brand-logo { display: flex; align-items: center; gap: 10px; cursor: pointer; }
    .brand-logo-icon { color: var(--primary); font-size: 32px; }
    .brand-logo-text { display: flex; flex-direction: column; }
    .brand-logo-title { font-family: 'Outfit', sans-serif; font-weight: 800; font-size: 18px; color: #0f172a; line-height: 1; letter-spacing: -0.5px; }
    .brand-logo-sub { font-size: 9px; font-weight: 700; letter-spacing: 4px; color: var(--primary); text-transform: uppercase; margin-top: 3px; }

    .header-links { display: flex; align-items: center; gap: 24px; font-size: 13px; font-weight: 600; color: #475569; }
    .header-links .link-item { display: flex; align-items: center; gap: 6px; text-decoration: none; color: #475569; transition: color 0.2s; }
    .header-links .link-item:hover { color: var(--primary); }
    .header-links .link-item i { font-size: 16px; color: var(--primary); }
    .header-divider { width: 1px; height: 16px; background: var(--border-light); }
    
    .btn-help {
        display: flex; align-items: center; gap: 6px; padding: 8px 18px; border-radius: 100px; 
        border: 1px solid #6ee7b7; background: #ffffff; color: var(--primary); text-decoration: none; 
        font-weight: 700; font-size: 13px; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
        box-shadow: 0 4px 10px rgba(16, 135, 92, 0.05); margin-left: 5px;
    }
    .btn-help:hover { background: #ecfdf5; box-shadow: 0 6px 14px rgba(16, 135, 92, 0.12); transform: translateY(-1px); }

    /* Main Container */
    .main-wrap {
      flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center;
      width: 100%; max-width: 1300px; margin: 0 auto; padding: 1rem 3rem 0 3rem; position: relative; z-index: 1;
    }

    .content-grid {
        display: grid; grid-template-columns: 55% 45%; gap: 40px; width: 100%; align-items: center; margin-bottom: 2rem;
    }

    /* Left side - Hero */
    .hero-section { display: flex; flex-direction: column; justify-content: center; position: relative; animation: fadeUp 0.8s ease-out forwards; opacity: 0; transform: translateY(20px); }

    .welcome-badge {
      display: inline-flex; align-items: center; gap: 6px; padding: 6px 16px; background: #e6f6f0; color: #10875c; 
      font-weight: 600; font-size: 12px; border-radius: 100px; margin-bottom: 1.25rem; width: fit-content;
      box-shadow: 0 4px 12px rgba(16, 135, 92, 0.05);
    }
    .welcome-badge i { font-size: 14px; animation: pulseIcon 2s infinite; }

    .hero-section h1 { font-family: "Outfit", sans-serif; font-size: 52px; font-weight: 800; color: #0b1c3c; line-height: 1.1; letter-spacing: -1px; }
    .hero-section h1 span { background: var(--primary-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent; display: block; }

    .deco-dash { display: flex; gap: 6px; margin-top: 1rem; margin-bottom: 1.25rem; }
    .deco-dash span:first-child { width: 40px; height: 4px; border-radius: 2px; background: var(--primary); }
    .deco-dash span:last-child { width: 12px; height: 4px; border-radius: 2px; background: #e2e8f0; }

    .hero-section p.hero-desc { color: #475569; font-size: 15px; line-height: 1.6; max-width: 500px; font-weight: 500; margin-bottom: 2rem; }

    /* Hero mini features */
    .hero-mini-features { display: flex; gap: 16px; margin-bottom: 2rem; flex-wrap: wrap; }
    .mini-feat { display: flex; align-items: center; gap: 12px; transition: transform 0.3s ease; }
    .mini-feat:hover { transform: translateY(-2px); }
    .mini-feat-icon { width: 46px; height: 46px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; transition: box-shadow 0.3s ease; }
    .mini-feat:hover .mini-feat-icon { box-shadow: 0 8px 16px rgba(0,0,0,0.06); }
    .mf-1 .mini-feat-icon { background: #e6f6f0; color: #10875c; }
    .mf-2 .mini-feat-icon { background: #eff6ff; color: #3b82f6; }
    .mf-3 .mini-feat-icon { background: #faf5ff; color: #a855f7; }
    .mini-feat-text { font-size: 13px; font-weight: 700; color: #0b1c3c; line-height: 1.3; width: 85px; }

    .hero-image-wrapper { position: relative; width: 100%; max-width: 520px; margin-top: -10px; animation: floatImg 6s ease-in-out infinite; }
    .hero-image-wrapper img { width: 100%; height: auto; object-fit: contain; filter: drop-shadow(0 20px 40px rgba(0,0,0,0.08)); }
    
    .leaf-blur-overlay {
        position: absolute; bottom: -10%; left: -15%; width: 350px; height: 450px;
        background: radial-gradient(ellipse at center, rgba(16, 135, 92, 0.12) 0%, transparent 60%);
        filter: blur(24px); pointer-events: none; z-index: 0; border-radius: 50%; transform: rotate(45deg);
    }

    /* Right side - Login Card */
    .login-section { display: flex; align-items: center; justify-content: flex-end; animation: fadeLeft 0.8s ease-out 0.2s forwards; opacity: 0; transform: translateX(20px); }
    .login-card {
      background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.4);
      border-radius: 28px; padding: 2.5rem; width: 100%; max-width: 440px; box-shadow: var(--shadow-soft); position: relative;
      transition: box-shadow 0.4s ease, transform 0.4s ease;
    }
    .login-card:hover { box-shadow: var(--shadow-hover); transform: translateY(-4px); }
    
    .login-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: var(--primary-gradient); border-top-left-radius: 28px; border-top-right-radius: 28px; }
    
    .login-header-wrap { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; position: relative; }
    .login-header h4 { font-size: 14px; color: var(--primary); font-weight: 700; margin-bottom: 4px; }
    .login-header h2 { font-family: "Outfit", sans-serif; font-weight: 800; font-size: 32px; color: #0b1c3c; margin: 0 0 4px 0; letter-spacing: -0.5px; }
    .login-header p { color: #64748b; font-size: 14px; font-weight: 500; margin: 0; }
    
    .shield-badge-wrapper { position: relative; width: 64px; height: 64px; display: flex; align-items: center; justify-content: center; }
    .shield-badge-dots { position: absolute; inset: 0; background-image: radial-gradient(#cbd5e1 1.5px, transparent 1.5px); background-size: 8px 8px; border-radius: 50%; opacity: 0.6; animation: spinSlow 30s linear infinite; }
    .shield-badge { width: 44px; height: 44px; background: #e6f6f0; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #10b981; font-size: 24px; position: relative; z-index: 2; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.15); border: 2px solid #ffffff; }

    /* Ultra-modern Role Toggle */
    .role-toggle { display: flex; background: #f1f5f9; padding: 6px; border-radius: 14px; position: relative; margin-bottom: 1.5rem; }
    .toggle-glider { position: absolute; top: 6px; left: 6px; width: calc(50% - 6px); height: calc(100% - 12px); background: #ffffff; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); transition: transform 0.4s cubic-bezier(0.4, 0.0, 0.2, 1); z-index: 1; }
    .role-toggle.admin-active .toggle-glider { transform: translateX(100%); }
    .role-toggle button { flex: 1; border: 0; padding: 12px; font-weight: 600; font-size: 14px; background: transparent; cursor: pointer; color: #64748b; position: relative; z-index: 2; transition: color 0.3s ease; display: flex; align-items: center; justify-content: center; gap: 8px; }
    .role-toggle button i { font-size: 18px; }
    .role-toggle button.active { color: var(--primary); }

    /* Role Info Box */
    .role-info-box { background: #f0fdf4; border: 1px solid #dcfce7; border-radius: 12px; padding: 14px 16px; display: flex; align-items: center; gap: 14px; margin-bottom: 1.5rem; transition: all 0.3s ease; }
    .role-info-box.admin-mode { background: #f8fafc; border: 1px solid #e2e8f0; }
    .role-info-icon { width: 36px; height: 36px; background: #d1fae5; color: #059669; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; transition: all 0.3s ease; }
    .admin-mode .role-info-icon { background: #e2e8f0; color: #475569; }
    .role-info-text { font-size: 13px; color: #064e3b; font-weight: 600; line-height: 1.4; transition: all 0.3s ease; }
    .admin-mode .role-info-text { color: #1e293b; }

    /* Buttons */
    .btn-proceed { background: linear-gradient(90deg, #166534 0%, #0d9488 100%); color: white; border: none; width: 100%; padding: 16px; border-radius: 12px; font-weight: 600; font-size: 15px; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; gap: 10px; text-decoration: none; box-shadow: 0 4px 15px rgba(13, 148, 136, 0.2); }
    .btn-proceed:hover { background: linear-gradient(90deg, #14532d 0%, #0f766e 100%); box-shadow: 0 8px 20px rgba(13, 148, 136, 0.3); transform: translateY(-2px); }

    .divider { display: flex; align-items: center; text-align: center; margin: 1.5rem 0; color: #94a3b8; font-size: 12px; font-weight: 500; }
    .divider::before, .divider::after { content: ''; flex: 1; border-bottom: 1px solid #e2e8f0; }
    .divider:not(:empty)::before { margin-right: 1em; }
    .divider:not(:empty)::after { margin-left: 1em; }

    .btn-switch { background: transparent; color: #475569; border: 1px solid #e2e8f0; width: 100%; padding: 14px; border-radius: 12px; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none; }
    .btn-switch:hover { background: #f1f5f9; color: #0f172a; border-color: #cbd5e1; }

    .terms-note { text-align: center; margin-top: 1.5rem; font-size: 12px; color: #64748b; line-height: 1.5; }
    .terms-note a { color: #0d9488; text-decoration: none; font-weight: 600; transition: color 0.2s; }
    .terms-note a:hover { color: #0f766e; text-decoration: underline; }

    /* Bottom Features Strip */
    .bottom-features {
        width: 100%; background: #ffffff; border-radius: 100px; padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; 
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.04); flex-shrink: 0; margin-bottom: 1.5rem; position: relative; z-index: 5;
        animation: fadeUp 0.8s ease-out 0.4s forwards; opacity: 0; transform: translateY(20px);
    }
    .bf-item { display: flex; align-items: center; gap: 14px; transition: transform 0.3s ease; }
    .bf-item:hover { transform: translateY(-2px); }
    .bf-icon { width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 22px; transition: box-shadow 0.3s ease; }
    .bf-item:hover .bf-icon { box-shadow: 0 6px 12px rgba(0,0,0,0.06); }
    .bf-item:nth-child(1) .bf-icon { background: #e6f6f0; color: #10875c; }
    .bf-item:nth-child(2) .bf-icon { background: #eff6ff; color: #3b82f6; }
    .bf-item:nth-child(3) .bf-icon { background: #faf5ff; color: #a855f7; }
    .bf-item:nth-child(4) .bf-icon { background: #fff7ed; color: #f97316; }
    .bf-text h5 { font-size: 14px; font-weight: 700; color: #0b1c3c; margin: 0 0 3px 0; }
    .bf-text p { font-size: 11px; color: var(--text-gray); margin: 0; font-weight: 500; line-height: 1.4; max-width: 180px; }
    .bf-divider { width: 1px; height: 30px; background: var(--border-light); }

    /* Page Footer */
    footer { padding: 0 0 1rem 0; text-align: center; flex-shrink: 0; z-index: 5; position: relative; }
    footer .copyright { font-size: 12px; color: #94a3b8; font-weight: 500; }

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
        .bottom-features { padding: 16px 30px; }
    }
    @media (max-width: 1024px) {
      body { height: auto; overflow-y: auto; }
      .main-wrap { padding: 2rem; }
      .content-grid { grid-template-columns: 1fr; gap: 40px; margin-bottom: 2rem; }
      .login-section { justify-content: center; }
      .hero-section { align-items: center; text-align: center; }
      .hero-section h1 { font-size: 42px; }
      .hero-section p.hero-desc { text-align: center; margin: 0 auto 2rem auto; }
      .hero-mini-features, .deco-dash { justify-content: center; }
      .top-header { padding: 1rem 2rem; }
      .bottom-features { flex-wrap: wrap; border-radius: 20px; gap: 20px; padding: 20px; }
      .bf-divider { display: none; }
      .bf-item { width: calc(50% - 10px); }
    }
    @media (max-width: 768px) {
      .top-header { flex-direction: column; gap: 16px; border-radius: 0; padding-bottom: 1.5rem; }
      .header-links { width: 100%; justify-content: center; flex-wrap: wrap; }
      .login-card { padding: 2rem; }
      .bf-item { width: 100%; }
    }
  </style>
</head>
<body>

<div class="bg-wave"></div>
<div class="leaf-blur-overlay"></div>

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
                            <i class='bx bxs-check-shield'></i>
                        </div>
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
                        <i class='bx bxs-user-circle'></i>
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

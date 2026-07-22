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

  <!-- Fonts + Bootstrap -->
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    :root {
      --primary: #047857;      /* Emerald 700 */
      --primary-light: #10b981; /* Emerald 500 */
      --primary-dark: #064e3b;  /* Emerald 900 */
      --accent: #0ea5e9;        /* Sky 500 */
      --text-main: #0f172a;
      --text-muted: #64748b;
      --glass-bg: rgba(255, 255, 255, 0.75);
      --glass-border: rgba(255, 255, 255, 0.5);
    }

    * {
      box-sizing: border-box;
    }

    body { 
      font-family: "Inter", system-ui, sans-serif; 
      margin: 0;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
      overflow-x: hidden;
      
      /* Animated Mesh Gradient Background */
      background: radial-gradient(circle at 15% 50%, #dcfce7, transparent 25%),
                  radial-gradient(circle at 85% 30%, #e0f2fe, transparent 25%),
                  radial-gradient(circle at 50% 80%, #f3e8ff, transparent 25%);
      background-color: #f8fafc;
      background-size: 100% 100%;
      animation: gradientBg 15s ease infinite;
    }

    @keyframes gradientBg {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }

    /* Abstract shapes behind the glass */
    .shape {
        position: absolute;
        filter: blur(60px);
        z-index: -1;
        opacity: 0.6;
        border-radius: 50%;
        animation: float 20s infinite alternate;
    }
    .shape-1 {
        top: -10%; left: -10%; width: 500px; height: 500px; background: #6ee7b7;
    }
    .shape-2 {
        bottom: -20%; right: -10%; width: 600px; height: 600px; background: #7dd3fc; animation-delay: -5s;
    }
    .shape-3 {
        top: 40%; left: 40%; width: 400px; height: 400px; background: #c4b5fd; animation-delay: -10s;
    }

    @keyframes float {
        0% { transform: translate(0, 0) rotate(0deg); }
        100% { transform: translate(100px, 50px) rotate(20deg); }
    }

    .wrap {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2rem;
      width: 100%;
      max-width: 1300px;
      margin: 0 auto;
      position: relative;
      z-index: 1;
    }

    /* Main Glassmorphism Container */
    .glass-panel {
      background: var(--glass-bg);
      backdrop-filter: blur(24px);
      -webkit-backdrop-filter: blur(24px);
      border-radius: 32px;
      border: 1px solid var(--glass-border);
      box-shadow: 0 24px 64px rgba(0, 0, 0, 0.04), inset 0 1px 0 rgba(255,255,255,1);
      display: grid;
      grid-template-columns: 1.2fr 1fr;
      overflow: hidden;
      width: 100%;
      min-height: 600px;
    }

    /* Left side - Branding & Illustration */
    .hero-section {
      padding: 3rem 4rem;
      display: flex;
      flex-direction: column;
      justify-content: center;
      position: relative;
    }

    .brand-badge {
      display: inline-block;
      padding: 8px 16px;
      background: rgba(16, 185, 129, 0.15);
      color: var(--primary-dark);
      font-weight: 600;
      font-size: 14px;
      border-radius: 100px;
      margin-bottom: 1.5rem;
      letter-spacing: 0.5px;
      border: 1px solid rgba(16, 185, 129, 0.3);
      width: fit-content;
    }

    .hero-section h1 { 
      font-family: "Outfit", sans-serif;
      margin: 0; 
      font-size: 48px; 
      font-weight: 800;
      color: var(--text-main);
      line-height: 1.1;
      letter-spacing: -1px;
    }
    
    .hero-section h1 span {
      background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .hero-section p { 
      color: var(--text-muted); 
      margin-top: 1.5rem; 
      font-size: 16px; 
      line-height: 1.6;
      max-width: 480px;
    }

    .hero-image-wrapper {
      margin-top: 2rem;
      position: relative;
      width: 100%;
      max-width: 400px;
      animation: floatImg 6s ease-in-out infinite;
    }

    @keyframes floatImg {
        0% { transform: translateY(0px); }
        50% { transform: translateY(-15px); }
        100% { transform: translateY(0px); }
    }

    .hero-image-wrapper img {
      width: 100%;
      height: auto;
      filter: drop-shadow(0 20px 30px rgba(0,0,0,0.1));
    }

    /* Right side - Login Card */
    .login-section {
      padding: 3rem 4rem;
      display: flex;
      align-items: center;
      justify-content: center;
      background: rgba(255, 255, 255, 0.4);
      border-left: 1px solid var(--glass-border);
    }

    .login-card {
      background: #ffffff;
      border-radius: 24px;
      padding: 2.5rem;
      width: 100%;
      max-width: 420px;
      box-shadow: 0 20px 48px rgba(15, 23, 42, 0.08);
      border: 1px solid rgba(15, 23, 42, 0.03);
      position: relative;
      overflow: hidden;
    }
    
    .login-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0; height: 4px;
        background: linear-gradient(90deg, var(--primary), var(--accent));
    }

    .login-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2rem;
    }

    .login-header h2 {
      font-family: "Outfit", sans-serif;
      font-weight: 700;
      font-size: 28px;
      color: var(--text-main);
      margin: 0;
    }

    .small-muted { color: var(--text-muted); font-size: 13px; font-weight: 500; }

    /* Ultra-modern Role Toggle */
    .role-toggle {
      display: flex;
      background: #f1f5f9;
      padding: 6px; 
      border-radius: 16px;
      position: relative;
      margin-bottom: 2rem;
    }
    
    .toggle-glider {
        position: absolute;
        top: 6px;
        left: 6px;
        width: calc(50% - 6px);
        height: calc(100% - 12px);
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
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
      font-size: 15px;
      background: transparent;
      cursor: pointer;
      color: var(--text-muted);
      position: relative;
      z-index: 2;
      transition: color 0.3s ease;
    }
    
    .role-toggle button.active {
      color: var(--primary-dark);
    }

    /* Action Area */
    .login-desc {
      font-size: 14px;
      color: var(--text-main);
      font-weight: 500;
      margin-bottom: 1.5rem;
      text-align: center;
    }

    .btn-proceed {
      background: var(--primary);
      color: white;
      border: none;
      width: 100%;
      padding: 16px;
      border-radius: 14px;
      font-weight: 600;
      font-size: 16px;
      cursor: pointer;
      transition: all 0.2s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      text-decoration: none;
      box-shadow: 0 8px 20px rgba(4, 120, 87, 0.2);
    }
    
    .btn-proceed:hover {
      background: var(--primary-dark);
      transform: translateY(-2px);
      box-shadow: 0 12px 24px rgba(4, 120, 87, 0.3);
      color: white;
    }

    .footer-note {
      text-align: center;
      margin-top: 1.5rem;
      font-size: 13px;
      color: var(--text-muted);
    }

    /* Page Footer */
    footer {
      padding: 24px;
      text-align: center;
      background: transparent;
      position: relative;
      z-index: 2;
    }
    
    footer .links {
      font-size: 13px;
      color: var(--text-muted);
      margin-bottom: 12px;
      font-weight: 500;
    }
    
    footer .links a {
      color: var(--text-main);
      text-decoration: none;
      margin: 0 12px;
      transition: color 0.2s;
    }
    
    footer .links a:hover {
      color: var(--primary);
    }
    
    footer .copyright {
      font-size: 12px;
      color: #94a3b8;
    }

    /* Responsive Design */
    @media (max-width: 1024px) {
      .glass-panel { grid-template-columns: 1fr 1fr; }
      .hero-section { padding: 2.5rem; }
      .login-section { padding: 2.5rem; }
      .hero-section h1 { font-size: 40px; }
    }

    @media (max-width: 768px) {
      body { height: auto; overflow-y: auto; }
      .wrap { padding: 1rem; margin-top: 1rem; }
      .glass-panel { 
        grid-template-columns: 1fr; 
        border-radius: 24px;
      }
      .hero-section { 
        padding: 2rem; 
        text-align: center;
        align-items: center;
      }
      .hero-section p { text-align: center; }
      .login-section { 
        padding: 2rem 1.5rem; 
        border-left: none;
        border-top: 1px solid var(--glass-border);
      }
      .hero-image-wrapper { max-width: 300px; }
      .hero-section h1 { font-size: 32px; }
    }
  </style>
</head>
<body>

<!-- Abstract Animated Shapes -->
<div class="shape shape-1"></div>
<div class="shape shape-2"></div>
<div class="shape shape-3"></div>

<div class="wrap">
  <div class="glass-panel">
    
    <!-- Left visual -->
    <div class="hero-section">
      <div class="brand-badge"><?php echo HOUSE_NAME; ?></div>
      <h1>Smart Property<br><span>Management</span></h1>
      <p>A unified digital experience. Access your account to manage bills, review payment history, and oversee financial records securely.</p>
      
      <div class="hero-image-wrapper">
        <img src="assets/img/hero_property_3d.png" alt="Smart Building Illustration">
      </div>
    </div>

    <!-- Right card -->
    <div class="login-section">
      <div class="login-card">
        
        <div class="login-header">
          <h2>Sign In</h2>
          <div class="small-muted">Choose your role</div>
        </div>

        <div class="role-toggle" id="role-toggle-container">
          <div class="toggle-glider"></div>
          <button id="btn-renter" class="active" data-role="renter">Resident</button>
          <button id="btn-admin" data-role="admin">Admin</button>
        </div>

        <div id="login-area">
          <p class="login-desc" id="login-desc-text">Access your resident portal to view and pay bills.</p>
          <a id="proceed-btn" href="login.php" class="btn-proceed">
            Proceed to Login
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
          </a>
          <div class="footer-note">Administrators, please switch to the Admin role to access the management dashboard.</div>
        </div>

      </div>
    </div>

  </div>
</div>

<footer>
    <div class="links">
        <a href="privacy-policy.php">Privacy Policy</a>
        <a href="terms-and-conditions.php">Terms & Conditions</a>
        <a href="cookie-policy.php">Cookie Policy</a>
        <a href="copyright.php">Copyright</a>
    </div>
    <div class="copyright">
        &copy; <?php echo date('Y'); ?> <?php echo SYSTEM_NAME; ?>. All rights reserved.
    </div>
</footer>

<script>
  const toggleContainer = document.getElementById('role-toggle-container');
  const btnRenter = document.getElementById('btn-renter');
  const btnAdmin = document.getElementById('btn-admin');
  const proceedBtn = document.getElementById('proceed-btn');
  const descText = document.getElementById('login-desc-text');

  function setRole(role) {
    if (role === 'renter') {
      toggleContainer.classList.remove('admin-active');
      btnRenter.classList.add('active');
      btnAdmin.classList.remove('active');
      proceedBtn.href = 'login.php';
      descText.innerText = 'Access your resident portal to view and pay bills.';
    } else {
      toggleContainer.classList.add('admin-active');
      btnAdmin.classList.add('active');
      btnRenter.classList.remove('active');
      proceedBtn.href = 'admin/login.php';
      descText.innerText = 'Access the comprehensive property management suite.';
    }
  }

  btnRenter.addEventListener('click', ()=> setRole('renter'));
  btnAdmin.addEventListener('click', ()=> setRole('admin'));
</script>

</body>
</html>

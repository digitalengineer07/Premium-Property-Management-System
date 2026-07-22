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
      --bg-color: #f0fdfa;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body { 
      font-family: "Inter", system-ui, sans-serif; 
      display: flex;
      flex-direction: column;
      min-height: 100vh;
      overflow-x: hidden;
      background: radial-gradient(circle at 10% 10%, #dcfce7, transparent 30%),
                  radial-gradient(circle at 90% 10%, #e0f2fe, transparent 30%),
                  radial-gradient(circle at 50% 90%, #f5f3ff, transparent 40%);
      background-color: #f8fafc;
    }

    .wrap {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2rem;
      width: 100%;
      max-width: 1400px;
      margin: 0 auto;
    }

    /* Main Container */
    .glass-panel {
      background: #ffffff;
      border-radius: 32px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.05);
      display: grid;
      grid-template-columns: 1fr 480px;
      overflow: hidden;
      width: 100%;
      min-height: 650px;
    }

    /* Left side - Branding & Illustration */
    .hero-section {
      padding: 4rem 5rem;
      display: flex;
      flex-direction: column;
      justify-content: flex-start;
      position: relative;
    }

    .brand-badge {
      display: inline-block;
      padding: 8px 18px;
      background: #d1fae5;
      color: #065f46;
      font-weight: 600;
      font-size: 13px;
      border-radius: 100px;
      margin-bottom: 2rem;
      border: 1px solid #a7f3d0;
      width: fit-content;
    }

    .hero-section h1 { 
      font-family: "Outfit", sans-serif;
      margin: 0; 
      font-size: 54px; 
      font-weight: 800;
      color: var(--text-main);
      line-height: 1.15;
      letter-spacing: -1px;
    }
    
    .hero-section h1 span {
      background: linear-gradient(90deg, #047857 0%, #06b6d4 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      display: block;
    }

    .hero-section p { 
      color: var(--text-muted); 
      margin-top: 1.5rem; 
      font-size: 17px; 
      line-height: 1.6;
      max-width: 500px;
      font-weight: 400;
    }

    .hero-image-wrapper {
      margin-top: 3rem;
      width: 100%;
      max-width: 480px;
    }

    .hero-image-wrapper img {
      width: 100%;
      height: auto;
      border-radius: 16px;
    }

    /* Right side - Login Card Wrapper */
    .login-section {
      padding: 4rem 4rem 4rem 0;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .login-card {
      background: #ffffff;
      border-radius: 24px;
      padding: 3rem;
      width: 100%;
      box-shadow: 0 24px 50px rgba(15, 23, 42, 0.06), 0 0 0 1px rgba(15, 23, 42, 0.02);
      position: relative;
    }
    
    .login-card::before {
        content: '';
        position: absolute;
        top: 0; left: 10%; right: 10%; height: 4px;
        background: linear-gradient(90deg, #047857, #0ea5e9);
        border-radius: 4px 4px 0 0;
    }

    .login-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2rem;
    }

    .login-header h2 {
      font-family: "Outfit", sans-serif;
      font-weight: 800;
      font-size: 28px;
      color: #0f172a;
      margin: 0;
    }

    .small-muted { color: #64748b; font-size: 13px; font-weight: 500; }

    /* Ultra-modern Role Toggle */
    .role-toggle {
      display: flex;
      background: #f1f5f9;
      padding: 6px; 
      border-radius: 12px;
      position: relative;
      margin-bottom: 2.5rem;
    }
    
    .toggle-glider {
        position: absolute;
        top: 6px;
        left: 6px;
        width: calc(50% - 6px);
        height: calc(100% - 12px);
        background: #ffffff;
        border-radius: 8px;
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
      font-size: 15px;
      background: transparent;
      cursor: pointer;
      color: #64748b;
      position: relative;
      z-index: 2;
      transition: color 0.3s ease;
    }
    
    .role-toggle button.active {
      color: #047857;
    }

    /* Action Area */
    .login-desc {
      font-size: 14px;
      color: #0f172a;
      font-weight: 600;
      margin-bottom: 1.5rem;
      text-align: center;
    }

    .btn-proceed {
      background: #047857;
      color: white;
      border: none;
      width: 100%;
      padding: 16px;
      border-radius: 12px;
      font-weight: 600;
      font-size: 16px;
      cursor: pointer;
      transition: all 0.2s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      text-decoration: none;
    }
    
    .btn-proceed:hover {
      background: #065f46;
      color: white;
    }

    .footer-note {
      text-align: center;
      margin-top: 2rem;
      font-size: 13px;
      color: #64748b;
      line-height: 1.5;
    }

    /* Page Footer */
    footer {
      padding: 24px;
      text-align: center;
      background: transparent;
      margin-top: auto;
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
      .glass-panel { grid-template-columns: 1fr; }
      .login-section { padding: 0 4rem 4rem 4rem; }
      .hero-image-wrapper { display: none; }
    }
    @media (max-width: 768px) {
      .wrap { padding: 1rem; }
      .hero-section { padding: 2.5rem; text-align: center; align-items: center; }
      .hero-section p { text-align: center; }
      .login-section { padding: 0 1.5rem 2.5rem 1.5rem; }
      .login-card { padding: 2rem; }
      .hero-section h1 { font-size: 36px; }
    }
  </style>
</head>
<body>

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
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><path d="M12 5l7 7-7 7"></path></svg>
          </a>
          <div class="footer-note" id="footer-note-text">Administrators, please switch to the Admin role to<br>access the management dashboard.</div>
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
  const footerNote = document.getElementById('footer-note-text');

  function setRole(role) {
    if (role === 'renter') {
      toggleContainer.classList.remove('admin-active');
      btnRenter.classList.add('active');
      btnAdmin.classList.remove('active');
      proceedBtn.href = 'login.php';
      descText.innerText = 'Access your resident portal to view and pay bills.';
      footerNote.innerHTML = 'Administrators, please switch to the Admin role to<br>access the management dashboard.';
    } else {
      toggleContainer.classList.add('admin-active');
      btnAdmin.classList.add('active');
      btnRenter.classList.remove('active');
      proceedBtn.href = 'admin/login.php';
      descText.innerText = 'Access the comprehensive property management suite.';
      footerNote.innerHTML = 'Residents, please switch to the Resident role to<br>access your personal portal.';
    }
  }

  btnRenter.addEventListener('click', ()=> setRole('renter'));
  btnAdmin.addEventListener('click', ()=> setRole('admin'));
</script>

</body>
</html>

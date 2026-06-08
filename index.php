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
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <link rel="stylesheet" href="assets/css/main.css">

  <style>
    :root{
      --panel-bg: linear-gradient(135deg, #d8f6ee 0%, #e5f3ff 60%);
      --accent: #1f7f6a;
      --card-radius: 18px;
    }

    body { 
      font-family: "Inter", system-ui, sans-serif; 
      background: #f3f7f9; 
      margin: 0;
      display: flex;
      flex-direction: column;
      height: 100vh;
      overflow: hidden; /* Prevent bounce scrolls */
    }

    .wrap {
      flex: 1;
      display:grid;
      grid-template-columns: 1fr 460px;
      gap:30px;
      align-items:center;
      padding: 0 48px;
    }

    /* left visual panel */
    .visual {
      background: var(--panel-bg);
      border-radius: 24px;
      padding: 30px;
      box-shadow: 0 16px 40px rgba(17,24,39,0.06);
      display:flex;
      gap:24px;
      align-items:center;
      justify-content:center;
    }
    .visual .text {
      max-width: 520px;
    }
    .visual h1 { margin:0; font-size:34px; color:#0b3b33; }
    .visual p { color: #164b44; opacity:.85; margin-top:10px; font-size:14px; }
    .visual img { width:280px; border-radius:14px; }

    /* right card */
    .card-panel {
      background: #ffffff;
      border-radius: var(--card-radius);
      padding: 28px;
      box-shadow: 0 20px 48px rgba(10,20,30,0.05);
      border: 1px solid rgba(10,20,30,0.03);
    }

    .role-toggle {
      display:flex;
      gap:8px;
      margin-bottom:18px;
      background: #f5fbf8;
      padding:6px; border-radius:12px;
    }
    .role-toggle button {
      flex:1;
      border-radius:10px;
      border:0;
      padding:10px 12px;
      font-weight:600;
      background:transparent;
      cursor:pointer;
      color:#114037;
    }
    .role-toggle button.active {
      background: linear-gradient(180deg,#0ea37a,#007f61);
      color:white;
      box-shadow: 0 8px 20px rgba(16,78,63,0.12);
    }

    .small-muted { color:#64748b; font-size:13px; margin-top:8px; }

    .login-small {
      margin-top:12px;
      font-size:13px;
    }

    .brand {
      font-weight:700; font-size:18px; color:#0b3b33;
    }

    @media (max-width: 980px) {
      body { height: auto; overflow: auto; }
      .wrap { grid-template-columns: 1fr; padding:22px 15px; }
      .visual img { display:none; }
      .visual { justify-content:flex-start; }
    }
    
    @media (max-width: 480px) {
      .wrap { padding: 15px; gap: 15px; }
      .visual { padding: 25px; border-radius: 18px; }
      .visual h1 { font-size: 24px; }
      .card-panel { padding: 20px; }
    }
  </style>
</head>
<body>

<div class="wrap container">

  <!-- Left visual -->
  <div class="visual">
    <div class="text">
      <div class="brand"><?php echo HOUSE_NAME; ?></div>
      <h1>Efficient Rent & Bill Management</h1>
      <p>Access your account to manage bills, review payment history, and oversee financial records. Proceed by selecting your role as an administrator (owner) or a resident.</p>
      <div class="small-muted">Note: Administrators are provided with a comprehensive management dashboard, whereas residents access a personalized portal.</div>
    </div>

    <!-- optional illustration (add your uploaded image path) -->
    <img src="assets/img/login-illustration.png" alt="illustration">
  </div>

  <!-- Right card -->
  <div class="card-panel">

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
      <div style="font-weight:700;font-size:20px">Sign in</div>
      <div class="small-muted">Please choose your role</div>
    </div>

    <div class="role-toggle" role="tablist" aria-label="Choose role">
      <button id="btn-renter" class="active" data-role="renter">Resident</button>
      <button id="btn-admin" data-role="admin">Admin</button>
    </div>

    <!-- Login links: we will redirect to the real login pages -->
    <div id="login-area">
      <p class="login-small">You will be taken to the Resident login form.</p>
      <div style="display:flex;gap:10px;margin-top:12px">
        <a id="proceed-btn" href="login.php" class="btn btn-primary w-100">Proceed to Resident Login</a>
      </div>
      <div style="margin-top:14px" class="small-muted">If you want admin login, toggle the role to Admin and click proceed.</div>
    </div>

  </div>
</div>

<footer style="padding: 16px; text-align: center; border-top: 1px solid rgba(0,0,0,0.05); background: white; flex-shrink: 0;">
    <div style="font-size: 12px; color: #64748b; margin-bottom: 4px;">
        <a href="privacy-policy.php" style="color: inherit; text-decoration: none; margin: 0 8px;">Privacy Policy</a> |
        <a href="terms-and-conditions.php" style="color: inherit; text-decoration: none; margin: 0 8px;">Terms & Conditions</a> |
        <a href="cookie-policy.php" style="color: inherit; text-decoration: none; margin: 0 8px;">Cookie Policy</a> |
        <a href="copyright.php" style="color: inherit; text-decoration: none; margin: 0 8px;">Copyright Notice</a>
    </div>
    <div style="font-size: 10px; color: #94a3b8;">
        &copy; <?php echo date('Y'); ?> <?php echo SYSTEM_NAME; ?>. All rights reserved.
    </div>
</footer>

<script>
  const btnRenter = document.getElementById('btn-renter');
  const btnAdmin = document.getElementById('btn-admin');
  const proceedBtn = document.getElementById('proceed-btn');
  const loginArea = document.getElementById('login-area');

  function setRole(role) {
    if (role === 'renter') {
      btnRenter.classList.add('active');
      btnAdmin.classList.remove('active');
      proceedBtn.href = 'login.php';
      loginArea.querySelector('.login-small').innerText = 'You will be taken to the Resident login form.';
      proceedBtn.innerText = 'Proceed to Resident Login';
    } else {
      btnAdmin.classList.add('active');
      btnRenter.classList.remove('active');
      proceedBtn.href = 'admin/login.php';
      loginArea.querySelector('.login-small').innerText = 'You will be taken to the Admin login form.';
      proceedBtn.innerText = 'Proceed to Admin Login';
    }
  }

  btnRenter.addEventListener('click', ()=> setRole('renter'));
  btnAdmin.addEventListener('click', ()=> setRole('admin'));
</script>

</body>
</html>

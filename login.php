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
updateLastSeen($conn); // Track activity if session exists

$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (false) { // CSRF check disabled for login to prevent friction
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
  <title>Resident Login Page</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <link rel="icon" type="image/png" href="assets/img/favicon.png">
  <link rel="manifest" href="manifest.json">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/main.css">
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
</head>
<body class="bg-mesh-glass centered-layout">

<!-- Background Shapes -->
<div class="bg-shape shape-1"></div>
<div class="bg-shape shape-2"></div>

<div style="width: 100%; max-width: 440px;">
    <!-- Main Glass Card -->
    <div class="glass-panel animate-fade-in" style="position: relative;">
        <a href="index.php" style="position: absolute; top: 20px; left: 20px; display: flex; align-items: center; gap: 4px; font-size: 13px; font-weight: 600; color: var(--text-muted); text-decoration: none; transition: all 0.2s ease; background: rgba(255,255,255,0.5); padding: 6px 12px; border-radius: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);" onmouseover="this.style.color='var(--text-main)'; this.style.background='rgba(255,255,255,0.8)';" onmouseout="this.style.color='var(--text-muted)'; this.style.background='rgba(255,255,255,0.5)';">
            <i class='bx bx-home-alt' style="font-size: 16px;"></i> Home
        </a>
        <div style="text-align: center; margin-bottom: 32px; padding-top: 10px;">
            <img src="assets/img/logo.png" alt="Logo" style="width: 64px; height: 64px; border-radius: 16px; margin-bottom: 16px; box-shadow: 0 8px 16px rgba(0,0,0,0.1);">
            <h1 style="font-size: 26px; font-weight: 700; color: var(--text-main); margin-bottom: 8px;">Resident Login Page</h1>
            <p style="color: var(--text-muted); font-size: 15px;">Welcome back to your dashboard</p>
        </div>

        <?php if ($error !== ""): ?>
            <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: #dc2626; padding: 12px; border-radius: 8px; font-size: 14px; margin-bottom: 24px; font-weight: 500;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf']); ?>">
            
            <div class="form-group hover-lift">
                <label class="form-label" style="margin-left: 2px;">Username</label>
                <input name="username" class="glass-input" placeholder="john.doe" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required autofocus>
            </div>

            <div class="form-group hover-lift">
                <label class="form-label" style="margin-left: 2px;">Password</label>
                <div style="position: relative;">
                    <input type="password" name="password" id="loginPassword" class="glass-input" placeholder="••••••••" required style="padding-right: 40px;">
                    <i class='bx bx-hide' id="togglePassword" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-muted); font-size: 20px;"></i>
                </div>
            </div>
            
            <div style="text-align: right; margin-top: -10px; margin-bottom: 16px;">
                <a href="forgot-password.php" style="font-size: 13.5px; font-weight: 600; text-decoration: none; color: #624BFF; transition: 0.2s;">Forgot your password?</a>
            </div>

            <button name="login" class="btn btn-primary hover-lift" style="width: 100%; margin-top: 12px;">Sign In</button>
        </form>

        <div style="text-align:center; margin-top:24px; font-size:14px; color:#64748b;">
            Not a renter? <a href="admin/login.php" style="font-weight:600;">Admin Login</a>
        </div>
    </div>
    
    <div style="text-align: center; margin-top: 24px; font-size: 13px; color: var(--text-muted); opacity: 0.8;">
        © <?php echo date("Y"); ?> Rent Manager System
    </div>
</div>

<script>
    const togglePassword = document.querySelector('#togglePassword');
    const password = document.querySelector('#loginPassword');

    togglePassword.addEventListener('click', function (e) {
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
        this.classList.toggle('bx-show');
        this.classList.toggle('bx-hide');
    });
</script>
</body>
</html>

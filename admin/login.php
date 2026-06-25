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
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/main.css">
</head>
<body class="bg-mesh-glass centered-layout">

<!-- Background Shapes -->
<div class="bg-shape shape-1"></div>
<div class="bg-shape shape-2"></div>

<div style="width: 100%; max-width: 440px;">
    <!-- Main Glass Card -->
    <div class="glass-panel animate-fade-in" style="position: relative;">
        <a href="../index.php" style="position: absolute; top: 20px; left: 20px; display: flex; align-items: center; gap: 4px; font-size: 13px; font-weight: 600; color: var(--text-muted); text-decoration: none; transition: all 0.2s ease; background: rgba(255,255,255,0.5); padding: 6px 12px; border-radius: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);" onmouseover="this.style.color='var(--text-main)'; this.style.background='rgba(255,255,255,0.8)';" onmouseout="this.style.color='var(--text-muted)'; this.style.background='rgba(255,255,255,0.5)';">
            <i class='bx bx-home-alt' style="font-size: 16px;"></i> Home
        </a>
        <div style="text-align: center; margin-bottom: 32px; padding-top: 10px;">
            <img src="../assets/img/logo.png" alt="Logo" style="width: 64px; height: 64px; border-radius: 16px; margin-bottom: 16px; box-shadow: 0 8px 16px rgba(0,0,0,0.1);">
            <h1 style="font-size: 26px; font-weight: 700; color: var(--text-main); margin-bottom: 8px;">Admin Login</h1>
            <p style="color: var(--text-muted); font-size: 15px;">Property Administration Panel</p>
        </div>

        <?php if ($error !== ""): ?>
            <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: #dc2626; padding: 12px; border-radius: 8px; font-size: 14px; margin-bottom: 24px; font-weight: 500;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <div class="form-group hover-lift">
                <label class="form-label" style="margin-left: 2px;">Username</label>
                <input name="username" class="glass-input" placeholder="admin" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required autofocus>
            </div>

            <div class="form-group hover-lift">
                <label class="form-label" style="margin-left: 2px;">Password</label>
                <div style="position: relative;">
                    <input type="password" name="password" id="loginPassword" class="glass-input" placeholder="••••••••" required style="padding-right: 40px;">
                    <i class='bx bx-hide' id="togglePassword" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-muted); font-size: 20px;"></i>
                </div>
            </div>

            <button name="login" class="btn btn-primary hover-lift" style="width: 100%; margin-top: 12px;">Sign In</button>
        </form>

        <div style="text-align:center; margin-top:24px; font-size:14px; color:#64748b;">
            Not an admin? <a href="../login.php" style="font-weight:600;">Resident Login</a>
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

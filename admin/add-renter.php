<?php
// admin/add-renter.php
require_once "../db.php";
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

$success = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf'] ?? '')) {
        $error = "Security validation failed. Please try again.";
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $name = trim($_POST['name'] ?? '');
        $room_no = trim($_POST['room_no'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if (empty($username) || empty($password) || empty($name)) {
            $error = "Username, Password and Name are required.";
        } else {
            // Check if username already exists
            $check = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ?");
            mysqli_stmt_bind_param($check, "s", $username);
            mysqli_stmt_execute($check);
            mysqli_stmt_store_result($check);
            
            if (mysqli_stmt_num_rows($check) > 0) {
                $error = "Username already taken. Please choose another.";
            } else {
                // Hash password
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $base_reading = (int)($_POST['base_reading'] ?? 0);
                $advance_payment = (float)($_POST['advance_payment'] ?? 0);
                $fixed_rent = (float)($_POST['fixed_rent'] ?? 0);
                $fixed_maintenance = (float)($_POST['fixed_maintenance'] ?? 0);
                
                $joining_date = $_POST['joining_date'] ?? null;
                if(empty($joining_date)) $joining_date = date('Y-m-d');
                $stmt = mysqli_prepare($conn, "INSERT INTO users (username, password, name, room_no, phone, email, base_reading, advance_payment, advance_updated_at, fixed_rent, fixed_maintenance, rent_maint_updated_at, rent_maint_updated_by, must_change_password, joining_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, NOW(), ?, 1, ?)");
                $admin_id = $_SESSION['admin_id'] ?? 1; // Basic fallback if admin_id is not set
                mysqli_stmt_bind_param($stmt, "ssssssidddis", $username, $hashed, $name, $room_no, $phone, $email, $base_reading, $advance_payment, $fixed_rent, $fixed_maintenance, $admin_id, $joining_date);
                
                if (mysqli_stmt_execute($stmt)) {
                    $new_id = mysqli_insert_id($conn);
                    $success = "Resident profile created successfully! <a href='../onboarding-guide.php?id=$new_id&pass=" . urlencode($password) . "' target='_blank' style='color: #10B981; text-decoration: underline; margin-left:10px;'>Print Onboarding Guide</a>";

                    // Welcome Email Logic
                    require_once "utils_mailer.php";
                    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS welcome_logs (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL UNIQUE,
                        sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )");
                    
                    if (!empty($email)) {
                        send_welcome_email($email, $name, $new_id, $username, $password);
                    }
                    mysqli_query($conn, "INSERT IGNORE INTO welcome_logs (user_id) VALUES ($new_id)");

                } else {
                    $error = "Error creating profile: " . mysqli_error($conn);
                }
                mysqli_stmt_close($stmt);
            }
            mysqli_stmt_close($check);
        }
    }
}

$admin_user = s($_SESSION['admin']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Add Resident | <?php echo HOUSE_NAME; ?></title>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-design-system.css">
</head>
<body>

<?php include "sidebar.php"; ?>

<main class="main">
    <?php include 'header.php'; ?>

    <div class="welcome animate-up">
        <h1>Resident Onboarding</h1>
        <p>Create a new account for a shifting-in tenant</p>
    </div>

    <div class="dashboard-grid-70 animate-up" style="margin-top: 30px; grid-template-columns: 1fr;">
        <div style="max-width: 700px; margin: 0 auto; width: 100%;">
            <div class="panel">
                <div class="panel-header" style="border-bottom: 1px solid var(--border); padding-bottom: 20px; margin-bottom: 30px;">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <div style="background: var(--bg-main); width: 50px; height: 50px; border-radius: 14px; display: flex; align-items: center; justify-content: center;">
                            <i class='bx bx-user-plus' style="font-size: 24px; color: var(--primary-purple);"></i>
                        </div>
                        <div>
                            <h2 style="font-size: 20px; font-weight: 700;">Account Details</h2>
                            <p style="font-size: 13px; color: var(--text-gray); margin: 0;">Fill in the login and personal details</p>
                        </div>
                    </div>
                </div>

                <?php if ($success): ?>
                    <div class="animate-up" style="background: #F0FDF4; color: #10B981; padding: 18px; border-radius: 14px; margin-bottom: 30px; border: 1px solid #DCFCE7; display: flex; align-items: center; gap: 12px;">
                        <i class='bx bx-check-circle' style="font-size: 24px;"></i>
                        <span style="font-weight: 600;"><?php echo $success; ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="animate-up" style="background: #FEF2F2; color: #EF4444; padding: 18px; border-radius: 14px; margin-bottom: 30px; border: 1px solid #FEE2E2; display: flex; align-items: center; gap: 12px;">
                        <i class='bx bx-error-circle' style="font-size: 24px;"></i>
                        <span style="font-weight: 600;"><?php echo $error; ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="csrf" value="<?php echo getCsrfToken(); ?>">
                    <div style="margin-bottom: 30px;">
                        <h4 style="font-size: 14px; color: var(--text-gray); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 20px;">Security & Login</h4>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px;">
                            <div class="form-group">
                                <label>Login Username</label>
                                <div style="position: relative;">
                                    <i class='bx bx-at' style="position: absolute; left: 16px; top: 14px; color: var(--text-gray);"></i>
                                    <input type="text" name="username" required placeholder="e.g. rajesh_101" style="padding-left: 45px;">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Login Password</label>
                                <div style="position: relative;">
                                    <i class='bx bx-lock-alt' style="position: absolute; left: 16px; top: 14px; color: var(--text-gray);"></i>
                                    <input type="password" name="password" required placeholder="••••••••" class="pwd-input" style="padding-left: 45px; padding-right: 40px;">
                                    <i class='bx bx-hide pwd-toggle' style="position: absolute; right: 16px; top: 14px; color: var(--text-gray); cursor: pointer; font-size: 20px;"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div style="margin-bottom: 30px;">
                        <h4 style="font-size: 14px; color: var(--text-gray); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 20px;">Personal Profile</h4>
                        <div class="form-group">
                            <label>Resident Full Name</label>
                            <input type="text" name="name" required placeholder="Legal Name of Resident">
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px;">
                            <div class="form-group">
                                <label>Room No / Floor</label>
                                <input type="text" id="roomNoInput" name="room_no" placeholder="e.g. 104, 2nd Floor">
                            </div>
                            <div class="form-group">
                                <label>Phone Number</label>
                                <div style="position: relative;">
                                    <i class='bx bx-phone' style="position: absolute; left: 16px; top: 14px; color: var(--text-gray);"></i>
                                    <input type="text" name="phone" placeholder="91XXXXXXXX" style="padding-left: 45px;">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Email Address</label>
                                <div style="position: relative;">
                                    <i class='bx bx-envelope' style="position: absolute; left: 16px; top: 14px; color: var(--text-gray);"></i>
                                    <input type="email" name="email" placeholder="renter@example.com" style="padding-left: 45px;">
                                </div>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px; margin-top:24px;">
                            <div class="form-group">
                                <label>Joining Date</label>
                                <div style="position: relative;">
                                    <i class='bx bx-calendar' style="position: absolute; left: 16px; top: 14px; color: var(--text-gray);"></i>
                                    <input type="date" name="joining_date" style="padding-left: 45px;" value="<?php echo date('Y-m-d'); ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div style="margin-bottom: 30px;">
                        <h4 style="font-size: 14px; color: var(--text-gray); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 20px;">Initial Utility Setup</h4>
                        <div class="form-group">
                            <label>Starting Meter Reading (Previous Month Units)</label>
                            <div style="position: relative;">
                                <i class='bx bx-bolt-circle' style="position: absolute; left: 16px; top: 14px; color: var(--text-gray);"></i>
                                <input type="number" id="baseReadingInput" name="base_reading" value="0" style="padding-left: 45px; transition: background-color 0.3s ease;">
                            </div>
                            <p style="font-size: 11px; color: var(--text-gray); margin-top: 8px;">This will be used as the "Last Reading" for the first bill.</p>
                        </div>
                    </div>

                    <div style="margin-bottom: 30px;">
                        <h4 style="font-size: 14px; color: var(--text-gray); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 20px;">Financial Initial Setup</h4>
                        <div class="form-group">
                            <label>Advance Payment (₹)</label>
                            <div style="display: flex; gap: 10px; align-items: stretch;">
                                <div style="position: relative; flex: 1;">
                                    <i class='bx bx-money' style="position: absolute; left: 16px; top: 14px; color: var(--text-gray);"></i>
                                    <input type="number" step="0.01" name="advance_payment" value="0" style="padding-left: 45px; height: 100%; border-radius: 12px; border: 1px solid var(--border);" placeholder="0">
                                </div>
                                <button type="button" class="btn-outline" onclick="generateAdvanceQR()" style="padding: 0 16px; border-radius: 12px; height: 48px; flex-shrink: 0;"><i class='bx bx-qr-scan'></i> QR</button>
                            </div>
                            <p style="font-size: 11px; color: var(--text-gray); margin-top: 8px;">Record the security/advance deposit received from the renter.</p>
                            <div id="advanceQRContainer" style="display: none; margin-top: 15px; text-align: center; background: white; padding: 15px; border-radius: 12px; border: 1px solid var(--border);">
                                <img id="advanceQRImg" src="" alt="Advance QR" style="width: 150px; height: 150px; display: inline-block;">
                                <p style="font-size: 11px; font-weight: 600; color: #10B981; margin-top: 8px;">Scan to pay Advance via UPI</p>
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px;">
                            <div class="form-group">
                                <label>Monthly Rent Amount (₹)</label>
                                <div style="position: relative;">
                                    <i class='bx bx-home-circle' style="position: absolute; left: 16px; top: 14px; color: var(--text-gray);"></i>
                                    <input type="number" step="0.01" name="fixed_rent" value="0" style="padding-left: 45px;" placeholder="0">
                                </div>
                                <p style="font-size: 11px; color: var(--text-gray); margin-top: 8px;">Fixed monthly rent for this renter.</p>
                            </div>
                            <div class="form-group">
                                <label>Monthly Maintenance Amount (₹)</label>
                                <div style="position: relative;">
                                    <i class='bx bx-wrench' style="position: absolute; left: 16px; top: 14px; color: var(--text-gray);"></i>
                                    <input type="number" step="0.01" name="fixed_maintenance" value="0" style="padding-left: 45px;" placeholder="0">
                                </div>
                                <p style="font-size: 11px; color: var(--text-gray); margin-top: 8px;">Fixed monthly maintenance for this renter.</p>
                            </div>
                        </div>
                    </div>

                    <div style="margin-top: 20px;">
                        <button type="submit" class="btn-primary" style="width: 100%; justify-content: center; padding: 18px; font-size: 16px; border-radius: 16px;">
                            <i class='bx bx-user-plus'></i> Confirm and Create Account
                        </button>
                        <p style="text-align: center; color: var(--text-gray); font-size: 13px; margin-top: 20px;">
                            <i class='bx bx-shield-quarter'></i> New accounts are activated and ready to use immediately.
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<script>
function generateAdvanceQR() {
    let amount = document.getElementsByName('advance_payment')[0].value;
    if(amount > 0) {
        let upiId = "nikhil119124-1@oksbi"; 
        let name = "Nikhil Kumar";
        let upiUrl = `upi://pay?pa=${upiId}&pn=${encodeURIComponent(name)}&tr=ADVANCE&am=${amount}&cu=INR`;
        let qrImg = document.getElementById('advanceQRImg');
        qrImg.src = `https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=${encodeURIComponent(upiUrl)}`;
        document.getElementById('advanceQRContainer').style.display = 'block';
    } else {
        alert("Please enter a valid advance amount first.");
    }
}

document.querySelectorAll('.pwd-toggle').forEach(icon => {
    icon.addEventListener('click', function() {
        const input = this.previousElementSibling;
        if(input) {
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            this.classList.toggle('bx-show');
            this.classList.toggle('bx-hide');
        }
    });
});

// Auto-fetch last meter reading when Room No is entered
document.getElementById('roomNoInput')?.addEventListener('blur', async function(e) {
    const roomNo = e.target.value.trim();
    if (!roomNo) return;
    
    try {
        const res = await fetch(`../api/admin/get_last_room_reading.php?room=${encodeURIComponent(roomNo)}`);
        const data = await res.json();
        
        if (data.status === 'success' && data.last_reading > 0) {
            const baseReadingInput = document.getElementById('baseReadingInput');
            baseReadingInput.value = data.last_reading;
            // Visual feedback
            baseReadingInput.style.backgroundColor = 'rgba(16, 185, 129, 0.1)';
            baseReadingInput.style.borderColor = '#10B981';
            setTimeout(() => {
                baseReadingInput.style.backgroundColor = '';
                baseReadingInput.style.borderColor = '';
            }, 1500);
        }
    } catch (err) {
        console.error("Failed to fetch last reading", err);
    }
});
</script>

</body>
</html>

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
                $total_collected = (float)($_POST['advance_payment'] ?? 0);
                $security_deposit = (float)($_POST['security_deposit'] ?? 0);
                $fixed_rent = (float)($_POST['fixed_rent'] ?? 0);
                $fixed_maintenance = (float)($_POST['fixed_maintenance'] ?? 0);
                // --- STRICT ONBOARDING PROTOCOL ---
                // The collected amount must be either exactly 1x Rent (goes to Sec Deposit) 
                // or exactly 2x Rent (1x Sec Deposit, 1x Advance for first month).
                // Or 0 if they haven't paid yet.
                if ($total_collected > 0 && $total_collected != $fixed_rent && $total_collected != ($fixed_rent * 2)) {
                    $error = "System Protocol Violation: The total collected amount (₹$total_collected) must be exactly equal to 1 month's rent (₹$fixed_rent) OR exactly double the rent (₹" . ($fixed_rent * 2) . "). Partial amounts are not accepted.";
                } else {
                    // Split the total collected amount based on protocol
                    if ($total_collected == ($fixed_rent * 2)) {
                        $amount_to_sec = $fixed_rent;
                        $amount_to_first_month = $fixed_rent;
                    } else if ($total_collected == $fixed_rent) {
                        $amount_to_sec = $fixed_rent;
                        $amount_to_first_month = 0;
                    } else {
                        // For 0 or other cases handled above
                        $amount_to_sec = 0;
                        $amount_to_first_month = 0;
                    }
                    
                    // The actual security deposit stored in the DB is what they paid
                    $security_deposit = $amount_to_sec;
                }
                
                // Set onboarding completed flag if they fully paid
                $onboarding_completed = ($total_collected >= ($security_deposit + $fixed_rent)) ? 1 : 0;
                
                $joining_date = $_POST['joining_date'] ?? null;
                if(empty($joining_date)) $joining_date = date('Y-m-d');
                $block = trim($_POST['block'] ?? '');
                $floor = trim($_POST['floor'] ?? '');
                $parking = trim($_POST['parking'] ?? '');
                
                // Check if onboarding_completed column exists
                $check_col = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'onboarding_completed'");
                if (mysqli_num_rows($check_col) == 0) {
                    mysqli_query($conn, "ALTER TABLE users ADD COLUMN onboarding_completed TINYINT(1) DEFAULT 0");
                }

                $stmt = mysqli_prepare($conn, "INSERT INTO users (username, password, name, room_no, phone, email, base_reading, advance_payment, security_deposit, advance_updated_at, fixed_rent, fixed_maintenance, rent_maint_updated_at, rent_maint_updated_by, must_change_password, joining_date, block, floor, parking, onboarding_completed) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, NOW(), ?, 1, ?, ?, ?, ?, ?)");
                $admin_id = $_SESSION['admin_id'] ?? 1; // Basic fallback if admin_id is not set
                $zero_advance = 0; // We do not put it in the advance wallet anymore
                mysqli_stmt_bind_param($stmt, "ssssssiddddissssi", $username, $hashed, $name, $room_no, $phone, $email, $base_reading, $zero_advance, $security_deposit, $fixed_rent, $fixed_maintenance, $admin_id, $joining_date, $block, $floor, $parking, $onboarding_completed);
                
                if (mysqli_stmt_execute($stmt)) {
                    $new_id = mysqli_insert_id($conn);
                    $success = "Resident profile created successfully! <a href='../onboarding-guide.php?id=$new_id&pass=" . urlencode($password) . "' target='_blank' style='color: #10B981; text-decoration: underline; margin-left:10px;'>Print Onboarding Guide</a>";

                    // Insert payment records for the ledger
                    $sys_tx_id = 'SYS_ONB_' . strtoupper(substr(md5(uniqid()), 0, 10));
                    if ($amount_to_sec > 0) {
                        mysqli_query($conn, "INSERT INTO payments (user_id, bill_type, bill_id, month, total_amount, payment_mode, paid_amount, payment_date, transaction_id, sys_tx_id) VALUES ($new_id, 'security_deposit', 0, 'Security Deposit', $amount_to_sec, 'Cash/Offline', $amount_to_sec, CURDATE(), 'Cash', '$sys_tx_id')");
                    }
                    if ($amount_to_first_month > 0) {
                        $joining_month = date('M Y', strtotime($joining_date));
                        mysqli_query($conn, "INSERT INTO payments (user_id, bill_type, bill_id, month, total_amount, payment_mode, paid_amount, payment_date, transaction_id, sys_tx_id) VALUES ($new_id, 'rent', 0, '$joining_month', $amount_to_first_month, 'Cash/Offline', $amount_to_first_month, CURDATE(), 'Cash', '$sys_tx_id')");
                    }

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

                    // Clear the form data so it's not retained after successful creation
                    $_POST = [];

                } else {
                    $error = "Error creating profile: " . mysqli_error($conn);
                }
                if (isset($stmt)) {
                    mysqli_stmt_close($stmt);
                }
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
    <style>
        /* Modern Premium Styling for Onboarding */
        .panel {
            background: #1E293B;
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            padding: 30px;
        }
        .form-group label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #94A3B8 !important;
            font-weight: 600;
            margin-bottom: 10px;
            display: block;
        }
        .form-group input {
            background: #0F172A !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            border-radius: 12px !important;
            color: #F8FAFC !important;
            height: 52px !important;
            transition: all 0.3s ease !important;
        }
        .form-group input:focus {
            background: #131E32 !important;
            border-color: #6366F1 !important;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15) !important;
            outline: none !important;
        }
        .form-group i {
            color: var(--text-gray) !important;
            transition: color 0.3s ease;
        }
        .form-group input:focus ~ i, .form-group input:focus + i, .form-group:focus-within i.bx {
            color: #6366F1 !important;
        }
        .section-title {
            font-size: 13px;
            color: #6366F1;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-weight: 700;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .section-title::after {
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(255,255,255,0.05);
        }
        .btn-primary.submit-btn {
            background: linear-gradient(135deg, #6366F1, #8B5CF6) !important;
            border: none !important;
            color: white !important;
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.3) !important;
            transition: transform 0.2s, box-shadow 0.2s !important;
            font-weight: 600 !important;
            letter-spacing: 0.5px;
        }
        .btn-primary.submit-btn:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 12px 25px rgba(99, 102, 241, 0.4) !important;
        }
    </style>
</head>
<body>

<?php include "sidebar.php"; ?>

<main class="main">
    <?php include 'header.php'; ?>

    <div class="welcome animate-up" style="margin-bottom: 40px; padding: 10px 0;">
        <h1 class="page-title" style="margin-bottom: 12px; display: flex; align-items: center; gap: 18px;">
            <div style="background: rgba(99, 102, 241, 0.1); border: 1px solid rgba(99, 102, 241, 0.2); padding: 12px; border-radius: 16px; display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 20px rgba(99, 102, 241, 0.15);">
                <i class='bx bx-user-pin' style="font-size: 32px; color: #818cf8; -webkit-text-fill-color: initial;"></i>
            </div>
            Resident Onboarding
        </h1>
        <p style="color: #94a3b8; font-size: 15px; max-width: 500px; line-height: 1.6; margin-left: 78px;">Create a new account for a shifting-in tenant. Fill in the required details below to instantly generate their secure profile.</p>
    </div>

    <div class="dashboard-grid-70 animate-up" style="margin-top: 30px; grid-template-columns: 1fr;">
        <div style="width: 100%;">
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

                <form id="addRenterForm" method="POST" autocomplete="off">
                    <input type="hidden" name="csrf" value="<?php echo getCsrfToken(); ?>">
                    <div style="margin-bottom: 30px;">
                        <div class="section-title">Security & Login</div>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px;">
                            <div class="form-group">
                                <label>Login Username</label>
                                <div style="position: relative;">
                                    <i class='bx bx-at' style="position: absolute; left: 16px; top: 14px; color: var(--text-gray);"></i>
                                    <input type="text" name="username" required placeholder="e.g. rajesh_101" style="padding-left: 45px;" autocomplete="new-password" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Login Password</label>
                                <div style="position: relative;">
                                    <i class='bx bx-lock-alt' style="position: absolute; left: 16px; top: 14px; color: var(--text-gray);"></i>
                                    <input type="password" name="password" required placeholder="••••••••" class="pwd-input" style="padding-left: 45px; padding-right: 40px;" autocomplete="new-password">
                                    <i class='bx bx-hide pwd-toggle' style="position: absolute; right: 16px; top: 14px; color: var(--text-gray); cursor: pointer; font-size: 20px;"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div style="margin-bottom: 30px;">
                        <div class="section-title">Personal Profile</div>
                        <div class="form-group">
                            <label>Resident Full Name</label>
                            <input type="text" name="name" required placeholder="Legal Name of Resident" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px;">
                            <div class="form-group">
                                <label>Flat / Room No.</label>
                                <input type="text" id="roomNoInput" name="room_no" placeholder="e.g. 104" value="<?php echo htmlspecialchars($_POST['room_no'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>Block / Building</label>
                                <input type="text" name="block" placeholder="e.g. Block A" value="<?php echo htmlspecialchars($_POST['block'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>Floor</label>
                                <input type="text" name="floor" placeholder="e.g. 2nd Floor" value="<?php echo htmlspecialchars($_POST['floor'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>Parking Slot</label>
                                <input type="text" name="parking" placeholder="e.g. A-15" value="<?php echo htmlspecialchars($_POST['parking'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>Phone Number</label>
                                <div style="position: relative;">
                                    <i class='bx bx-phone' style="position: absolute; left: 16px; top: 14px; color: var(--text-gray);"></i>
                                    <input type="text" name="phone" placeholder="91XXXXXXXX" style="padding-left: 45px;" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Email Address</label>
                                <div style="position: relative;">
                                    <i class='bx bx-envelope' style="position: absolute; left: 16px; top: 14px; color: var(--text-gray);"></i>
                                    <input type="email" name="email" placeholder="renter@example.com" style="padding-left: 45px;" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px; margin-top:24px;">
                            <div class="form-group">
                                <label>Joining Date</label>
                                <div style="position: relative;">
                                    <i class='bx bx-calendar' style="position: absolute; left: 16px; top: 14px; color: var(--text-gray);"></i>
                                    <input type="date" name="joining_date" style="padding-left: 45px;" value="<?php echo htmlspecialchars($_POST['joining_date'] ?? date('Y-m-d')); ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div style="margin-bottom: 30px;">
                        <div class="section-title">Initial Utility Setup</div>
                        <div class="form-group">
                            <label>Starting Meter Reading (Previous Month Units)</label>
                            <div style="position: relative;">
                                <i class='bx bx-bolt-circle' style="position: absolute; left: 16px; top: 14px; color: var(--text-gray);"></i>
                                <input type="number" id="baseReadingInput" name="base_reading" value="<?php echo htmlspecialchars($_POST['base_reading'] ?? '0'); ?>" style="padding-left: 45px; transition: background-color 0.3s ease;">
                            </div>
                            <p style="font-size: 11px; color: var(--text-gray); margin-top: 8px;">This will be used as the "Last Reading" for the first bill.</p>
                        </div>
                    </div>

                    <div style="margin-bottom: 30px;">
                        <div class="section-title">Financial Initial Setup</div>
                        <div class="form-group">
                            <div style="display: flex; gap: 10px; align-items: stretch; width: 100%;">
                                <div style="flex: 1;">
                                    <label class="form-label" style="font-weight: 600; color: var(--text-dark);" title="Total cash collected at joining">Total Initial Payment (₹)</label>
                                <div style="position: relative; height: 48px;">
                                    <i class='bx bx-wallet' style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94A3B8; font-size: 20px;"></i>
                                    <input type="number" step="0.01" name="advance_payment" id="totalPaymentInput" value="0" style="padding-left: 40px; height: 100%; border-radius: 12px; border: 1px solid var(--border);" placeholder="0">
                                </div>
                            </div>
                                <div style="display: flex; align-items: flex-end;">
                                    <button type="button" class="btn-outline" onclick="generateAdvanceQR()" style="padding: 0 16px; border-radius: 12px; height: 48px; flex-shrink: 0;"><i class='bx bx-qr-scan'></i> QR</button>
                                </div>
                            </div>
                            <p style="font-size: 11px; color: var(--text-gray); margin-top: 8px;">Record the total payment received during onboarding. Security Deposit will be automatically set equal to 1 month's rent.</p>
                            <div id="advanceQRContainer" style="display: none; margin-top: 15px; text-align: center; background: var(--white); padding: 15px; border-radius: 12px; border: 1px solid var(--border);">
                                <img id="advanceQRImg" src="" alt="Advance QR" style="width: 150px; height: 150px; display: inline-block;">
                                <p style="font-size: 11px; font-weight: 600; color: #10B981; margin-top: 8px;">Scan to pay Advance via UPI</p>
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px;">
                            <div class="form-group">
                                <label>Monthly Rent Amount</label>
                                <div style="position: relative; display: flex; align-items: center;">
                                    <span style="position: absolute; left: 16px; font-size: 15px; color: #94A3B8; font-weight: 600; pointer-events: none;">₹</span>
                                    <input type="number" step="0.01" name="fixed_rent" id="fixedRentInput" value="<?php echo htmlspecialchars($_POST['fixed_rent'] ?? '0'); ?>" style="padding-left: 40px;" placeholder="0">
                                </div>
                                <p style="font-size: 11px; color: var(--text-gray); margin-top: 8px;">Fixed monthly rent for this renter.</p>
                            </div>
                            <div class="form-group">
                                <label>Monthly Maintenance Amount</label>
                                <div style="position: relative; display: flex; align-items: center;">
                                    <span style="position: absolute; left: 16px; font-size: 15px; color: #94A3B8; font-weight: 600; pointer-events: none;">₹</span>
                                    <input type="number" step="0.01" name="fixed_maintenance" value="<?php echo htmlspecialchars($_POST['fixed_maintenance'] ?? '0'); ?>" style="padding-left: 40px;" placeholder="0">
                                </div>
                                <p style="font-size: 11px; color: var(--text-gray); margin-top: 8px;">Fixed monthly maintenance for this renter.</p>
                            </div>
                        </div>
                    </div>

                    <div style="margin-top: 20px;">
                        <button type="submit" id="submitBtn" class="btn-primary submit-btn" style="width: 100%; justify-content: center; padding: 18px; font-size: 16px; border-radius: 16px; transition: all 0.3s ease;">
                            <span class="btn-content" style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                                <i class='bx bx-user-plus'></i> Confirm and Create Account
                            </span>
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
    if (amount === '' || parseFloat(amount) < 0) {
        alert('Advance Payment must be 0 or greater');
        return;
    }
    
    let secAmount = document.getElementsByName('security_deposit')[0].value;
    if (secAmount === '' || parseFloat(secAmount) < 0) {
        alert('Security Deposit must be 0 or greater');
        return;
    }
    
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

function confirmSubmission(e) {
    const totalPayment = parseFloat(document.getElementById('totalPaymentInput').value) || 0;
    const securityDeposit = parseFloat(document.getElementById('securityDepositInput').value) || 0;
    const fixedRent = parseFloat(document.getElementById('fixedRentInput').value) || 0;
    
    const expectedFull = securityDeposit + fixedRent;
    
    if (totalPayment > 0 && totalPayment !== expectedFull && totalPayment !== securityDeposit) {
        const proceed = confirm(`Warning:\nThe Total Initial Payment entered (₹${totalPayment}) does not exactly match the expected Security Deposit (₹${securityDeposit}) or Full Onboarding (₹${expectedFull}).\n\nAre you sure you want to proceed with this custom amount?`);
        if (!proceed) {
            e.preventDefault();
            return false;
        }
    }
    return true;
}

document.querySelector('form').addEventListener('submit', confirmSubmission);

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

// Auto-hide zero values on focus for better UX
document.querySelectorAll('input[type="number"]').forEach(input => {
    input.addEventListener('focus', function() {
        if (this.value === '0' || this.value === '0.00') {
            this.value = '';
        }
    });
    input.addEventListener('blur', function() {
        if (this.value.trim() === '') {
            this.value = '0';
        }
    });
});

document.getElementById('addRenterForm').addEventListener('submit', function() {
    const btn = document.getElementById('submitBtn');
    if (btn) {
        btn.querySelector('.btn-content').innerHTML = `<i class='bx bx-loader-alt bx-spin' style='font-size: 20px;'></i> <span>Creating Secure Profile...</span>`;
        btn.style.opacity = '0.9';
        btn.style.cursor = 'wait';
        btn.style.background = 'linear-gradient(135deg, #4F46E5, #7C3AED)';
        setTimeout(() => { btn.style.pointerEvents = 'none'; }, 10);
    }
});
</script>

</body>
</html>

<?php
// onboarding-guide.php - Professional 2-Page Resident Handbook
require_once "db.php";

$renter_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$renter_name = "Resident Name";
$room_no = "___";
$username = "your_username";
$password = "your_password";

if ($renter_id > 0) {
    $stmt = mysqli_prepare($conn, "SELECT name, room_no, username FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $renter_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($res)) {
        $renter_name = $row['name'];
        $room_no = $row['room_no'];
        $username = $row['username'];
    }
    mysqli_stmt_close($stmt);
}

// Allow passing password via URL for printing, OR default to placeholder
$password = isset($_GET['pass']) ? htmlspecialchars($_GET['pass']) : "123456"; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resident Handbook | <?php echo HOUSE_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #6366F1;
            --primary-dark: #4F46E5;
            --secondary: #10B981;
            --accent: #F59E0B;
            --bg-body: #F1F5F9;
            --text-main: #1E293B;
            --text-muted: #64748B;
            --white: #FFFFFF;
            --border: #E2E8F0;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Outfit', sans-serif; 
            background: var(--bg-body); 
            color: var(--text-main); 
            line-height: 1.4;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Container for screen viewing */
        .handbook-wrapper {
            max-width: 900px;
            margin: 40px auto;
            padding: 20px;
        }

        /* Page Structure */
        .page {
            width: 210mm;
            height: 297mm;
            background: var(--white);
            margin: 0 auto 30px auto;
            padding: 20mm;
            position: relative;
            box-shadow: 0 10px 40px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid var(--primary);
            padding-bottom: 12px;
            margin-bottom: 25px;
        }

        .house-brand { display: flex; align-items: center; gap: 10px; }
        .house-brand i { font-size: 28px; color: var(--primary); }
        .house-brand h1 { font-size: 22px; font-weight: 800; color: var(--primary); text-transform: uppercase; letter-spacing: 0.5px; }
        .handbook-title h2 { font-size: 13px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1.5px; }

        /* Welcome Section */
        .welcome-hero {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 20px;
            padding: 25px;
            color: white;
            margin-bottom: 25px;
        }
        .welcome-hero h3 { font-size: 24px; font-weight: 700; margin-bottom: 5px; }
        .welcome-hero p { font-size: 14px; opacity: 0.9; }

        /* Credentials Wrapper */
        .cred-wrapper {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 30px;
        }
        .cred-card {
            background: #F8FAFC; border: 1px solid var(--border); border-radius: 12px; padding: 15px; text-align: center;
        }
        .cred-card label { display: block; font-size: 10px; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 5px; }
        .cred-card span { font-size: 16px; font-weight: 800; color: var(--primary-dark); font-family: monospace; }

        /* Step List */
        .section-tag {
            display: inline-flex; align-items: center; gap: 6px; background: #EEF2FF; color: var(--primary-dark); padding: 5px 12px; border-radius: 100px; font-size: 11px; font-weight: 700; margin-bottom: 15px; text-transform: uppercase;
        }
        .step-entry { display: flex; gap: 20px; margin-bottom: 25px; }
        .step-num { width: 32px; height: 32px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 14px; flex-shrink: 0; }
        .step-body h4 { font-size: 16px; font-weight: 700; margin-bottom: 6px; }
        .step-body p { font-size: 13px; color: var(--text-muted); margin-bottom: 12px; }
        .step-img { border-radius: 10px; border: 1px solid var(--border); max-width: 420px; width: 100%; max-height: 240px; object-fit: contain; }

        /* Special Callouts */
        .security-hero {
            background: #FFFBEB; border-left: 5px solid var(--accent); border-radius: 12px; padding: 20px; margin-bottom: 25px; display: flex; gap: 15px; align-items: center;
        }
        .security-hero i { font-size: 32px; color: var(--accent); }
        .security-hero h4 { font-size: 16px; font-weight: 800; color: #92400E; margin-bottom: 2px; }
        .security-hero p { font-size: 13px; color: #B45309; }

        .payment-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .payment-img { border-radius: 10px; border: 1px solid var(--border); width: 100%; height: 200px; object-fit: contain; background: #F8FAFC; }

        .feature-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 25px; }
        .feature-card { background: #F5F3FF; padding: 15px; border-radius: 12px; display: flex; gap: 10px; }
        .feature-card i { font-size: 24px; color: #7C3AED; }
        .feature-card h5 { font-size: 14px; font-weight: 700; color: #5B21B6; }
        .feature-card p { font-size: 11px; color: #7C3AED; }

        /* Footer Access Panel */
        .footer-cta {
            background: #1E293B; color: white; border-radius: 20px; padding: 25px; margin-top: auto; display: flex; justify-content: space-between; align-items: center;
        }
        .cta-info h3 { font-size: 20px; font-weight: 700; margin-bottom: 5px; }
        .cta-link { font-size: 18px; color: var(--secondary); font-weight: 700; font-family: monospace; display: block; margin-bottom: 8px; }
        .cta-info p { font-size: 12px; opacity: 0.7; }
        .qr-box { background: white; padding: 8px; border-radius: 12px; width: 120px; height: 120px; flex-shrink: 0; }
        .qr-box img { width: 100%; height: 100%; object-fit: contain; }

        .copyright-tag { text-align: center; font-size: 10px; color: var(--text-muted); margin-top: 20px; }

        /* PRINT STYLES - STRICTOR */
        @media print {
            body { background: white !important; margin: 0 !important; padding: 0 !important; }
            .handbook-wrapper { margin: 0 !important; padding: 0 !important; max-width: none !important; }
            .page { 
                margin: 0 !important; 
                box-shadow: none !important; 
                width: 210mm !important; 
                height: 297mm !important; 
                padding: 15mm 15mm !important; 
                page-break-after: always !important; 
                break-after: page !important;
                display: flex !important;
            }
            .no-print { display: none !important; }
        }

        /* Interactive UI */
        .no-print-btn {
            position: fixed; bottom: 30px; right: 30px; background: var(--primary); color: white; padding: 15px 30px; border-radius: 50px; font-weight: 700; border: none; cursor: pointer; box-shadow: 0 10px 30px rgba(99,102,241,0.4); z-index: 1000;
        }
        [contenteditable="true"]:hover { background: rgba(99,102,241,0.05); outline: 2px dashed var(--primary); border-radius: 4px; }
    </style>
</head>
<body>

<button class="no-print-btn no-print" onclick="window.print()">
    <i class='bx bx-printer'></i> Print Perfect Handbook
</button>

<div class="handbook-wrapper">

    <!-- PAGE 1 -->
    <div class="page">
        <div class="header">
            <div class="house-brand">
                <i class='bx bxs-home-heart'></i>
                <h1><?php echo HOUSE_NAME; ?></h1>
            </div>
            <div class="handbook-title">
                <h2>Resident Quickstart</h2>
            </div>
        </div>

        <div class="welcome-hero">
            <h3>Welcome, <span contenteditable="true"><?php echo htmlspecialchars($renter_name); ?></span>!</h3>
            <p>We are excited to have you. Use this personalized handbook to manage your bills and apartment services digitsally.</p>
        </div>

        <div class="cred-wrapper">
            <div class="cred-card">
                <label>User ID</label>
                <span contenteditable="true"><?php echo htmlspecialchars($username); ?></span>
            </div>
            <div class="cred-card">
                <label>Temp Password</label>
                <span contenteditable="true"><?php echo htmlspecialchars($password); ?></span>
            </div>
            <div class="cred-card">
                <label>Room No.</label>
                <span contenteditable="true"><?php echo htmlspecialchars($room_no); ?></span>
            </div>
        </div>

        <div class="section-tag">Part 1: Initial Access</div>

        <div class="step-entry">
            <div class="step-num">1</div>
            <div class="step-body">
                <h4>Open the Dashboard</h4>
                <p>Scan the code on Page 2 or visit the portal URL. Select the <b>Resident</b> portal.</p>
                <img src="assets/img/guide-home.png" class="step-img" alt="Step 1">
            </div>
        </div>

        <div class="step-entry" style="margin-bottom: 0;">
            <div class="step-num">2</div>
            <div class="step-body">
                <h4>Your Live Status</h4>
                <p>Log in to see your current electricity reading and rent status instantly.</p>
                <img src="assets/img/guide-dashboard.png" class="step-img" alt="Step 2">
            </div>
        </div>
    </div>

    <!-- PAGE 2 -->
    <div class="page">
        <div class="header">
            <div class="house-brand">
                <i class='bx bxs-shield-alt-2'></i>
                <h1>Portal Control</h1>
            </div>
            <div class="handbook-title">
                <h2>Security & Bills</h2>
            </div>
        </div>

        <div class="security-hero">
            <i class='bx bxs-lock-alt'></i>
            <div>
                <h4>Security Alert</h4>
                <p>On your first login, the system will ask you to change your password. Keep your new password strictly private for your privacy.</p>
            </div>
        </div>

        <div style="text-align: center; margin-bottom: 25px;">
            <img src="assets/img/guide-secure-account.png" class="step-img" style="max-height: 200px;" alt="Secure">
        </div>

        <div class="section-tag">Part 2: Payments & Features</div>

        <div class="step-entry">
            <div class="step-num">3</div>
            <div class="step-body">
                <h4>Instant UPI Payments</h4>
                <p>Click <b>Pay Now</b> and scan the dynamic UPI QR code with any app (GPay/PhonePe). Enter the UTR ID to confirm.</p>
                <div class="payment-grid">
                    <img src="assets/img/guide-pay-btn.png" class="payment-img" alt="Pay Btn">
                    <img src="assets/img/guide-pay-qr.png" class="payment-img" alt="Pay QR">
                </div>
            </div>
        </div>

        <div class="feature-grid">
            <div class="feature-card">
                <i class='bx bxs-moon'></i>
                <div>
                    <h5>Self Support</h5>
                    <p>Raise tickets for plumbing/electrical issues instantly.</p>
                </div>
            </div>
            <div class="feature-card">
                <i class='bx bxs-zap'></i>
                <div>
                    <h5>Dark Mode</h5>
                    <p>Toggle for a premium dark visual experience.</p>
                </div>
            </div>
        </div>

        <div class="footer-cta">
            <div class="cta-info">
                <h3>Scan to Login</h3>
                <span class="cta-link">https://succorkart.in</span>
                <p>Password issues? Visit Room 101 for help.</p>
            </div>
            <div class="qr-box">
                <img src="assets/img/website-qr.jpg" alt="Portal QR">
            </div>
        </div>

        <div class="copyright-tag">
            &copy; <?php echo date('Y'); ?> Madhav Kunj Management System. Professional Home Billing.
        </div>
    </div>

</div>

</body>
</html>

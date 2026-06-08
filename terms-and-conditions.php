<?php
// terms-and-conditions.php
require_once "config.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Terms and Conditions | <?php echo HOUSE_NAME; ?></title>
    <link rel="icon" type="image/png" href="assets/img/favicon.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
        :root {
            --bg-page: #f4f7f9;
            --bg-card: #ffffff;
            --bg-section: #f8fafc;
            --border-color: #e2e8f0;
            --text-heading: #0f172a;
            --text-body: #475569;
            --text-muted: #64748b;
            --accent: #1f7f6a;
        }

        body { 
            font-family: "Inter", system-ui, sans-serif; 
            margin: 0;
            background-color: var(--bg-page);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            color: var(--text-body);
        }

        .wrap {
            flex: 1;
            padding: 60px 20px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }

        .legal-container {
            background: var(--bg-card);
            border-radius: 20px;
            padding: 50px 60px;
            box-shadow: 0 10px 40px -10px rgba(0,0,0,0.05);
            border: 1px solid rgba(0,0,0,0.03);
            max-width: 800px;
            width: 100%;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--text-muted);
            font-weight: 500;
            font-size: 14px;
            text-decoration: none;
            transition: color 0.2s ease;
            margin-bottom: 30px;
        }

        .back-link:hover {
            color: var(--accent);
        }

        .legal-header {
            margin-bottom: 40px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 30px;
        }

        .legal-header h1 {
            font-size: 36px;
            font-weight: 800;
            color: var(--text-heading);
            margin: 0 0 12px 0;
            letter-spacing: -0.5px;
        }

        .last-updated {
            color: var(--text-muted);
            font-size: 14px;
            font-weight: 500;
        }

        section {
            background: var(--bg-section);
            padding: 32px 40px;
            border-radius: 16px;
            margin-bottom: 24px;
            border: 1px solid var(--border-color);
        }

        h2 {
            font-size: 20px;
            font-weight: 700;
            margin-top: 0;
            margin-bottom: 16px;
            color: var(--text-heading);
        }

        h3 {
            font-size: 17px;
            font-weight: 600;
            margin-top: 24px;
            margin-bottom: 12px;
            color: var(--text-heading);
        }

        p {
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 16px;
            line-height: 1.7;
            color: var(--text-body);
        }
        
        p:last-child {
            margin-bottom: 0;
        }

        ul {
            margin-top: 0;
            margin-bottom: 20px;
            padding-left: 24px;
        }
        
        ul:last-child {
            margin-bottom: 0;
        }

        li {
            margin-bottom: 12px;
            font-size: 16px;
            line-height: 1.7;
            color: var(--text-body);
        }
        
        li:last-child {
            margin-bottom: 0;
        }

        .legal-footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            color: var(--text-muted);
            font-size: 13px;
        }

        @media (max-width: 768px) {
            .wrap { padding: 40px 16px; }
            .legal-container { padding: 40px 24px; border-radius: 16px; }
            section { padding: 24px; margin-bottom: 20px; }
            .legal-header h1 { font-size: 28px; }
            h2 { font-size: 18px; }
        }
    </style>
</head>
<body>

    <div class="wrap">
        <div class="legal-container animate-up">
        <a href="index.php" class="back-link">
            ← Back to Login
        </a>

        <div class="legal-header">
            <h1>Terms and Conditions</h1>
            <div class="last-updated">Last updated: <?php echo date('F d, Y'); ?></div>
        </div>

        <section>
            <h2>1. Acceptance of Terms</h2>
            <p>By accessing and using the <?php echo HOUSE_NAME; ?> Rent Manager system (the "Service"), you agree to be bound by these Terms and Conditions. If you disagree with any part of these terms, you may not access the Service.</p>
        </section>

        <section>
            <h2>2. User Responsibilities</h2>
            <p>As a Resident using this system, you agree to:</p>
            <ul>
                <li>Maintain the confidentiality of your login credentials.</li>
                <li>Provide accurate information when updating your profile or reporting payments.</li>
                <li>Use the platform solely for managing your tenancy-related data and payments.</li>
                <li>Notify the administrator immediately of any unauthorized access to your account.</li>
            </ul>
        </section>

        <section>
            <h2>3. Admin Responsibilities</h2>
            <p>The system administrators agree to:</p>
            <ul>
                <li>Ensure the accuracy of rent and electricity bill generation to the best of their ability.</li>
                <li>Protect user data in accordance with our Privacy Policy.</li>
                <li>Respond to support queries and complaints in a timely manner.</li>
                <li>Maintain system uptime and accessibility during normal operating hours.</li>
            </ul>
        </section>

        <section>
            <h2>4. System Usage Rules</h2>
            <p>Users must not:</p>
            <ul>
                <li>Attempt to bypass any security features or gain unauthorized access to other user accounts.</li>
                <li>Upload malicious software or code to the platform.</li>
                <li>Use the system for any illegal or fraudulent activities.</li>
                <li>Interfere with the proper functioning of the Service.</li>
            </ul>
        </section>

        <section>
            <h2>5. Payment & Billing Disclaimer</h2>
            <p>The Rent Manager system generates bill estimates based on input data (e.g., meter readings). While we strive for 100% accuracy, the Service is provided "as is". Final billing amounts are subject to verification by the property manager. All payments carried out via dynamic UPI QR codes are processed through third-party banking networks, and we are not liable for transaction failures outside our control.</p>
        </section>

        <section>
            <h2>6. Limitation of Liability</h2>
            <p>In no event shall <?php echo HOUSE_NAME; ?> or its developers be liable for any indirect, incidental, special, consequential, or punitive damages, including without limitation, loss of profits, data, use, goodwill, or other intangible losses, resulting from your access to or use of or inability to access or use the Service.</p>
        </section>

        <section>
            <h2>7. Termination of Access</h2>
            <p>We reserve the right to terminate or suspend access to our Service immediately, without prior notice or liability, for any reason whatsoever, including without limitation if you breach the Terms and Conditions.</p>
        </section>

        <section>
            <h2>8. Changes to Terms</h2>
            <p>We reserve the right, at our sole discretion, to modify or replace these Terms at any time. What constitutes a material change will be determined at our sole discretion.</p>
        </section>

        <section>
            <h2>9. Governing Law</h2>
            <p>These Terms shall be governed and construed in accordance with the local laws of the jurisdiction in which the property is located, without regard to its conflict of law provisions.</p>
        </section>

        <section>
            <h2>10. Contact Information</h2>
            <p>If you have any questions about these Terms, please contact the system administrator.</p>
        </section>

        <div class="legal-footer">
            &copy; <?php echo date('Y'); ?> <?php echo SYSTEM_NAME; ?>. All rights reserved.
        </div>
        </div>
    </div>

</body>
</html>

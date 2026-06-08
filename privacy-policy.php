<?php
// privacy-policy.php
require_once "config.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Privacy Policy | <?php echo HOUSE_NAME; ?></title>
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
            <h1>Privacy Policy</h1>
            <div class="last-updated">Last updated: <?php echo date('F d, Y'); ?></div>
        </div>

        <section>
            <h2>1. Introduction</h2>
            <p>Welcome to <?php echo HOUSE_NAME; ?>. We are committed to protecting your personal information and your right to privacy. This Privacy Policy explains how we collect, use, and safeguard your information when you use our Rent Manager application.</p>
        </section>

        <section>
            <h2>2. Information We Collect</h2>
            <p>We collect personal information that you voluntarily provide to us when you register on the system, express an interest in obtaining information about us or our services, or otherwise when you contact us.</p>
            <ul>
                <li><strong>Personal Data:</strong> Name, email address, phone number, and room details.</li>
                <li><strong>Payment Data:</strong> Transaction IDs and payment verification screenshots provided during bill settlement.</li>
                <li><strong>Log Data:</strong> IP addresses, browser type, and timestamps of your logins for security auditing.</li>
                <li><strong>Usage Data:</strong> Information on how you interact with our dashboard and features.</li>
            </ul>
        </section>

        <section>
            <h2>3. How We Use Information</h2>
            <p>We use the information we collect for the following purposes:</p>
            <ul>
                <li>To provide and maintain our Service, including to monitor the usage of our Service.</li>
                <li>To manage your account, including managing your registration as a user of the Service.</li>
                <li>To process and verify rent and electricity payments.</li>
                <li>To send automated reminders and notifications via email.</li>
                <li>To respond to user queries and support requests.</li>
                <li>To improve the security and functionality of our platform.</li>
            </ul>
        </section>

        <section>
            <h2>4. Data Protection & Security</h2>
            <p>We implement a variety of security measures to maintain the safety of your personal information. We use administrative, technical, and physical security measures to help protect your personal information. While we have taken reasonable steps to secure the personal information you provide to us, please be aware that despite our efforts, no security measures are perfect or impenetrable.</p>
        </section>

        <section>
            <h2>5. Information Sharing</h2>
            <p>We do not sell, trade, or otherwise transfer your personally identifiable information to outside parties. This does not include trusted third parties who assist us in operating our website, conducting our business, or serving our users, so long as those parties agree to keep this information confidential.</p>
        </section>

        <section>
            <h2>6. User Rights</h2>
            <p>As a user of our system, you have the following rights regarding your data:</p>
            <ul>
                <li>The right to access the personal data we hold about you.</li>
                <li>The right to request correction of inaccurate data.</li>
                <li>The right to request deletion of your data (subject to administrative requirements and local laws).</li>
                <li>The right to withdraw consent for certain data processing activities.</li>
            </ul>
        </section>

        <section>
            <h2>7. Changes to This Policy</h2>
            <p>We may update our Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on this page. You are advised to review this Privacy Policy periodically for any changes.</p>
        </section>

        <section>
            <h2>8. Contact Information</h2>
            <p>If you have any questions or suggestions about our Privacy Policy, do not hesitate to contact the system administrator or the property owner/manager.</p>
        </section>

        <div class="legal-footer">
            &copy; <?php echo date('Y'); ?> <?php echo SYSTEM_NAME; ?>. All rights reserved.
        </div>
        </div>
    </div>

</body>
</html>

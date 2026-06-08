<?php
// cookie-policy.php
require_once "config.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Cookie Policy | <?php echo HOUSE_NAME; ?></title>
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
            <h1>Cookie Policy</h1>
            <div class="last-updated">Last updated: <?php echo date('F d, Y'); ?></div>
        </div>

        <section>
            <h2>1. What Are Cookies</h2>
            <p>Cookies are small text files that are placed on your computer or mobile device by websites that you visit. They are widely used to make websites work, or work more efficiently, as well as to provide information to the owners of the site.</p>
        </section>

        <section>
            <h2>2. How We Use Cookies</h2>
            <p>We use cookies to improve your experience on our Service by:</p>
            <ul>
                <li>Keeping you signed in to your dashboard during your session.</li>
                <li>Remembering your theme preferences (e.g., Light Mode or Dark Mode).</li>
                <li>Securing your session and protecting against CSRF (Cross-Site Request Forgery) attacks.</li>
                <li>Analyzing basic usage patterns to improve system performance.</li>
            </ul>
        </section>

        <section>
            <h2>3. Types of Cookies Used</h2>
            <ul>
                <li><strong>Essential Cookies:</strong> These are strictly necessary to provide you with the services available through our system and to use some of its features, such as access to secure areas.</li>
                <li><strong>Preference Cookies:</strong> These cookies allow our system to remember choices you make when you use the dashboard (such as your theme setting) to provide a more personalized experience.</li>
                <li><strong>Session Cookies:</strong> These are temporary cookies that expire once you close your browser or your session timeouts. They are used to link your actions during a single browser session.</li>
            </ul>
        </section>

        <section>
            <h2>4. Managing Cookies</h2>
            <p>Most web browsers allow some control of most cookies through the browser settings. To find out more about cookies, including how to see what cookies have been set and how to manage and delete them, visit <a href="https://www.aboutcookies.org" target="_blank">www.aboutcookies.org</a> or <a href="https://www.allaboutcookies.org" target="_blank">www.allaboutcookies.org</a>.</p>
            <p>Please note that if you choose to block or delete cookies, certain features of our Service (like staying logged in) may no longer function correctly.</p>
        </section>

        <section>
            <h2>5. Changes to This Policy</h2>
            <p>We may update our Cookie Policy from time to time to reflect changes in the cookies we use or for other operational, legal, or regulatory reasons. Please revisit this Cookie Policy regularly to stay informed about our use of cookies.</p>
        </section>

        <div class="legal-footer">
            &copy; <?php echo date('Y'); ?> <?php echo SYSTEM_NAME; ?>. All rights reserved.
        </div>
        </div>
    </div>

</body>
</html>

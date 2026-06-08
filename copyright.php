<?php
// copyright.php
require_once "config.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Copyright Notice | <?php echo HOUSE_NAME; ?></title>
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
            <h1>Copyright Notice</h1>
            <div class="last-updated">Last updated: <?php echo date('F d, Y'); ?></div>
        </div>

        <section>
            <h2>1. Ownership of Content</h2>
            <p>The <?php echo HOUSE_NAME; ?> Rent Manager application, including but not limited to its design, layout, look, appearance, graphics, source code, and software functionality, is the property of the system administrators and developers. All content is protected by international copyright, trademark, and other intellectual property laws.</p>
        </section>

        <section>
            <h2>2. Permitted Use</h2>
            <p>Users are granted a non-exclusive, non-transferable, and limited license to access and use the Service for personal, non-commercial purposes related to their residency management. This includes:</p>
            <ul>
                <li>Viewing your personal rent and electricity records.</li>
                <li>Downloading or printing generated bills and payment receipts for your personal records.</li>
                <li>Raising support queries through the provided modules.</li>
            </ul>
        </section>

        <section>
            <h2>3. Restrictions</h2>
            <p>Except as expressly permitted, you must not:</p>
            <ul>
                <li>Reproduce, duplicate, copy, sell, resell, or exploit any portion of the Service.</li>
                <li>Modify, adapt, or hack the Service or modify another website so as to falsely imply that it is associated with the Service.</li>
                <li>Remove any copyright, trademark, or other proprietary notices from the Service.</li>
                <li>Use any automated system (robots, spiders, or scrapers) to access the Service for any purpose.</li>
            </ul>
        </section>

        <section>
            <h2>4. Trademarks</h2>
            <p>All trademarks, logos, and service marks displayed on the platform are the property of their respective owners. You are not permitted to use these marks without the prior written consent of the owner.</p>
        </section>

        <section>
            <h2>5. Reporting Violations</h2>
            <p>If you believe that any content in the Service violates your copyright, please contact the system administrator immediately with a detailed description of the alleged violation.</p>
        </section>

        <div class="legal-footer">
            &copy; <?php echo date('Y'); ?> <?php echo SYSTEM_NAME; ?>. All rights reserved.
        </div>
        </div>
    </div>

</body>
</html>

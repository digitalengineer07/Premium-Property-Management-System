<?php
require_once "../db.php";
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>About Developer | <?php echo HOUSE_NAME; ?></title>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link rel="manifest" href="../manifest.json">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-design-system.css?v=<?php echo time(); ?>">
    <script>
      if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
          navigator.serviceWorker.register('../sw.js').then(reg => {
            console.log('SW registered');
          }).catch(err => {
            console.log('SW failed', err);
          });
        });
      }
    </script>
    <style>
        /* Consistent spacing for about-dev page */
        .dev-container {
            max-width: 1000px;
            margin: 0 auto;
        }
        .dev-header-card {
            background: var(--white);
            border-radius: 32px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
            margin-bottom: 30px;
            position: relative;
        }
        .dev-banner {
            height: 160px;
            background: linear-gradient(135deg, var(--primary-purple), #8B5CF6);
            position: relative;
        }
        .dev-profile-section {
            padding: 0 40px 40px 40px;
            margin-top: -60px;
            display: flex;
            align-items: flex-end;
            gap: 30px;
            position: relative;
            z-index: 2;
        }
        .dev-img-wrapper {
            width: 180px;
            height: 180px;
            border-radius: 24px;
            border: 6px solid var(--white);
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            background: var(--bg-main);
        }
        .dev-img-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .dev-info {
            padding-bottom: 10px;
            flex: 1;
        }
        .dev-name {
            font-size: 32px;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .dev-tagline {
            font-size: 16px;
            color: var(--text-gray);
            font-weight: 500;
        }
        .dev-grid {
            display: grid;
            grid-template-columns: 1.6fr 1fr;
            gap: 30px;
        }
        .content-card {
            background: var(--white);
            padding: 32px;
            border-radius: 24px;
            box-shadow: var(--card-shadow);
        }
        .section-title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--text-dark);
        }
        .section-title i {
            color: var(--primary-purple);
        }
        .skill-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            background: #F4F4FF;
            color: var(--primary-purple);
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
            margin: 4px;
        }
        :root.dark-theme .skill-pill {
            background: rgba(98, 75, 255, 0.1);
        }
        .contact-link {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 16px;
            background: var(--bg-main);
            border-radius: 16px;
            text-decoration: none;
            color: var(--text-dark);
            font-weight: 600;
            transition: var(--transition);
            margin-bottom: 12px;
        }
        .contact-link:hover {
            transform: translateX(10px);
            background: #F8F7FF;
            color: var(--primary-purple);
        }
        .dark-theme .contact-link:hover {
            background: rgba(98, 75, 255, 0.15);
        }
        .contact-link i {
            font-size: 24px;
        }
        .philosophy-box {
            background: #F8F7FF;
            padding: 25px;
            border-radius: 20px;
            border-left: 5px solid var(--primary-purple);
        }
        .dark-theme .philosophy-box {
            background: rgba(98, 75, 255, 0.1);
        }
        .journey-card {
            margin-top: 40px;
            text-align: center;
            background: linear-gradient(to bottom, #f9f9ff, #ffffff);
            padding: 30px;
            border-radius: 20px;
            border: 1px solid var(--border);
        }
        .dark-theme .journey-card {
            background: linear-gradient(to bottom, rgba(255,255,255,0.02), rgba(255,255,255,0.05));
        }
        @media (max-width: 768px) {
            .header-renter { 
                position: relative !important; 
                top: auto !important; 
                margin-bottom: 24px !important; 
                flex-direction: column; 
                align-items: center;
                gap: 15px;
                text-align: center;
            }
            .dev-banner { height: 120px; }
            .dev-profile-section {
                flex-direction: column;
                align-items: center;
                text-align: center;
                margin-top: -70px;
                padding: 0 20px 30px 20px;
                gap: 15px;
            }
            .dev-img-wrapper {
                width: 140px;
                height: 140px;
                border-width: 4px;
            }
            .dev-name {
                font-size: 24px;
                justify-content: center;
            }
            .dev-tagline { font-size: 14px; }
            .dev-tags {
                justify-content: center;
            }
            .dev-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            .content-card { padding: 24px 20px; }
            .section-title { font-size: 18px; }
            .philosophy-box { padding: 18px; }
            .philosophy-box p { font-size: 13px; }
            .contact-link { padding: 12px; font-size: 14px; }
        }
                    .user-profile-pill { display: flex; align-items: center; gap: 12px; cursor: pointer; padding-left: 12px; border-left: 1px solid var(--border); white-space: nowrap; }
        .user-avatar { width: 40px; height: 40px; background: var(--primary-purple); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 16px; box-shadow: 0 4px 10px rgba(98,75,255,0.2); }
        .user-info h4 { font-size: 14px; font-weight: 700; margin: 0; color: var(--text-dark); }
        .user-info p { font-size: 12px; color: var(--text-gray); margin: 0; }
    
        .mb-nav-center {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background: #624BFF;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            box-shadow: 0 6px 16px rgba(98, 75, 255, 0.4);
            cursor: pointer;
            margin-top: -24px;
            border: 4px solid var(--white, #FFFFFF);
            transition: transform 0.2s;
        }
        .dark-theme .mb-nav-center {
            border-color: #111827;
        }

</style>
</head>
<body style="display: block;">

<main class="main-renter">
        <!-- 1. EXCLUSIVE MOBILE VIEW CODE (Isolated in views/mobile/about-dev_mobile.php) -->
        <div class="mobile-view-wrapper">
            <?php include __DIR__ . '/views/mobile/about-dev_mobile.php'; ?>
        </div>

        <!-- 2. EXCLUSIVE DESKTOP VIEW CODE (Isolated in views/desktop/about-dev_desktop.php) -->
        <div class="desktop-view-wrapper">
            <?php include __DIR__ . '/views/desktop/about-dev_desktop.php'; ?>
        </div>
</main>

<script>
    const themeToggle = document.getElementById('themeToggle');
    if (localStorage.getItem('theme') === 'dark') {
        document.documentElement.classList.add('dark-theme');
        themeToggle?.classList.replace('bx-moon', 'bx-sun');
    }
    
</script>

</body>
</html>

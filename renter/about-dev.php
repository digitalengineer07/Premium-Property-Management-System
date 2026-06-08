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
    </style>
</head>
<body style="display: block;">

<main class="main-renter">
    <header class="header-renter">
        <div class="brand-renter">
            <img src="../assets/img/logo.png" alt="Logo" style="width: 32px; height: 32px; border-radius: 8px; object-fit: cover;">
            <span><?php echo HOUSE_NAME; ?></span>
        </div>
        <div style="display: flex; gap: 12px; align-items: center;">
            <i class='bx bx-moon' id="themeToggle" style="font-size: 24px; cursor: pointer; color: var(--text-gray);"></i>
            <a href="dashboard.php" class="btn-outline">Back to Dashboard</a>
        </div>
    </header>

    <div class="dev-container animate-up">
        <div class="dev-header-card">
            <div class="dev-banner"></div>
            <div class="dev-profile-section">
                <div class="dev-img-wrapper">
                    <img src="../assets/img/nikhil.png?v=<?php echo time(); ?>" alt="Nikhil Kr.">
                </div>
                <div class="dev-info">
                    <h1 class="dev-name">Nikhil Kr. <i class='bx bxs-check-circle' style='color:#624bff; font-size:24px;'></i></h1>
                    <p class="dev-tagline">Software Architect | B.Tech 3rd Year Student</p>
                    <div class="dev-tags" style="margin-top: 15px; display: flex; gap: 10px; flex-wrap: wrap;">
                        <span class="badge" style="background: rgba(98, 75, 255, 0.1); color: var(--primary-purple); border: 1px solid rgba(98, 75, 255, 0.2);">Full-Stack Engineering</span>
                        <span class="badge" style="background: rgba(16, 185, 129, 0.1); color: #10B981; border: 1px solid rgba(16, 185, 129, 0.2);">Database Design</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="dev-grid">
            <div class="left-col">
                <div class="content-card">
                    <h2 class="section-title"><i class='bx bx-user-circle'></i> About Me</h2>
                    <p style="color: var(--text-gray); line-height: 1.8; margin-bottom: 20px;">
                        Hello! I'm <strong>Nikhil Kr.</strong>, a dedicated Software Developer currently in my <strong>3rd year of B.Tech</strong>. I specialize in crafting high-performance web applications that bridge the gap between complex functionality and elegant user experience.
                    </p>
                    <p style="color: var(--text-gray); line-height: 1.8; margin-bottom: 20px;">
                        The <strong><?php echo HOUSE_NAME; ?></strong> project is a testament to my commitment to building practical, real-world solutions. My aim was to create a system that eliminates administrative friction through automation and modern design.
                    </p>
                    <div class="philosophy-box">
                        <p style="font-weight: 600; color: var(--text-dark); font-style: italic;">
                            "My philosophy is simple: write code that solves problems and designs that inspire confidence."
                        </p>
                    </div>
                </div>

                <div class="content-card" style="margin-top: 30px;">
                    <h2 class="section-title"><i class='bx bx-terminal'></i> Technical Expertise</h2>
                    <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                        <span class="skill-pill"><i class='bx bxl-php'></i> PHP Architecture</span>
                        <span class="skill-pill"><i class='bx bxl-mysql'></i> MySQL Mastery</span>
                        <span class="skill-pill"><i class='bx bxl-javascript'></i> Modern JS (ES6+)</span>
                        <span class="skill-pill"><i class='bx bxl-css3'></i> Advanced CSS3</span>
                        <span class="skill-pill"><i class='bx bx-layout'></i> UI/UX Design</span>
                        <span class="skill-pill"><i class='bx bx-git-branch'></i> Version Control</span>
                    </div>
                </div>
            </div>

            <div class="right-col">
                <div class="content-card" style="height: 100%;">
                    <h2 class="section-title"><i class='bx bx-link-alt'></i> Get in Touch</h2>
                    <p style="color: var(--text-gray); font-size: 14px; margin-bottom: 24px;">Always open for collaboration or interesting project discussions.</p>
                    
                    <a href="mailto:nikhil119124@gmail.com" class="contact-link" target="_blank">
                        <i class='bx bx-envelope' style='color: #EA4335;'></i>
                        Email Me
                    </a>
                    <a href="https://www.linkedin.com/in/ñíkhìl-kûmãr-4ab202239" class="contact-link" target="_blank">
                        <i class='bx bxl-linkedin-square' style='color: #0077B5;'></i>
                        LinkedIn
                    </a>
                    <a href="https://github.com/digitalengineer07" class="contact-link" target="_blank">
                        <i class='bx bxl-github' style='color: var(--text-dark);'></i>
                        GitHub
                    </a>

                    <div style="margin-top: 25px; padding: 18px; background: rgba(16, 185, 129, 0.08); border-radius: 16px; border: 1px dashed rgba(16, 185, 129, 0.4);">
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                            <span style="width: 10px; height: 10px; background: #10B981; border-radius: 50%; box-shadow: 0 0 8px rgba(16, 185, 129, 0.6);"></span>
                            <span style="color: #10B981; font-weight: 700; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">Available for Hire</span>
                        </div>
                        <p style="color: var(--text-gray); font-size: 13px; line-height: 1.6; margin: 0;">
                            🎓 <strong>B.Tech 3rd Year</strong> student.<br>
                            💼 Exploring internships &amp; projects.<br>
                            🌍 Based in India.<br>
                            Let's build something awesome together!
                        </p>
                    </div>
                    
                    <div class="journey-card">
                        <i class='bx bx-award' style='font-size: 40px; color: #F59E0B; margin-bottom: 15px;'></i>
                        <h4 style="margin-bottom: 5px; color: var(--text-dark);">3+ Years Journey</h4>
                        <p style="font-size: 12px; color: var(--text-gray);">10+ Projects Completed</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    const themeToggle = document.getElementById('themeToggle');
    if (localStorage.getItem('theme') === 'dark') {
        document.documentElement.classList.add('dark-theme');
        themeToggle?.classList.replace('bx-moon', 'bx-sun');
    }
    themeToggle?.addEventListener('click', () => {
        const isDark = document.documentElement.classList.toggle('dark-theme');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        if (isDark) themeToggle.classList.replace('bx-moon', 'bx-sun');
        else themeToggle.classList.replace('bx-sun', 'bx-moon');
    });
</script>

</body>
</html>

<?php
// EXCLUSIVE DESKTOP VIEW FOR ABOUT-DEV
?>
<header class="header-renter">
        <div class="brand-renter">
            <img src="../assets/img/logo.png" alt="Logo" style="width: 32px; height: 32px; border-radius: 8px; object-fit: cover;">
            <span><?php echo HOUSE_NAME; ?></span>
        </div>
        <div style="display: flex; gap: 12px; align-items: center;">
            <i class='bx bx-moon' id="themeToggle" style="font-size: 24px; cursor: pointer; color: var(--text-gray);" onclick="if(typeof toggleTheme==='function'){toggleTheme(event);}else{const d=!document.documentElement.classList.contains('dark-theme');document.documentElement.classList.toggle('dark-theme',d);if(document.body)document.body.classList.toggle('dark-theme',d);localStorage.setItem('theme',d?'dark':'light');const i=this.querySelector('i')||(this.tagName==='I'?this:null);if(i)i.className=d?'bx bx-sun':'bx bx-moon';}"></i>
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
<?php
require_once "../db.php";
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>About Developer | <?php echo HOUSE_NAME; ?></title>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-design-system.css">
    <style>
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
            position: relative;
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
            transition: var(--transition);
        }
        .dev-img-edit-overlay {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: var(--transition);
            cursor: pointer;
            color: white;
            font-weight: 600;
            gap: 8px;
            font-size: 15px;
        }
        .dev-img-wrapper:hover .dev-img-edit-overlay {
            opacity: 1;
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
            .dev-profile-section {
                flex-direction: column;
                align-items: center;
                text-align: center;
                margin-top: -90px;
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
            .dev-tagline { 
                font-size: 14px; 
            }
            .dev-tags {
                justify-content: center;
            }
            .dev-grid {
                grid-template-columns: 1fr;
            }
        }
        
        /* Cropper Modal styling */
        .crop-modal {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 10000;
            background: rgba(0, 0, 0, 0.85); align-items: center; justify-content: center; padding: 20px;
        }
        .crop-modal-content {
            background: var(--white); padding: 24px; border-radius: 24px; max-width: 600px; width: 100%;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
        .img-container { max-height: 400px; width: 100%; margin: 20px 0; background: #e5e7eb; overflow: hidden; border-radius: 12px; }
        .img-container img { max-width: 100%; display: block; }
        .crop-controls { display: flex; justify-content: space-between; gap: 10px; flex-wrap: wrap; margin-bottom: 20px; }
    </style>
</head>
<body>

<?php include "sidebar.php"; ?>

<main class="main">
    <header class="header">
        <div class="header-content">
            <div class="search-bar">
                <i class='bx bx-search'></i>
                <input type="text" placeholder="Explore Developer profile...">
            </div>
            <div class="user-profile">
                <i class='bx bx-moon' id="themeToggle"></i>
            </div>
        </div>
    </header>

    <div class="dev-container animate-up">
        <div class="dev-header-card">
            <div class="dev-banner"></div>
            <div class="dev-profile-section">
                <div class="dev-img-wrapper">
                    <img id="profileImage" src="../assets/img/nikhil.png?v=<?php echo time(); ?>" alt="Nikhil Kr.">
                    <div class="dev-img-edit-overlay" onclick="document.getElementById('photoInput').click()">
                        <i class='bx bx-camera'></i> Change
                    </div>
                </div>
                <input type="file" id="photoInput" accept=".jpg,.jpeg,.png,.webp" style="display: none;">
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

<!-- Cropper Modal -->
<div id="cropperModal" class="crop-modal">
    <div class="crop-modal-content animate-up">
        <h3 style="font-size: 20px; font-weight: 800; margin-bottom: 5px;"><i class='bx bx-crop'></i> Edit Photo</h3>
        <p style="color: var(--text-gray); font-size: 14px;">Crop and align the photo perfectly to maintain a crisp 1:1 aspect ratio layout. Hold Shift to constrain proportions.</p>
        
        <div class="img-container">
            <img id="imageToCrop" src="">
        </div>
        
        <div class="crop-controls">
            <div style="display: flex; gap: 10px;">
                <button type="button" class="btn-outline" onclick="cropper.zoom('0.1')" style="padding: 8px 12px;" title="Zoom In"><i class='bx bx-zoom-in'></i> Zoom In</button>
                <button type="button" class="btn-outline" onclick="cropper.zoom('-0.1')" style="padding: 8px 12px;" title="Zoom Out"><i class='bx bx-zoom-out'></i> Zoom Out</button>
            </div>
            <div style="display: flex; gap: 10px;">
                <button type="button" class="btn-outline" onclick="closeCropperModal()" style="border-color: #EF4444; color: #EF4444;">Cancel</button>
                <button type="button" class="btn-primary" onclick="saveCroppedImage()" id="btnSaveCrop"><i class='bx bx-upload'></i> Save & Upload</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
<script>
    let cropper;
    const photoInput = document.getElementById('photoInput');
    const cropperModal = document.getElementById('cropperModal');
    const imageToCrop = document.getElementById('imageToCrop');
    
    photoInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                imageToCrop.src = event.target.result;
                cropperModal.style.display = 'flex';
                
                if (cropper) cropper.destroy();
                // Initialize Cropper with strict aspect ratio
                cropper = new Cropper(imageToCrop, {
                    aspectRatio: 1,
                    viewMode: 1,
                    dragMode: 'move',
                    autoCropArea: 0.9,
                    restore: false,
                    guides: true,
                    center: true,
                    highlight: false,
                    cropBoxMovable: true,
                    cropBoxResizable: true,
                    toggleDragModeOnDblclick: false,
                });
            };
            reader.readAsDataURL(file);
        }
    });

    function closeCropperModal() {
        cropperModal.style.display = 'none';
        if (cropper) cropper.destroy();
        photoInput.value = ''; // reset
    }

    function saveCroppedImage() {
        if (!cropper) return;
        const btnSave = document.getElementById('btnSaveCrop');
        btnSave.innerHTML = "<i class='bx bx-spin bx-loader-alt'></i> Uploading...";
        btnSave.disabled = true;

        cropper.getCroppedCanvas({
            width: 400,
            height: 400,
            imageSmoothingEnabled: true,
            imageSmoothingQuality: 'high',
        }).toBlob(function(blob) {
            const formData = new FormData();
            formData.append('photo', blob, 'nikhil.png');

            fetch('upload-dev-photo.php', {
                method: 'POST',
                body: formData
            })
            .then(async res => {
                const text = await res.text();
                try {
                    return JSON.parse(text);
                } catch(e) {
                    throw new Error('Invalid JSON: ' + text);
                }
            })
            .then(data => {
                if(data.success) {
                    document.getElementById('profileImage').src = data.url;
                    closeCropperModal();
                } else {
                    alert('Error: ' + data.message);
                    btnSave.innerHTML = "<i class='bx bx-upload'></i> Save & Upload";
                    btnSave.disabled = false;
                }
            })
            .catch(err => {
                console.error(err);
                alert('Upload request failed: ' + err.message);
                btnSave.innerHTML = "<i class='bx bx-upload'></i> Save & Upload";
                btnSave.disabled = false;
            });
        }, 'image/png');
    }
</script>

</body>
</html>

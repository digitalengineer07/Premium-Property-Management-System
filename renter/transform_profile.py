import codecs

html_content = """<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>My Profile | <?php echo HOUSE_NAME; ?></title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    
    <!-- Immediate Theme Setter to prevent flashes -->
    <script>
        (function() {
            if (localStorage.getItem('theme') === 'dark') {
                document.documentElement.classList.add('dark-theme');
            }
        })();
    </script>
    
    <!-- Fonts & Icons -->
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
    
    <!-- Cropper JS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
    
    <style>
        .header-renter { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; flex-wrap: wrap; gap: 20px; }
        .brand-renter { display: flex; align-items: center; gap: 12px; }
        .brand-renter i { background: var(--primary-purple); color: white; padding: 10px; border-radius: 12px; font-size: 24px; }
        .brand-renter span { font-weight: 800; font-size: 22px; color: var(--text-dark); letter-spacing: -0.5px; }
        
        /* Form Overrides */
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 13px; color: var(--text-gray); text-transform: uppercase; letter-spacing: 0.5px; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 12px 16px; border: 1px solid var(--border); border-radius: 12px; background: transparent; color: var(--text-dark); outline: none; transition: var(--transition); font-family: inherit; }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus { border-color: var(--primary-purple); box-shadow: 0 0 0 4px rgba(98, 75, 255, 0.1); }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .full-width { grid-column: 1 / -1; }

        .aadhaar-preview { background: transparent; border: 1px dashed var(--border); border-radius: 16px; padding: 12px; margin-top: 10px; display: flex; align-items: center; gap: 12px; text-decoration: none; color: var(--text-dark); }
        .aadhaar-preview:hover { border-color: var(--primary-purple); background: rgba(98, 75, 255, 0.05); }

        /* Mobile Adjustments */
        @media (max-width: 768px) {
            .header-renter { flex-direction: column !important; align-items: center; text-align: center; gap: 15px !important; margin-bottom: 24px !important; }
            .form-grid { grid-template-columns: 1fr; }
            .dashboard-grid-70 { display: flex !important; flex-direction: column !important; gap: 24px; }
            .left-col, .right-col { width: 100% !important; }
            
            .hero-header-row { flex-direction: column !important; align-items: center !important; text-align: center !important; }
            .hero-profile-group { flex-direction: column !important; align-items: center !important; text-align: center !important; gap: 16px !important; width: 100% !important; }
            .hero-details-text { justify-content: center !important; }
            .avatar { transform: none !important; margin: 0 auto !important; position: relative !important; left: auto !important; bottom: auto !important; }
            .hero-card-padding { padding: 0 20px 20px 20px !important; }
        }

        /* Cropper Modal */
        #cropperModal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; align-items: center; justify-content: center; padding: 20px; }
        .cropper-content { background: var(--white); padding: 24px; border-radius: 20px; max-width: 500px; width: 100%; box-shadow: 0 20px 50px rgba(0,0,0,0.3); }
        .img-container { width: 100%; max-height: 400px; margin-bottom: 20px; overflow: hidden; border-radius: 12px; }
    </style>
</head>
<body style="display: block;">

<main class="main-renter">
    <header class="header-renter">
        <div class="brand-renter">
            <img src="../assets/img/logo.png" alt="Logo" style="width: 32px; height: 32px; border-radius: 8px; object-fit: cover;">
            <span><?php echo HOUSE_NAME; ?></span>
        </div>
        <div class="user-profile" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap; justify-content: center;">
            <i class='bx bx-moon' id="themeToggle" style="font-size: 24px; cursor: pointer; color: var(--text-gray);"></i>
            <a href="queries.php" class="btn-outline" style="padding: 10px 16px; font-size: 14px; border-color: #FCD34D; color: #B45309;"><i class='bx bx-help-circle'></i> Support</a>
            <a href="dashboard.php" class="btn-outline" style="padding: 10px 16px; font-size: 14px;">Dashboard</a>
        </div>
    </header>

    <?php if ($errmsg): ?>
        <div class="animate-up" style="background: rgba(239,68,68,0.05); color: #EF4444; padding: 16px; border-radius: 12px; margin-bottom: 24px; border: 1px solid rgba(239,68,68,0.2);">
            <i class='bx bx-error-circle'></i> <?php echo htmlspecialchars($errmsg); ?>
        </div>
    <?php elseif ($success): ?>
        <div class="animate-up" style="background: rgba(16,185,129,0.05); color: #10B981; padding: 16px; border-radius: 12px; margin-bottom: 24px; border: 1px solid rgba(16,185,129,0.2);">
            <i class='bx bx-check-circle'></i> <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <!-- HERO PROFILE CARD -->
    <div class="panel animate-up" style="padding: 0; overflow: hidden; margin-bottom: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); border: none;">
        <div style="height: 160px; background: linear-gradient(135deg, var(--primary-purple), #93A5CF); position: relative;">
            <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0.1; background-image: radial-gradient(circle at right, var(--bg-main) 0%, transparent 50%);"></div>
        </div>
        
        <div class="hero-card-padding" style="padding: 0 32px 32px 32px; position: relative;">
            <div class="hero-header-row" style="display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 24px; margin-top: -60px; margin-bottom: 32px; border-bottom: 1px solid var(--border); padding-bottom: 24px;">
                <div class="hero-profile-group" style="display: flex; align-items: flex-end; gap: 24px;">
                    <div class="avatar" id="avatarPreviewContainer" style="width: 130px; height: 130px; border: 6px solid var(--bg-main); background-image: url('../<?php echo htmlspecialchars($profile_pic); ?>'); background-size: cover; background-position: center; border-radius: 24px; box-shadow: var(--card-shadow); flex-shrink: 0; background-color: var(--bg-main); z-index: 2;"></div>
                    <div style="padding-bottom: 4px; width: 100%;">
                        <h2 style="font-weight: 800; font-size: 32px; line-height: 1.1; margin-bottom: 4px; color: var(--text-dark);"><?php echo htmlspecialchars($display_name); ?></h2>
                        <p class="hero-details-text" style="color: var(--text-gray); font-size: 15px; font-weight: 500; display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                            <i class='bx bx-user-circle' style="font-size: 18px;"></i> @<?php echo htmlspecialchars($user['username']); ?> 
                            <span style="color: var(--border);">|</span> 
                            <span style="color: var(--primary-purple); background: rgba(98, 75, 255, 0.1); padding: 2px 10px; border-radius: 20px; font-weight: 700; font-size: 12px;"><i class='bx bx-door-open'></i> Room <?php echo htmlspecialchars($user['room_no'] ?: 'N/A'); ?></span>
                        </p>
                    </div>
                </div>
                <div style="display: flex; gap: 12px; padding-bottom: 6px; flex-wrap: wrap;">
                    <span class="badge" style="background: rgba(16, 185, 129, 0.1); color: #10B981; padding: 10px 18px; font-size: 14px;"><i class='bx bx-check-shield'></i> Active Resident</span>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 32px;">
                
                <!-- Contact Info -->
                <div>
                    <h4 style="font-size: 13px; color: var(--text-gray); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 16px; font-weight: 700;">Contact Details</h4>
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <div style="display: flex; align-items: center; gap: 12px; font-size: 14px; font-weight: 500; color: var(--text-dark);">
                            <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(98,75,255,0.08); display: flex; align-items: center; justify-content: center; color: var(--primary-purple); font-size: 18px;"><i class='bx bx-phone'></i></div>
                            <?php echo htmlspecialchars($user['phone'] ?: 'No Phone Number'); ?>
                        </div>
                        <div style="display: flex; align-items: center; gap: 12px; font-size: 14px; font-weight: 500; color: var(--text-dark);">
                            <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(239,68,68,0.08); display: flex; align-items: center; justify-content: center; color: #EF4444; font-size: 18px;"><i class='bx bx-envelope'></i></div>
                            <?php echo htmlspecialchars($user['email'] ?: 'No Email Address'); ?>
                        </div>
                        <div style="display: flex; align-items: center; gap: 12px; font-size: 14px; font-weight: 500; color: var(--text-dark);">
                            <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(37,211,102,0.08); display: flex; align-items: center; justify-content: center; color: #25D366; font-size: 18px;"><i class='bx bxl-whatsapp'></i></div>
                            <?php echo htmlspecialchars($user['whatsapp'] ?: 'No WhatsApp'); ?>
                        </div>
                    </div>
                </div>

                <!-- Documentation overview -->
                <div>
                    <h4 style="font-size: 13px; color: var(--text-gray); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 16px; font-weight: 700;">Documentation Overview</h4>
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <?php if (!empty($user['aadhaar_file'])): ?>
                            <a href="../<?php echo htmlspecialchars($user['aadhaar_file']); ?>" target="_blank" class="btn-outline" style="width: 100%; display: flex; align-items: center; justify-content: space-between; padding: 14px 16px; background: transparent; border: 1px solid var(--primary-purple); color: var(--primary-purple);">
                                <span style="display: flex; align-items: center; gap: 10px; font-weight: 600;"><i class='bx bx-id-card' style="font-size: 20px;"></i> Identity Proof (Aadhaar)</span>
                                <i class='bx bx-link-external'></i>
                            </a>
                        <?php else: ?>
                            <div style="background: transparent; padding: 14px 16px; border-radius: 12px; font-size: 14px; font-weight: 500; color: #EF4444; border: 1px dashed #EF4444;">
                                <i class='bx bx-error-circle' style="margin-right: 8px;"></i> No Identity Proof Uploaded
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($user['agreement_document'])): ?>
                            <?php 
                                $expiry_status = "";
                                if ($user['agreement_expiry_date']) {
                                    $days = (strtotime($user['agreement_expiry_date']) - time()) / 86400;
                                    if ($days < 0) $expiry_status = "Expired";
                                    elseif ($days <= 30) $expiry_status = "Expiring Soon";
                                }
                            ?>
                            <a href="../admin/download-agreement.php?id=<?php echo (int)$user_id; ?>" target="_blank" class="btn-outline" style="width: 100%; display: flex; align-items: center; justify-content: space-between; padding: 14px 16px; position: relative; background: transparent; border: 1px solid #10B981; color: #10B981;">
                                <span style="display: flex; align-items: center; gap: 10px; font-weight: 600;"><i class='bx bx-file' style="font-size: 20px;"></i> Room Rental Agreement</span>
                                <i class='bx bx-link-external'></i>
                                <?php if ($expiry_status): ?>
                                    <span class="badge" style="position: absolute; top: -10px; right: 10px; font-size: 10px; padding: 4px 8px; background: <?php echo $expiry_status == 'Expired' ? '#EF4444' : '#F59E0B'; ?>; color: white; border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                                        <?php echo $expiry_status; ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                        <?php else: ?>
                            <div style="background: transparent; padding: 14px 16px; border-radius: 12px; font-size: 14px; font-weight: 500; color: #F59E0B; border: 1px dashed #F59E0B;">
                                <i class='bx bx-file-blank' style="margin-right: 8px;"></i> No Agreement Document
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <?php if(!empty($user['about'])): ?>
            <div style="margin-top: 32px; padding: 20px; background: transparent; border-radius: 16px; border: 1px solid var(--border);">
                <h4 style="font-size: 12px; color: var(--text-gray); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; font-weight: 700; display: flex; align-items: center; gap: 6px;"><i class='bx bx-note'></i> About / Bio</h4>
                <p style="font-size: 15px; line-height: 1.6; color: var(--text-dark); margin: 0; font-weight: 500;"><?php echo nl2br(htmlspecialchars($user['about'])); ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>


    <div class="dashboard-grid-70 animate-up">
        
        <!-- MAIN EDIT FORM -->
        <div class="left-col">
            <div class="panel">
                <div class="panel-header">
                    <h2 style="font-size: 18px; font-weight: 700;">Edit Profile Information</h2>
                </div>
                
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf']); ?>">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" placeholder="Your legal name">
                        </div>
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" placeholder="resident@example.com">
                        </div>

                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="91XXXXXXXX">
                        </div>
                        <div class="form-group">
                            <label>WhatsApp Number</label>
                            <input type="text" name="whatsapp" value="<?php echo htmlspecialchars($user['whatsapp'] ?? ''); ?>" placeholder="Include country code (e.g. 91)">
                        </div>

                        <div class="form-group full-width">
                            <label>Room Number</label>
                            <input type="text" name="room_no" value="<?php echo htmlspecialchars($user['room_no'] ?? ''); ?>" placeholder="e.g. 101, Ground Floor">
                        </div>

                        <div class="form-group full-width">
                            <label>About Me / Bio</label>
                            <textarea name="about" rows="3" placeholder="A little bit about yourself..."><?php echo htmlspecialchars($user['about'] ?? ''); ?></textarea>
                        </div>
                        
                        <!-- Uploads -->
                        <div class="form-group">
                            <label>Change Profile Picture</label>
                            <input type="file" id="profilePicInput" accept="image/*" style="padding: 10px; height: auto; border: 1px dashed var(--border);">
                            <input type="hidden" name="cropped_image" id="croppedImageInput">
                            <small style="color: var(--text-gray); font-size: 11px; margin-top: 4px; display: block;">JPG, PNG or WEBP (Standardized to 1:1 ratio)</small>
                        </div>
                        <div class="form-group">
                            <label>Upload/Update Identity Proof</label>
                            <input type="file" name="aadhaar" accept="image/*,application/pdf" style="padding: 10px; height: auto; border: 1px dashed var(--border);">
                            <small style="color: var(--text-gray); font-size: 11px; margin-top: 4px; display: block;">JPG, PNG or PDF (Max 5MB)</small>
                        </div>
                    </div>

                    <div style="margin-top: 10px; border-top: 1px solid var(--border); padding-top: 24px; display: flex; justify-content: flex-end;">
                        <button type="submit" name="save_profile" class="btn-primary" style="padding: 14px 40px; border-radius: 12px; font-size: 15px; width: auto;">
                            <i class='bx bx-save'></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- RIGHT COL: SECURITY -->
        <div class="right-col">
            <div class="panel">
                <div class="panel-header">
                    <h2 style="font-size: 18px; font-weight: 700;">Security Settings</h2>
                </div>
                <form method="POST">
                    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf']); ?>">
                    <div style="display: flex; flex-direction: column; gap: 20px;">
                        <div class="form-group" style="margin: 0;">
                            <label>Current Password</label>
                            <input type="password" name="current_password" placeholder="••••••••" required>
                        </div>
                        <div class="form-group" style="margin: 0;">
                            <label>New Password</label>
                            <input type="password" name="new_password" placeholder="Min 6 characters" required>
                        </div>
                        <div class="form-group" style="margin: 0;">
                            <label>Confirm New Password</label>
                            <input type="password" name="confirm_password" placeholder="Confirm new password" required>
                        </div>
                    </div>
                    <div style="margin-top: 24px;">
                        <button type="submit" name="change_password" class="btn-primary" style="width: 100%; justify-content: center; padding: 14px; border-radius: 12px; background: #EF4444; border-color: #EF4444; box-shadow: 0 4px 12px rgba(239,68,68,0.2);">
                            <i class='bx bx-lock-alt'></i> Update Password
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="panel" style="margin-top: 24px; background: transparent; border: 1px dashed var(--border); box-shadow: none;">
                <div style="text-align: center; color: var(--text-gray);">
                    <i class='bx bx-info-circle' style="font-size: 32px; margin-bottom: 10px; opacity: 0.5;"></i>
                    <p style="font-size: 13px; line-height: 1.5;">Your Room Rental Agreement is managed securely by the Administrator. If you notice any discrepancies, please reach out to Support.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Cropper Modal -->
    <div id="cropperModal">
        <div class="cropper-content animate-up">
            <h3 style="margin-bottom: 15px; font-weight: 700;">Crop Your Image</h3>
            <div class="img-container">
                <img id="imageToCrop" src="" alt="To Crop">
            </div>
            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" class="btn-outline" onclick="closeCropper()" style="padding: 10px 20px;">Cancel</button>
                <button type="button" class="btn-primary" onclick="applyCrop()" style="padding: 10px 20px;">Crop & Set</button>
            </div>
        </div>
    </div>
</main>

<script>
    const themeToggle = document.getElementById('themeToggle');
    if (document.documentElement.classList.contains('dark-theme')) {
        themeToggle?.classList.replace('bx-moon', 'bx-sun');
    }

    themeToggle?.addEventListener('click', () => {
        const isDark = document.documentElement.classList.toggle('dark-theme');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        if (isDark) {
            themeToggle.classList.replace('bx-moon', 'bx-sun');
        } else {
            themeToggle.classList.replace('bx-sun', 'bx-moon');
        }
    });

    // Cropper Logic
    let cropper = null;
    const profilePicInput = document.getElementById('profilePicInput');
    const cropperModal = document.getElementById('cropperModal');
    const imageToCrop = document.getElementById('imageToCrop');
    const croppedImageInput = document.getElementById('croppedImageInput');
    const avatarPreviewContainer = document.getElementById('avatarPreviewContainer');

    profilePicInput.onchange = function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                imageToCrop.src = event.target.result;
                cropperModal.style.display = 'flex';
                if (cropper) cropper.destroy();
                cropper = new Cropper(imageToCrop, {
                    aspectRatio: 1,
                    viewMode: 1,
                    dragMode: 'move',
                    autoCropArea: 1,
                    restore: false,
                    guides: false,
                    center: true,
                    highlight: false,
                    cropBoxMovable: true,
                    cropBoxResizable: true,
                    toggleDragModeOnDblclick: false,
                });
            };
            reader.readAsDataURL(file);
        }
    };

    function closeCropper() {
        cropperModal.style.display = 'none';
        profilePicInput.value = '';
        if (cropper) cropper.destroy();
    }

    function applyCrop() {
        if (!cropper) return;
        const canvas = cropper.getCroppedCanvas({
            width: 400,
            height: 400,
            imageSmoothingQuality: 'high'
        });
        
        const base64Image = canvas.toDataURL('image/jpeg', 0.9);
        croppedImageInput.value = base64Image;
        avatarPreviewContainer.style.backgroundImage = `url(${base64Image})`;
        
        cropperModal.style.display = 'none';
        if (cropper) cropper.destroy();
    }
</script>

</body>
</html>
"""

with codecs.open('c:/xampp/htdocs/renter-system/renter/profile.php', 'r', 'utf-8') as f:
    text = f.read()

php_head = text[:text.find('<!doctype html>')]

with codecs.open('c:/xampp/htdocs/renter-system/renter/profile.php', 'w', 'utf-8') as f:
    f.write(php_head + html_content)

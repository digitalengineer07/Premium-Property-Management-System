<?php
// renter/profile.php
require_once "../db.php";   // include DB BEFORE session_start
session_start();
require_once "../audit.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}
$user_id = (int) $_SESSION['user_id'];

/* CSRF */
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));

$errmsg = ""; $success = "";

/* Handle profile updates (name/phone/room/about + profile pic + aadhaar upload) */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {

    if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
        $errmsg = "Invalid form submission (CSRF).";
    } else {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $whatsapp = trim($_POST['whatsapp'] ?? '');
        $room_no = trim($_POST['room_no'] ?? '');
        $about = trim($_POST['about'] ?? '');

        // Basic validation
        if ($name === '') $errmsg = "Name cannot be empty.";

        // handle cropped profile pic upload
        $profileUploadedPath = null;
        if (empty($errmsg) && !empty($_POST['cropped_image'])) {
            $data = $_POST['cropped_image'];
            if (preg_match('/^data:image\/(\w+);base64,/', $data, $type)) {
                $data = substr($data, strpos($data, ',') + 1);
                $type = strtolower($type[1]); // jpg, png, etc

                if (!in_array($type, ['jpg', 'jpeg', 'png', 'webp'])) {
                    $errmsg = "Invalid image type after crop.";
                } else {
                    $data = base64_decode($data);
                    if ($data === false) {
                        $errmsg = "Image data is corrupted.";
                    } else {
                        $safe = $user_id . "_profile_" . time() . ".jpg";
                        $dest = __DIR__ . "/../uploads/profiles/";
                        if (!is_dir($dest)) mkdir($dest, 0777, true);
                        if (!file_put_contents($dest . $safe, $data)) {
                            $errmsg = "Failed to save cropped image.";
                        } else {
                            chmod($dest . $safe, 0644);
                            $profileUploadedPath = "uploads/profiles/" . $safe;
                        }
                    }
                }
            } else {
                $errmsg = "Invalid image format.";
            }
        }

        // handle aadhaar upload (image or pdf)
        $aadhaarPath = null;
        if (empty($errmsg) && !empty($_FILES['aadhaar']) && $_FILES['aadhaar']['error'] !== UPLOAD_ERR_NO_FILE) {
            $f = $_FILES['aadhaar'];
            if ($f['error'] !== UPLOAD_ERR_OK) $errmsg = "Aadhaar upload error.";
            else {
                $allowedMimes = ['image/jpeg'=>'jpg','image/png'=>'png','application/pdf'=>'pdf'];
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $f['tmp_name']);
                finfo_close($finfo);
                if (!isset($allowedMimes[$mime])) $errmsg = "Aadhaar must be JPG/PNG or PDF.";
                elseif ($f['size'] > 5*1024*1024) $errmsg = "Aadhaar file must be < 5MB.";
                else {
                    $ext = $allowedMimes[$mime];
                    $safe = $user_id . "_aadhaar_" . time() . "." . $ext;
                    $dest = __DIR__ . "/../uploads/aadhaar/";
                    if (!is_dir($dest)) mkdir($dest,0777,true);
                    if (!move_uploaded_file($f['tmp_name'], $dest . $safe)) $errmsg = "Failed to save Aadhaar file.";
                    else {
                        chmod($dest . $safe, 0644);
                        $aadhaarPath = "uploads/aadhaar/" . $safe;
                    }
                }
            }
        }

        // Update DB
        if (empty($errmsg)) {
            $email = trim($_POST['email'] ?? '');
            
            if ($profileUploadedPath && $aadhaarPath) {
                $stmt = mysqli_prepare($conn, "UPDATE users SET name=?, phone=?, email=?, whatsapp=?, room_no=?, about=?, profile_pic=?, aadhaar_file=? WHERE id=?");
                mysqli_stmt_bind_param($stmt, "ssssssssi", $name, $phone, $email, $whatsapp, $room_no, $about, $profileUploadedPath, $aadhaarPath, $user_id);
            } elseif ($profileUploadedPath) {
                $stmt = mysqli_prepare($conn, "UPDATE users SET name=?, phone=?, email=?, whatsapp=?, room_no=?, about=?, profile_pic=? WHERE id=?");
                mysqli_stmt_bind_param($stmt, "sssssssi", $name, $phone, $email, $whatsapp, $room_no, $about, $profileUploadedPath, $user_id);
            } elseif ($aadhaarPath) {
                $stmt = mysqli_prepare($conn, "UPDATE users SET name=?, phone=?, email=?, whatsapp=?, room_no=?, about=?, aadhaar_file=? WHERE id=?");
                mysqli_stmt_bind_param($stmt, "sssssssi", $name, $phone, $email, $whatsapp, $room_no, $about, $aadhaarPath, $user_id);
            } else {
                $stmt = mysqli_prepare($conn, "UPDATE users SET name=?, phone=?, email=?, whatsapp=?, room_no=?, about=? WHERE id=?");
                mysqli_stmt_bind_param($stmt, "ssssssi", $name, $phone, $email, $whatsapp, $room_no, $about, $user_id);
            }
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            logAction($conn, "renter", $user_id, "Updated profile and files");
            $success = "Profile updated.";
        }
    }
}

/* Handle Password Change */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
        $errmsg = "Invalid CSRF token.";
    } else {
        $curr_pass = $_POST['current_password'] ?? '';
        $new_pass = $_POST['new_password'] ?? '';
        $conf_pass = $_POST['confirm_password'] ?? '';

        if (empty($curr_pass) || empty($new_pass) || empty($conf_pass)) {
            $errmsg = "All password fields are required.";
        } elseif ($new_pass !== $conf_pass) {
            $errmsg = "New passwords do not match.";
        } elseif (strlen($new_pass) < 6) {
            $errmsg = "New password must be at least 6 characters.";
        } else {
            // Verify current
            $stmt = mysqli_prepare($conn, "SELECT password FROM users WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $u = mysqli_fetch_assoc($res);
            mysqli_stmt_close($stmt);

            if (!password_verify($curr_pass, $u['password'])) {
                $errmsg = "Current password is incorrect.";
            } else {
                $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
                $stmt = mysqli_prepare($conn, "UPDATE users SET password = ?, must_change_password = 0 WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "si", $hashed, $user_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                
                logAction($conn, "renter", $user_id, "Changed password");
                $success = "Password updated successfully!";
            }
        }
    }
}

/* Fetch user info */
$stmt = mysqli_prepare($conn, "SELECT username, name, phone, email, whatsapp, room_no, profile_pic, about, aadhaar_file, agreement_document, agreement_upload_date, agreement_expiry_date FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

/* Fetch electricity records for this user, recent first */
$elec_q = mysqli_prepare($conn, "SELECT * FROM electricity WHERE user_id = ? ORDER BY id DESC");
mysqli_stmt_bind_param($elec_q, "i", $user_id);
mysqli_stmt_execute($elec_q);
$elec_res = mysqli_stmt_get_result($elec_q);
$elec_rows = [];
while ($r = mysqli_fetch_assoc($elec_res)) $elec_rows[] = $r;
mysqli_stmt_close($elec_q);

$display_name = $user['name'] ?: $user['username'];
$profile_pic = $user['profile_pic'] ?: "assets/img/default-avatar.png";
$aadhaar_file = $user['aadhaar_file'] ?? null;
?>
<!doctype html>
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
    
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
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
        @media (max-width: 768px) {
            .header-renter {
                flex-direction: column !important;
                text-align: center;
                gap: 15px !important;
                margin-bottom: 24px !important;
            }
            .profile-grid { grid-template-columns: 1fr !important; }
        }
        .header-renter {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .brand-renter {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-renter i {
            background: var(--primary-purple);
            color: white;
            padding: 10px;
            border-radius: 12px;
            font-size: 24px;
        }

        .brand-renter span {
            font-weight: 800;
            font-size: 22px;
            color: var(--text-dark);
            letter-spacing: -0.5px;
        }

        .profile-container {
            display: grid;
            grid-template-columns: 360px 1fr;
            gap: 24px;
            align-items: stretch;
        }

        /* Modern Banner Output */
        .profile-header-banner {
            width: 100%;
            height: 200px;
            border-radius: 24px;
            background: linear-gradient(135deg, var(--primary-purple) 0%, #3B28CC 100%);
            position: relative;
            margin-bottom: 0px;
            box-shadow: 0 10px 30px rgba(98, 75, 255, 0.2);
            overflow: hidden;
        }
        
        .profile-header-banner::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: radial-gradient(circle at top right, rgba(255,255,255,0.1) 0%, transparent 60%);
        }

        .profile-avatar-wrapper {
            display: flex;
            align-items: flex-end;
            gap: 24px;
            padding: 0 40px;
            margin-top: -65px;
            margin-bottom: 40px;
            position: relative;
            z-index: 10;
        }

        .avatar-huge {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            object-fit: cover;
            border: 6px solid var(--bg-main);
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
            background: var(--bg-main);
            flex-shrink: 0;
            position: relative;
            z-index: 2;
            transition: transform 0.3s ease;
        }
        
        .avatar-huge:hover {
            transform: scale(1.05);
        }

        .profile-name-info {
            align-self: center;
            margin-top: -35px;
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
        }

        .profile-name-info h1 {
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 24px;
            color: #ffffff;
            line-height: 1;
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }

        .profile-name-info p {
            font-size: 16px;
            color: var(--text-gray);
            font-weight: 600;
            line-height: 1;
        }
        
        /* When overlapping banner it is white, when overflowing it becomes dark. 
           We will position it just below the avatar in mobile or push avatar up. */

        .form-group { margin-bottom: 20px; }
        .form-group label { 
            display: block; margin-bottom: 6px; font-weight: 600; font-size: 13px; 
            color: var(--text-gray); text-transform: uppercase; letter-spacing: 0.5px;
        }
        .form-group input, .form-group textarea {
            width: 100%; padding: 12px 14px; border: 1px solid var(--border); border-radius: 12px;
            background: var(--bg-main); color: var(--text-dark); outline: none; transition: var(--transition);
            font-family: inherit; font-size: 14px;
        }
        .form-group input:focus, .form-group textarea:focus { 
            border-color: var(--primary-purple); box-shadow: 0 0 0 4px rgba(98, 75, 255, 0.1); 
            background: var(--white);
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .full-width {
            grid-column: 1 / -1;
        }

        .aadhaar-preview {
            background: var(--bg-main);
            border: 1px dashed var(--border);
            border-radius: 16px;
            padding: 16px;
            margin-top: 10px;
            display: flex;
            align-items: center;
            gap: 16px;
            text-decoration: none;
            color: var(--text-dark);
            transition: var(--transition);
        }

        .aadhaar-preview:hover {
            border-color: var(--primary-purple);
            background: rgba(98, 75, 255, 0.05);
            transform: translateY(-2px);
        }
        
        .panel {
            background: var(--white);
            border-radius: 20px;
            padding: 24px;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border);
            transition: var(--transition);
        }
        
        .panel:hover {
            box-shadow: 0 12px 30px rgba(0,0,0,0.08); /* Hover lift */
        }
        
        .info-list-item {
            display: flex; 
            align-items: center; 
            gap: 14px; 
            margin-bottom: 16px;
            padding: 12px;
            border-radius: 12px;
            background: var(--bg-main);
            transition: var(--transition);
        }
        
        .info-list-item:hover {
            background: rgba(98, 75, 255, 0.05);
            transform: translateX(4px);
        }

        @media (max-width: 992px) {
            .profile-container {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 768px) {
            .header-renter { 
                margin-bottom: 24px; 
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            .form-grid { grid-template-columns: 1fr; gap: 16px; }
            .profile-avatar-wrapper {
                padding-left: 0;
                justify-content: center;
                flex-direction: column;
                align-items: center;
                text-align: center;
                margin-top: -65px;
                gap: 12px;
            }
            .profile-header-banner {
                margin-bottom: 0px;
            }
            .profile-name-info {
                margin-top: 0;
            }
            .profile-name-info h1 {
                margin-bottom: 8px;
                color: var(--text-dark);
                text-shadow: none;
            }
        }

        /* Cropper Modal Styles */
        #cropperModal {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            padding: 20px;
            backdrop-filter: blur(5px);
        }
        .cropper-content {
            background: var(--white);
            padding: 32px;
            border-radius: 24px;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 20px 50px rgba(0,0,0,0.3);
        }
        .img-container {
            width: 100%;
            max-height: 400px;
            margin-bottom: 24px;
            overflow: hidden;
            border-radius: 16px;
        }
            .user-profile-pill {
            display: flex; align-items: center; gap: 10px; cursor: pointer; padding-left: 8px;
            white-space: nowrap;
        }
        .user-avatar { width: 38px; height: 38px; background: var(--primary-purple); color: white; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; box-shadow: 0 4px 10px rgba(98,75,255,0.2); }
        .user-info h4 { font-size: 14px; font-weight: 700; margin: 0; }
        .user-info p { font-size: 11px; color: var(--text-gray); margin: 0; }
    </style>
</head>
<body style="display: block;">

<main class="main-renter">
    <header class="header-renter">
        <div class="brand-renter">
            <img src="../assets/img/logo.png" alt="Logo" style="width: 32px; height: 32px; border-radius: 8px; object-fit: cover;">
            <span><?php echo HOUSE_NAME; ?></span>
        </div>
        <div class="user-profile" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
            <i class='bx bx-moon' id="themeToggle" style="font-size: 24px; cursor: pointer; color: var(--text-gray);"></i>
            <a href="queries.php" class="btn-outline" style="padding: 10px 16px; font-size: 14px; border-color: #FCD34D; color: #B45309;"><i class='bx bx-help-circle'></i> Help & Support</a>
            <a href="dashboard.php" class="btn-outline" style="padding: 10px 16px; font-size: 14px;">Back to Dashboard</a>
        </div>
    </header>

    <div class="profile-header-banner animate-up">
    </div>
    
    <div class="profile-avatar-wrapper animate-up">
        <img src="../<?php echo htmlspecialchars($profile_pic); ?>" alt="profile" class="avatar-huge">
        <div class="profile-name-info">
            <h1><?php echo htmlspecialchars($display_name); ?></h1>
            <p>@<?php echo htmlspecialchars($user['username']); ?></p>
        </div>
    </div>

    <!-- Removed the redundant generic "Welcome" block and integrated to banner -->

    <?php if ($errmsg): ?>
        <div id="statusAlert" class="animate-up" style="background: #FEF2F2; color: #EF4444; padding: 16px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #FEE2E2; transition: opacity 0.5s ease;">
            <i class='bx bx-error-circle'></i> <?php echo htmlspecialchars($errmsg); ?>
        </div>
    <?php elseif ($success): ?>
        <div id="statusAlert" class="animate-up" style="background: #F0FDF4; color: #10B981; padding: 16px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #DCFCE7; transition: opacity 0.5s ease;">
            <i class='bx bx-check-circle'></i> <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <div class="profile-container animate-up">
        <!-- Left Column: Quick View -->
        <div class="profile-card-left" style="display: flex; flex-direction: column; gap: 24px;">
            <div class="panel" style="margin-bottom: 0;">
                <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 16px;">Account Status</h3>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 20px;">
                    <div style="background: var(--bg-main); padding: 12px; border-radius: 12px; text-align: center;">
                        <small style="color: var(--text-gray); display: block; font-size: 11px; text-transform: uppercase; font-weight: 600; margin-bottom: 4px;">Room</small>
                        <span style="font-weight: 800; font-size: 18px; color: var(--primary-purple);"><?php echo htmlspecialchars($user['room_no'] ?? 'N/A'); ?></span>
                    </div>
                    <div style="background: var(--bg-main); padding: 12px; border-radius: 12px; text-align: center;">
                        <small style="color: var(--text-gray); display: block; font-size: 11px; text-transform: uppercase; font-weight: 600; margin-bottom: 4px;">Status</small>
                        <span style="font-weight: 800; font-size: 14px; color: #10B981; padding: 4px 10px; background: rgba(16, 185, 129, 0.1); border-radius: 8px;">Active</span>
                    </div>
                </div>
                
                <h3 style="font-size: 14px; font-weight: 700; margin-bottom: 12px; color: var(--text-gray); text-transform: uppercase; letter-spacing: 0.5px;">Contact Details</h3>
                
                <div style="margin-top: 10px;">
                    <div class="info-list-item">
                        <div style="width: 36px; height: 36px; border-radius: 10px; background: var(--white); display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                            <i class='bx bx-phone' style="color: var(--primary-purple); font-size: 20px;"></i>
                        </div>
                        <span style="font-size: 15px; font-weight: 600; color: var(--text-dark);"><?php echo htmlspecialchars($user['phone'] ?? 'Update phone'); ?></span>
                    </div>
                    <?php if(!empty($user['email'])): ?>
                    <div class="info-list-item">
                        <div style="width: 36px; height: 36px; border-radius: 10px; background: var(--white); display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                            <i class='bx bx-envelope' style="color: var(--primary-purple); font-size: 20px;"></i>
                        </div>
                        <span style="font-size: 15px; font-weight: 600; color: var(--text-dark); word-break: break-all;"><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if($user['whatsapp']): ?>
                    <div class="info-list-item">
                        <div style="width: 36px; height: 36px; border-radius: 10px; background: var(--white); display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                            <i class='bx bxl-whatsapp' style="color: #25D366; font-size: 20px;"></i>
                        </div>
                        <span style="font-size: 15px; font-weight: 600; color: var(--text-dark);"><?php echo htmlspecialchars($user['whatsapp']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Rental Agreement Section Moved to Left Panel -->
                <h3 style="font-size: 14px; font-weight: 700; margin-top: 24px; margin-bottom: 12px; color: var(--text-gray); text-transform: uppercase; letter-spacing: 0.5px;">Rental Agreement</h3>
                
                <div style="margin-top: 10px;">
                    <?php if (!empty($user['agreement_document'])): ?>
                        <div style="background: var(--white); padding: 12px; border-radius: 12px; border: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="width: 40px; height: 40px; background: rgba(16, 185, 129, 0.1); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                    <i class='bx bx-file' style="font-size: 20px; color: #10B981;"></i>
                                </div>
                                <div>
                                    <div style="font-weight: 600; font-size: 13px; color: var(--text-dark);">View Document</div>
                                    <div style="font-size: 11px; color: var(--text-gray);">Uploaded <?php echo date('M Y', strtotime($user['agreement_upload_date'])); ?></div>
                                </div>
                            </div>
                            <div style="display: flex; gap: 8px;">
                                <a href="../admin/download-agreement.php?id=<?php echo $user_id; ?>" target="_blank" class="btn-outline" style="padding: 6px 12px; font-size: 12px; border-color: #10B981; color: #10B981;">
                                    <i class='bx bx-show'></i>
                                </a>
                            </div>
                        </div>
                        <?php if (!empty($user['agreement_expiry_date'])): ?>
                            <?php 
                                $exp_ts = strtotime($user['agreement_expiry_date']);
                                $is_exp = $exp_ts < time();
                                $is_soon = ($exp_ts - time()) <= (30 * 86400);
                            ?>
                            <div style="margin-top: 8px; font-size: 12px; display: flex; align-items: center; gap: 6px; font-weight: 600; color: <?php echo $is_exp ? '#EF4444' : ($is_soon ? '#F59E0B' : 'var(--text-gray)'); ?>;">
                                <i class='bx bx-calendar-event'></i> 
                                Expires: <?php echo date('d M Y', $exp_ts); ?>
                                <?php if ($is_exp) echo " (Expired)"; elseif ($is_soon) echo " (Expiring Soon)"; ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div style="background: var(--bg-main); padding: 16px; border-radius: 12px; border: 1px dashed var(--border); text-align: center;">
                            <i class='bx bx-file-blank' style="font-size: 24px; color: var(--text-gray); opacity: 0.5;"></i>
                            <p style="font-size: 12px; color: var(--text-gray); margin-top: 4px;">No agreement uploaded yet.</p>
                        </div>
                    <?php endif; ?>
                    <small style="color: var(--text-gray); font-size: 11px; display: block; margin-top: 8px;">*Only Admin can replace this document.</small>
                </div>
            </div>

            <!-- Security Section Moved to Left Column for Symmetry -->
            <div class="panel animate-up" style="margin-top: 0; margin-bottom: 0; border-top: 4px solid #F59E0B; flex-grow: 1; display: flex; flex-direction: column;">
                <div class="panel-header" style="margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid var(--border);">
                    <div>
                        <h2 style="font-size: 18px; font-weight: 800; color: var(--text-dark); display: flex; align-items: center; gap: 8px;">
                            <i class='bx bx-shield-quarter' style="color: #F59E0B; font-size: 24px;"></i> Security Settings
                        </h2>
                        <p style="font-size: 13px; color: var(--text-gray); margin-top: 4px;">Update your password.</p>
                    </div>
                </div>
                <form method="POST" style="display: flex; flex-direction: column; flex-grow: 1;">
                    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf']); ?>">
                    <div class="form-group">
                        <label>Current Password</label>
                        <div style="position: relative;">
                            <input type="password" name="current_password" placeholder="••••••••" class="pwd-input" required style="padding-right: 40px;">
                            <i class='bx bx-hide pwd-toggle' style="position: absolute; right: 16px; top: 14px; color: var(--text-gray); cursor: pointer; font-size: 20px;"></i>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>New Password</label>
                        <div style="position: relative;">
                            <input type="password" name="new_password" placeholder="Min 6 characters" class="pwd-input" required style="padding-right: 40px;">
                            <i class='bx bx-hide pwd-toggle' style="position: absolute; right: 16px; top: 14px; color: var(--text-gray); cursor: pointer; font-size: 20px;"></i>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <div style="position: relative;">
                            <input type="password" name="confirm_password" placeholder="Confirm new password" class="pwd-input" required style="padding-right: 40px;">
                            <i class='bx bx-hide pwd-toggle' style="position: absolute; right: 16px; top: 14px; color: var(--text-gray); cursor: pointer; font-size: 20px;"></i>
                        </div>
                    </div>
                    <div style="margin-top: auto;">
                        <button type="submit" name="change_password" class="btn-primary" style="padding: 12px 24px; width: 100%; border-radius: 12px; background: #F59E0B; font-size: 15px; justify-content: center;">
                            <i class='bx bx-lock-alt'></i> Update Password
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Right Column: Edit Form -->
        <div class="profile-card-right" style="display: flex; flex-direction: column;">
            <div class="panel" style="margin-bottom: 0; flex-grow: 1; display: flex; flex-direction: column;">
                <div class="panel-header" style="margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--border);">
                    <div>
                        <h2 style="font-size: 20px; font-weight: 800; color: var(--text-dark);">Profile Information</h2>
                        <p style="font-size: 13px; color: var(--text-gray); margin-top: 4px;">Update your personal details below.</p>
                    </div>
                </div>
                
                <form method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; flex-grow: 1;">
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
                            <label>About Me (Bio)</label>
                            <textarea name="about" rows="3" placeholder="A little bit about yourself..."><?php echo htmlspecialchars($user['about'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <div class="form-grid" style="margin-top: 16px;">
                        <div class="form-group">
                            <label>Profile Picture</label>
                            <div style="position: relative; overflow: hidden; border-radius: 12px; border: 2px dashed var(--border); background: var(--bg-main); transition: var(--transition);" onmouseover="this.style.borderColor='var(--primary-purple)';" onmouseout="this.style.borderColor='var(--border)';">
                                <input type="file" id="profilePicInput" accept="image/*" style="position: absolute; width: 100%; height: 100%; opacity: 0; cursor: pointer; z-index: 2;">
                                <div style="padding: 16px; text-align: center; color: var(--text-gray);">
                                    <i class='bx bx-cloud-upload' style="font-size: 28px; color: var(--primary-purple); margin-bottom: 4px;"></i>
                                    <div style="font-weight: 600; font-size: 13px; color: var(--text-dark);">Upload Photo</div>
                                </div>
                            </div>
                            <input type="hidden" name="cropped_image" id="croppedImageInput">
                            <small style="color: var(--text-gray); font-size: 11px; margin-top: 6px; display: block;">JPG/PNG (1:1)</small>
                        </div>
                        <div class="form-group">
                            <label>Aadhaar Document</label>
                            
                            <?php if (!$aadhaar_file): ?>
                                <div style="position: relative; overflow: hidden; border-radius: 12px; border: 2px dashed var(--border); background: var(--bg-main); transition: var(--transition);" onmouseover="this.style.borderColor='var(--primary-purple)';" onmouseout="this.style.borderColor='var(--border)';">
                                    <input type="file" name="aadhaar" accept="image/*,application/pdf" style="position: absolute; width: 100%; height: 100%; opacity: 0; cursor: pointer; z-index: 2;">
                                    <div style="padding: 16px; text-align: center; color: var(--text-gray);">
                                        <i class='bx bx-id-card' style="font-size: 28px; color: var(--primary-purple); margin-bottom: 4px;"></i>
                                        <div style="font-weight: 600; font-size: 13px; color: var(--text-dark);">Upload Aadhaar</div>
                                    </div>
                                </div>
                                <small style="color: var(--text-gray); font-size: 11px; margin-top: 6px; display: block;">JPG/PDF (Max 5MB)</small>
                            <?php else: ?>
                                <a href="../<?php echo htmlspecialchars($aadhaar_file); ?>" class="aadhaar-preview" target="_blank">
                                    <?php if (preg_match('/\.pdf$/i', $aadhaar_file)): ?>
                                        <i class='bx bxs-file-pdf' style="font-size: 32px; color: #EF4444;"></i>
                                        <div>
                                            <div style="font-weight: 600; font-size: 13px;">View Aadhaar (PDF)</div>
                                            <div style="font-size: 11px; color: var(--text-gray);">Managed securely by Administrator</div>
                                        </div>
                                    <?php else: ?>
                                        <img src="../<?php echo htmlspecialchars($aadhaar_file); ?>" alt="aadhaar" style="width: 40px; height: 40px; border-radius: 8px; object-fit: cover;">
                                        <div>
                                            <div style="font-weight: 600; font-size: 13px;">View Aadhaar Image</div>
                                            <div style="font-size: 11px; color: var(--text-gray);">Managed securely by Administrator</div>
                                        </div>
                                    <?php endif; ?>
                                </a>
                                <small style="color: var(--text-gray); font-size: 11px; margin-top: 6px; display: block;">*Only Admin can replace this document.</small>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div style="margin-top: 24px; display: flex; justify-content: flex-end;">
                        <button type="submit" name="save_profile" class="btn-primary" style="padding: 12px 32px; border-radius: 12px; font-size: 15px; min-width: 180px; justify-content: center;">
                            <i class='bx bx-save'></i> Save Changes
                        </button>
                    </div>
                </form>
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
    
    // Sync initial icon state
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
    const avatarPreview = document.querySelector('.avatar-huge');

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
        avatarPreview.src = base64Image;
        
        cropperModal.style.display = 'none';
        if (cropper) cropper.destroy();
    }

    const aadhaarInput = document.querySelector('input[name="aadhaar"]');
    if (aadhaarInput) {
        aadhaarInput.addEventListener('change', function(e) {
            const fileName = e.target.files[0] ? e.target.files[0].name : 'Upload Aadhaar';
            const textElement = this.nextElementSibling.querySelector('div:nth-of-type(1)');
            const subElement = this.nextElementSibling.querySelector('div:nth-of-type(2)');
            const iconElement = this.nextElementSibling.querySelector('i');
            if(e.target.files[0]) {
                textElement.textContent = fileName;
                textElement.style.color = 'var(--primary-purple)';
                subElement.textContent = 'Ready to upload';
                iconElement.className = 'bx bxs-check-circle';
            } else {
                textElement.textContent = 'Upload Aadhaar';
                textElement.style.color = 'var(--text-dark)';
                subElement.textContent = 'Drag & drop or click';
                iconElement.className = 'bx bx-id-card';
            }
        });
    }
    
    // Auto-dismiss the status alert
    const statusAlert = document.getElementById('statusAlert');
    if (statusAlert) {
        setTimeout(() => {
            statusAlert.style.opacity = '0';
            setTimeout(() => statusAlert.remove(), 500);
        }, 3000);
    }

    document.querySelectorAll('.pwd-toggle').forEach(icon => {
        icon.addEventListener('click', function() {
            const input = this.previousElementSibling;
            if(input) {
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                this.classList.toggle('bx-show');
                this.classList.toggle('bx-hide');
            }
        });
    });
</script>

</body>
</html>

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
            $dob = empty($_POST['dob']) ? null : $_POST['dob'];
            $gender = empty($_POST['gender']) ? null : $_POST['gender'];
            $address = empty($_POST['address']) ? null : $_POST['address'];
            $emg_name = empty($_POST['emergency_contact_name']) ? null : $_POST['emergency_contact_name'];
            $emg_rel = empty($_POST['emergency_contact_relation']) ? null : $_POST['emergency_contact_relation'];
            $emg_phone = empty($_POST['emergency_contact_phone']) ? null : $_POST['emergency_contact_phone'];
            $emg_addr = empty($_POST['emergency_contact_address']) ? null : $_POST['emergency_contact_address'];

            if ($profileUploadedPath && $aadhaarPath) {
                $stmt = mysqli_prepare($conn, "UPDATE users SET name=?, phone=?, email=?, whatsapp=?, room_no=?, about=?, profile_pic=?, aadhaar_file=?, dob=?, gender=?, address=?, emergency_contact_name=?, emergency_contact_relation=?, emergency_contact_phone=?, emergency_contact_address=? WHERE id=?");
                mysqli_stmt_bind_param($stmt, "sssssssssssssssi", $name, $phone, $email, $whatsapp, $room_no, $about, $profileUploadedPath, $aadhaarPath, $dob, $gender, $address, $emg_name, $emg_rel, $emg_phone, $emg_addr, $user_id);
            } elseif ($profileUploadedPath) {
                $stmt = mysqli_prepare($conn, "UPDATE users SET name=?, phone=?, email=?, whatsapp=?, room_no=?, about=?, profile_pic=?, dob=?, gender=?, address=?, emergency_contact_name=?, emergency_contact_relation=?, emergency_contact_phone=?, emergency_contact_address=? WHERE id=?");
                mysqli_stmt_bind_param($stmt, "ssssssssssssssi", $name, $phone, $email, $whatsapp, $room_no, $about, $profileUploadedPath, $dob, $gender, $address, $emg_name, $emg_rel, $emg_phone, $emg_addr, $user_id);
            } elseif ($aadhaarPath) {
                $stmt = mysqli_prepare($conn, "UPDATE users SET name=?, phone=?, email=?, whatsapp=?, room_no=?, about=?, aadhaar_file=?, dob=?, gender=?, address=?, emergency_contact_name=?, emergency_contact_relation=?, emergency_contact_phone=?, emergency_contact_address=? WHERE id=?");
                mysqli_stmt_bind_param($stmt, "ssssssssssssssi", $name, $phone, $email, $whatsapp, $room_no, $about, $aadhaarPath, $dob, $gender, $address, $emg_name, $emg_rel, $emg_phone, $emg_addr, $user_id);
            } else {
                $stmt = mysqli_prepare($conn, "UPDATE users SET name=?, phone=?, email=?, whatsapp=?, room_no=?, about=?, dob=?, gender=?, address=?, emergency_contact_name=?, emergency_contact_relation=?, emergency_contact_phone=?, emergency_contact_address=? WHERE id=?");
                mysqli_stmt_bind_param($stmt, "sssssssssssssi", $name, $phone, $email, $whatsapp, $room_no, $about, $dob, $gender, $address, $emg_name, $emg_rel, $emg_phone, $emg_addr, $user_id);
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
$stmt = mysqli_prepare($conn, "SELECT username, name, phone, email, whatsapp, room_no, profile_pic, about, aadhaar_file, agreement_document, agreement_upload_date, agreement_expiry_date, electricity_document, dob, gender, address, emergency_contact_name, emergency_contact_relation, emergency_contact_phone, emergency_contact_address, block, floor, parking, joining_date, fixed_rent, advance_payment FROM users WHERE id = ?");
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
// Fetch notices for header notification dropdown
$qNotices = mysqli_query($conn, "SELECT * FROM announcements ORDER BY created_at DESC LIMIT 5");
$notices = [];
$unread_count = 0;
while($n = mysqli_fetch_assoc($qNotices)) {
    $ts = strtotime($n['created_at']);
    $is_new = (time() - $ts) <= 7 * 86400;
    if ($is_new) $unread_count++;
    
    $badge_color = '#10B981';
    $badge_bg = 'rgba(16, 185, 129, 0.1)';
    $icon = 'bx-info-circle';
    $icon_color = '#3B82F6';
    $icon_bg = 'rgba(59, 130, 246, 0.1)';
    
    if ($n['priority'] === 'High' || $n['priority'] === 'Urgent') {
        $badge_color = '#EF4444';
        $badge_bg = 'rgba(239, 68, 68, 0.1)';
        $icon = 'bxs-megaphone';
        $icon_color = '#EF4444';
        $icon_bg = 'rgba(239, 68, 68, 0.1)';
    }
    
    $notices[] = [
        'id' => $n['id'],
        'title' => $n['title'],
        'desc' => $n['message'],
        'date' => date('d M Y', $ts),
        'time' => date('h:i A', $ts),
        'is_new' => $is_new,
        'icon' => $icon,
        'icon_color' => $icon_color,
        'icon_bg' => $icon_bg
    ];
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>My Profile | <?php echo HOUSE_NAME; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    
    <!-- Immediate Theme Setter to prevent flashes -->
    <script>
        window.HOUSE_NAME = <?php echo json_encode(HOUSE_NAME); ?>;
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
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
    <script src="../assets/js/pwa.js" defer></script>
    
    <!-- Cropper JS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
    
    <style>
        :root {
            --bg-main: #FAFBFC;
            --sidebar-bg: #FFFFFF;
            --text-dark: #0F172A;
            --text-gray: #64748B;
            --primary-purple: #624BFF;
            --primary-hover: #5039E6;
            --border: #F1F5F9;
            --white: #FFFFFF;
            --card-shadow: 0 4px 24px rgba(0, 0, 0, 0.03);
            
            /* Neons/Accents */
            --accent-red: #FF4B6B;
            --accent-yellow: #F59E0B;
            --accent-purple: #8B5CF6;
            --accent-green: #10B981;
            
            --transition: all 0.3s ease;
        }
        
        * { box-sizing: border-box; }
        
        body {
            font-family: 'Outfit', sans-serif !important;
            background-color: var(--bg-main);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            color: var(--text-dark);
            overflow-x: hidden;
        }

        .app-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 230px;
            background: var(--sidebar-bg);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            padding: 24px 20px;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            z-index: 100;
        }

        .sidebar-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 40px;
        }
        .sidebar-logo {
            width: 40px; height: 40px;
            background: #1E293B; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 20px; font-weight: 800;
        }
        .sidebar-brand h2 { font-size: 18px; font-weight: 800; margin: 0; line-height: 1.2; letter-spacing: -0.5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 140px; }
        .sidebar-brand p { font-size: 12px; color: var(--text-gray); margin: 0; font-weight: 500; }

        .nav-menu { display: flex; flex-direction: column; gap: 8px; flex: 1; }
        .nav-item {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 16px; border-radius: 14px;
            color: var(--text-gray); text-decoration: none; font-weight: 600; font-size: 14px;
            transition: all 0.2s ease;
        }
        .nav-item i { font-size: 18px; opacity: 0.8; }
        .nav-item:hover { background: rgba(98, 75, 255, 0.03); color: var(--primary-purple); }
        .nav-item.active { background: var(--primary-purple); color: white; box-shadow: 0 4px 12px rgba(98, 75, 255, 0.25); }
        .nav-item.active i { opacity: 1; }

        .go-mobile-widget {
            background: rgba(98, 75, 255, 0.03); border: 1px solid rgba(98, 75, 255, 0.05);
            border-radius: 16px; padding: 16px; text-align: left;
            margin-top: auto;
        }
        .go-mobile-widget h4 { font-size: 15px; font-weight: 800; margin-bottom: 4px; color: var(--text-dark); }
        .go-mobile-widget p { font-size: 12px; color: var(--text-gray); margin-bottom: 12px; line-height: 1.4; }
        .go-mobile-imgs { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
        .go-mobile-imgs .mock-phone { width: 50px; height: 80px; background: #333; border-radius: 8px; border: 2px solid #111; display: flex; align-items: center; justify-content: center; }
        .go-mobile-imgs .mock-qr { width: 60px; height: 60px; background: white; padding: 4px; border-radius: 6px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .btn-download {
            width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px;
            background: var(--primary-purple); color: white; border: none; padding: 10px;
            border-radius: 10px; font-weight: 600; font-size: 13px; cursor: pointer; text-decoration: none; transition: 0.2s;
        }
        .btn-download:hover { background: var(--primary-hover); transform: translateY(-1px); }

        
        
        .main-content {
            flex: 1;
            margin-left: 230px;
            padding: 32px 40px;
            max-width: calc(100% - 230px);
            box-sizing: border-box;
        }

        
        @media (max-width: 992px) {
            .profile-grid { grid-template-columns: 1fr; }
            .sidebar { width: 80px; padding: 24px 10px; }
            .sidebar-brand p, .sidebar-brand h2, .nav-item span, .go-mobile-widget { display: none; }
            .nav-item { justify-content: center; padding: 12px; }
            .nav-item i { font-size: 24px; }
            .main-content { margin-left: 80px; max-width: calc(100% - 80px); }
        }
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; max-width: 100%; padding: 20px; }
            
            .header-renter { flex-direction: column !important; text-align: center; gap: 15px !important; margin-bottom: 24px !important; }
            .profile-grid { grid-template-columns: 1fr !important; }
            .residence-grid { grid-template-columns: 1fr !important; }
            .preferences-grid { grid-template-columns: 1fr !important; }
        }

        
        .header-renter {
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; flex-wrap: wrap; gap: 20px;
        }

        .header-greeting { display: flex; align-items: center; gap: 16px; }
        .header-icon-wrapper { width: 48px; height: 48px; background: linear-gradient(135deg, rgba(98, 75, 255, 0.1), rgba(139, 92, 246, 0.1)); border-radius: 14px; display: flex; align-items: center; justify-content: center; box-shadow: inset 0 2px 4px rgba(255,255,255,0.5); flex-shrink: 0; }
        
        .header-title-area h1 { margin: 0 0 4px 0; font-size: 28px; font-weight: 800; color: var(--text-dark); letter-spacing: -0.5px; }
        .header-title-area p { margin: 0; font-size: 14px; color: var(--text-gray); font-weight: 500; }

        .header-actions { display: flex; align-items: center; gap: 16px; }
        .header-actions .icon-btn {
            width: 44px; height: 44px; border-radius: 50%; border: 1px solid var(--border); background: white;
            display: flex; align-items: center; justify-content: center; color: var(--text-dark); font-size: 20px;
            position: relative; cursor: pointer; text-decoration: none; transition: 0.2s; box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        }
        .header-actions .icon-btn:hover { background: #f8fafc; transform: translateY(-1px); }
        .header-actions .icon-btn .badge { position: absolute; top: -5px; right: -5px; background: #EF4444; color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; border: 2px solid white; }
        
        .user-profile-pill { display: flex; align-items: center; gap: 12px; cursor: pointer; padding-left: 12px; border-left: 1px solid var(--border); white-space: nowrap; }
        .user-avatar { width: 40px; height: 40px; background: var(--primary-purple); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 16px; box-shadow: 0 4px 10px rgba(98,75,255,0.2); }
        .user-info h4 { font-size: 14px; font-weight: 700; margin: 0; color: var(--text-dark); }
        .user-info p { font-size: 12px; color: var(--text-gray); margin: 0; }

        .btn-outline-support {
            border: 1px solid rgba(98, 75, 255, 0.15); background: white; color: var(--primary-purple);
            padding: 10px 16px; border-radius: 20px; font-weight: 600; font-size: 13px; display: flex; align-items: center; gap: 8px; text-decoration: none; transition: 0.2s; box-shadow: 0 2px 8px rgba(0,0,0,0.02);
            white-space: nowrap;
        }
        .btn-outline-support:hover { background: #f8fafc; transform: translateY(-1px); }

        .profile-grid { display: grid; grid-template-columns: 1fr 1.2fr; gap: 24px; align-items: start; }
        .grid-col-left { display: flex; flex-direction: column; gap: 24px; }
        .grid-col-right { display: flex; flex-direction: column; gap: 24px; }
        
        .panel { background: var(--white); border-radius: 16px; padding: 24px; box-shadow: var(--card-shadow); border: 1px solid var(--border); transition: var(--transition); }
        .panel-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .panel-header h3 { font-size: 16px; font-weight: 700; color: var(--text-dark); margin: 0; display: flex; align-items: center; gap: 8px; }
        .panel-header h3 i { font-size: 20px; color: var(--primary-purple); }

        /* Avatar Card overrides */
        .avatar-card { text-align: center; background: #F8F7FF; border: none; padding: 32px 20px; display: flex; flex-direction: column; justify-content: center; align-items: center; min-height: 270px; }
        .avatar-wrapper { position: relative; display: inline-block; margin-bottom: 20px; }
        .avatar-huge { width: 140px; height: 140px; border-radius: 50%; object-fit: cover; box-shadow: 0 10px 30px rgba(0,0,0,0.08); background: var(--white); }
        .btn-edit-avatar { position: absolute; bottom: 4px; right: 4px; width: 40px; height: 40px; border-radius: 50%; background: var(--white); border: 3px solid #F8F7FF; box-shadow: 0 4px 10px rgba(0,0,0,0.05); color: var(--primary-purple); cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 20px; transition: var(--transition); }
        .btn-edit-avatar:hover { transform: scale(1.1); }
        
        .info-row { display: flex; justify-content: space-between; align-items: center; padding: 14px 0; border-bottom: 1px solid var(--border); }
        .info-row:last-child { border-bottom: none; padding-bottom: 0; }
        .info-label { display: flex; align-items: center; gap: 10px; color: var(--text-gray); font-size: 13px; font-weight: 500; }
        .info-label i { font-size: 18px; color: var(--text-gray); }
        .info-value { font-size: 14px; font-weight: 600; color: var(--text-dark); text-align: right; max-width: 60%; word-break: break-word; }

        /* Residence Grid */
        .residence-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .residence-item { display: flex; align-items: flex-start; gap: 12px; }
        .residence-icon { width: 40px; height: 40px; border-radius: 10px; background: rgba(98, 75, 255, 0.08); color: var(--primary-purple); display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
        .residence-info h4 { margin: 0 0 4px 0; font-size: 12px; color: var(--text-gray); font-weight: 500; }
        .residence-info p { margin: 0; font-size: 14px; font-weight: 700; color: var(--text-dark); }

        /* Preferences Toggles */
        .preferences-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; }
        .pref-item { display: flex; justify-content: space-between; align-items: center; gap: 16px; padding: 16px; background: #F8FAFC; border-radius: 14px; border: 1px solid var(--border); }
        .pref-info h4 { margin: 0 0 4px 0; font-size: 13px; font-weight: 600; color: var(--text-dark); display: flex; align-items: center; gap: 6px; }
        .pref-info p { margin: 0; font-size: 11px; color: var(--text-gray); }
        
        .toggle-switch { position: relative; width: 40px; height: 22px; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .toggle-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #CBD5E1; transition: .4s; border-radius: 34px; }
        .toggle-slider:before { position: absolute; content: ""; height: 16px; width: 16px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        input:checked + .toggle-slider { background-color: var(--primary-purple); }
        input:checked + .toggle-slider:before { transform: translateX(18px); }

        /* Security Info Values */
        .value-green { color: #10B981 !important; font-weight: 700; }
        
        /* Document List */
        .doc-item { display: flex; align-items: center; gap: 16px; padding: 16px; background: #F8FAFC; border-radius: 14px; margin-bottom: 12px; border: 1px solid var(--border); }
        .doc-item:last-child { margin-bottom: 0; }
        .doc-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
        .doc-icon.green { background: rgba(16, 185, 129, 0.1); color: #10B981; }
        .doc-icon.purple { background: rgba(98, 75, 255, 0.1); color: var(--primary-purple); }
        .doc-icon.red { background: rgba(239, 68, 68, 0.1); color: #EF4444; }
        .doc-icon.yellow { background: rgba(245, 158, 11, 0.1); color: #F59E0B; }
        .doc-info { flex-grow: 1; }
        .doc-info h4 { margin: 0; font-size: 14px; font-weight: 600; color: var(--text-dark); }
        
        .status-pill { padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-block; }
        .status-verified { background: rgba(16, 185, 129, 0.1); color: #10B981; }
        .status-pending { background: rgba(245, 158, 11, 0.1); color: #F59E0B; }
        
        .doc-actions { display: flex; align-items: center; gap: 8px; color: var(--text-gray); }
        .doc-actions a { color: var(--text-gray); font-size: 18px; transition: var(--transition); text-decoration: none; display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; background: var(--white); border: 1px solid var(--border); }
        .doc-actions a:hover { color: var(--primary-purple); border-color: var(--primary-purple); }
        
        .upload-doc-link { display: inline-flex; align-items: center; gap: 6px; color: var(--primary-purple); font-weight: 600; font-size: 13px; text-decoration: none; margin-top: 16px; }
        .upload-doc-link:hover { text-decoration: underline; }

        /* Cropper Modal Styles */
        #cropperModal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; align-items: center; justify-content: center; padding: 20px; backdrop-filter: blur(5px); }
        .cropper-content { background: var(--white); padding: 32px; border-radius: 24px; max-width: 500px; width: 100%; box-shadow: 0 20px 50px rgba(0,0,0,0.3); }
        .img-container { width: 100%; max-height: 400px; margin-bottom: 24px; overflow: hidden; border-radius: 16px; }
        
        .hidden-form { display: none; }
    
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

    </style>
</head>
<body style="display: block;">

<div class="app-container">
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <i class='bx bx-home-heart'></i>
            </div>
            <div class="sidebar-brand">
                <h2><?php echo htmlspecialchars(HOUSE_NAME); ?></h2>
                <p>Resident Dashboard</p>
            </div>
        </div>
        
        <nav class="nav-menu">
            <a href="dashboard.php" class="nav-item">
                <i class='bx bx-grid-alt'></i>
                <span>Dashboard</span>
            </a>
            <a href="my-payments.php" class="nav-item">
                <i class='bx bx-wallet'></i>
                <span>My Payments</span>
            </a>
            <a href="electricity-record.php" class="nav-item">
                <i class='bx bx-bolt-circle'></i>
                <span>Electricity Record</span>
            </a>
            <a href="my-bills.php" class="nav-item">
                <i class='bx bx-receipt'></i>
                <span>My Bills</span>
            </a>
            <a href="queries.php" class="nav-item">
                <i class='bx bx-message-square-dots'></i>
                <span>Raise Query</span>
            </a>
            <a href="notices.php" class="nav-item">
                <i class='bx bx-bell'></i>
                <span>Notices</span>
            </a>
            <a href="documents.php" class="nav-item">
                <i class='bx bx-folder'></i>
                <span>Documents</span>
            </a>
            <a href="profile.php" class="nav-item active">
                <i class='bx bx-user-circle'></i>
                <span>Profile Settings</span>
            </a>
            <a href="../logout.php" class="nav-item" style="color: #FF4B6B; margin-top: 20px;">
                <i class='bx bx-log-out'></i>
                <span>Logout</span>
            </a>
        </nav>
        
        <div class="go-mobile-widget">
            <h4>Go Mobile!</h4>
            <p>Manage your payments on the go.</p>
            <div class="go-mobile-imgs">
                <div class="mock-phone">
                    <i class='bx bx-wallet' style="color: white; font-size: 20px;"></i>
                </div>
                <div class="mock-qr">
                    <img src="../assets/img/qr-placeholder.png" alt="QR" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiPjxyZWN0IHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIGZpbGw9IiNlMGUwZTAiLz48dGV4dCB4PSI1MCUiIHk9IjUwJSIgZm9udC1mYW1pbHk9InNhbnMtc2VyaWYiIGZvbnQtc2l6ZT0iMTBweCIgZmlsbD0iIzY2NiIgZG1pbmFudC1iYXNlbGluZT0ibWlkZGxlIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIj5RUjwvdGV4dD48L3N2Zz4='">
                </div>
            </div>
            <a href="#" class="btn-download"><i class='bx bx-download'></i> Download App</a>
        </div>
    </aside>

<main class="main-content">
    <header class="header-renter">
        <div class="header-greeting">
            <div class="header-icon-wrapper">
                <i class='bx bx-user-circle' style="font-size: 24px; color: var(--primary-purple);"></i>
            </div>
            <div class="header-title-area">
                <h1>Profile Settings</h1>
                <p>View and update your personal information and preferences.</p>
            </div>
        </div>
          <div class="header-actions">
              <div class="notification-wrapper">
                  <div class="icon-btn bell-icon" onclick="document.getElementById('notifDropdown').style.display = document.getElementById('notifDropdown').style.display === 'none' ? 'block' : 'none';">
                      <i class='bx bx-bell'></i>
                      <?php if ($unread_count > 0): ?>
                          <span style="position: absolute; top: -5px; right: -5px; background: #EF4444; color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; border: 2px solid white; animation: pulse 2s infinite;">
                              <?php echo $unread_count; ?>
                          </span>
                      <?php endif; ?>
                  </div>
                  
                  <!-- Notification Dropdown -->
                  <div id="notifDropdown" style="display: none;">
                      <div style="padding: 16px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
                          <h3 style="margin: 0; font-size: 15px; font-weight: 700; color: var(--text-dark);">Notifications</h3>
                          <?php if($unread_count > 0): ?>
                              <span style="font-size: 11px; background: rgba(239, 68, 68, 0.1); color: #EF4444; padding: 4px 8px; border-radius: 10px; font-weight: 600;"><?php echo $unread_count; ?> New</span>
                          <?php endif; ?>
                      </div>
                      <div style="max-height: 350px; overflow-y: auto;">
                          <?php if (empty($unread_notifications)): ?>
                              <div style="padding: 30px; text-align: center; color: var(--text-gray);">
                                  <i class='bx bx-bell-off' style="font-size: 40px; opacity: 0.5; margin-bottom: 10px;"></i>
                                  <p style="margin: 0; font-size: 14px;">You're all caught up!</p>
                              </div>
                          <?php else: ?>
                              <?php foreach ($unread_notifications as $notif): ?>
                                  <div class="notif-item animate-up" data-id="<?php echo $notif['id']; ?>" style="border-bottom: 1px solid var(--border); position: relative; overflow: hidden; background: white; cursor: default;">
                                      <div style="position: absolute; right: 0; top: 0; bottom: 0; width: 80px; background: #EF4444; display: flex; align-items: center; justify-content: center; color: white; font-size: 24px; z-index: 1;">
                                          <i class='bx bx-trash'></i>
                                      </div>
                                      <div class="notif-content" style="padding: 16px; display: flex; gap: 12px; position: relative; z-index: 2; background: white; transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);">
                                          <div style="width: 40px; height: 40px; border-radius: 50%; background: <?php echo $notif['color']; ?>15; color: <?php echo $notif['color']; ?>; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;">
                                              <i class='bx <?php echo $notif['icon']; ?>'></i>
                                          </div>
                                          <div style="flex: 1; padding-right: 36px;">
                                              <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 4px;">
                                                  <h4 style="margin: 0; font-size: 14px; font-weight: 700; color: var(--text-dark); padding-right: 8px;"><?php echo htmlspecialchars($notif['title']); ?></h4>
                                                  <span style="font-size: 11px; color: var(--text-gray); font-weight: 600; white-space: nowrap;"><?php echo date('M d', strtotime($notif['time'])); ?></span>
                                              </div>
                                              <p style="margin: 0; font-size: 13px; color: var(--text-gray); line-height: 1.4;"><?php echo htmlspecialchars($notif['message']); ?></p>
                                          </div>
                                          <button onclick="dismissNotification('<?php echo $notif['id']; ?>', this)" style="position: absolute; right: 12px; top: 16px; background: none; border: none; font-size: 18px; color: var(--text-gray); opacity: 0.5; cursor: pointer; padding: 4px; border-radius: 50%; display: flex; align-items: center; justify-content: center;" onmouseover="this.style.background='rgba(0,0,0,0.05)'; this.style.opacity='1'" onmouseout="this.style.background='none'; this.style.opacity='0.5'" title="Dismiss">
                                              <i class='bx bx-x'></i>
                                          </button>
                                      </div>
                                  </div>
                              <?php endforeach; ?>
                          <?php endif; ?>
                      </div>
                  </div>
              </div>
              <div class="icon-btn" id="themeToggle" onclick="document.body.classList.toggle('dark-theme')">
                  <i class='bx bx-moon'></i>
              </div>
              <a href="queries.php" class="btn-outline-support"><i class='bx bx-help-circle'></i> Help & Support</a>
            
            <div style="position: relative;">
                  <div class="user-profile-pill" onclick="document.getElementById('profileDropdown').style.display = document.getElementById('profileDropdown').style.display === 'none' ? 'block' : 'none'; event.stopPropagation();">
                      <div class="user-avatar" style="overflow: hidden; background: #E0E7FF; color: var(--primary-purple); display: flex; align-items: center; justify-content: center;">
<?php 
    $real_pic = '';
    if (isset($user['profile_pic']) && !empty($user['profile_pic'])) $real_pic = $user['profile_pic'];
    elseif (isset($usr['profile_pic']) && !empty($usr['profile_pic'])) $real_pic = $usr['profile_pic'];
    elseif (isset($profile_pic) && $profile_pic !== 'assets/img/default-avatar.png' && !empty($profile_pic)) $real_pic = $profile_pic;
    
    $d_name = $display_name ?? $user['name'] ?? $usr['name'] ?? 'User';
?>
<?php if (!empty($real_pic)): ?>
    <img src="../<?php echo htmlspecialchars($real_pic); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
<?php else: ?>
    <span style="color: var(--primary-purple); font-weight: 700;"><?php echo strtoupper(substr(trim($d_name), 0, 2)); ?></span>
<?php endif; ?>
</div>
                      <div class="user-info">
                          <h4><?php echo htmlspecialchars(explode(' ', trim($display_name ?? $user['name'] ?? 'User'))[0]); ?></h4>
                          <p>Room <?php echo htmlspecialchars($room_no ?? $user['room_no'] ?? $_SESSION['room_no'] ?? 'N/A'); ?></p>
                      </div>
                      <i class='bx bx-chevron-down' style="color: var(--text-gray);"></i>
                  </div>
                  
                  <div id="profileDropdown" style="display: none; position: absolute; top: 110%; right: 0; background: white; border: 1px solid var(--border); border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); width: 200px; z-index: 1000; overflow: hidden;">
                      <a href="profile.php" style="display: flex; align-items: center; gap: 10px; padding: 14px 16px; text-decoration: none; color: var(--text-dark); font-size: 14px; font-weight: 500; border-bottom: 1px solid var(--border); transition: 0.2s;">
                          <i class='bx bx-user' style="font-size: 18px; color: var(--primary-purple);"></i> Profile Settings
                      </a>
                      <a href="../logout.php" style="display: flex; align-items: center; gap: 10px; padding: 14px 16px; text-decoration: none; color: #FF4B6B; font-size: 14px; font-weight: 500; transition: 0.2s;">
                          <i class='bx bx-log-out' style="font-size: 18px;"></i> Logout
                      </a>
                  </div>
              </div>
          </div>
      </header>

    <?php if ($errmsg): ?>
        <div id="statusAlert" class="animate-up" style="background: #FEF2F2; color: #EF4444; padding: 16px; border-radius: 14px; margin-bottom: 24px; border: 1px solid #FEE2E2; transition: opacity 0.5s ease;">
            <i class='bx bx-error-circle'></i> <?php echo htmlspecialchars($errmsg); ?>
        </div>
    <?php elseif ($success): ?>
        <div id="statusAlert" class="animate-up" style="background: #F0FDF4; color: #10B981; padding: 16px; border-radius: 14px; margin-bottom: 24px; border: 1px solid #DCFCE7; transition: opacity 0.5s ease;">
            <i class='bx bx-check-circle'></i> <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <div class="profile-grid animate-up">
        <!-- LEFT COLUMN -->
        <div class="grid-col-left">
            <!-- Avatar Card -->
            <div class="panel avatar-card">
                <div class="avatar-wrapper">
                    <?php if (!empty($profile_pic) && file_exists("../" . $profile_pic)): ?>
                        <img src="../<?php echo htmlspecialchars($profile_pic); ?>" alt="profile" class="avatar-huge" id="profileAvatarImg">
                    <?php else: ?>
                        <div class="avatar-huge" id="profileAvatarFallback" style="display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, var(--primary-purple), #8B5CF6); box-shadow: 0 10px 25px rgba(98, 75, 255, 0.25); color: white; font-size: 48px; font-weight: 700; letter-spacing: 2px; text-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                            <?php echo strtoupper(substr($display_name, 0, 2)); ?>
                        </div>
                        <img src="" alt="profile" class="avatar-huge" id="profileAvatarImg" style="display: none;">
                    <?php endif; ?>
                    <button type="button" class="btn-edit-avatar" onclick="document.getElementById('profilePicInput').click()">
                        <i class='bx bx-camera'></i>
                    </button>
                    <!-- Hidden form for profile pic -->
                    <form method="POST" id="hiddenProfileForm" class="hidden-form">
                        <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf']); ?>">
                        <input type="hidden" name="name" value="<?php echo htmlspecialchars($user['name']); ?>">
                        <input type="hidden" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                        <input type="hidden" name="email" value="<?php echo htmlspecialchars($user['email']); ?>">
                        <input type="hidden" name="whatsapp" value="<?php echo htmlspecialchars($user['whatsapp']); ?>">
                        <input type="hidden" name="room_no" value="<?php echo htmlspecialchars($user['room_no']); ?>">
                        <input type="hidden" name="about" value="<?php echo htmlspecialchars($user['about']); ?>">
                        <input type="file" id="profilePicInput" accept="image/*">
                        <input type="hidden" name="cropped_image" id="croppedImageInput">
                        <button type="submit" name="save_profile" id="saveProfileBtn"></button>
                    </form>
                </div>
                <h2 style="margin: 0 0 12px 0; font-weight: 800; font-size: 22px; color: var(--text-dark); letter-spacing: -0.5px;"><?php echo htmlspecialchars($display_name); ?></h2>
                <span style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 18px; background: rgba(98, 75, 255, 0.1); color: var(--primary-purple); font-weight: 700; border-radius: 20px; font-size: 13.5px; box-shadow: inset 0 0 0 1px rgba(98, 75, 255, 0.1);"><i class='bx bx-door-open' style="font-size: 17px;"></i> Room <?php echo htmlspecialchars($user['room_no']); ?></span>
            </div>

            <!-- Basic Information -->
            <div class="panel">
                  <div class="panel-header">
                      <h3>Basic Information</h3>
                      <button class="btn-outline" onclick="document.getElementById('editProfileModal').style.display='flex'"><i class='bx bx-edit-alt'></i> Edit</button>
                  </div>
                  <div class="info-list">
                      <div class="info-row"><div class="info-label"><i class='bx bx-user'></i> Full Name</div><div class="info-value"><?php echo htmlspecialchars($user['name'] ?: '-'); ?></div></div>
                      <div class="info-row"><div class="info-label"><i class='bx bx-envelope'></i> Email Address</div><div class="info-value"><?php echo htmlspecialchars($user['email'] ?: '-'); ?></div></div>
                      <div class="info-row"><div class="info-label"><i class='bx bx-phone'></i> Phone Number</div><div class="info-value"><?php echo htmlspecialchars($user['phone'] ?: '-'); ?></div></div>
                      <div class="info-row"><div class="info-label"><i class='bx bx-phone-call'></i> Alternate Number</div><div class="info-value"><?php echo htmlspecialchars($user['whatsapp'] ?: '-'); ?></div></div>
                      <div class="info-row"><div class="info-label"><i class='bx bx-calendar'></i> Date of Birth</div><div class="info-value"><?php echo $user['dob'] ? date('d M Y', strtotime($user['dob'])) : '-'; ?></div></div>
                      <div class="info-row"><div class="info-label"><i class='bx bx-male'></i> Gender</div><div class="info-value"><?php echo htmlspecialchars($user['gender'] ?: '-'); ?></div></div>
                      <div class="info-row"><div class="info-label"><i class='bx bx-map'></i> Address</div><div class="info-value" style="text-align: right; line-height: 1.4;"><?php echo htmlspecialchars($user['address'] ?: '-'); ?></div></div>
                  </div>
              </div>

            <!-- Residence Details -->
            <div class="panel">
                <div class="panel-header">
                    <h3>Residence Details</h3>
                </div>
                <div class="residence-grid">
                    <div class="residence-item">
                        <div class="residence-icon"><i class='bx bx-home-alt-2'></i></div>
                        <div class="residence-info"><h4>Flat / Room No.</h4><p><?php echo htmlspecialchars($user['room_no']); ?></p></div>
                    </div>
                    <div class="residence-item">
                        <div class="residence-icon"><i class='bx bx-building-house'></i></div>
                        <div class="residence-info"><h4>Block / Building</h4><p>Block A</p></div>
                    </div>
                    <div class="residence-item">
                        <div class="residence-icon"><i class='bx bx-map-alt'></i></div>
                        <div class="residence-info"><h4>Property Name</h4><p><?php echo HOUSE_NAME; ?> Apartments</p></div>
                    </div>
                    <div class="residence-item">
                        <div class="residence-icon"><i class='bx bx-layer'></i></div>
                        <div class="residence-info"><h4>Floor</h4><p>2nd Floor</p></div>
                    </div>
                    <div class="residence-item">
                        <div class="residence-icon"><i class='bx bx-calendar-event'></i></div>
                        <div class="residence-info"><h4>Move-in Date</h4><p><?php echo $user['joining_date'] ? date('d M Y', strtotime($user['joining_date'])) : '01 Jan 2024'; ?></p></div>
                    </div>
                    <div class="residence-item">
                        <div class="residence-icon"><i class='bx bx-rupee'></i></div>
                        <div class="residence-info"><h4>Monthly Rent</h4><p>₹<?php echo number_format($user['fixed_rent'] ?? 8000, 2); ?></p></div>
                    </div>
                    <div class="residence-item">
                        <div class="residence-icon"><i class='bx bx-check-shield'></i></div>
                        <div class="residence-info"><h4>Security Deposit</h4><p>₹<?php echo number_format($user['advance_payment'] ?? 16000, 2); ?></p></div>
                    </div>
                    <div class="residence-item">
                        <div class="residence-icon"><i class='bx bx-car'></i></div>
                        <div class="residence-info"><h4>Parking Slot</h4><p>A-15</p></div>
                    </div>
                </div>
            </div>
            
            <!-- Preferences -->
            <div class="panel">
                <div class="panel-header" style="margin-bottom: 16px;">
                    <h3>Preferences</h3>
                </div>
                <div class="preferences-grid">
                    <div class="pref-item">
                        <div class="pref-info">
                            <h4><i class='bx bx-envelope' style="color: var(--primary-purple);"></i> Email Notifications</h4>
                            <p>Receive important updates via email</p>
                        </div>
                        <label class="toggle-switch"><input type="checkbox" checked><span class="toggle-slider"></span></label>
                    </div>
                    <div class="pref-item">
                        <div class="pref-info">
                            <h4><i class='bx bx-message-rounded-dots' style="color: var(--primary-purple);"></i> SMS Notifications</h4>
                            <p>Receive important updates via SMS</p>
                        </div>
                        <label class="toggle-switch"><input type="checkbox" checked><span class="toggle-slider"></span></label>
                    </div>
                    <div class="pref-item">
                        <div class="pref-info">
                            <h4><i class='bx bx-bell' style="color: var(--primary-purple);"></i> Bill Reminders</h4>
                            <p>Receive reminders before due</p>
                        </div>
                        <label class="toggle-switch"><input type="checkbox" checked><span class="toggle-slider"></span></label>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN -->
        <div class="grid-col-right">
            <!-- Account & Security -->
            <div class="panel">
                <div class="panel-header">
                    <h3><i class='bx bx-check-shield'></i> Account & Security</h3>
                </div>
                <div class="info-list">
                    <div class="info-row">
                        <div class="info-label"><i class='bx bx-lock-alt'></i> Password</div>
                        <div style="display: flex; align-items: center; gap: 20px;">
                            <div class="info-value">••••••••</div>
                            <button class="btn-outline" style="padding: 4px 12px;" onclick="alert('Password change modal')">Change</button>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label"><i class='bx bx-envelope'></i> Login Email</div>
                        <div style="display: flex; align-items: center; gap: 20px;">
                            <div class="info-value"><?php echo htmlspecialchars($user['email'] ?: 'user@example.com'); ?></div>
                            <button class="btn-outline" style="padding: 4px 12px;">Change</button>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label"><i class='bx bx-shield-quarter'></i> Two-Factor Auth</div>
                        <div style="display: flex; align-items: center; gap: 20px;">
                            <div class="info-value value-green">Enabled</div>
                            <button class="btn-outline" style="padding: 4px 12px;">Manage</button>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label"><i class='bx bx-info-circle'></i> Account Status</div>
                        <div class="info-value value-green">Active</div>
                    </div>
                </div>
            </div>

            <!-- Emergency Contact -->
            <div class="panel">
                  <div class="panel-header">
                      <h3><i class='bx bx-user-circle'></i> Emergency Contact</h3>
                      <button class="btn-outline" onclick="document.getElementById('editProfileModal').style.display='flex'"><i class='bx bx-edit-alt'></i> Edit</button>
                  </div>
                  <div class="info-list">
                      <div class="info-row"><div class="info-label"><i class='bx bx-user'></i> Contact Name</div><div class="info-value"><?php echo htmlspecialchars($user['emergency_contact_name'] ?: '-'); ?></div></div>
                      <div class="info-row"><div class="info-label"><i class='bx bx-group'></i> Relationship</div><div class="info-value"><?php echo htmlspecialchars($user['emergency_contact_relation'] ?: '-'); ?></div></div>
                      <div class="info-row"><div class="info-label"><i class='bx bx-phone'></i> Phone Number</div><div class="info-value"><?php echo htmlspecialchars($user['emergency_contact_phone'] ?: '-'); ?></div></div>
                      <div class="info-row"><div class="info-label"><i class='bx bx-map'></i> Address</div><div class="info-value" style="text-align: right; line-height: 1.4;"><?php echo htmlspecialchars($user['emergency_contact_address'] ?: '-'); ?></div></div>
                  </div>
              </div>

            <!-- Linked Documents -->
            <div class="panel">
                <div class="panel-header">
                    <h3><i class='bx bx-file'></i> Linked Documents</h3>
                    <button class="btn-outline" style="padding: 6px 12px;" onclick="window.location.href='documents.php'">View All</button>
                </div>
                <div class="doc-list">
                    <!-- Aadhar Card -->
                    <div class="doc-item">
                        <div class="doc-icon green"><i class='bx bx-id-card'></i></div>
                        <div class="doc-info"><h4>Aadhar Card</h4></div>
                        <div class="status-pill <?php echo $user['aadhaar_file'] ? 'status-verified' : 'status-pending'; ?>" <?php echo !$user['aadhaar_file'] ? 'style="background: rgba(245, 158, 11, 0.1); color: #F59E0B;"' : ''; ?>><?php echo $user['aadhaar_file'] ? 'Verified' : 'Pending'; ?></div>
                        <div class="doc-actions">
                            <?php if ($user['aadhaar_file']): ?>
                                <a href="../<?php echo htmlspecialchars($user['aadhaar_file']); ?>" target="_blank"><i class='bx bx-show'></i></a>
                            <?php else: ?>
                                <a href="documents.php" title="Upload"><i class='bx bx-upload'></i></a>
                            <?php endif; ?>
                            <a href="documents.php"><i class='bx bx-chevron-right'></i></a>
                        </div>
                    </div>
                    
                    <!-- Agreement Copy -->
                    <div class="doc-item">
                        <div class="doc-icon purple"><i class='bx bx-file-blank'></i></div>
                        <div class="doc-info"><h4>Agreement Copy</h4></div>
                        <div class="status-pill <?php echo $user['agreement_document'] ? 'status-verified' : 'status-pending'; ?>" <?php echo !$user['agreement_document'] ? 'style="background: rgba(245, 158, 11, 0.1); color: #F59E0B;"' : ''; ?>><?php echo $user['agreement_document'] ? 'Verified' : 'Pending'; ?></div>
                        <div class="doc-actions">
                            <?php if ($user['agreement_document']): ?>
                                <a href="../<?php echo htmlspecialchars($user['agreement_document']); ?>" target="_blank"><i class='bx bx-show'></i></a>
                            <?php endif; ?>
                            <a href="documents.php"><i class='bx bx-chevron-right'></i></a>
                        </div>
                    </div>

                    <!-- Electricity Copy -->
                    <div class="doc-item">
                        <div class="doc-icon" style="background: rgba(16, 185, 129, 0.1); color: #10B981;"><i class='bx bx-bolt-circle'></i></div>
                        <div class="doc-info"><h4>Electricity Copy</h4></div>
                        <div class="status-pill <?php echo $user['electricity_document'] ? 'status-verified' : 'status-pending'; ?>" <?php echo !$user['electricity_document'] ? 'style="background: rgba(245, 158, 11, 0.1); color: #F59E0B;"' : ''; ?>><?php echo $user['electricity_document'] ? 'Verified' : 'Pending'; ?></div>
                        <div class="doc-actions">
                            <?php if ($user['electricity_document']): ?>
                                <a href="../<?php echo htmlspecialchars($user['electricity_document']); ?>" target="_blank"><i class='bx bx-show'></i></a>
                            <?php endif; ?>
                            <a href="documents.php"><i class='bx bx-chevron-right'></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Profile Modal -->
    <div id="editProfileModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 9999; align-items: center; justify-content: center; padding: 20px; backdrop-filter: blur(8px);">
        <div class="no-scrollbar" style="background: var(--white); padding: 40px; border-radius: 28px; max-width: 620px; width: 100%; box-shadow: 0 25px 60px rgba(0,0,0,0.4), inset 0 2px 0 rgba(255,255,255,0.5); max-height: 90vh; overflow-y: auto; box-sizing: border-box; position: relative;">
            
            <!-- Decorative Top Gradient Line -->
            <div style="position: absolute; top: 0; left: 0; right: 0; height: 6px; background: linear-gradient(135deg, var(--primary-purple), #8B5CF6); border-radius: 28px 28px 0 0;"></div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; margin-top: 8px;">
                <h3 style="margin: 0; font-size: 26px; font-weight: 800; display: flex; align-items: center; gap: 10px;">
                    <div style="width: 40px; height: 40px; border-radius: 14px; background: rgba(98, 75, 255, 0.1); display: flex; align-items: center; justify-content: center;">
                        <i class='bx bx-edit-alt' style="color: var(--primary-purple); font-size: 24px;"></i>
                    </div>
                    <span style="background: linear-gradient(135deg, var(--primary-purple), #8B5CF6); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Edit Profile</span>
                </h3>
                <button type="button" onclick="document.getElementById('editProfileModal').style.display='none'" style="width: 36px; height: 36px; border-radius: 10px; background: rgba(0,0,0,0.04); border: none; font-size: 20px; cursor: pointer; color: var(--text-dark); display: flex; align-items: center; justify-content: center; transition: 0.2s;"><i class='bx bx-x'></i></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf'] ?? ''); ?>">
                <input type="hidden" name="save_profile" value="1">
                <input type="hidden" name="room_no" value="<?php echo htmlspecialchars($user['room_no'] ?? ''); ?>">
                <input type="hidden" name="about" value="<?php echo htmlspecialchars($user['about'] ?? ''); ?>">
                
                <h4 style="margin: 0 0 16px 0; font-size: 15px; color: var(--primary-purple);"><i class='bx bx-user'></i> Basic Information</h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; color: var(--text-dark); margin-bottom: 8px;">Full Name</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" style="width: 100%; padding: 12px 16px; border-radius: 14px; border: 1px solid var(--border); background: #F8FAFC; font-size: 14px; box-sizing: border-box;" required>
                    </div>
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; color: var(--text-dark); margin-bottom: 8px;">Email Address</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" style="width: 100%; padding: 12px 16px; border-radius: 14px; border: 1px solid var(--border); background: #F8FAFC; font-size: 14px; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; color: var(--text-dark); margin-bottom: 8px;">Phone Number</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" style="width: 100%; padding: 12px 16px; border-radius: 14px; border: 1px solid var(--border); background: #F8FAFC; font-size: 14px; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; color: var(--text-dark); margin-bottom: 8px;">Alternate Number</label>
                        <input type="text" name="whatsapp" value="<?php echo htmlspecialchars($user['whatsapp'] ?? ''); ?>" style="width: 100%; padding: 12px 16px; border-radius: 14px; border: 1px solid var(--border); background: #F8FAFC; font-size: 14px; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; color: var(--text-dark); margin-bottom: 8px;">Date of Birth</label>
                        <input type="date" name="dob" value="<?php echo htmlspecialchars($user['dob'] ?? ''); ?>" style="width: 100%; padding: 12px 16px; border-radius: 14px; border: 1px solid var(--border); background: #F8FAFC; font-size: 14px; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; color: var(--text-dark); margin-bottom: 8px;">Gender</label>
                        <select name="gender" style="width: 100%; padding: 12px 16px; border-radius: 14px; border: 1px solid var(--border); background: #F8FAFC; font-size: 14px; box-sizing: border-box;">
                            <option value="">Select Gender</option>
                            <option value="Male" <?php echo ($user['gender'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo ($user['gender'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo ($user['gender'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    <div style="grid-column: span 2;">
                        <label style="display: block; font-size: 13px; font-weight: 600; color: var(--text-dark); margin-bottom: 8px;">Address</label>
                        <input type="text" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" style="width: 100%; padding: 12px 16px; border-radius: 14px; border: 1px solid var(--border); background: #F8FAFC; font-size: 14px; box-sizing: border-box;">
                    </div>
                </div>

                <div style="width: 100%; height: 1px; background: var(--border); margin: 24px 0;"></div>

                <h4 style="margin: 0 0 16px 0; font-size: 15px; color: var(--primary-purple);"><i class='bx bx-plus-medical'></i> Emergency Contact</h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; color: var(--text-dark); margin-bottom: 8px;">Contact Name</label>
                        <input type="text" name="emergency_contact_name" value="<?php echo htmlspecialchars($user['emergency_contact_name'] ?? ''); ?>" style="width: 100%; padding: 12px 16px; border-radius: 14px; border: 1px solid var(--border); background: #F8FAFC; font-size: 14px; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; color: var(--text-dark); margin-bottom: 8px;">Relationship</label>
                        <input type="text" name="emergency_contact_relation" value="<?php echo htmlspecialchars($user['emergency_contact_relation'] ?? ''); ?>" style="width: 100%; padding: 12px 16px; border-radius: 14px; border: 1px solid var(--border); background: #F8FAFC; font-size: 14px; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; color: var(--text-dark); margin-bottom: 8px;">Phone Number</label>
                        <input type="text" name="emergency_contact_phone" value="<?php echo htmlspecialchars($user['emergency_contact_phone'] ?? ''); ?>" style="width: 100%; padding: 12px 16px; border-radius: 14px; border: 1px solid var(--border); background: #F8FAFC; font-size: 14px; box-sizing: border-box;">
                    </div>
                    <div style="grid-column: span 2;">
                        <label style="display: block; font-size: 13px; font-weight: 600; color: var(--text-dark); margin-bottom: 8px;">Contact Address</label>
                        <input type="text" name="emergency_contact_address" value="<?php echo htmlspecialchars($user['emergency_contact_address'] ?? ''); ?>" style="width: 100%; padding: 12px 16px; border-radius: 14px; border: 1px solid var(--border); background: #F8FAFC; font-size: 14px; box-sizing: border-box;">
                    </div>
                </div>

                <div style="display: flex; gap: 12px; justify-content: flex-end;">
                    <button type="button" class="btn-outline" onclick="document.getElementById('editProfileModal').style.display='none'" style="border: none;">Cancel</button>
                    <button type="submit" class="btn-primary" style="width: auto; padding: 12px 32px;">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Cropper Modal -->
    <div id="cropperModal">
        <div class="cropper-content animate-up">
            <h3 style="margin-bottom: 15px; font-weight: 700; color: var(--text-dark);">Crop Your Image</h3>
            <div class="img-container">
                <img id="imageToCrop" src="" alt="To Crop">
            </div>
            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" class="btn-outline" onclick="closeCropper()" style="padding: 10px 20px;">Cancel</button>
                <button type="button" class="btn-primary" onclick="applyCrop()" style="padding: 10px 20px; background: var(--primary-purple); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">Crop & Set</button>
            </div>
        </div>
    </div>
</main>
</div>



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
    const avatarPreview = document.getElementById('profileAvatarImg');

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
        avatarPreview.style.display = 'block';
        if (document.getElementById('profileAvatarFallback')) {
            document.getElementById('profileAvatarFallback').style.display = 'none';
        }
        
        cropperModal.style.display = 'none';
        if (cropper) cropper.destroy();
        
        // Auto submit form to save picture
        document.getElementById('saveProfileBtn').click();
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

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(event) {
        const profileDropdown = document.getElementById('profileDropdown');
        const profileToggle = document.querySelector('.user-profile-pill');
        if (profileDropdown && profileDropdown.style.display === 'block') {
            if (!profileDropdown.contains(event.target) && !profileToggle.contains(event.target)) {
                profileDropdown.style.display = 'none';
            }
        }
        
        const notifDropdown = document.getElementById('notificationDropdown');
        const notifToggle = document.querySelector('.icon-btn'); // The bell icon
        if (notifDropdown && notifDropdown.style.display === 'block') {
            if (!notifDropdown.contains(event.target) && !event.target.closest('.icon-btn')) {
                notifDropdown.style.display = 'none';
            }
        }
    });

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

<?php
// admin/edit-renter.php
require_once "../db.php";
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { header("Location: manage-renters.php"); exit; }

$success = "";
$error = "";

// Fetch current data
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$user) { header("Location: manage-renters.php"); exit; }

// Check if any bills exist to disable base_reading
$bill_check = mysqli_query($conn, "SELECT id FROM electricity WHERE user_id = $id LIMIT 1");
$has_bills = mysqli_num_rows($bill_check) > 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf'] ?? '')) {
        $error = "Security validation failed. Please try again.";
    } else {
        $name = trim($_POST['name'] ?? '');
        $room_no = trim($_POST['room_no'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $whatsapp = trim($_POST['whatsapp'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $whatsapp = trim($_POST['whatsapp'] ?? '');
        $about = trim($_POST['about'] ?? '');
        $joining_date = $_POST['joining_date'] ?? null;
        $advance_payment = (float)($_POST['advance_payment'] ?? 0);
        $fixed_rent = (float)($_POST['fixed_rent'] ?? 0);
        $fixed_maintenance = (float)($_POST['fixed_maintenance'] ?? 0);
        
        if (empty($name)) {
            $error = "Name is required.";
        } else {
            $admin_id = $_SESSION['admin_id'] ?? 1; // Fallback
            $old_rent = (float)$user['fixed_rent'];
            $old_maint = (float)$user['fixed_maintenance'];
            $rent_maint_changed = ($old_rent != $fixed_rent || $old_maint != $fixed_maintenance);

            $aadhaar_update = "";
            $agreement_update = "";
            $types = "sssssssdddd";
            $params = [$name, $room_no, $phone, $email, $whatsapp, $about, $joining_date, $advance_payment, $advance_payment, $fixed_rent, $fixed_maintenance];

            // Handle Aadhaar Upload
            if (isset($_FILES['aadhaar_file']) && $_FILES['aadhaar_file']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['aadhaar_file']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['pdf','png','jpg','jpeg'])) {
                    $new_name = "uploads/aadhaar/{$id}_aadhaar_" . time() . ".{$ext}";
                    if(!is_dir("../uploads/aadhaar/")) mkdir("../uploads/aadhaar/", 0777, true);
                    if(move_uploaded_file($_FILES['aadhaar_file']['tmp_name'], "../".$new_name)) {
                        $aadhaar_update = ", aadhaar_file=?";
                        $types .= "s";
                        $params[] = $new_name;
                    }
                }
            }
            
            // Handle Agreement Upload
            if (isset($_FILES['agreement_document']) && $_FILES['agreement_document']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['agreement_document']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['pdf','png','jpg','jpeg'])) {
                    $new_name = $id . "_agreement_" . time() . "." . $ext;
                    if(!is_dir("../uploads/agreements/")) mkdir("../uploads/agreements/", 0777, true);
                    if(move_uploaded_file($_FILES['agreement_document']['tmp_name'], "../uploads/agreements/".$new_name)) {
                        $agreement_update = ", agreement_document=?, agreement_upload_date=NOW()";
                        $types .= "s";
                        $params[] = $new_name;
                    }
                }
            }

            // If advance_payment is changed, update advance_updated_at
            $sql = "UPDATE users SET name=?, room_no=?, phone=?, email=?, whatsapp=?, about=?, joining_date=?, advance_updated_at = IF(advance_payment != ?, NOW(), advance_updated_at), advance_payment=?, fixed_rent=?, fixed_maintenance=? {$aadhaar_update} {$agreement_update}";

            if ($rent_maint_changed) {
                $sql .= ", rent_maint_updated_at=NOW(), rent_maint_updated_by=?";
                $types .= "i";
                $params[] = $admin_id;
            }

            // Only update base_reading if no bills exist
            if (!$has_bills) {
                $sql .= ", base_reading=?";
                $types .= "i";
                $params[] = (int)($_POST['base_reading'] ?? 0);
            }

            $sql .= " WHERE id=?";
            $types .= "i";
            $params[] = $id;

            $upd = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($upd, $types, ...$params);
            
            if (mysqli_stmt_execute($upd)) {
                $success = "Resident profile updated successfully!";
                
                // Log changes if rent/maintenance changed
                if (file_exists('../audit.php')) {
                    require_once '../audit.php';
                    if ($old_rent != $fixed_rent) {
                        $action = "Rent Amount updated from ₹" . number_format($old_rent, 2) . " to ₹" . number_format($fixed_rent, 2);
                        logAction($conn, 'admin', $id, $action);
                    }
                    if ($old_maint != $fixed_maintenance) {
                        $action = "Maintenance Amount updated from ₹" . number_format($old_maint, 2) . " to ₹" . number_format($fixed_maintenance, 2);
                        logAction($conn, 'admin', $id, $action);
                    }
                }
            } else {
                $error = "Update failed: " . mysqli_error($conn);
            }
            mysqli_stmt_close($upd);
            
            // Refetch user
            $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            mysqli_stmt_close($stmt);
        }
    }
}

$admin_user = s($_SESSION['admin']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Edit Resident | <?php echo HOUSE_NAME; ?></title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-design-system.css">
</head>
<body>

<?php include "sidebar.php"; ?>

<main class="main">
    <?php include 'header.php'; ?>

    <div class="animate-up" style="display: flex; align-items: center; gap: 16px; margin-bottom: 24px; max-width: 1000px; margin-left: auto; margin-right: auto; width: 100%;">
        <a href="view-renter.php?id=<?php echo $user['id']; ?>" class="btn-outline" style="padding: 8px; width: 56px; height: 56px; display: flex; align-items: center; justify-content: center; border-radius: 16px; margin-right: 4px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); border: 1px solid #E2E8F0; background: #ffffff; text-decoration: none; transition: all 0.2s ease;" onmouseover="this.style.background='#F8FAFC'; this.style.borderColor='#CBD5E1'; this.style.transform='translateY(-1px)';" onmouseout="this.style.background='#ffffff'; this.style.borderColor='#E2E8F0'; this.style.transform='none';">
            <i class='bx bx-arrow-back' style="font-size: 24px; color: #64748B;"></i>
        </a>
        <div style="width: 56px; height: 56px; background: linear-gradient(135deg, rgba(98, 75, 255, 0.1), rgba(16, 185, 129, 0.1)); color: var(--primary-purple); border-radius: 16px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
            <i class='bx bx-user-pin' style="font-size: 28px;"></i>
        </div>
        <div>
            <h1 style="font-size: 24px; font-weight: 700; color: var(--text-dark); margin: 0; letter-spacing: -0.5px;">Edit Profile</h1>
            <p style="font-size: 14px; color: var(--text-gray); margin: 4px 0 0 0;">Modify details and configurations for <span style="font-weight: 600; color: var(--primary-purple);"><?php echo htmlspecialchars($user['name']); ?></span></p>
        </div>
    </div>

    <div class="animate-up">
        <div style="max-width: 1000px; margin: 0 auto; width: 100%;">
            <div class="panel" style="padding: 40px 48px; border-radius: 20px; box-shadow: 0 12px 40px rgba(0,0,0,0.06); border: 1px solid rgba(0,0,0,0.05); background: #ffffff; border-top: 5px solid var(--primary-purple);">
                <?php if ($success): ?>
                    <div style="background: #F0FDF4; color: #10B981; padding: 15px; border-radius: 12px; margin-bottom: 20px; border: 1px solid #DCFCE7;">
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div style="background: #FEF2F2; color: #EF4444; padding: 15px; border-radius: 12px; margin-bottom: 20px; border: 1px solid #FEE2E2;">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf" value="<?php echo getCsrfToken(); ?>">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 24px; margin-bottom: 32px;">
                        
                        <!-- Card 1: PERSONAL INFO -->
                        <div style="background: #F8FAFC; border-radius: 20px; padding: 28px; border: 1px solid #F1F5F9;">
                            <div style="display: inline-flex; align-items: center; gap: 8px; background: #ffffff; padding: 8px 16px; border-radius: 20px; color: var(--primary-purple); font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); margin-bottom: 24px; border: 1px solid #E2E8F0;">
                                <i class='bx bx-user-circle' style="font-size: 16px;"></i> BASIC INFO
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                                <div class="form-group" style="margin: 0; grid-column: 1 / -1;">
                                    <label style="font-size: 11px; font-weight: 700; color: #64748B; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; display: block;">Resident Full Name <span style="color:#EF4444">*</span></label>
                                    <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required style="width: 100%; padding: 12px 16px; border-radius: 12px; border: 1px solid #E2E8F0; background: #ffffff; font-size: 14px; font-weight: 500; color: #1E293B; outline: none; transition: all 0.2s ease;" onfocus="this.style.borderColor='var(--primary-purple)'; this.style.boxShadow='0 0 0 3px rgba(98, 75, 255, 0.1)';" onblur="this.style.borderColor='#E2E8F0'; this.style.boxShadow='none';">
                                </div>
                                <div class="form-group" style="margin: 0;">
                                    <label style="font-size: 11px; font-weight: 700; color: #64748B; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; display: block;">Room No</label>
                                    <input type="text" name="room_no" value="<?php echo htmlspecialchars($user['room_no']); ?>" style="width: 100%; padding: 12px 16px; border-radius: 12px; border: 1px solid #E2E8F0; background: #ffffff; font-size: 14px; font-weight: 500; color: #1E293B; outline: none; transition: all 0.2s ease;" onfocus="this.style.borderColor='var(--primary-purple)'; this.style.boxShadow='0 0 0 3px rgba(98, 75, 255, 0.1)';" onblur="this.style.borderColor='#E2E8F0'; this.style.boxShadow='none';">
                                </div>
                                <div class="form-group" style="margin: 0;">
                                    <label style="font-size: 11px; font-weight: 700; color: #64748B; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; display: block;">Joining Date</label>
                                    <input type="date" name="joining_date" value="<?php echo htmlspecialchars($user['joining_date'] ?? ''); ?>" style="width: 100%; padding: 12px 16px; border-radius: 12px; border: 1px solid #E2E8F0; background: #ffffff; font-size: 14px; font-weight: 500; color: #1E293B; outline: none; transition: all 0.2s ease;" onfocus="this.style.borderColor='var(--primary-purple)'; this.style.boxShadow='0 0 0 3px rgba(98, 75, 255, 0.1)';" onblur="this.style.borderColor='#E2E8F0'; this.style.boxShadow='none';">
                                </div>
                            </div>
                        </div>

                        <!-- Card 2: CONTACT DETAILS -->
                        <div style="background: #F8FAFC; border-radius: 20px; padding: 28px; border: 1px solid #F1F5F9;">
                            <div style="display: inline-flex; align-items: center; gap: 8px; background: #ffffff; padding: 8px 16px; border-radius: 20px; color: var(--primary-purple); font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); margin-bottom: 24px; border: 1px solid #E2E8F0;">
                                <i class='bx bx-phone' style="font-size: 16px;"></i> CONTACT DETAILS
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                                <div class="form-group" style="margin: 0; grid-column: 1 / -1;">
                                    <label style="font-size: 11px; font-weight: 700; color: #64748B; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; display: block;">Email Address</label>
                                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" style="width: 100%; padding: 12px 16px; border-radius: 12px; border: 1px solid #E2E8F0; background: #ffffff; font-size: 14px; font-weight: 500; color: #1E293B; outline: none; transition: all 0.2s ease;" onfocus="this.style.borderColor='var(--primary-purple)'; this.style.boxShadow='0 0 0 3px rgba(98, 75, 255, 0.1)';" onblur="this.style.borderColor='#E2E8F0'; this.style.boxShadow='none';">
                                </div>
                                <div class="form-group" style="margin: 0;">
                                    <label style="font-size: 11px; font-weight: 700; color: #64748B; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; display: block;">Phone Number</label>
                                    <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" style="width: 100%; padding: 12px 16px; border-radius: 12px; border: 1px solid #E2E8F0; background: #ffffff; font-size: 14px; font-weight: 500; color: #1E293B; outline: none; transition: all 0.2s ease;" onfocus="this.style.borderColor='var(--primary-purple)'; this.style.boxShadow='0 0 0 3px rgba(98, 75, 255, 0.1)';" onblur="this.style.borderColor='#E2E8F0'; this.style.boxShadow='none';">
                                </div>
                                <div class="form-group" style="margin: 0;">
                                    <label style="font-size: 11px; font-weight: 700; color: #64748B; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; display: block;">WhatsApp</label>
                                    <input type="text" name="whatsapp" value="<?php echo htmlspecialchars($user['whatsapp'] ?? ''); ?>" style="width: 100%; padding: 12px 16px; border-radius: 12px; border: 1px solid #E2E8F0; background: #ffffff; font-size: 14px; font-weight: 500; color: #1E293B; outline: none; transition: all 0.2s ease;" onfocus="this.style.borderColor='var(--primary-purple)'; this.style.boxShadow='0 0 0 3px rgba(98, 75, 255, 0.1)';" onblur="this.style.borderColor='#E2E8F0'; this.style.boxShadow='none';">
                                </div>
                            </div>
                        </div>

                        <!-- Card 3: LEASE & FINANCIALS -->
                        <div style="background: #F8FAFC; border-radius: 20px; padding: 28px; border: 1px solid #F1F5F9;">
                            <div style="display: inline-flex; align-items: center; gap: 8px; background: #ffffff; padding: 8px 16px; border-radius: 20px; color: var(--primary-purple); font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); margin-bottom: 24px; border: 1px solid #E2E8F0;">
                                <i class='bx bx-wallet' style="font-size: 16px;"></i> LEASE CHARGES
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                                <div class="form-group" style="margin: 0;">
                                    <label style="font-size: 11px; font-weight: 700; color: #64748B; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; display: block;">Monthly Rent</label>
                                    <div style="position: relative; display: flex; align-items: center;">
                                        <span style="position: absolute; left: 16px; font-size: 15px; color: #94A3B8; font-weight: 600; pointer-events: none;">₹</span>
                                        <input type="number" step="0.01" name="fixed_rent" value="<?php echo number_format($user['fixed_rent'] ?? 0, 2, '.', ''); ?>" style="width: 100%; padding: 12px 16px 12px 40px; border-radius: 12px; border: 1px solid #E2E8F0; background: #ffffff; font-size: 14px; font-weight: 500; color: #1E293B; outline: none; transition: all 0.2s ease;" onfocus="this.style.borderColor='var(--primary-purple)'; this.style.boxShadow='0 0 0 3px rgba(98, 75, 255, 0.1)';" onblur="this.style.borderColor='#E2E8F0'; this.style.boxShadow='none';">
                                    </div>
                                </div>
                                <div class="form-group" style="margin: 0;">
                                    <label style="font-size: 11px; font-weight: 700; color: #64748B; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; display: block;">Maintenance</label>
                                    <div style="position: relative; display: flex; align-items: center;">
                                        <span style="position: absolute; left: 16px; font-size: 15px; color: #94A3B8; font-weight: 600; pointer-events: none;">₹</span>
                                        <input type="number" step="0.01" name="fixed_maintenance" value="<?php echo number_format($user['fixed_maintenance'] ?? 0, 2, '.', ''); ?>" style="width: 100%; padding: 12px 16px 12px 40px; border-radius: 12px; border: 1px solid #E2E8F0; background: #ffffff; font-size: 14px; font-weight: 500; color: #1E293B; outline: none; transition: all 0.2s ease;" onfocus="this.style.borderColor='var(--primary-purple)'; this.style.boxShadow='0 0 0 3px rgba(98, 75, 255, 0.1)';" onblur="this.style.borderColor='#E2E8F0'; this.style.boxShadow='none';">
                                    </div>
                                </div>
                                <div class="form-group" style="margin: 0;">
                                    <label style="font-size: 11px; font-weight: 700; color: #64748B; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; display: block;">Deposit / Adv.</label>
                                    <div style="position: relative; display: flex; align-items: center;">
                                        <span style="position: absolute; left: 16px; font-size: 15px; color: #94A3B8; font-weight: 600; pointer-events: none;">₹</span>
                                        <input type="number" step="0.01" name="advance_payment" value="<?php echo number_format($user['advance_payment'], 2, '.', ''); ?>" style="width: 100%; padding: 12px 16px 12px 40px; border-radius: 12px; border: 1px solid #E2E8F0; background: #ffffff; font-size: 14px; font-weight: 500; color: #1E293B; outline: none; transition: all 0.2s ease;" onfocus="this.style.borderColor='var(--primary-purple)'; this.style.boxShadow='0 0 0 3px rgba(98, 75, 255, 0.1)';" onblur="this.style.borderColor='#E2E8F0'; this.style.boxShadow='none';">
                                    </div>
                                </div>
                                <div class="form-group" style="margin: 0;">
                                    <label style="font-size: 11px; font-weight: 700; color: #64748B; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; display: block;">Initial Meter</label>
                                    <input type="number" name="base_reading" value="<?php echo (int)$user['base_reading']; ?>" <?php echo $has_bills ? 'readonly' : ''; ?> style="width: 100%; padding: 12px 16px; border-radius: 12px; border: 1px solid #E2E8F0; background: <?php echo $has_bills ? '#F1F5F9' : '#ffffff'; ?>; font-size: 14px; font-weight: 500; color: #1E293B; outline: none; transition: all 0.2s ease;">
                                    <?php if($has_bills): ?>
                                        <div style="font-size: 10px; color: #EF4444; margin-top: 4px; font-weight: 600;"><i class='bx bx-lock'></i> Locked</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Card 4: DOCUMENTS & NOTES -->
                        <div style="background: #F8FAFC; border-radius: 20px; padding: 28px; border: 1px solid #F1F5F9;">
                            <div style="display: inline-flex; align-items: center; gap: 8px; background: #ffffff; padding: 8px 16px; border-radius: 20px; color: var(--primary-purple); font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); margin-bottom: 24px; border: 1px solid #E2E8F0;">
                                <i class='bx bx-folder' style="font-size: 16px;"></i> DOCUMENTS & FILES
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                                <div class="form-group" style="margin: 0;">
                                    <label style="font-size: 11px; font-weight: 700; color: #64748B; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; display: block;">Identity Proof (Aadhaar)</label>
                                    <input type="file" name="aadhaar_file" accept=".pdf, .png, .jpg, .jpeg" style="width: 100%; padding: 10px; border-radius: 12px; border: 1px dashed var(--primary-purple); background: #ffffff; font-size: 13px; color: #1E293B; cursor: pointer;">
                                    <?php if (!empty($user['aadhaar_file'])): ?>
                                        <div style="font-size: 11px; color: #10B981; margin-top: 4px; font-weight: 600;"><i class='bx bx-check-circle'></i> File uploaded.</div>
                                    <?php endif; ?>
                                </div>
                                <div class="form-group" style="margin: 0;">
                                    <label style="font-size: 11px; font-weight: 700; color: #64748B; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; display: block;">Rental Agreement</label>
                                    <input type="file" name="agreement_document" accept=".pdf, .png, .jpg, .jpeg" style="width: 100%; padding: 10px; border-radius: 12px; border: 1px dashed var(--primary-purple); background: #ffffff; font-size: 13px; color: #1E293B; cursor: pointer;">
                                    <?php if (!empty($user['agreement_document'])): ?>
                                        <div style="font-size: 11px; color: #10B981; margin-top: 4px; font-weight: 600;"><i class='bx bx-check-circle'></i> File uploaded.</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Card 5: EXTRA ADJUSTMENTS (Full Width) -->
                        <div style="background: #F8FAFC; border-radius: 20px; padding: 28px; border: 1px solid #F1F5F9; grid-column: 1 / -1;">
                            <div style="display: inline-flex; align-items: center; gap: 8px; background: #ffffff; padding: 8px 16px; border-radius: 20px; color: var(--primary-purple); font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); margin-bottom: 24px; border: 1px solid #E2E8F0;">
                                <i class='bx bx-note' style="font-size: 16px;"></i> EXTRA ADJUSTMENTS / NOTES
                            </div>
                            <div class="form-group" style="margin: 0;">
                                <label style="font-size: 11px; font-weight: 700; color: #64748B; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; display: block;">Admin Notes / About</label>
                                <textarea name="about" rows="3" style="width:100%; padding:14px; border-radius:12px; border: 1px solid #E2E8F0; background: #ffffff; color:#1E293B; outline:none; font-family:inherit; font-size: 14px; transition: all 0.2s ease; resize: vertical;" onfocus="this.style.borderColor='var(--primary-purple)'; this.style.boxShadow='0 0 0 3px rgba(98, 75, 255, 0.1)';" onblur="this.style.borderColor='#E2E8F0'; this.style.boxShadow='none';"><?php echo htmlspecialchars($user['about']); ?></textarea>
                            </div>
                        </div>

                    </div>

                    <div style="display: flex; justify-content: flex-end; padding-top: 24px; border-top: 1px solid var(--border);">
                        <button type="submit" class="btn-primary" style="padding: 14px 40px; font-size: 15px; font-weight: 600; border-radius: 10px; display: inline-flex; justify-content: center;"><i class='bx bx-save'></i> Save Profile Changes</button>
                </form>
            </div>
        </div>
    </div>
</main>
</body>
</html>

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

    <div class="welcome animate-up">
        <h1>Edit Profile</h1>
        <p>Modify details for <?php echo htmlspecialchars($user['name']); ?></p>
    </div>

    <div class="animate-up" style="margin-top: 30px;">
        <div style="max-width: 1200px; margin: 0 auto; width: 100%;">
            <div class="panel" style="padding: 40px;">
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
                    <!-- Personal Information -->
                    <div style="margin-bottom: 32px;">
                        <h4 style="font-size: 14px; color: var(--text-gray); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid var(--border);"><i class='bx bx-user'></i> Personal Information</h4>
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px;">
                            <div class="form-group">
                                <label>Resident Full Name</label>
                                <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required style="background: #F8FAFC;">
                            </div>
                            <div class="form-group">
                                <label>Room No</label>
                                <input type="text" name="room_no" value="<?php echo htmlspecialchars($user['room_no']); ?>" style="background: #F8FAFC;">
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; margin-top: 24px;">
                            <div class="form-group">
                                <label>Phone</label>
                                <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" style="background: #F8FAFC;">
                            </div>
                            <div class="form-group">
                                <label>WhatsApp Number</label>
                                <input type="text" name="whatsapp" value="<?php echo htmlspecialchars($user['whatsapp'] ?? ''); ?>" style="background: #F8FAFC;">
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" style="background: #F8FAFC;">
                            </div>
                        </div>
                    </div>

                    <!-- Lease & Financial Details -->
                    <div style="margin-bottom: 32px;">
                        <h4 style="font-size: 14px; color: var(--text-gray); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid var(--border);"><i class='bx bx-wallet'></i> Lease & Financial Details</h4>
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px;">
                            <div class="form-group">
                                <label>Joining Date</label>
                                <input type="date" name="joining_date" value="<?php echo htmlspecialchars($user['joining_date'] ?? ''); ?>" style="background: #F8FAFC;">
                            </div>
                            <div class="form-group">
                                <label>Initial Meter Reading (Units)</label>
                                <input type="number" name="base_reading" value="<?php echo (int)$user['base_reading']; ?>" <?php echo $has_bills ? 'disabled' : ''; ?> style="background: <?php echo $has_bills ? '#F1F5F9' : '#F8FAFC'; ?>;">
                                <?php if($has_bills): ?>
                                    <p style="font-size: 11px; color: #EF4444; margin-top: 6px; font-weight: 500;"><i class='bx bx-lock-alt'></i> Locked after first bill is generated.</p>
                                <?php else: ?>
                                    <p style="font-size: 11px; color: var(--text-gray); margin-top: 6px;">Locked once the first bill is generated.</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; margin-top: 24px;">
                            <div class="form-group">
                                <label>Monthly Rent (₹)</label>
                                <input type="number" step="0.01" name="fixed_rent" value="<?php echo number_format($user['fixed_rent'] ?? 0, 2, '.', ''); ?>" style="background: #F8FAFC;">
                            </div>
                            <div class="form-group">
                                <label>Monthly Maintenance (₹)</label>
                                <input type="number" step="0.01" name="fixed_maintenance" value="<?php echo number_format($user['fixed_maintenance'] ?? 0, 2, '.', ''); ?>" style="background: #F8FAFC;">
                            </div>
                            <div class="form-group">
                                <label>Advance Deposit (₹)</label>
                                <input type="number" step="0.01" name="advance_payment" value="<?php echo number_format($user['advance_payment'], 2, '.', ''); ?>" style="background: #F8FAFC;">
                            </div>
                        </div>
                    </div>

                    <!-- Notes & Documents -->
                    <div style="margin-bottom: 32px;">
                        <h4 style="font-size: 14px; color: var(--text-gray); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid var(--border);"><i class='bx bx-note'></i> Additional Information</h4>
                        
                        <div class="form-group">
                            <label>Admin Notes / About</label>
                            <textarea name="about" rows="3" style="width:100%; padding:14px; border:1px solid var(--border); border-radius:12px; background:#F8FAFC; color:var(--text-dark); outline:none; font-family:inherit; transition: all 0.2s ease; resize: vertical;"><?php echo htmlspecialchars($user['about']); ?></textarea>
                        </div>
                    </div>

                    <div style="margin-bottom: 40px;">
                        <h4 style="font-size: 14px; color: var(--text-gray); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid var(--border);"><i class='bx bx-folder'></i> Documents & Files</h4>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px;">
                            <div class="form-group">
                                <label>Identity Proof (Aadhaar)</label>
                                <input type="file" name="aadhaar_file" accept=".pdf, .png, .jpg, .jpeg" style="width: 100%; padding: 12px; border: 1px dashed var(--primary-purple); border-radius: 12px; background: #F8FAFC; color: var(--text-dark); cursor: pointer;">
                                <?php if (!empty($user['aadhaar_file'])): ?>
                                    <p style="font-size: 12px; color: #10B981; margin-top: 8px; font-weight: 500;"><i class='bx bx-check-circle'></i> Document exists. Select new file to overwrite.</p>
                                <?php else: ?>
                                    <p style="font-size: 11px; color: var(--text-gray); margin-top: 8px;">Accepted: PDF, JPG, PNG.</p>
                                <?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label>Rental Agreement</label>
                                <input type="file" name="agreement_document" accept=".pdf, .png, .jpg, .jpeg" style="width: 100%; padding: 12px; border: 1px dashed var(--primary-purple); border-radius: 12px; background: #F8FAFC; color: var(--text-dark); cursor: pointer;">
                                <?php if (!empty($user['agreement_document'])): ?>
                                    <p style="font-size: 12px; color: #10B981; margin-top: 8px; font-weight: 500;"><i class='bx bx-check-circle'></i> Document exists. Select new file to overwrite.</p>
                                <?php else: ?>
                                    <p style="font-size: 11px; color: var(--text-gray); margin-top: 8px;">Accepted: PDF, JPG, PNG.</p>
                                <?php endif; ?>
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

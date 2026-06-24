<?php
// admin/view-renter.php - Redesigned with SaaS UI
require_once "../db.php";
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    echo "Invalid renter id";
    exit;
}

/* Fetch user */
$stmt = mysqli_prepare($conn, "SELECT id, username, name, phone, email, whatsapp, room_no, profile_pic, aadhaar_file, agreement_document, agreement_expiry_date, about, pending_adjustment, advance_payment, advance_updated_at, fixed_rent, fixed_maintenance, rent_maint_updated_at, rent_maint_updated_by, joining_date FROM users WHERE id = ?");

if (!$stmt) {
    die("<div style='padding:20px; background:#ffebeb; color:#d32f2f; border:1px solid #d32f2f; margin:20px;'><strong>Database Query Failed!</strong><br>Error details: " . mysqli_error($conn) . "</div>");
}

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$user) {
    echo "Resident not found";
    exit;
}

/* Fetch electricity records */
$stmt = mysqli_prepare($conn, "
    SELECT e.*, 
           (SELECT SUM(paid_amount) FROM payments WHERE bill_type = 'electricity' AND bill_id = e.id) as total_paid,
           (SELECT SUM(adjustment_amount) FROM payments WHERE bill_type = 'electricity' AND bill_id = e.id) as adjustment_amount
    FROM electricity e 
    WHERE e.user_id = ? 
    ORDER BY e.id DESC
");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$elec_res = mysqli_stmt_get_result($stmt);
$elecs = []; while ($r = mysqli_fetch_assoc($elec_res)) $elecs[] = $r;
mysqli_stmt_close($stmt);

/* Fetch rent records (from electricity table as rent and maintenance combined) */
$stmt = mysqli_prepare($conn, "
    SELECT r.id, r.month, r.rent_amount, r.maintenance, r.status, 
           (SELECT SUM(paid_amount) FROM payments WHERE bill_type = 'electricity' AND bill_id = r.id) as total_paid
    FROM electricity r 
    WHERE r.user_id = ? AND (r.rent_amount > 0 OR r.maintenance > 0)
    ORDER BY r.id DESC
");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$rent_res = mysqli_stmt_get_result($stmt);
$rents = []; while ($r = mysqli_fetch_assoc($rent_res)) $rents[] = $r;
mysqli_stmt_close($stmt);

// Calculate advance paid
$stmt = mysqli_prepare($conn, "SELECT IFNULL(SUM(paid_amount), 0) as adv_paid FROM payments WHERE user_id = ? AND bill_type = 'advance'");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$adv_paid_res = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
$adv_paid = (float)$adv_paid_res['adv_paid'];
mysqli_stmt_close($stmt);

$advance_due = max(0, ($user['advance_payment'] ?? 0) - $adv_paid);

$admin_user = s($_SESSION['admin'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($user['name']); ?> | Profile</title>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-design-system.css">
    <style>
        @media (max-width: 1024px) {
            .dashboard-grid-70 {
                display: flex !important;
                flex-direction: column-reverse !important;
                gap: 24px !important;
            }
            .left-col, .right-col {
                width: 100% !important;
            }
        }
        @media (max-width: 768px) {
            /* .main padding and top-bar alignment are now handled globally */
            .welcome {
                text-align: center !important;
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                padding: 0 !important;
                margin-top: 15px !important;
            }
            .welcome .welcome-top {
                flex-direction: column !important;
                gap: 15px !important;
                width: 100% !important;
            }
            .welcome h1 { 
                font-size: 28px !important; 
                line-height: 1.2 !important;
                margin-top: 10px !important;
            }
            .avatar {
                transform: none !important;
                margin: 0 auto !important;
                position: relative !important;
                left: auto !important;
                bottom: auto !important;
            }
            .hero-profile-group {
                flex-direction: column !important;
                align-items: center !important;
                text-align: center !important;
                width: 100% !important;
                gap: 16px !important;
            }
            .hero-header-row {
                flex-direction: column !important;
                align-items: center !important;
            }
            .hero-details-text {
                justify-content: center !important;
            }
            .hero-card-padding {
                padding: 0 20px 20px 20px !important;
            }
            .right-col .panel > div:nth-child(2) {
                padding: 60px 20px 24px 20px !important;
                text-align: center !important;
            }
            .right-col .panel [style*="display: flex; flex-direction: column"] {
                align-items: center !important;
                text-align: center !important;
            }
            .btn-outline, .btn-primary {
                width: 100% !important;
                justify-content: center !important;
            }
            .back-btn-mobile {
                width: 100% !important;
                display: flex !important;
                justify-content: center !important;
            }
        }
    </style>
</head>
<body>

<?php include "sidebar.php"; ?>

<main class="main">
    <?php include 'header.php'; ?>

    <div class="welcome animate-up">
        <div class="welcome-top" style="display: flex; align-items: center; gap: 16px; margin-bottom: 8px;">
            <a href="manage-renters.php" class="btn-outline back-btn-mobile" style="padding: 10px 20px;"><i class='bx bx-arrow-back'></i></a>
            <div style="flex: 1;">
                <h1 style="margin: 0; margin-bottom: 4px; font-size: 32px; font-weight: 800;"><?php echo ucwords(htmlspecialchars($user['name'])); ?></h1>
                <p style="margin: 0; color: var(--text-gray); font-size: 15px;">Full history and documentation for resident at <?php echo ucwords(HOUSE_NAME); ?></p>
            </div>
        </div>
    </div>

        
    <div class="panel animate-up" style="padding: 0; overflow: hidden; margin-bottom: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); border: none;">
        <div style="height: 160px; background: linear-gradient(135deg, var(--primary-purple), #93A5CF); position: relative;">
            <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0.1; background-image: radial-gradient(circle at right, #fff 0%, transparent 50%);"></div>
        </div>
        
        <div class="hero-card-padding" style="padding: 0 32px 32px 32px; position: relative;">
            <div class="hero-header-row" style="display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 24px; margin-top: -60px; margin-bottom: 32px; border-bottom: 1px solid var(--border); padding-bottom: 24px;">
                <div class="hero-profile-group" style="display: flex; align-items: flex-end; gap: 24px;">
                    <div class="avatar" style="width: 130px; height: 130px; border: 6px solid var(--bg-main); background-image: url('../<?php echo $user['profile_pic'] ?: 'assets/img/default-avatar.png'; ?>'); background-size: cover; background-position: center; border-radius: 24px; box-shadow: var(--card-shadow); flex-shrink: 0; background-color: var(--bg-main); z-index: 2;"></div>
                    <div style="padding-bottom: 4px; width: 100%;">
                        <h2 style="font-weight: 800; font-size: 32px; line-height: 1.1; margin-bottom: 4px; color: var(--text-dark);"><?php echo htmlspecialchars($user['name']); ?></h2>
                        <p class="hero-details-text" style="color: var(--text-gray); font-size: 15px; font-weight: 500; display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                            <i class='bx bx-user-circle' style="font-size: 18px;"></i> @<?php echo htmlspecialchars($user['username']); ?> 
                            <span style="color: var(--border);">|</span> 
                            <span style="color: var(--primary-purple); background: rgba(98, 75, 255, 0.1); padding: 2px 10px; border-radius: 20px; font-weight: 700; font-size: 12px;"><i class='bx bx-door-open'></i> Room <?php echo htmlspecialchars($user['room_no'] ?: 'N/A'); ?></span>
                        </p>
                    </div>
                </div>
                
                <div style="display: flex; gap: 12px; padding-bottom: 6px; flex-wrap: wrap;">
                    <a href="bill-generator.php?user_id=<?php echo $user['id']; ?>" class="btn-primary" style="padding: 10px 18px; box-shadow: 0 4px 12px rgba(98, 75, 255, 0.2);"><i class='bx bx-plus'></i> New Bill</a>
                    <a href="edit-renter.php?id=<?php echo $user['id']; ?>" class="btn-outline" style="padding: 10px 18px; background: transparent;"><i class='bx bx-edit-alt'></i> Edit Profile</a>
                    <button onclick="openAgreementModal()" class="btn-outline" style="padding: 10px 18px; background: transparent;"><i class='bx bx-upload'></i> Agreement</button>
                    <button onclick="openAadhaarModal()" class="btn-outline" style="padding: 10px 18px; background: transparent;"><i class='bx bx-id-card'></i> Aadhaar</button>
                    <button onclick="resetPassword(<?php echo $user['id']; ?>, '<?php echo addslashes($user['name']); ?>')" class="btn-outline" style="padding: 10px 18px; border-color: #FCA5A5; color: #EF4444; background: rgba(239, 68, 68, 0.05);"><i class='bx bx-lock-alt'></i> Password</button>
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
                        <?php if(!empty($user['joining_date'])): ?>
                        <div style="display: flex; align-items: center; gap: 12px; font-size: 14px; font-weight: 500; color: var(--text-dark);">
                            <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(59,130,246,0.08); display: flex; align-items: center; justify-content: center; color: #3B82F6; font-size: 18px;"><i class='bx bx-calendar-plus'></i></div>
                            Joined: <?php echo date('M d, Y', strtotime($user['joining_date'])); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Financial Status -->
                <div>
                    <h4 style="font-size: 13px; color: var(--text-gray); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 16px; font-weight: 700;">Financial Snapshot</h4>
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <?php if ($user['pending_adjustment'] != 0): ?>
                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; border-radius: 12px; background: <?php echo $user['pending_adjustment'] > 0 ? 'rgba(16,185,129,0.08)' : 'rgba(239,68,68,0.08)'; ?>; border: 1px solid <?php echo $user['pending_adjustment'] > 0 ? 'rgba(16,185,129,0.2)' : 'rgba(239,68,68,0.2)'; ?>;">
                            <span style="font-size: 14px; font-weight: 600; color: <?php echo $user['pending_adjustment'] > 0 ? '#059669' : '#DC2626'; ?>; display: flex; align-items: center; gap: 8px;"><i class='bx bx-wallet-alt' style="font-size: 18px;"></i> <?php echo $user['pending_adjustment'] > 0 ? 'Total Credit' : 'Pending Due'; ?></span>
                            <span style="font-weight: 800; font-size: 16px; color: <?php echo $user['pending_adjustment'] > 0 ? '#059669' : '#DC2626'; ?>;">₹<?php echo number_format(abs($user['pending_adjustment']), 2); ?></span>
                        </div>
                        <?php endif; ?>

                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; border-radius: 12px; background: transparent; border: 1px solid var(--border);">
                            <div style="display: flex; flex-direction: column;">
                                <span style="font-size: 14px; font-weight: 600; color: var(--text-dark); display: flex; align-items: center; gap: 8px;"><i class='bx bx-money' style="color: var(--primary-purple); font-size: 18px;"></i> Security Deposit</span>
                                <?php if ($advance_due > 0): ?>
                                    <span style="font-size: 12px; color: #EF4444; font-weight: 600; margin-top: 4px;">Due: ₹<?php echo number_format($advance_due, 2); ?></span>
                                    <button onclick="openPaymentModal('advance', <?php echo $user['id']; ?>, <?php echo $advance_due; ?>, 'Advance Security')" class="btn-primary" style="margin-top: 8px; font-size: 11px; padding: 4px 10px; width: max-content;">Mark Paid</button>
                                <?php elseif (($user['advance_payment'] ?? 0) > 0): ?>
                                    <span style="font-size: 12px; color: #10B981; font-weight: 600; margin-top: 4px;">Fully Paid</span>
                                <?php endif; ?>
                            </div>
                            <span style="font-weight: 800; font-size: 15px; color: var(--text-dark);">₹<?php echo number_format($user['advance_payment'] ?? 0, 2); ?></span>
                        </div>
                        
                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; border-radius: 12px; background: transparent; border: 1px solid var(--border);">
                            <div style="display: flex; flex-direction: column;">
                                <span style="font-size: 14px; font-weight: 600; color: var(--text-dark); display: flex; align-items: center; gap: 8px;"><i class='bx bx-home-circle' style="color: #10B981; font-size: 18px;"></i> Fixed Charges</span>
                                <span style="font-size: 12px; color: var(--text-gray); margin-left: 26px; font-weight: 500;">Rent: ₹<?php echo number_format($user['fixed_rent'] ?? 0); ?> &bull; Maint: ₹<?php echo number_format($user['fixed_maintenance'] ?? 0); ?></span>
                            </div>
                            <span style="font-weight: 800; font-size: 15px; color: var(--text-dark);">₹<?php echo number_format(($user['fixed_rent'] ?? 0) + ($user['fixed_maintenance'] ?? 0), 2); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Documents -->
                <div>
                    <h4 style="font-size: 13px; color: var(--text-gray); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 16px; font-weight: 700;">Documentation</h4>
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <?php if (!empty($user['aadhaar_file'])): ?>
                            <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; background: var(--bg-main); border: 1px solid var(--border); border-radius: 12px;">
                                <div style="display: flex; align-items: center; gap: 12px; min-width: 0;">
                                    <div style="width: 40px; height: 40px; background: rgba(98, 75, 255, 0.1); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                        <i class='bx bx-id-card' style="font-size: 20px; color: var(--primary-purple);"></i>
                                    </div>
                                    <div style="overflow: hidden;">
                                        <div style="font-weight: 600; font-size: 13px; color: var(--text-dark); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Identity Proof (Aadhaar)</div>
                                        <a href="download.php?type=aadhaar&user_id=<?php echo (int)$user['id']; ?>" target="_blank" style="font-size: 11px; color: var(--primary-purple); text-decoration: none; font-weight: 500;">View Document <i class='bx bx-link-external'></i></a>
                                    </div>
                                </div>
                                <a href="delete-doc.php?type=aadhaar&user_id=<?php echo (int)$user['id']; ?>" onclick="return confirm('WARNING: Are you sure you want to delete this Aadhaar document?');" style="width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; background: rgba(239, 68, 68, 0.1); color: #EF4444; text-decoration: none; flex-shrink: 0; transition: all 0.2s ease;" onmouseover="this.style.background='#EF4444'; this.style.color='white';" onmouseout="this.style.background='rgba(239, 68, 68, 0.1)'; this.style.color='#EF4444';">
                                    <i class='bx bx-trash' style="font-size: 18px;"></i>
                                </a>
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
                            <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; background: var(--bg-main); border: 1px solid var(--border); border-radius: 12px; position: relative;">
                                <div style="display: flex; align-items: center; gap: 12px; min-width: 0;">
                                    <div style="width: 40px; height: 40px; background: rgba(16, 185, 129, 0.1); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                        <i class='bx bx-file' style="font-size: 20px; color: #10B981;"></i>
                                    </div>
                                    <div style="overflow: hidden;">
                                        <div style="font-weight: 600; font-size: 13px; color: var(--text-dark); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Rental Agreement</div>
                                        <a href="download-agreement.php?id=<?php echo (int)$user['id']; ?>" target="_blank" style="font-size: 11px; color: #10B981; text-decoration: none; font-weight: 500;">View Document <i class='bx bx-link-external'></i></a>
                                    </div>
                                </div>
                                
                                <div style="display: flex; align-items: center; gap: 8px; flex-shrink: 0;">
                                    <?php if ($expiry_status): ?>
                                        <span style="font-size: 10px; padding: 4px 8px; border-radius: 20px; font-weight: 600; background: <?php echo $expiry_status == 'Expired' ? 'rgba(239, 68, 68, 0.1)' : 'rgba(245, 158, 11, 0.1)'; ?>; color: <?php echo $expiry_status == 'Expired' ? '#EF4444' : '#F59E0B'; ?>;">
                                            <?php echo $expiry_status; ?>
                                        </span>
                                    <?php endif; ?>
                                    <a href="delete-doc.php?type=agreement&user_id=<?php echo (int)$user['id']; ?>" onclick="return confirm('WARNING: Are you sure you want to delete this Rental Agreement?');" style="width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; background: rgba(239, 68, 68, 0.1); color: #EF4444; text-decoration: none; transition: all 0.2s ease;" onmouseover="this.style.background='#EF4444'; this.style.color='white';" onmouseout="this.style.background='rgba(239, 68, 68, 0.1)'; this.style.color='#EF4444';">
                                        <i class='bx bx-trash' style="font-size: 18px;"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (empty($user['aadhaar_file']) && empty($user['agreement_document'])): ?>
                            <div style="background: transparent; padding: 24px; border-radius: 12px; text-align: center; font-size: 14px; font-weight: 500; color: var(--text-gray); border: 1px dashed var(--border);">
                                <i class='bx bx-folder-open' style="font-size: 32px; margin-bottom: 12px; display: block; color: var(--text-gray); opacity: 0.5;"></i>
                                No documents uploaded yet
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
            
            <?php if(!empty($user['about'])): ?>
            <div style="margin-top: 32px; padding: 20px; background: transparent; border-radius: 16px; border: 1px solid var(--border);">
                <h4 style="font-size: 12px; color: var(--text-gray); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; font-weight: 700; display: flex; align-items: center; gap: 6px;"><i class='bx bx-note'></i> Admin Notes / About</h4>
                <p style="font-size: 15px; line-height: 1.6; color: var(--text-dark); margin: 0; font-weight: 500;"><?php echo nl2br(htmlspecialchars($user['about'])); ?></p>
            </div>
            <?php endif; ?>

        </div>
    </div>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 24px;" class="animate-up">
        <div class="panel">
                <div class="panel-header">
                    <h2 style="font-size: 18px; font-weight: 700;">Utility History</h2>
                    <span class="badge" style="background: rgba(98, 75, 255, 0.1); color: var(--primary-purple);"><?php echo count($elecs); ?> Records</span>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Units</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($elecs)): ?>
                                <tr><td colspan="5" style="text-align: center; padding: 40px; color: var(--text-gray);">No electricity records.</td></tr>
                            <?php else: foreach ($elecs as $e): ?>
                            <tr>
                                <td style="font-weight: 600;"><?php echo htmlspecialchars($e['month']); ?></td>
                                <td><?php echo htmlspecialchars($e['units_consumed'] ?? ($e['current_reading'] - $e['previous_reading'])); ?> Units</td>
                                <td style="font-weight: 700;">
                                    ₹<?php echo number_format($e['total_amount'], 2); ?>
                                    <?php if ($e['total_paid'] > 0): ?>
                                        <div style="font-size: 10px; font-weight: 600; color: #10B981;">
                                            Paid: ₹<?php echo number_format($e['total_paid'], 0); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge <?php echo $e['status'] == 'Paid' ? 'badge-paid' : ($e['status'] == 'Partial' ? 'badge-pending' : 'badge-due'); ?>" style="<?php echo $e['status'] == 'Partial' ? 'background: rgba(245, 158, 11, 0.1); color: #F59E0B;' : ''; ?>"><?php echo $e['status']; ?></span></td>
                                <td>
                                    <div style="display: flex; gap: 8px;">
                                        <a href="slip.php?elec_id=<?php echo $e['id']; ?>" class="btn-outline" style="padding: 6px 12px; font-size: 11px;" target="_blank">Slip</a>
                                        <a href="update-electricity.php?user_id=<?php echo $e['user_id']; ?>&id=<?php echo $e['id']; ?>" class="btn-outline" style="padding: 6px 8px; font-size: 11px;"><i class='bx bx-edit'></i></a>
                                        <a href="delete-bill.php?id=<?php echo $e['id']; ?>" class="btn-outline" style="padding: 6px 8px; font-size: 11px; color: #EF4444; border-color: #FCA5A5;" onclick="return confirm('Delete this utility bill completely?');"><i class='bx bx-trash'></i></a>
                                        <?php if($e['status'] != 'Paid'): 
                                                $remaining = max(0, $e['total_amount'] - $e['total_paid']); ?>
                                            <button onclick="openPaymentModal('electricity', <?php echo $e['id']; ?>, <?php echo $remaining; ?>, '<?php echo addslashes($e['month']); ?>')" class="btn-primary" style="padding: 6px 12px; font-size: 11px;">Pay</button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <div class="panel">
                <div class="panel-header">
                    <h2 style="font-size: 18px; font-weight: 700;">Rent History</h2>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rents)): ?>
                                <tr><td colspan="4" style="text-align: center; padding: 40px; color: var(--text-gray);">No rent records.</td></tr>
                            <?php else: foreach ($rents as $r): ?>
                            <tr>
                                <td style="font-weight: 600;"><?php echo htmlspecialchars($r['month']); ?></td>
                                <td style="font-weight: 700;">
                                    <div style="font-size: 11px; color: var(--text-gray); margin-bottom: 2px;">
                                        Rent: ₹<?php echo number_format($r['rent_amount'], 2); ?> <br>
                                        Maint: ₹<?php echo number_format($r['maintenance'], 2); ?>
                                    </div>
                                    <span style="color: var(--text-dark);">Total: ₹<?php echo number_format($r['rent_amount'] + $r['maintenance'], 2); ?></span>
                                    <?php if ($r['total_paid'] > 0): ?>
                                        <div style="font-size: 10px; font-weight: 600; color: #10B981;">
                                            Paid: ₹<?php echo number_format($r['total_paid'], 0); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge <?php echo $r['status'] == 'Paid' ? 'badge-paid' : ($r['status'] == 'Partial' ? 'badge-pending' : 'badge-due'); ?>" style="<?php echo $r['status'] == 'Partial' ? 'background: rgba(245, 158, 11, 0.1); color: #F59E0B;' : ''; ?>"><?php echo $r['status']; ?></span></td>
                                <td>
                                    <?php if($r['status'] != 'Paid'): 
                                            $remaining = max(0, ($r['rent_amount'] + $r['maintenance']) - $r['total_paid']); ?>
                                        <button onclick="openPaymentModal('electricity', <?php echo $r['id']; ?>, <?php echo $remaining; ?>, '<?php echo addslashes($r['month']); ?>')" class="btn-primary" style="padding: 6px 12px; font-size: 11px;">Pay</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
    </div>
</main>
    <!-- Password Reset Modal -->
    <div id="passwordModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; align-items: center; justify-content: center; padding: 20px;">
        <div class="panel animate-up" style="max-width: 400px; width: 100%; padding: 32px; background: var(--white);">
            <div style="text-align: center; margin-bottom: 24px;">
                <div style="width: 64px; height: 64px; background: rgba(239, 68, 68, 0.1); border-radius: 16px; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                    <i class='bx bx-lock-alt' style="font-size: 32px; color: #EF4444;"></i>
                </div>
                <h3 style="font-size: 20px; font-weight: 800; color: var(--text-dark);">Reset Password</h3>
                <p id="resetUsername" style="color: var(--text-gray); font-size: 14px; margin-top: 4px;">Set a new password</p>
            </div>
            
            <input type="hidden" id="resetUserId">
            <div class="form-group" style="margin-bottom: 24px;">
                <label>New Password</label>
                <input type="text" id="newPasswordInput" required style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 12px;">
                <button type="button" onclick="closePasswordModal()" class="btn-outline" style="justify-content: center;">Cancel</button>
                <button type="button" onclick="submitPasswordReset()" class="btn-primary" style="justify-content: center; background: #EF4444; border-color: #EF4444;">Update Password</button>
            </div>
        </div>
    </div>

    <!-- Agreement Upload Modal -->
    <div id="agreementModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; align-items: center; justify-content: center; padding: 20px;">
        <div class="panel animate-up" style="max-width: 400px; width: 100%; padding: 32px; background: var(--white);">
            <div style="text-align: center; margin-bottom: 24px;">
                <div style="width: 64px; height: 64px; background: rgba(16, 185, 129, 0.1); border-radius: 16px; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                    <i class='bx bx-file' style="font-size: 32px; color: #10B981;"></i>
                </div>
                <h3 style="font-size: 20px; font-weight: 800; color: var(--text-dark);">Rental Agreement</h3>
                <p style="color: var(--text-gray); font-size: 14px; margin-top: 4px;">Upload new agreement for <?php echo htmlspecialchars($user['name']); ?></p>
            </div>
            
            <form action="upload-agreement.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="user_id" value="<?php echo $id; ?>">
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Select Document (PDF, PNG, JPG)</label>
                    <input type="file" name="agreement_file" accept=".pdf, .png, .jpg, .jpeg" required style="width: 100%; padding: 8px; border: 1px solid var(--border); border-radius: 8px;">
                    <small style="color: var(--text-gray); font-size: 11px;">Max size: 200MB</small>
                </div>

                <div class="form-group" style="margin-bottom: 24px;">
                    <label>Expiry Date (Optional)</label>
                    <input type="date" name="expiry_date" value="<?php echo $user['agreement_expiry_date']; ?>" style="width: 100%;">
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 12px;">
                    <button type="button" onclick="closeAgreementModal()" class="btn-outline" style="justify-content: center;">Cancel</button>
                    <button type="submit" class="btn-primary" style="justify-content: center; background: #10B981;">Upload Document</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Aadhaar Upload Modal -->
    <div id="aadhaarModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; align-items: center; justify-content: center; padding: 20px;">
        <div class="panel animate-up" style="max-width: 400px; width: 100%; padding: 32px; background: var(--white);">
            <div style="text-align: center; margin-bottom: 24px;">
                <div style="width: 64px; height: 64px; background: rgba(59, 130, 246, 0.1); border-radius: 16px; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                    <i class='bx bx-id-card' style="font-size: 32px; color: #3B82F6;"></i>
                </div>
                <h3 style="font-size: 20px; font-weight: 800; color: var(--text-dark);">Identity Proof</h3>
                <p style="color: var(--text-gray); font-size: 14px; margin-top: 4px;">Upload Aadhaar for <?php echo htmlspecialchars($user['name']); ?></p>
            </div>
            
            <form action="upload-aadhaar.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="user_id" value="<?php echo $id; ?>">
                
                <div class="form-group" style="margin-bottom: 24px;">
                    <label>Select Document (PDF, PNG, JPG)</label>
                    <input type="file" name="aadhaar_file" accept=".pdf, .png, .jpg, .jpeg" required style="width: 100%; padding: 8px; border: 1px solid var(--border); border-radius: 8px;">
                    <small style="color: var(--text-gray); font-size: 11px;">Max size: 200MB</small>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 12px;">
                    <button type="button" onclick="closeAadhaarModal()" class="btn-outline" style="justify-content: center;">Cancel</button>
                    <button type="submit" class="btn-primary" style="justify-content: center; background: #3B82F6; border: none;">Upload Document</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Payment Mode Modal -->
    <div id="paymentModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; align-items: center; justify-content: center; padding: 20px;">
        <div class="panel animate-up" style="max-width: 450px; width: 100%; padding: 32px;">
            <div style="text-align: center; margin-bottom: 24px;">
                <div style="width: 64px; height: 64px; background: rgba(16, 185, 129, 0.1); border-radius: 16px; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                    <i class='bx bx-credit-card' style="font-size: 32px; color: #10B981;"></i>
                </div>
                <h3 style="font-size: 20px; font-weight: 800; color: var(--text-dark);">Record Payment</h3>
                <p id="paymentBillInfo" style="color: var(--text-gray); font-size: 14px; margin-top: 4px;">Select payment method and amount.</p>
            </div>
            
            <form action="mark-paid.php" method="POST">
                <input type="hidden" name="csrf" value="<?php echo getCsrfToken(); ?>">
                <input type="hidden" name="id" id="paymentBillId">
                <input type="hidden" name="type" id="paymentBillType">
                <input type="hidden" name="bill_amount" id="paymentBillAmount">
                
                <div class="form-group">
                    <label>Payment Mode</label>
                    <select name="payment_mode" id="paymentMode" onchange="toggleOfflineFields()" required>
                        <option value="Online">Online Payment (Full)</option>
                        <option value="Offline">Offline / Cash</option>
                    </select>
                </div>

                <div id="offlineFields" style="display: none; border-top: 1px dashed var(--border); padding-top: 20px; margin-top: 20px;">
                    <div class="form-group">
                        <label>Amount Paid (₹)</label>
                        <input type="number" step="0.01" name="paid_amount" id="paidAmountInput" placeholder="Enter amount">
                        <small style="color: var(--text-gray); font-size: 11px;">Partial or extra payments will be adjusted next month.</small>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="form-group">
                            <label>Date</label>
                            <input type="date" name="payment_date" id="paymentDateInput">
                        </div>
                        <div class="form-group">
                            <label>Time</label>
                            <input type="time" name="payment_time" id="paymentTimeInput">
                        </div>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 12px; margin-top: 24px;">
                    <button type="button" onclick="closePaymentModal()" class="btn-outline" style="justify-content: center;">Cancel</button>
                    <button type="submit" class="btn-primary" style="justify-content: center; background: #10B981;">Confirm Payment</button>
                </div>
            </form>
        </div>
    </div>

<script>
    let currentResetId = null;

    function resetPassword(id, name) {
        currentResetId = id;
        document.getElementById('resetUserId').value = id;
        document.getElementById('resetUsername').textContent = `Set a new password for ${name}`;
        document.getElementById('newPasswordInput').value = "123456";
        document.getElementById('passwordModal').style.display = 'flex';
    }

    function closePasswordModal() {
        document.getElementById('passwordModal').style.display = 'none';
        currentResetId = null;
    }

    async function submitPasswordReset() {
        const id = document.getElementById('resetUserId').value;
        const newPass = document.getElementById('newPasswordInput').value;
        
        if (!newPass) {
            alert("Password cannot be empty");
            return;
        }

        try {
            const res = await fetch('reset-password.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=${id}&new_password=${encodeURIComponent(newPass)}&csrf=<?php echo getCsrfToken(); ?>`
            });
            const data = await res.json();
            if (data.success) {
                alert("Password updated successfully!");
                closePasswordModal();
            } else {
                alert(data.message);
            }
        } catch (e) {
            alert("Network error occurred.");
        }
    }

    function openPaymentModal(type, id, amount, month) {
        document.getElementById('paymentBillId').value = id;
        document.getElementById('paymentBillType').value = type;
        document.getElementById('paymentBillAmount').value = amount;
        document.getElementById('paidAmountInput').value = amount;
        document.getElementById('paymentBillInfo').textContent = `${type.charAt(0).toUpperCase() + type.slice(1)} Bill for ${month} (₹${amount})`;
        
        // Init date/time
        const now = new Date();
        document.getElementById('paymentDateInput').value = now.toISOString().split('T')[0];
        document.getElementById('paymentTimeInput').value = now.toTimeString().slice(0, 5);
        
        document.getElementById('paymentModal').style.display = 'flex';
        toggleOfflineFields();
    }

    function closePaymentModal() {
        document.getElementById('paymentModal').style.display = 'none';
    }

    function toggleOfflineFields() {
        const mode = document.getElementById('paymentMode').value;
        const offlineBox = document.getElementById('offlineFields');
        const amountInput = document.getElementById('paidAmountInput');
        const dateInput = document.getElementById('paymentDateInput');
        
        if (mode === 'Offline') {
            offlineBox.style.display = 'block';
            amountInput.required = true;
            dateInput.required = true;
        } else {
            offlineBox.style.display = 'none';
            amountInput.required = false;
            dateInput.required = false;
        }
    }
    
    function openAgreementModal() {
        document.getElementById('agreementModal').style.display = 'flex';
    }

    function closeAgreementModal() {
        document.getElementById('agreementModal').style.display = 'none';
    }

    function openAadhaarModal() {
        document.getElementById('aadhaarModal').style.display = 'flex';
    }

    function closeAadhaarModal() {
        document.getElementById('aadhaarModal').style.display = 'none';
    }
</script>

</body>
</html>

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

        <?php 
        $initials = '';
        $nameParts = explode(' ', $user['name']);
        if (isset($nameParts[0])) $initials .= strtoupper(substr($nameParts[0], 0, 1));
        if (isset($nameParts[1])) $initials .= strtoupper(substr($nameParts[1], 0, 1));
    ?>

    <!-- 1. Top Header Card -->
    <div class="panel animate-up" style="margin-bottom: 24px; padding: 32px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: nowrap; gap: 16px;">
            <div style="display: flex; gap: 16px; align-items: center;">
                <div style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
                    <?php if ($user['profile_pic']): ?>
                        <div style="width: 80px; height: 80px; border-radius: 50%; background-image: url('../<?php echo htmlspecialchars($user['profile_pic']); ?>'); background-size: cover; background-position: center; border: 2px solid #F8FAFC;"></div>
                    <?php else: ?>
                        <div style="width: 80px; height: 80px; border-radius: 50%; background: #F4F7FF; color: #624BFF; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 28px; border: 2px solid #FFFFFF; box-shadow: 0 4px 10px rgba(98, 75, 255, 0.1);"><?php echo $initials ?: '?'; ?></div>
                    <?php endif; ?>
                    <?php if (($user['status'] ?? 'active') == 'active'): ?>
                        <span style="color: #10B981; font-weight: 600; font-size: 12px; background: rgba(16, 185, 129, 0.1); padding: 4px 12px; border-radius: 20px;"><i class='bx bxs-circle' style="font-size: 8px;"></i> Active</span>
                    <?php endif; ?>
                </div>
                
                <div>
                    <h2 style="font-weight: 800; font-size: 24px; margin: 0 0 8px 0; color: var(--text-dark); display: flex; align-items: center; gap: 12px;">
                        <?php echo htmlspecialchars($user['name']); ?>
                    </h2>
                    <div style="display: flex; align-items: center; gap: 12px; color: var(--text-gray); font-size: 13px; font-weight: 500;">
                        <span style="display: flex; align-items: center; gap: 4px;"><i class='bx bx-user-circle' style="font-size: 16px;"></i> @<?php echo htmlspecialchars($user['username']); ?></span>
                        <span style="color: var(--border);">|</span> 
                        <span style="display: flex; align-items: center; gap: 4px; color: var(--primary-purple); background: rgba(98, 75, 255, 0.1); padding: 4px 10px; border-radius: 20px; font-weight: 600; font-size: 12px;"><i class='bx bx-door-open' style="font-size: 14px;"></i> Room <?php echo htmlspecialchars($user['room_no'] ?: 'N/A'); ?></span>

                    </div>
                </div>
            </div>

            <div style="display: flex; gap: 8px; flex-wrap: nowrap; transform: translateY(-12px);">
                <a href="bill-generator.php?user_id=<?php echo $user['id']; ?>" class="btn-primary" style="padding: 9px 18px; border-radius: 8px; white-space: nowrap; font-size: 14px; font-weight: 500;"><i class='bx bx-plus'></i> New Bill</a>
                <a href="edit-renter.php?id=<?php echo $user['id']; ?>" class="btn-outline" style="padding: 9px 18px; border-radius: 8px; background: transparent; white-space: nowrap; font-size: 14px; font-weight: 500;"><i class='bx bx-edit-alt'></i> Edit Profile</a>
                <button onclick="openAgreementModal()" class="btn-outline" style="padding: 9px 18px; border-radius: 8px; background: transparent; white-space: nowrap; font-size: 14px; font-weight: 500;"><i class='bx bx-upload'></i> Agreement</button>
                
                <div style="position: relative; display: inline-block;">
                    <button onclick="document.getElementById('moreDropdown').style.display = document.getElementById('moreDropdown').style.display === 'flex' ? 'none' : 'flex'" class="btn-outline" style="padding: 9px 18px; border-radius: 8px; background: transparent; white-space: nowrap; font-size: 14px; font-weight: 500;"><i class='bx bx-dots-horizontal-rounded'></i> More</button>
                    <div id="moreDropdown" style="display: none; position: absolute; right: 0; top: calc(100% + 8px); background: #FFFFFF; border: 1px solid var(--border); border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); width: 180px; z-index: 100; flex-direction: column; overflow: hidden;">
                        <button onclick="openAadhaarModal(); document.getElementById('moreDropdown').style.display='none';" style="padding: 12px 16px; text-align: left; background: none; border: none; border-bottom: 1px solid var(--border); font-size: 13px; color: var(--text-dark); cursor: pointer; display: flex; align-items: center; gap: 8px;"><i class='bx bx-id-card' style="font-size: 16px; color: #3B82F6;"></i> Aadhaar</button>
                        <button onclick="resetPassword(<?php echo $user['id']; ?>, '<?php echo addslashes($user['name']); ?>'); document.getElementById('moreDropdown').style.display='none';" style="padding: 12px 16px; text-align: left; background: none; border: none; font-size: 13px; color: #EF4444; cursor: pointer; display: flex; align-items: center; gap: 8px;"><i class='bx bx-lock-alt' style="font-size: 16px;"></i> Password</button>
                    </div>
                </div>
            </div>
        </div>

        <div style="display: flex; gap: 32px; align-items: center; border-top: 1px solid var(--border); padding-top: 24px; margin-top: 24px; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 36px; height: 36px; border-radius: 50%; background: rgba(98,75,255,0.08); display: flex; align-items: center; justify-content: center; color: var(--primary-purple); font-size: 18px;"><i class='bx bx-phone'></i></div>
                <div>
                    <div style="font-weight: 600; color: var(--text-dark); font-size: 13px;"><?php echo htmlspecialchars($user['phone'] ?: 'No Phone Number'); ?></div>
                    <div style="color: var(--text-gray); font-size: 11px;">Phone</div>
                </div>
            </div>
            
            <div style="width: 1px; height: 32px; background: var(--border);"></div>
            
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 36px; height: 36px; border-radius: 50%; background: rgba(98,75,255,0.08); display: flex; align-items: center; justify-content: center; color: var(--primary-purple); font-size: 18px;"><i class='bx bx-envelope'></i></div>
                <div>
                    <div style="font-weight: 600; color: var(--text-dark); font-size: 13px;"><?php echo htmlspecialchars($user['email'] ?: 'No Email Address'); ?></div>
                    <div style="color: var(--text-gray); font-size: 11px;">Email</div>
                </div>
            </div>

            <div style="width: 1px; height: 32px; background: var(--border);"></div>

            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 36px; height: 36px; border-radius: 50%; background: rgba(37,211,102,0.08); display: flex; align-items: center; justify-content: center; color: #25D366; font-size: 18px;"><i class='bx bxl-whatsapp'></i></div>
                <div>
                    <div style="font-weight: 600; color: var(--text-dark); font-size: 13px;"><?php echo htmlspecialchars($user['whatsapp'] ?: 'No WhatsApp'); ?></div>
                    <div style="color: var(--text-gray); font-size: 11px;">WhatsApp</div>
                </div>
            </div>

            <?php if(!empty($user['joining_date'])): ?>
            <div style="width: 1px; height: 32px; background: var(--border);"></div>

            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 36px; height: 36px; border-radius: 50%; background: rgba(59,130,246,0.08); display: flex; align-items: center; justify-content: center; color: #3B82F6; font-size: 18px;"><i class='bx bx-calendar'></i></div>
                <div>
                    <div style="font-weight: 600; color: var(--text-dark); font-size: 13px;"><?php echo date('M d, Y', strtotime($user['joining_date'])); ?></div>
                    <div style="color: var(--text-gray); font-size: 11px;">Member Since</div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 2. Middle Section -->
    <div style="display: flex; flex-direction: column; gap: 24px; margin-bottom: 24px;" class="animate-up">
        <!-- Financial Snapshot -->
        <div class="panel">
            <h4 style="font-size: 14px; color: var(--text-dark); margin-bottom: 20px; font-weight: 700; display: flex; align-items: center; gap: 8px;"><div style="width: 32px; height: 32px; background: rgba(98,75,255,0.1); color: var(--primary-purple); border-radius: 8px; display: flex; align-items: center; justify-content: center;"><i class='bx bx-wallet'></i></div> Financial Snapshot</h4>
            
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 16px; border: 1px solid var(--border); border-radius: 12px;">
                    <div style="display: flex; align-items: center; gap: 16px;">
                        <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(16,185,129,0.1); display: flex; align-items: center; justify-content: center; color: #10B981; font-size: 20px;"><i class='bx bx-check-shield'></i></div>
                        <div>
                            <div style="font-weight: 700; color: var(--text-dark); font-size: 14px;">Security Deposit</div>
                            <?php if ($advance_due > 0): ?>
                                <div style="color: #EF4444; font-size: 12px; font-weight: 600; margin-top: 2px;">Due: ₹<?php echo number_format($advance_due, 2); ?></div>
                                <button onclick="openPaymentModal('advance', <?php echo $user['id']; ?>, <?php echo $advance_due; ?>, 'Advance Security')" class="btn-primary" style="margin-top: 6px; font-size: 11px; padding: 4px 10px; width: max-content;">Mark Paid</button>
                            <?php else: ?>
                                <div style="color: #10B981; font-size: 12px; font-weight: 500; margin-top: 2px;">Fully Paid</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div style="font-weight: 700; font-size: 16px; color: #10B981;">₹<?php echo number_format($user['advance_payment'] ?? 0, 2); ?></div>
                </div>

                <div style="display: flex; align-items: center; justify-content: space-between; padding: 16px; border: 1px solid var(--border); border-radius: 12px;">
                    <div style="display: flex; align-items: center; gap: 16px;">
                        <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(59,130,246,0.1); display: flex; align-items: center; justify-content: center; color: #3B82F6; font-size: 20px;"><i class='bx bx-home'></i></div>
                        <div>
                            <div style="font-weight: 700; color: var(--text-dark); font-size: 14px;">Fixed Charges</div>
                            <div style="color: var(--text-gray); font-size: 12px; font-weight: 500; margin-top: 2px;">Rent: ₹<?php echo number_format($user['fixed_rent'] ?? 0); ?> &bull; Maint: ₹<?php echo number_format($user['fixed_maintenance'] ?? 0); ?></div>
                        </div>
                    </div>
                    <div style="font-weight: 700; font-size: 16px; color: #3B82F6;">₹<?php echo number_format(($user['fixed_rent'] ?? 0) + ($user['fixed_maintenance'] ?? 0), 2); ?></div>
                </div>

                <div style="display: flex; align-items: center; justify-content: space-between; padding: 16px; border: 1px solid <?php echo $user['pending_adjustment'] > 0 ? 'rgba(16,185,129,0.2)' : ($user['pending_adjustment'] < 0 ? 'rgba(239,68,68,0.2)' : 'var(--border)'); ?>; border-radius: 12px; background: <?php echo $user['pending_adjustment'] > 0 ? 'rgba(16,185,129,0.05)' : ($user['pending_adjustment'] < 0 ? 'rgba(239,68,68,0.05)' : 'transparent'); ?>;">
                    <div style="display: flex; align-items: center; gap: 16px;">
                        <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(239,68,68,0.1); display: flex; align-items: center; justify-content: center; color: #EF4444; font-size: 20px;"><i class='bx bx-file'></i></div>
                        <div>
                            <div style="font-weight: 700; color: var(--text-dark); font-size: 14px;"><?php echo $user['pending_adjustment'] > 0 ? 'Total Credit' : 'Total Outstanding'; ?></div>
                            <div style="color: var(--text-gray); font-size: 12px; font-weight: 500; margin-top: 2px;"><?php echo $user['pending_adjustment'] == 0 ? 'All pending dues cleared' : ($user['pending_adjustment'] > 0 ? 'Credit balance available' : 'Pending dues to be paid'); ?></div>
                        </div>
                    </div>
                    <div style="font-weight: 700; font-size: 16px; color: <?php echo $user['pending_adjustment'] > 0 ? '#10B981' : '#94A3B8'; ?>;"><?php echo $user['pending_adjustment'] < 0 ? '<span style="color:#EF4444;">₹'.number_format(abs($user['pending_adjustment']), 2).'</span>' : '₹'.number_format($user['pending_adjustment'], 2); ?></div>
                </div>
            </div>
        </div>
        
        <?php if(!empty($user['about'])): ?>
        <!-- Admin Notes -->
        <div class="panel">
            <h4 style="font-size: 14px; color: var(--text-dark); margin-bottom: 16px; font-weight: 700; display: flex; align-items: center; gap: 8px;"><div style="width: 32px; height: 32px; background: rgba(98,75,255,0.1); color: var(--primary-purple); border-radius: 8px; display: flex; align-items: center; justify-content: center;"><i class='bx bx-note'></i></div> Admin Notes</h4>
            <p style="font-size: 14px; line-height: 1.6; color: var(--text-dark); margin: 0;"><?php echo nl2br(htmlspecialchars($user['about'])); ?></p>
        </div>
        <?php endif; ?>
    </div>

    <!-- 3. History Section (2 columns) -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;" class="animate-up">
        <!-- Utility History -->
        <div class="panel">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h4 style="font-size: 14px; color: var(--text-dark); margin: 0; font-weight: 700;">Utility History</h4>
                <span style="background: rgba(98, 75, 255, 0.1); color: var(--primary-purple); padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;"><?php echo count($elecs); ?> Records</span>
            </div>
            
            <div class="table-responsive">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th style="padding: 12px 8px; text-align: left; font-size: 11px; color: var(--text-gray); text-transform: uppercase;">Month</th>
                            <th style="padding: 12px 8px; text-align: left; font-size: 11px; color: var(--text-gray); text-transform: uppercase;">Units</th>
                            <th style="padding: 12px 8px; text-align: left; font-size: 11px; color: var(--text-gray); text-transform: uppercase;">Amount</th>
                            <th style="padding: 12px 8px; text-align: left; font-size: 11px; color: var(--text-gray); text-transform: uppercase;">Status</th>
                            <th style="padding: 12px 8px; text-align: left; font-size: 11px; color: var(--text-gray); text-transform: uppercase;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($elecs)): ?>
                            <tr><td colspan="5" style="text-align: center; padding: 20px; color: var(--text-gray); font-size: 13px;">No utility records.</td></tr>
                        <?php else: ?>
                            <?php $shown_elecs = array_slice($elecs, 0, 3); foreach ($shown_elecs as $e): ?>
                            <tr style="border-bottom: 1px solid var(--border);">
                                <td style="padding: 12px 8px; font-weight: 600; font-size: 12px; color: var(--text-dark);"><?php echo htmlspecialchars($e['month']); ?></td>
                                <td style="padding: 12px 8px; font-size: 12px; color: var(--text-gray);"><?php echo htmlspecialchars($e['units_consumed'] ?? ($e['current_reading'] - $e['previous_reading'])); ?> Units</td>
                                <td style="padding: 12px 8px; font-weight: 700; font-size: 12px; color: var(--text-dark);">₹<?php echo number_format($e['total_amount'], 2); ?></td>
                                <td style="padding: 12px 8px;"><span style="font-size: 10px; font-weight: 600; padding: 4px 8px; border-radius: 4px; <?php echo $e['status'] == 'Paid' ? 'color: #10B981; background: rgba(16,185,129,0.1);' : ($e['status'] == 'Partial' ? 'color: #F59E0B; background: rgba(245,158,11,0.1);' : 'color: #EF4444; background: rgba(239,68,68,0.1);'); ?>"><?php echo $e['status']; ?></span></td>
                                <td style="padding: 12px 8px;">
                                    <?php if($e['status'] != 'Paid'): $remaining = max(0, $e['total_amount'] - $e['total_paid']); ?>
                                        <button onclick="openPaymentModal('electricity', <?php echo $e['id']; ?>, <?php echo $remaining; ?>, '<?php echo addslashes($e['month']); ?>')" style="background: var(--primary-purple); color: #FFF; border: none; padding: 4px 12px; border-radius: 6px; font-size: 11px; font-weight: 600; cursor: pointer;">Pay</button>
                                    <?php else: ?>
                                        <a href="slip.php?elec_id=<?php echo $e['id']; ?>" target="_blank" style="color: var(--text-gray); text-decoration: none; border: 1px solid var(--border); padding: 4px 12px; border-radius: 6px; font-size: 11px; font-weight: 600;">Slip</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div style="margin-top: 16px; padding-top: 16px;">
                <a href="electricity-list.php?search=<?php echo urlencode($user['name']); ?>" style="color: var(--primary-purple); font-size: 12px; font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 4px;">View All Utility History <i class='bx bx-right-arrow-alt'></i></a>
            </div>
        </div>

        <!-- Rent History -->
        <div class="panel">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h4 style="font-size: 14px; color: var(--text-dark); margin: 0; font-weight: 700;">Rent History</h4>
                <span style="background: rgba(98, 75, 255, 0.1); color: var(--primary-purple); padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;"><?php echo count($rents); ?> Records</span>
            </div>
            
            <div class="table-responsive">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th style="padding: 12px 8px; text-align: left; font-size: 11px; color: var(--text-gray); text-transform: uppercase;">Month</th>
                            <th style="padding: 12px 8px; text-align: left; font-size: 11px; color: var(--text-gray); text-transform: uppercase;">Details</th>
                            <th style="padding: 12px 8px; text-align: left; font-size: 11px; color: var(--text-gray); text-transform: uppercase;">Total</th>
                            <th style="padding: 12px 8px; text-align: left; font-size: 11px; color: var(--text-gray); text-transform: uppercase;">Status</th>
                            <th style="padding: 12px 8px; text-align: left; font-size: 11px; color: var(--text-gray); text-transform: uppercase;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rents)): ?>
                            <tr><td colspan="5" style="text-align: center; padding: 20px; color: var(--text-gray); font-size: 13px;">No rent records.</td></tr>
                        <?php else: ?>
                            <?php $shown_rents = array_slice($rents, 0, 3); foreach ($shown_rents as $r): ?>
                            <tr style="border-bottom: 1px solid var(--border);">
                                <td style="padding: 12px 8px; font-weight: 600; font-size: 12px; color: var(--text-dark);"><?php echo htmlspecialchars($r['month']); ?></td>
                                <td style="padding: 12px 8px; font-size: 10px; color: var(--text-gray);">Rent: ₹<?php echo number_format($r['rent_amount']); ?><br>Maint: ₹<?php echo number_format($r['maintenance']); ?></td>
                                <td style="padding: 12px 8px; font-weight: 700; font-size: 12px; color: var(--text-dark);">₹<?php echo number_format($r['rent_amount'] + $r['maintenance'], 2); ?></td>
                                <td style="padding: 12px 8px;"><span style="font-size: 10px; font-weight: 600; padding: 4px 8px; border-radius: 4px; <?php echo $r['status'] == 'Paid' ? 'color: #10B981; background: rgba(16,185,129,0.1);' : ($r['status'] == 'Partial' ? 'color: #F59E0B; background: rgba(245,158,11,0.1);' : 'color: #EF4444; background: rgba(239,68,68,0.1);'); ?>"><?php echo $r['status']; ?></span></td>
                                <td style="padding: 12px 8px;">
                                    <?php if($r['status'] != 'Paid'): $remaining = max(0, ($r['rent_amount'] + $r['maintenance']) - $r['total_paid']); ?>
                                        <button onclick="openPaymentModal('electricity', <?php echo $r['id']; ?>, <?php echo $remaining; ?>, '<?php echo addslashes($r['month']); ?>')" style="background: var(--primary-purple); color: #FFF; border: none; padding: 4px 12px; border-radius: 6px; font-size: 11px; font-weight: 600; cursor: pointer;">Pay</button>
                                    <?php else: ?>
                                        <span style="color: var(--text-gray); padding: 4px 12px; font-size: 11px; font-weight: 600;">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div style="margin-top: 16px; padding-top: 16px;">
                <a href="electricity-list.php?search=<?php echo urlencode($user['name']); ?>" style="color: var(--primary-purple); font-size: 12px; font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 4px;">View All Rent History <i class='bx bx-right-arrow-alt'></i></a>
            </div>
        </div>
    </div>

    <!-- 4. Documents Section -->
    <div class="panel animate-up" style="margin-bottom: 24px;">
        <h4 style="font-size: 14px; color: var(--text-dark); margin-bottom: 20px; font-weight: 700; display: flex; align-items: center; gap: 8px;"><div style="width: 32px; height: 32px; background: rgba(98,75,255,0.1); color: var(--primary-purple); border-radius: 8px; display: flex; align-items: center; justify-content: center;"><i class='bx bx-file'></i></div> Documents</h4>
        
        <div style="display: flex; gap: 24px; flex-wrap: wrap;">
            <?php if (!empty($user['aadhaar_file'])): ?>
            <div style="flex: 1; min-width: 280px; display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; background: #F8FAFC; border: 1px solid var(--border); border-radius: 12px; transition: all 0.2s ease;">
                <div style="display: flex; align-items: center; gap: 16px;">
                    <div style="width: 48px; height: 48px; background: rgba(59, 130, 246, 0.1); color: #3B82F6; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px;">
                        <i class='bx bx-id-card'></i>
                    </div>
                    <div>
                        <div style="font-weight: 600; color: var(--text-dark); font-size: 14px; margin-bottom: 4px;">Aadhaar Card</div>
                        <div style="font-size: 12px; color: var(--text-gray);">Identity Proof</div>
                    </div>
                </div>
                <div style="display: flex; gap: 8px;">
                    <a href="download.php?type=aadhaar&user_id=<?php echo (int)$user['id']; ?>" target="_blank" style="width: 36px; height: 36px; border-radius: 8px; background: #FFFFFF; border: 1px solid var(--border); color: var(--text-dark); display: flex; align-items: center; justify-content: center; text-decoration: none; transition: all 0.2s ease;"><i class='bx bx-show'></i></a>
                    <a href="delete-doc.php?type=aadhaar&user_id=<?php echo (int)$user['id']; ?>" onclick="return confirm('Delete this Aadhaar document?');" style="width: 36px; height: 36px; border-radius: 8px; background: #FFFFFF; border: 1px solid var(--border); color: #EF4444; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: all 0.2s ease;"><i class='bx bx-trash'></i></a>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($user['agreement_document'])): ?>
            <div style="flex: 1; min-width: 280px; display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; background: #F8FAFC; border: 1px solid var(--border); border-radius: 12px; transition: all 0.2s ease;">
                <div style="display: flex; align-items: center; gap: 16px;">
                    <div style="width: 48px; height: 48px; background: rgba(16, 185, 129, 0.1); color: #10B981; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px;">
                        <i class='bx bx-file-blank'></i>
                    </div>
                    <div>
                        <div style="font-weight: 600; color: var(--text-dark); font-size: 14px; margin-bottom: 4px;">Rental Agreement</div>
                        <div style="font-size: 12px; color: var(--text-gray);">Signed Document</div>
                    </div>
                </div>
                <div style="display: flex; gap: 8px;">
                    <a href="download-agreement.php?id=<?php echo (int)$user['id']; ?>" target="_blank" style="width: 36px; height: 36px; border-radius: 8px; background: #FFFFFF; border: 1px solid var(--border); color: var(--text-dark); display: flex; align-items: center; justify-content: center; text-decoration: none; transition: all 0.2s ease;"><i class='bx bx-show'></i></a>
                    <a href="delete-doc.php?type=agreement&user_id=<?php echo (int)$user['id']; ?>" onclick="return confirm('Delete this Agreement?');" style="width: 36px; height: 36px; border-radius: 8px; background: #FFFFFF; border: 1px solid var(--border); color: #EF4444; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: all 0.2s ease;"><i class='bx bx-trash'></i></a>
                </div>
            </div>
            <?php endif; ?>

            <?php if (empty($user['aadhaar_file']) && empty($user['agreement_document'])): ?>
            <div style="width: 100%; padding: 32px 16px; text-align: center; border: 1px dashed var(--border); border-radius: 12px; color: var(--text-gray); font-size: 13px; background: #F8FAFC;">
                <i class='bx bx-folder-open' style="font-size: 32px; margin-bottom: 12px; color: #CBD5E1;"></i><br>
                No documents uploaded yet
            </div>
            <?php endif; ?>
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
                    <select name="payment_mode" id="paymentMode" required>
                        <option value="Online">Online</option>
                        <option value="Cash">Cash</option>
                        <option value="UPI">UPI</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                    </select>
                </div>

                <div style="border-top: 1px dashed var(--border); padding-top: 20px; margin-top: 20px;">
                    <div class="form-group">
                        <label>Amount Paid (₹)</label>
                        <input type="number" step="0.01" name="paid_amount" id="paidAmountInput" placeholder="Enter amount" required>
                        <small style="color: var(--text-gray); font-size: 11px;">You can enter a partial payment amount.</small>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="form-group">
                            <label>Date</label>
                            <input type="date" name="payment_date" id="paymentDateInput" required>
                        </div>
                        <div class="form-group">
                            <label>Time</label>
                            <input type="time" name="payment_time" id="paymentTimeInput" required>
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

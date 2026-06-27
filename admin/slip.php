<?php
// admin/slip.php - Premium Printable electricity bill slip
require_once "../db.php";
session_start();

$elec_id = isset($_GET['elec_id']) ? (int)$_GET['elec_id'] : 0;
if ($elec_id <= 0) {
    echo "Invalid ID";
    exit;
}

if (!isset($_SESSION['admin']) && !isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Fetch electricity record with user details
$stmt = mysqli_prepare($conn, "SELECT e.*, u.name, u.room_no FROM electricity e LEFT JOIN users u ON e.user_id = u.id WHERE e.id = ?");
mysqli_stmt_bind_param($stmt, "i", $elec_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$row) { 
    echo "Record not found"; 
    exit; 
}

// Security: If renter is logged in (and not admin), they can only see their own bill
if (!isset($_SESSION['admin']) && isset($_SESSION['user_id'])) {
    if ((int)$row['user_id'] !== (int)$_SESSION['user_id']) {
        echo "Access Denied: You cannot view this bill.";
        exit;
    }
}

// Prepare variables
$date = date("F d, Y", strtotime($row['payment_date'] ?? $row['created_at'] ?? date('Y-m-d')));
$name = $row['name'] ?: 'N/A';
$room = $row['room_no'] ?: 'N/A';
$month_period = $row['month'] ?? 'N/A';

// Readings and calculations
$current_reading = (int)($row['current_reading'] ?? 0);
$previous_reading = (int)($row['previous_reading'] ?? 0);
$units_consumed = $current_reading - $previous_reading;
$rate_per_unit = (float)($row['rate_per_unit'] ?? DEFAULT_RATE);
$electricity_amount = $units_consumed * $rate_per_unit;

// Rent details
$rent_amount = (float)($row['rent_amount'] ?? 0);
$maintenance = (float)($row['maintenance'] ?? 0);
$dues = (float)($row['dues'] ?? 0);
$extra_charges = (float)($row['extra_charges'] ?? 0);
$extra_charges_desc = $row['extra_charges_desc'] ?? '';

$total_amount = $electricity_amount + $rent_amount + $maintenance + $dues + $extra_charges;

$admin_user = htmlspecialchars($_SESSION['admin'] ?? 'Admin', ENT_QUOTES, 'UTF-8');

// Back URL for fallback
$back_url = "dashboard.php";
if (!isset($_SESSION['admin']) && isset($_SESSION['user_id'])) {
    $back_url = "../renter/dashboard.php";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bill Slip | <?php echo htmlspecialchars($name); ?></title>
    
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary: #624BFF;
            --text-dark: #1A1A1A;
            --text-gray: #71717A;
            --border: #F1F1F4;
            --bg-light: #FBFBFF;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f4f4f9; color: var(--text-dark); -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }

        .invoice-wrapper {
            max-width: 950px;
            margin: 40px auto;
            background: white;
            padding: 50px 60px;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.08);
            position: relative;
            overflow: hidden;
        }

        /* Branding element */
        .invoice-wrapper::before {
            content: '';
            position: absolute;
            top: 0; right: 0;
            width: 150px; height: 150px;
            background: radial-gradient(circle at top right, rgba(98, 75, 255, 0.05), transparent);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .brand-info h2 { 
            font-size: 24px; font-weight: 800; color: var(--primary);
            display: flex; align-items: center; gap: 10px; margin-bottom: 5px;
        }
        .brand-info p { font-size: 14px; color: var(--text-gray); }

        .bill-title { text-align: right; }
        .bill-title h1 { font-size: 32px; font-weight: 800; margin-bottom: 5px; letter-spacing: -0.5px; }
        .bill-title span { background: #F0EDFF; color: var(--primary); padding: 5px 12px; border-radius: 8px; font-weight: 700; font-size: 12px; }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1.5px solid var(--border);
        }

        .info-group h4 { font-size: 10px; color: var(--text-gray); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px; }
        .info-group p { font-size: 14px; font-weight: 700; }

        .table-container { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 10px 0; border-bottom: 2px solid var(--text-dark); font-size: 11px; color: var(--text-gray); text-transform: uppercase; letter-spacing: 0.5px; }
        td { padding: 12px 0; border-bottom: 1px solid var(--border); font-size: 13px; vertical-align: middle; }

        .reading-box { display: flex; align-items: center; gap: 15px; }
        .reading-tag { background: var(--bg-light); border: 1px solid var(--border); padding: 6px 12px; border-radius: 10px; font-size: 13px; font-weight: 600; }

        .total-section {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 20px;
        }
        .total-box {
            width: 100%;
            max-width: 360px;
            background: #F8F7FF;
            padding: 20px;
            border-radius: 20px;
        }
        .total-row { display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 14px; }
        .total-row.grand-total { 
            margin-top: 20px; 
            padding-top: 20px; 
            border-top: 2px dashed rgba(98, 75, 255, 0.2); 
            margin-bottom: 0;
            align-items: center;
        }
        .total-row.grand-total span:first-child { 
            font-size: 16px; 
            font-weight: 700; 
            color: var(--text-dark); 
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .total-row.grand-total span:last-child { 
            font-size: 26px; 
            font-weight: 800; 
            color: var(--primary); 
        }

        .footer {
            margin-top: 20px;
            text-align: center;
            border-top: 1.5px solid var(--border);
            padding-top: 15px;
        }
        .footer p { color: var(--text-gray); font-size: 12px; line-height: 1.4; }

        .actions {
            max-width: 800px;
            margin: 20px auto;
            display: flex;
            justify-content: center;
            gap: 15px;
        }

        @media print {
            body { background: white; padding: 0; }
            .invoice-wrapper { 
                margin: 0; 
                box-shadow: none; 
                width: 100%; 
                max-width: 100%; 
                padding: 30px !important;
                min-height: 27cm; /* Ensures it takes up full A4 height */
                display: flex;
                flex-direction: column;
                border: none !important;
            }
            .actions { display: none; }
            .total-box { background: #F8F7FF !important; -webkit-print-color-adjust: exact; }
            .footer { 
                margin-top: auto !important; 
                padding-top: 20px;
                padding-bottom: 10px;
            }
            @page { 
                size: auto;
                margin: 0.5cm; 
            }
            .table-container td { padding: 8px 0 !important; }
            .header { margin-bottom: 15px !important; }
            .info-grid { margin-bottom: 15px !important; }
            .total-section { margin-bottom: 10px !important; }
            .meter-ss-container { max-width: 220px !important; }
        }

        .btn {
            background: var(--primary); color: white; border: none; padding: 12px 30px;
            border-radius: 14px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 10px;
            transition: 0.2s; text-decoration: none;
        }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(98, 75, 255, 0.2); }
        .btn-outline { background: white; border: 1.5px solid var(--border); color: var(--text-dark); }

        @media (max-width: 768px) {
            .actions { margin: 10px; gap: 8px; flex-wrap: nowrap; }
            .btn { padding: 10px 12px; font-size: 12px; border-radius: 10px; flex: 1; justify-content: center; white-space: nowrap; }
            .btn-outline i { display: none; } /* Hide arrow on mobile to save space */
            
            .invoice-wrapper { padding: 24px 16px; margin: 0; border-radius: 0; }
            .header { flex-direction: column; gap: 20px; margin-bottom: 24px; text-align: center; align-items: center; }
            .bill-title { text-align: center; }
            .brand-info h2 { font-size: 20px; justify-content: center; }
            .brand-info p { font-size: 13px; max-width: 250px; margin: 0 auto; }
            
            .bill-title h1 { font-size: 24px; }
            .info-grid { grid-template-columns: 1fr; gap: 15px; margin-bottom: 24px; text-align: center; }
            
            .table-container th { font-size: 10px; padding: 8px 0; }
            .table-container td { font-size: 12px; padding: 12px 0; }
            
            .reading-box { justify-content: center; gap: 8px; }
            .reading-tag { padding: 4px 8px; font-size: 12px; }
            
            .total-box { padding: 16px; border-radius: 16px; }
            .total-row.grand-total span:first-child { font-size: 14px; }
            .total-row.grand-total span:last-child { font-size: 20px; }
        }
    </style>
</head>
<body>

    <div class="actions">
        <button type="button" onclick="window.print()" class="btn">
            <i class='bx bx-printer'></i> Print Slip
        </button>
        <button type="button" onclick="if(window.opener) { window.close(); } else if (window.history.length > 1 || document.referrer) { window.history.back(); } else { window.location.href='<?php echo $back_url; ?>'; }" class="btn btn-outline">
            <i class='bx bx-arrow-back'></i> Close & Back
        </button>
    </div>

    <div class="invoice-wrapper">
        <div class="header">
            <div class="brand-info">
                <h2><i class='bx bxs-building-house'></i> <?php echo defined('HOUSE_NAME') ? HOUSE_NAME : 'Premium Property'; ?></h2>
                <p>Official Billing Receipt</p>
            </div>
            <div class="bill-title">
                <h1>BILL INVOICE</h1>
                <span><?php echo htmlspecialchars($month_period); ?></span>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-group">
                <h4>Billing Date</h4>
                <p><?php echo $date; ?></p>
            </div>
            <div class="info-group">
                <h4>Bill To</h4>
                <p style="font-size: 18px; color: var(--text-dark); margin-bottom: 2px;"><?php echo htmlspecialchars($name); ?></p>
                <p style="font-size: 14px; color: var(--primary); font-weight: 600;">Room: <?php echo htmlspecialchars($room); ?></p>
            </div>
            <div class="info-group">
                <h4>Invoice No.</h4>
                <p>#RM-<?php echo str_pad($elec_id, 5, '0', STR_PAD_LEFT); ?></p>
            </div>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th style="width: 200px;">Readings</th>
                        <th style="text-align: right;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <div style="font-weight: 700;">Electricity Consumption</div>
                            <div style="font-size: 13px; color: var(--text-gray); margin-top: 4px;">Usage for <?php echo $month_period; ?></div>
                        </td>
                        <td>
                            <div class="reading-box">
                                <div class="reading-tag"><?php echo $previous_reading; ?></div>
                                <i class='bx bx-right-arrow-alt' style="color: var(--text-gray);"></i>
                                <div class="reading-tag"><?php echo $current_reading; ?></div>
                            </div>
                            <div style="font-size: 12px; margin-top: 8px; color: var(--primary); font-weight: 600;">Net Usage: <?php echo $units_consumed; ?> Units</div>
                        </td>
                        <td style="text-align: right; font-weight: 700;">₹<?php echo number_format($electricity_amount, 2); ?></td>
                    </tr>
                    <?php if ($rent_amount > 0): ?>
                    <tr>
                        <td>
                            <div style="font-weight: 700;">Monthly Standard Rent</div>
                            <div style="font-size: 13px; color: var(--text-gray); margin-top: 4px;">Fixed base rent for room</div>
                        </td>
                        <td>—</td>
                        <td style="text-align: right; font-weight: 700;">₹<?php echo number_format($rent_amount, 2); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($maintenance > 0): ?>
                    <tr>
                        <td>
                            <div style="font-weight: 700;">Maintenance Charges</div>
                        </td>
                        <td>—</td>
                        <td style="text-align: right; font-weight: 700;">₹<?php echo number_format($maintenance, 2); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($extra_charges > 0): ?>
                    <tr>
                        <td>
                            <div style="font-weight: 700;">Additional Charges</div>
                            <div style="font-size: 13px; color: var(--text-gray); margin-top: 4px;"><?php echo htmlspecialchars($extra_charges_desc ? $extra_charges_desc : "Other one-off charges"); ?></div>
                        </td>
                        <td>—</td>
                        <td style="text-align: right; font-weight: 700;">₹<?php echo number_format($extra_charges, 2); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($dues != 0): ?>
                    <tr>
                        <td>
                            <div style="font-weight: 700; color: <?php echo $dues > 0 ? '#EF4444' : '#10B981'; ?>;">
                                <?php echo $dues > 0 ? 'Arrears / Remaining' : 'Adjustment (Extra Paid)'; ?>
                            </div>
                            <div style="font-size: 13px; color: var(--text-gray); margin-top: 4px;">Carried forward from previous month</div>
                        </td>
                        <td>—</td>
                        <td style="text-align: right; font-weight: 700; color: <?php echo $dues > 0 ? '#EF4444' : '#10B981'; ?>;">
                            ₹<?php echo ($dues > 0 ? '+' : '-') . number_format(abs($dues), 2); ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="total-section">
            <div class="total-box">
                <div class="total-row">
                    <span>Subtotal</span>
                    <span style="font-weight: 700;">₹<?php echo number_format($electricity_amount + $rent_amount + $maintenance + $extra_charges, 2); ?></span>
                </div>
                <?php if ($dues != 0): ?>
                <div class="total-row">
                    <span><?php echo $dues > 0 ? 'Arrears' : 'Extra Applied'; ?></span>
                    <span style="color: <?php echo $dues > 0 ? '#EF4444' : '#10B981'; ?>; font-weight: 600;">
                        ₹<?php echo ($dues > 0 ? '+' : '-') . number_format(abs($dues), 2); ?>
                    </span>
                </div>
                <?php endif; ?>
                <div class="total-row grand-total" style="display: flex; justify-content: space-between; align-items: center; gap: 20px;">
                    <span style="flex-shrink: 0;">Grand Total</span>
                    <span style="flex-shrink: 0;">₹<?php echo number_format($total_amount, 2); ?></span>
                </div>
            </div>
        </div>

        <?php 
        $display_img = !empty($row['meter_screenshot']) ? $row['meter_screenshot'] : ($row['meter_screenshot_orig'] ?? null);
        if (!empty($display_img)): 
        ?>
        <div style="margin-top: 15px; padding-top: 15px; border-top: 1.5px solid var(--border); page-break-inside: avoid;">
            <h4 style="font-size: 10px; color: var(--text-gray); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Meter Reading</h4>
            <div class="meter-ss-container" style="background: var(--bg-light); border: 1px solid var(--border); border-radius: 12px; overflow: hidden; max-width: 250px; margin: 0 auto;">
                <img src="../uploads/meter_readings/<?php echo htmlspecialchars($display_img); ?>" alt="Meter Reading" style="width: 100%; height: auto; display: block;">
            </div>
        </div>
        <?php endif; ?>

        <footer class="footer">
            <p><strong>Thank you for your payment!</strong><br>Generated by <?php echo SYSTEM_NAME; ?> for <?php echo HOUSE_NAME; ?>. Please keep this for your records.</p>
        </div>
    </div>

</body>
</html>

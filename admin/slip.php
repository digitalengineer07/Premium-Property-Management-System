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
$stmt = mysqli_prepare($conn, "SELECT e.*, u.name, u.room_no, u.phone, u.email FROM electricity e LEFT JOIN users u ON e.user_id = u.id WHERE e.id = ?");
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
$phone = $row['phone'] ?? '98765 43210';
$email = $row['email'] ?? 'madhavkunj@example.com';
$month_period = $row['month'] ?? 'N/A';
$invoice_no = 'ELC-' . str_pad($elec_id, 5, '0', STR_PAD_LEFT);

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

$total_amount = $electricity_amount;

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
    <title>Electricity Bill | <?php echo htmlspecialchars($name); ?></title>
    
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary: #4634FF;
            --primary-light: #F0EDFF;
            --text-dark: #1F2937;
            --text-gray: #6B7280;
            --border: #F3F4F6;
            --bg-light: #FAFAFB;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background: #e9ecef; 
            color: var(--text-dark); 
            -webkit-print-color-adjust: exact !important; 
            print-color-adjust: exact !important; 
        }

        .invoice-wrapper {
            max-width: 900px;
            margin: 40px auto;
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
        }

        /* --- HEADER --- */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 24px;
        }

        .brand-section {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .brand-icon {
            font-size: 42px;
            color: var(--primary);
        }
        .brand-text h2 {
            font-size: 26px;
            font-weight: 800;
            color: var(--primary);
            line-height: 1.1;
        }
        .brand-text p {
            font-size: 14px;
            color: var(--text-gray);
            font-weight: 500;
        }

        .title-section {
            text-align: right;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 6px;
        }
        .title-section h1 {
            font-size: 32px;
            font-weight: 800;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .title-section h1 i {
            color: var(--primary);
            font-size: 36px;
        }
        .month-pill {
            background: var(--primary);
            color: white;
            padding: 4px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 700;
        }

        /* --- CONTACT INFO --- */
        .contact-info {
            display: flex;
            justify-content: center;
            gap: 30px;
            padding-bottom: 24px;
            margin-bottom: 24px;
            border-bottom: 1px solid var(--border);
            font-size: 13px;
            color: var(--text-gray);
            font-weight: 500;
        }
        .contact-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .contact-item i {
            color: var(--primary);
            font-size: 16px;
        }

        /* --- META CARDS --- */
        .meta-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        .meta-card {
            background: var(--bg-light);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .meta-icon {
            width: 48px;
            height: 48px;
            background: var(--primary-light);
            color: var(--primary);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            flex-shrink: 0;
        }
        .meta-info h4 {
            font-size: 11px;
            color: var(--text-gray);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .meta-info p {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-dark);
            line-height: 1.2;
        }
        .meta-info .sub-p {
            font-size: 13px;
            color: var(--primary);
            font-weight: 600;
            margin-top: 2px;
        }

        /* --- METER READING SECTION --- */
        .section-card {
            border: 1px solid var(--border);
            border-radius: 16px;
            margin-bottom: 24px;
            overflow: hidden;
            background: white;
        }
        .section-header {
            padding: 12px 20px;
            display: flex;
            align-items: center;
            gap: 8px;
            border-bottom: 1px solid var(--border);
        }
        .section-header i {
            color: var(--primary);
            font-size: 20px;
        }
        .section-header h3 {
            font-size: 14px;
            font-weight: 700;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .meter-flow {
            padding: 30px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border);
        }
        .flow-item {
            text-align: center;
            flex: 1;
        }
        .flow-item h4 {
            font-size: 12px;
            color: var(--text-gray);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
        }
        .flow-item .val {
            font-size: 28px;
            font-weight: 800;
            color: var(--text-dark);
        }
        .flow-item .unit {
            font-size: 14px;
            color: var(--primary);
            font-weight: 700;
            margin-top: 4px;
        }
        .flow-arrow {
            width: 40px;
            height: 40px;
            background: var(--primary-light);
            color: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
            font-weight: 800;
        }
        
        .flow-item.highlight {
            background: var(--primary-light);
            padding: 20px;
            border-radius: 12px;
            flex: 1.5;
        }
        .flow-item.highlight h4 { color: var(--primary); }

        .meter-meta {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            padding: 20px;
            gap: 20px;
        }
        .meter-meta-item {
            display: flex;
            align-items: center;
            gap: 12px;
            border-right: 1px solid var(--border);
        }
        .meter-meta-item:last-child {
            border-right: none;
        }
        .meter-meta-item i {
            width: 36px;
            height: 36px;
            background: var(--primary-light);
            color: var(--primary);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        .meta-sm h5 {
            font-size: 10px;
            color: var(--text-gray);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }
        .meta-sm p {
            font-size: 14px;
            font-weight: 700;
            color: var(--text-dark);
        }

        /* --- PHOTO SECTION --- */
        .photo-body {
            padding: 20px;
            display: flex;
            gap: 30px;
            align-items: center;
        }
        .photo-img {
            width: 300px;
            height: 140px;
            border-radius: 12px;
            object-fit: cover;
            border: 1px solid var(--border);
        }
        .photo-details {
            display: flex;
            flex-direction: column;
            gap: 16px;
            flex: 1;
        }
        .photo-det-row {
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }
        .photo-det-row i {
            color: var(--text-gray);
            font-size: 18px;
            margin-top: 2px;
        }
        .photo-det-info h5 {
            font-size: 12px;
            color: var(--text-gray);
            font-weight: 500;
            margin-bottom: 2px;
        }
        .photo-det-info p {
            font-size: 14px;
            color: var(--text-dark);
            font-weight: 600;
        }
        .photo-det-info .note {
            color: var(--primary);
            font-weight: 500;
        }

        /* --- CHARGES BREAKDOWN --- */
        .charges-table {
            width: 100%;
            border-collapse: collapse;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid var(--border);
            margin-bottom: 20px;
        }
        .charges-table thead {
            background: var(--primary);
            color: white;
        }
        .charges-table th {
            padding: 16px 24px;
            text-align: left;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .charges-table th:last-child {
            text-align: right;
        }
        .charges-table td {
            padding: 16px 24px;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-dark);
            border-bottom: 1px solid var(--border);
        }
        .charges-table td:last-child {
            text-align: right;
        }
        .charges-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* --- TOTAL AMOUNT --- */
        .total-box {
            background: var(--bg-light);
            border-radius: 16px;
            padding: 24px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid var(--primary-light);
        }
        .total-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .total-icon {
            width: 48px;
            height: 48px;
            background: var(--primary-light);
            color: var(--primary);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        .total-left h3 {
            font-size: 15px;
            font-weight: 800;
            color: var(--text-dark);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .total-left p {
            font-size: 13px;
            color: var(--text-gray);
            margin-top: 4px;
        }
        .total-amount {
            font-size: 36px;
            font-weight: 800;
            color: var(--primary);
        }

        /* --- FOOTER --- */
        .footer {
            margin-top: 40px;
            text-align: center;
        }
        .thank-you {
            font-family: 'Brush Script MT', 'Dancing Script', cursive, sans-serif;
            font-size: 28px;
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 10px;
        }
        .thank-you::before, .thank-you::after {
            content: '';
            height: 1px;
            width: 40px;
            background: var(--primary);
        }
        .footer-query {
            font-size: 15px;
            color: var(--text-gray);
            font-weight: 500;
        }

        /* PRINT STYLES */
        .actions {
            max-width: 900px;
            margin: 20px auto;
            display: flex;
            justify-content: flex-end;
            gap: 15px;
        }
        .btn {
            background: var(--primary); color: white; border: none; padding: 12px 24px;
            border-radius: 12px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px;
            text-decoration: none; font-size: 14px; transition: all 0.2s ease;
        }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(70, 52, 255, 0.2); }
        .btn:active { transform: scale(0.96); box-shadow: none; }
        
        .btn-outline {
            background: white; border: 1px solid var(--border); color: var(--text-dark);
        }
        .btn-outline:hover { background: #f8f9fa; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .btn-outline:active { background: #e9ecef; transform: scale(0.96); box-shadow: none; }

        @media print {
            body { background: white; margin: 0; }
            @page { margin: 0.5cm; } /* Reduce default browser margins */
            .invoice-wrapper {
                margin: 0; padding: 0; box-shadow: none; max-width: 100%;
            }
            /* Compress spacing to fit one page */
            .header { margin-bottom: 12px; }
            .contact-info { margin-bottom: 12px; padding-bottom: 12px; font-size: 10px; gap: 12px; }
            .contact-item { white-space: nowrap; }
            .meta-grid { margin-bottom: 16px; gap: 12px; }
            .meta-card { padding: 12px 16px; }
            .section-card { margin-bottom: 16px; }
            .meter-flow { padding: 16px 20px; }
            .meter-meta { padding: 12px 20px; gap: 12px; }
            .photo-body { padding: 12px 20px; gap: 20px; }
            .photo-img { height: 110px; width: 240px; }
            .charges-table { margin-bottom: 16px; }
            .charges-table th, .charges-table td { padding: 10px 16px; }
            .total-box { padding: 16px 24px; }
            .footer { margin-top: 20px; }
            
            .actions { display: none; }
            .meta-card, .flow-item.highlight, .meter-meta-item i, .total-box, .total-icon {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .charges-table thead {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
        @media screen and (max-width: 768px) {
            body {
                padding: 10px;
            }
            .slip-container {
                padding: 20px 15px;
                border-radius: 12px;
            }
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
                background: rgba(98, 75, 255, 0.04);
                padding: 20px;
                border-radius: 16px;
                border: 1px solid rgba(98, 75, 255, 0.1);
            }
            .brand-section {
                flex-direction: row;
                align-items: center;
                gap: 12px;
            }
            .brand-icon {
                font-size: 28px;
                background: white;
                width: 48px;
                height: 48px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 12px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            }
            .title-section {
                text-align: left;
                align-items: flex-start;
                width: 100%;
                border-top: 1px dashed rgba(98, 75, 255, 0.2);
                padding-top: 16px;
                margin-top: 4px;
            }
            .title-section h1 {
                font-size: 20px;
                flex-wrap: wrap;
                gap: 6px;
            }
            .contact-info {
                flex-direction: column;
                align-items: flex-start;
                text-align: left;
                background: var(--bg-light);
                padding: 16px;
                border-radius: 16px;
                border: 1px solid var(--border);
                gap: 12px;
                margin-bottom: 24px;
            }
            .contact-item {
                flex-direction: row;
                align-items: flex-start;
                gap: 10px;
                font-size: 12px;
                line-height: 1.4;
            }
            .contact-item i {
                font-size: 16px;
                margin-top: 1px;
                color: var(--primary);
                background: white;
                padding: 6px;
                border-radius: 8px;
                border: 1px solid var(--border);
            }
            .meta-grid {
                grid-template-columns: 1fr;
            }
            .photo-details-grid {
                grid-template-columns: 1fr;
            }
            .charges-table th, .charges-table td {
                padding: 12px 10px;
                font-size: 12px;
            }
            .charges-table th:first-child, .charges-table td:first-child {
                padding-left: 10px;
            }
            .charges-table th:last-child, .charges-table td:last-child {
                padding-right: 10px;
            }
            .footer {
                flex-direction: column;
                gap: 24px;
                text-align: center;
                align-items: center;
            }
            .thank-you {
                font-size: 18px;
                gap: 6px;
                white-space: nowrap;
            }
            .thank-you::before, .thank-you::after {
                width: 20px;
            }
            .footer-signature {
                text-align: center;
                align-items: center;
            }
            .footer-signature::before {
                margin: 0 auto 12px auto;
            }
            .brand-text h2 {
                font-size: 22px;
            }
            .title-section h1 {
                font-size: 26px;
            }
            /* Action buttons at the top should stack */
            .actions {
                flex-direction: row;
                justify-content: center;
                align-items: center;
                gap: 12px;
                width: 100%;
                margin: 10px auto 20px auto;
                padding: 0 15px;
                box-sizing: border-box;
            }
            .actions .btn {
                width: auto;
                justify-content: center;
                padding: 10px 20px;
                font-size: 13px;
            }
            /* Structure Adjustments */
            .meter-flow {
                flex-direction: column;
                gap: 16px;
            }
            .flow-arrow {
                transform: rotate(90deg);
                margin: 0 auto;
            }
            .meter-meta {
                grid-template-columns: repeat(2, 1fr);
                gap: 16px 12px;
            }
            .meter-meta-item {
                border-right: none;
                border-bottom: none;
                padding-bottom: 0;
            }
            .meter-meta-item:last-child {
                border-bottom: none;
                padding-bottom: 0;
            }
            .photo-body {
                flex-direction: column;
                align-items: stretch;
            }
            .photo-img {
                width: 100% !important;
                height: auto !important;
            }
            .total-box {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
            }
        }
    </style>
</head>
<body>

    <div class="actions">
        <button type="button" onclick="window.print()" class="btn">
            <i class='bx bx-printer'></i> Print Bill
        </button>
        <button type="button" onclick="if(document.referrer) { window.history.back(); } else { window.location.href='<?php echo $back_url; ?>'; }" class="btn btn-outline">
            <i class='bx bx-arrow-back'></i> Close
        </button>
    </div>

    <div class="invoice-wrapper">
        
        <!-- Header -->
        <div class="header">
            <div class="brand-section">
                <i class='bx bxs-building-house brand-icon'></i>
                <div class="brand-text">
                    <h2><?php echo defined('HOUSE_NAME') ? HOUSE_NAME : 'Madhav kunj'; ?></h2>
                    <p>Electricity Bill</p>
                </div>
            </div>
            <div class="title-section">
                <h1><i class='bx bxs-zap'></i> ELECTRICITY BILL</h1>
                <div class="month-pill"><?php echo htmlspecialchars($month_period); ?></div>
            </div>
        </div>

        <!-- Contact Info -->
        <div class="contact-info">
            <div class="contact-item">
                <i class='bx bx-map'></i>
                Vastu Estate colony Madhav Kunj apartment behind RPS School
            </div>
            <div class="contact-item" style="white-space: nowrap;">
                <i class='bx bx-phone-call'></i>
                <?php echo (strpos(trim($phone), '+91') === 0) ? htmlspecialchars(trim($phone)) : '+91&nbsp;' . htmlspecialchars(trim($phone)); ?>
            </div>
            <div class="contact-item" style="white-space: nowrap;">
                <i class='bx bx-envelope'></i>
                <?php echo htmlspecialchars(trim($email)); ?>
            </div>
        </div>

        <!-- Meta Cards -->
        <div class="meta-grid">
            <div class="meta-card">
                <div class="meta-icon"><i class='bx bx-calendar'></i></div>
                <div class="meta-info">
                    <h4>Billing Date</h4>
                    <p><?php echo date("F d, Y", strtotime($date)); ?></p>
                </div>
            </div>
            <div class="meta-card">
                <div class="meta-icon"><i class='bx bx-user'></i></div>
                <div class="meta-info">
                    <h4>Billed To</h4>
                    <p><?php echo htmlspecialchars($name); ?></p>
                    <p class="sub-p">Room: <?php echo htmlspecialchars($room); ?></p>
                </div>
            </div>
            <div class="meta-card">
                <div class="meta-icon"><i class='bx bx-receipt'></i></div>
                <div class="meta-info">
                    <h4>Invoice No.</h4>
                    <p><?php echo $invoice_no; ?></p>
                </div>
            </div>
        </div>

        <!-- Meter Details -->
        <div class="section-card">
            <div class="section-header">
                <i class='bx bx-tachometer'></i>
                <h3>Meter Reading Details</h3>
            </div>
            <div class="meter-flow">
                <div class="flow-item">
                    <h4>Current Reading</h4>
                    <div class="val"><?php echo $current_reading; ?></div>
                    <div class="unit">Units</div>
                </div>
                <div class="flow-arrow"><i class='bx bx-minus'></i></div>
                <div class="flow-item">
                    <h4>Previous Reading</h4>
                    <div class="val"><?php echo $previous_reading; ?></div>
                    <div class="unit">Units</div>
                </div>
                <div class="flow-arrow">=</div>
                <div class="flow-item highlight">
                    <h4>Units Consumed</h4>
                    <div class="val"><?php echo $units_consumed; ?></div>
                    <div class="unit">Units</div>
                </div>
            </div>
            <div class="meter-meta">
                <div class="meter-meta-item">
                    <i class='bx bx-money'></i>
                    <div class="meta-sm">
                        <h5>Unit Rate</h5>
                        <p>₹<?php echo number_format($rate_per_unit, 2); ?></p>
                    </div>
                </div>
                <div class="meter-meta-item">
                    <i class='bx bx-chip'></i>
                    <div class="meta-sm">
                        <h5>Meter Type</h5>
                        <p>Digital</p>
                    </div>
                </div>
                <div class="meter-meta-item">
                    <i class='bx bx-calendar-check'></i>
                    <div class="meta-sm">
                        <h5>Reading Date</h5>
                        <p><?php echo date("d M Y", strtotime($row['created_at'])); ?></p>
                    </div>
                </div>
                <div class="meter-meta-item">
                    <i class='bx bx-calendar-event'></i>
                    <div class="meta-sm">
                        <h5>Bill Period</h5>
                        <p><?php echo htmlspecialchars($month_period); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Meter Photo (Conditional) -->
        <?php 
        $display_img = !empty($row['meter_screenshot_orig']) ? $row['meter_screenshot_orig'] : (!empty($row['meter_screenshot']) ? $row['meter_screenshot'] : null);
        if (!empty($display_img)): 
        ?>
        <div class="section-card">
            <div class="section-header">
                <i class='bx bx-camera'></i>
                <h3>Meter Reading Photo</h3>
            </div>
            <div class="photo-body">
                <img src="../uploads/meter_readings/<?php echo htmlspecialchars($display_img); ?>" alt="Meter Reading" class="photo-img">
                <div class="photo-details">
                    <div class="photo-det-row">
                        <i class='bx bx-calendar'></i>
                        <div class="photo-det-info">
                            <h5>Photo Captured On</h5>
                            <p><?php echo date("d M Y, h:i A", strtotime($row['created_at'])); ?></p>
                        </div>
                    </div>
                    <div class="photo-det-row">
                        <i class='bx bx-user'></i>
                        <div class="photo-det-info">
                            <h5>Captured By</h5>
                            <p>Admin</p>
                        </div>
                    </div>
                    <div class="photo-det-row">
                        <i class='bx bx-message-square-detail'></i>
                        <div class="photo-det-info">
                            <h5>Note</h5>
                            <p class="note">Reading captured for <?php echo date("F Y", strtotime($row['month'] . '-01')); ?>.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Breakdown Table -->
        <table class="charges-table">
            <thead>
                <tr>
                    <th><i class='bx bxs-zap' style="margin-right: 8px;"></i> Electricity Charges Breakdown</th>
                    <th>Amount (₹)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Energy Charges (<?php echo $units_consumed; ?> Units @ ₹<?php echo number_format($rate_per_unit, 2); ?>)</td>
                    <td>₹<?php echo number_format($electricity_amount, 2); ?></td>
                </tr>
                
                <!-- Display dynamically based on DB values -->
                
            </tbody>
        </table>

        <!-- Total Box -->
        <div class="total-box">
            <div class="total-left">
                <div class="total-icon">
                    <i class='bx bx-file'></i>
                </div>
                <div>
                    <h3>Total Amount Payable</h3>
                    <p>(Inclusive of all charges)</p>
                </div>
            </div>
            <div class="total-amount">
                ₹<?php echo number_format($total_amount, 2); ?>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="thank-you">♥ Thank you for your timely payment! ♥</div>
            <div class="footer-query">For any queries, contact +91 7667184920</div>
        </div>

    </div>

</body>
</html>

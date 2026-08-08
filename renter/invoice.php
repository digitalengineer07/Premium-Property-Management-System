<?php
// renter/invoice.php
require_once "../db.php";
require_once "../config.php";
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$bill_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$bill_id) {
    die("Invalid Bill ID");
}

// Fetch bill details
$stmt = mysqli_prepare($conn, "
    SELECT e.*, u.name, u.room_no, u.phone, u.email 
    FROM electricity e 
    JOIN users u ON e.user_id = u.id 
    WHERE e.id = ? AND e.user_id = ?
");
mysqli_stmt_bind_param($stmt, "ii", $bill_id, $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$bill = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$bill) {
    die("Bill not found or access denied.");
}

// Calculate Payments
$pay_q = mysqli_query($conn, "SELECT SUM(paid_amount - COALESCE(adjustment_amount, 0)) as total_paid FROM payments WHERE bill_type IN ('electricity', 'elec_rent') AND bill_id = $bill_id");
$pay_res = mysqli_fetch_assoc($pay_q);
$amount_paid = (float)($pay_res['total_paid'] ?? 0);

$total_payable = (float)$bill['amount'] + (float)$bill['rent_amount'] + (float)$bill['maintenance'] + (float)$bill['dues'] + (float)$bill['extra_charges'];
$remaining_amount = max(0, $total_payable - $amount_paid);

$status = $bill['status']; 
if (strtolower($status) === 'paid' || $remaining_amount <= 0) {
    $status = 'Paid';
    $status_color = '#10B981'; // Green
    $status_bg = '#D1FAE5';
} elseif (strtolower($status) === 'partial' || ($amount_paid > 0 && $remaining_amount > 0)) {
    $status = 'Partial';
    $status_color = '#F59E0B'; // Orange
    $status_bg = '#FEF3C7';
} else {
    $status = 'Unpaid';
    $status_color = '#EF4444'; // Red
    $status_bg = '#FEE2E2';
}


$bill_invoice_id = !empty($bill['bill_invoice_id']) ? $bill['bill_invoice_id'] : 'BIL-'.date('Ym', strtotime($bill['month'])).'-'.str_pad($bill['id'], 6, '0', STR_PAD_LEFT);
$bill_date = date('d M Y', strtotime($bill['created_at']));
$due_date = date('d M Y', strtotime($bill['due_date']));
$is_overdue = (strtotime($bill['due_date']) < time() && $remaining_amount > 0);

$room_no = $bill['room_no'];
$name = $bill['name'];
$phone = $bill['phone'] ?? '+91 98765 43210';
$email = $bill['email'] ?? 'renter@example.com';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bill Invoice - <?php echo htmlspecialchars($bill_invoice_id); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #4F46E5;
            --primary-light: #EEF2FF;
            --text-dark: #1E293B;
            --text-gray: #64748B;
            --bg-body: #F8FAFC;
            --white: #FFFFFF;
            --border: #E2E8F0;
            --danger: #EF4444;
            --success: #10B981;
            --warning: #F59E0B;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Outfit', sans-serif;
        }

        body {
            background-color: var(--bg-body);
            color: var(--text-dark);
            -webkit-font-smoothing: antialiased;
            padding: 40px 20px;
        }

        .invoice-container {
            max-width: 1000px;
            margin: 0 auto;
            background: var(--white);
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.05);
            overflow: hidden;
            border: 1px solid var(--border);
        }

        .invoice-content {
            padding: 40px;
        }

        /* Header Section */
        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 40px;
        }

        .brand-section {
            display: flex;
            gap: 16px;
            flex: 1;
        }

        .brand-icon {
            width: 60px;
            height: 60px;
            background: var(--primary);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 32px;
        }

        .brand-info {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .brand-info h1 {
            font-size: 26px;
            font-weight: 800;
            color: #1E1B4B;
            margin-bottom: 6px;
            letter-spacing: 0.5px;
            line-height: 1;
        }

        .brand-residence-line {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
            max-width: 200px;
        }
        
        .brand-residence-line span {
            font-size: 14px;
            letter-spacing: 4px;
            color: var(--text-gray);
            font-weight: 500;
        }
        
        .brand-residence-line .line {
            height: 1px;
            background: #CBD5E1;
            flex: 1;
        }

        .brand-address {
            font-size: 13px;
            color: var(--text-gray);
            line-height: 1.6;
            max-width: 250px;
        }

        .brand-contact {
            display: flex;
            gap: 16px;
            margin-top: 10px;
            font-size: 13px;
            color: var(--primary);
            font-weight: 600;
        }
        
        .brand-contact span {
            display: flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }

        .title-section {
            text-align: center;
            flex: 1;
            margin-top: -10px;
            transform: translateX(-30px);
        }

        .title-section h2 {
            font-size: 26px;
            font-weight: 800;
            color: #1E1B4B;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }
        
        .title-underline {
            width: 40px;
            height: 3px;
            background: var(--primary);
            margin: 0 auto 16px auto;
            border-radius: 4px;
        }

        .month-badge {
            background: var(--primary-light);
            color: var(--primary);
            padding: 8px 16px;
            border-radius: 24px;
            font-size: 13px;
            font-weight: 700;
            display: inline-block;
            text-align: center;
            white-space: nowrap;
        }

        .meta-section {
            background: linear-gradient(135deg, #ffffff 0%, #F5F7FA 100%);
            border: 1px solid rgba(79, 70, 229, 0.15);
            border-radius: 14px;
            padding: 18px 22px;
            width: 300px;
            margin-left: auto;
            flex: 1;
            max-width: 320px;
            box-shadow: 0 4px 20px rgba(79, 70, 229, 0.04);
        }

        .meta-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
            font-size: 13px;
        }
        
        .meta-row:last-child {
            margin-bottom: 0;
        }

        .meta-label {
            color: var(--text-gray);
            font-weight: 600;
        }

        .meta-value {
            font-weight: 700;
            color: var(--text-dark);
            white-space: nowrap;
        }

        .meta-value.highlight {
            color: var(--primary);
        }
        
        .meta-value.danger {
            color: var(--danger);
        }

        .due-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            margin-top: 4px;
        }

        /* Info Cards */
        .info-cards {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            margin-bottom: 40px;
        }

        .info-card {
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 24px 20px;
            display: flex;
            align-items: flex-start;
            gap: 16px;
        }

        .info-card-body {
            flex: 1;
        }

        .info-icon {
            width: 48px;
            height: 48px;
            background: var(--primary-light);
            color: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            flex-shrink: 0;
        }

        .info-title {
            font-size: 12px;
            font-weight: 700;
            color: var(--text-gray);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: block;
            margin-bottom: 12px;
        }

        .info-content h3 {
            font-size: 16px;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 6px;
        }

        .info-content p {
            font-size: 13px;
            color: var(--text-gray);
            line-height: 1.6;
            margin: 0;
        }
        
        .info-contact {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            font-weight: 600;
            color: var(--primary);
            margin-top: 12px;
        }

        .prop-row {
            display: flex;
            margin-bottom: 10px;
            font-size: 13px;
        }
        .prop-row:last-child { margin-bottom: 0; }
        .prop-label { width: 105px; color: var(--text-gray); font-weight: 500; flex-shrink: 0; }
        .prop-val { font-weight: 600; color: var(--text-dark); white-space: nowrap; }

        .payment-status-card {
            background: #F8FAFC;
            display: block;
        }

        .status-pill {
            float: right;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            background: <?php echo $status_bg; ?>;
            color: <?php echo $status_color; ?>;
            text-transform: uppercase;
        }

        .payable-amount {
            font-size: 32px;
            font-weight: 800;
            color: var(--success);
            margin: 16px 0 8px 0;
            display: flex;
            align-items: center;
            gap: 4px;
            white-space: nowrap;
            flex-wrap: nowrap;
        }
        
        .payable-note {
            font-size: 12px;
            color: var(--text-gray);
            line-height: 1.5;
        }
        .payable-note strong {
            color: var(--danger);
        }

        /* Table Section */
        .table-section {
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
            margin-bottom: 40px;
        }

        .table-header {
            padding: 16px 24px;
            background: var(--white);
            border-bottom: 1px solid var(--border);
        }

        .table-header h3 {
            font-size: 14px;
            font-weight: 700;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 500px; /* Ensure table doesn't squish too much before scrolling */
        }

        th {
            background: var(--primary-light);
            color: var(--primary);
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 12px 24px;
            text-align: left;
        }

        th:last-child { text-align: right; }

        td {
            padding: 16px 24px;
            font-size: 14px;
            border-bottom: 1px solid var(--border);
            color: var(--text-dark);
        }

        td.col-num { font-weight: 700; width: 50px; }
        td.col-part { font-weight: 600; width: 250px; }
        td.col-desc { color: var(--text-gray); }
        td.col-amt { font-weight: 700; text-align: right; }
        
        tr.danger-row td { color: var(--danger); }
        tr.danger-row td.col-desc { color: var(--danger); }

        .table-footer-wrapper {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding: 24px;
            background: var(--white);
        }

        .warning-box {
            background: var(--primary-light);
            border: 1px dashed rgba(79, 70, 229, 0.3);
            border-radius: 12px;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            max-width: 450px;
        }

        .warning-box i {
            color: var(--primary);
            font-size: 20px;
        }

        .warning-box p {
            font-size: 13px;
            color: var(--text-gray);
            margin: 0;
        }

        .totals-box {
            background: #FAFBFC;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px 24px;
            width: 380px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 14px;
            color: var(--text-dark);
            font-weight: 600;
        }

        .total-row.discount {
            color: var(--success);
        }

        .total-row.final {
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid var(--border);
            font-size: 18px;
            font-weight: 800;
            color: #1E1B4B;
            margin-bottom: 0;
        }

        /* Bottom Section */
        .bottom-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 24px;
        }

        .payment-info {
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
        }

        .bill-summary {
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
            display: flex;
            flex-direction: column;
        }
        
        .section-title {
            font-size: 14px;
            font-weight: 700;
            color: var(--primary);
            text-transform: uppercase;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
        }

        .qr-section {
            display: flex;
            gap: 24px;
            align-items: center;
        }

        .qr-code {
            width: 140px;
            height: 140px;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 10px;
        }

        .qr-code img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .bank-details {
            font-size: 13px;
            line-height: 1.6;
        }
        
        .bank-details p { margin-bottom: 4px; color: var(--text-gray); }
        .bank-details p strong { color: var(--text-dark); font-weight: 600; }
        
        .qr-note {
            text-align: center;
            font-size: 12px;
            color: var(--primary);
            font-weight: 500;
            margin-top: 16px;
        }

        .summary-list {
            display: flex;
            flex-direction: column;
            flex: 1;
            gap: 12px;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
        }

        .summary-item-label {
            color: var(--text-gray);
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
        }
        .summary-item-label i { font-size: 16px; color: var(--primary); }
        .summary-item-val {
            font-weight: 600;
            color: var(--text-dark);
            text-align: right;
        }
        
        .summary-item.remaining .summary-item-val {
            font-size: 16px;
            color: var(--danger);
            font-weight: 800;
        }

        .footer-banner {
            background: var(--primary-light);
            border-radius: 16px;
            padding: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .banner-quote {
            display: flex;
            gap: 16px;
            align-items: flex-start;
        }
        .banner-quote i { font-size: 24px; color: var(--primary); opacity: 0.5; }
        .banner-quote p { font-size: 14px; font-weight: 600; color: var(--primary); margin: 0; line-height: 1.5; }
        .banner-quote p span { font-weight: 400; font-size: 13px; display: block; opacity: 0.8; }

        .signatory {
            text-align: center;
        }
        .signatory-img { height: 40px; margin-bottom: 8px; opacity: 0.8; }
        .signatory p { font-size: 12px; color: var(--text-gray); font-weight: 500; margin: 0; }
        .signatory p.sig-name { color: var(--primary); font-weight: 600; margin-top: 2px; }

        .support-info {
            display: flex;
            align-items: center;
            gap: 12px;
            text-align: right;
        }
        .support-info i { font-size: 24px; color: var(--primary); }
        .support-info h4 { font-size: 13px; color: var(--primary); margin: 0 0 4px 0; }
        .support-info p { font-size: 14px; font-weight: 700; color: var(--text-dark); margin: 0 0 2px 0; }
        .support-info span { font-size: 11px; color: var(--text-gray); }

        /* Actions */
        .actions-bar {
            display: flex;
            gap: 16px;
        }

        .btn {
            flex: 1;
            padding: 16px;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: 0.2s;
            border: none;
        }

        .btn-outline {
            background: var(--white);
            border: 1px solid var(--border);
            color: var(--primary);
        }
        .btn-outline:hover { background: var(--bg-body); }

        .btn-primary {
            background: var(--primary);
            color: var(--white);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }
        .btn-primary:hover {
            background: #4338CA;
            transform: translateY(-2px);
        }

        /* Print Styles */
        @media print {
            @page {
                size: A4;
                margin: 8mm;
            }
            body, html { background: white; padding: 0; margin: 0; height: 100%; }
            .invoice-container { 
                box-shadow: none; border: none; max-width: 100%; border-radius: 0; margin: 0; padding: 0; 
                min-height: 100vh;
                display: flex;
                flex-direction: column;
            }
            .invoice-content { 
                padding: 0; zoom: 0.85; 
                flex: 1;
                display: flex !important;
                flex-direction: column !important;
                min-height: 110vh !important; /* Accounting for zoom to stretch full page */
            }
            .actions-bar { display: none !important; }
            
            /* Force side-by-side header in print to save huge vertical space */
            .header-section { 
                display: flex !important; 
                flex-direction: row !important; 
                justify-content: space-between !important; 
                align-items: center !important;
                text-align: left !important;
                gap: 16px !important;
            }
            .brand-section { 
                display: flex !important; 
                flex-direction: row !important; 
                align-items: center !important; 
                text-align: left !important;
            }
            .title-section { padding-top: 0 !important; }
            .title-section h2 { font-size: 20px !important; }
            
            .meta-section { width: auto !important; min-width: 220px !important; padding: 12px 16px !important; margin-left: auto !important; }
            
            /* Force Side-by-Side layout in print to save vertical space */
            .info-cards { 
                display: grid !important;
                grid-template-columns: 1fr 1fr 1fr !important;
                gap: 12px !important;
                margin-bottom: 20px !important;
                align-items: stretch;
            }
            .info-card { padding: 12px !important; }
            
            .table-section { margin-bottom: 20px; }
            td, th { padding: 8px 12px !important; }
            
            /* Prevent table footer components from stacking vertically in print */
            .table-footer-wrapper {
                display: flex !important;
                flex-direction: row !important;
                justify-content: space-between !important;
                align-items: flex-end !important;
                gap: 16px !important;
            }
            .totals-box { width: 380px !important; }
            .warning-box { max-width: 60% !important; margin-bottom: 0 !important; }
            
            .bottom-section { 
                display: grid !important;
                grid-template-columns: 1.5fr 1fr !important;
                gap: 16px !important;
                margin-bottom: 20px !important;
                align-items: stretch;
            }
            .payment-info, .bill-summary { padding: 16px !important; }
            
            .footer-banner { 
                margin-top: auto !important; 
                margin-bottom: 0 !important; 
                padding: 16px !important; 
                background: #f8f9fa !important; 
                -webkit-print-color-adjust: exact; 
                print-color-adjust: exact; 
            }

            th { background: #f8f9fa !important; color: #000 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .month-badge { background: #f8f9fa !important; border: 1px solid #ccc !important; -webkit-print-color-adjust: exact; print-color-adjust: exact;}
            
            /* Allow internal breaks if needed but keep components intact */
            .info-card, .table-section, .payment-info, .bill-summary, .footer-banner { break-inside: avoid; page-break-inside: avoid; }
            * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
        
        @media (max-width: 768px) {
            .info-cards { grid-template-columns: 1fr; }
            .table-footer-wrapper { flex-direction: column; gap: 20px; align-items: stretch; }
            .totals-box { width: 100%; }
            .bottom-section { grid-template-columns: 1fr; }
            .footer-banner { flex-direction: column; gap: 24px; text-align: center; }
            .support-info { text-align: center; flex-direction: column; }
            .actions-bar { flex-direction: column; }
        }

        @media (max-width: 600px) {
            html, body { overflow-x: hidden; max-width: 100vw; }
            .invoice-container { width: 100%; max-width: 100%; }
            body { padding: 16px 10px; }
            .invoice-content { padding: 20px 16px; }
            .header-section { flex-direction: column; gap: 24px; align-items: center; text-align: center; }
            .brand-section { flex-direction: column; align-items: center; text-align: center; }
            .meta-section { width: 100%; }
        }
        
        @media (max-width: 500px) {
            th, td { padding: 10px 8px; font-size: 11px; }
            table { min-width: 100% !important; }
            th[style], td.col-num, td.col-part { width: auto !important; }
            .table-footer-wrapper { padding: 12px; }
            .totals-box { padding: 12px; }
            .qr-code { width: 100px; height: 100px; }
            .payment-info, .bill-summary, .footer-banner { padding: 12px; }
            .total-row.final { font-size: 16px; }
            .summary-item { align-items: flex-start; }
            .summary-item-val { max-width: 60%; line-height: 1.4; }
        }
    </style>
</head>
<body>

    <div class="invoice-container">
        <div class="invoice-content" id="invoiceArea">
            
            <!-- Header -->
            <div class="header-section">
                <div class="brand-section">
                    <img src="../assets/img/logo.png?v=<?php echo time(); ?>" onerror="this.src='../assets/img/favicon.png'; this.onerror=null;" alt="Logo" style="height: 64px; object-fit: contain;">
                    <div class="brand-info">
                        <h1><?php echo strtoupper(explode(' ', HOUSE_NAME)[0] . (isset(explode(' ', HOUSE_NAME)[1]) ? ' ' . explode(' ', HOUSE_NAME)[1] : '')); ?></h1>
                        <div class="brand-residence-line">
                            <div class="line"></div>
                            <span>RESIDENCE</span>
                            <div class="line"></div>
                        </div>
                        <p class="brand-address"><?php echo htmlspecialchars(HOUSE_ADDRESS); ?></p>
                        <div class="brand-contact">
                            <span><i class='bx bxs-phone'></i> +91 6206936907</span>
                            <span><i class='bx bxs-envelope'></i> madhavkunj@succorkart.in</span>
                        </div>
                    </div>
                </div>

                <div class="title-section">
                    <h2>BILL INVOICE</h2>
                    <div class="title-underline"></div>
                    <div class="month-badge">For the Month of <?php echo date('F Y', strtotime($bill['month'])); ?></div>
                </div>

                <div class="meta-section">
                    <div class="meta-row">
                        <span class="meta-label">BILL ID</span>
                        <span class="meta-value highlight"><?php echo $bill_invoice_id; ?></span>
                    </div>
                    <div class="meta-row">
                        <span class="meta-label">BILL DATE</span>
                        <span class="meta-value"><?php echo $bill_date; ?></span>
                    </div>
                    <div class="meta-row" style="align-items: flex-start;">
                        <span class="meta-label" style="margin-top: 2px;">DUE DATE</span>
                        <span class="meta-value <?php echo $is_overdue ? 'danger' : 'highlight'; ?>" style="text-align: right;">
                            <?php echo $due_date; ?>
                            <?php if ($is_overdue): ?>
                            <br><span class="due-badge"><i class='bx bx-calendar-exclamation'></i> Overdue</span>
                            <?php elseif (date('Y-m-d', strtotime($bill['due_date'])) == date('Y-m-d')): ?>
                            <br><span class="due-badge" style="color:var(--danger); background: rgba(239, 68, 68, 0.1);"><i class='bx bx-calendar-event'></i> Due Today</span>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Info Cards -->
            <div class="info-cards">
                <div class="info-card">
                    <div class="info-icon"><i class='bx bxs-user'></i></div>
                    <div class="info-card-body">
                        <span class="info-title">Billed To</span>
                        <div class="info-content">
                            <h3 style="word-break: break-word; hyphens: auto; font-size: clamp(14px, 1.2vw, 16px);"><?php echo htmlspecialchars($name); ?></h3>
                            <p>Room No. <?php echo htmlspecialchars($room_no); ?>, <?php echo htmlspecialchars($room['block_wing'] ?? 'Block B'); ?><br><?php echo HOUSE_NAME; ?> Residence</p>
                            <div class="info-contact">
                                <i class='bx bxs-phone-call'></i> <?php echo (strpos(trim($phone), '+91') === 0) ? htmlspecialchars(trim($phone)) : '+91 ' . htmlspecialchars(trim($phone)); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="info-card">
                    <div class="info-icon"><i class='bx bx-building-house'></i></div>
                    <div class="info-card-body">
                        <span class="info-title">Property Details</span>
                        <div class="info-content">
                            <div class="prop-row"><span class="prop-label">Name</span><span class="prop-val">: <?php echo HOUSE_NAME; ?></span></div>
                            <div class="prop-row"><span class="prop-label">Block / Wing</span><span class="prop-val">: <?php echo htmlspecialchars($room['block_wing'] ?? 'Block B'); ?></span></div>
                            <div class="prop-row"><span class="prop-label">Room No.</span><span class="prop-val">: <?php echo htmlspecialchars($room_no); ?></span></div>
                            <div class="prop-row"><span class="prop-label">Resident Type</span><span class="prop-val">: Family</span></div>
                        </div>
                    </div>
                </div>

                <div class="info-card payment-status-card">
                    <span class="status-pill"><?php echo $status; ?></span>
                    <span class="info-title" style="margin-bottom:16px;">Payment Status</span>
                    <div class="info-content">
                        <p style="margin-bottom: 4px; color:var(--text-gray); font-weight: 500;">Total Billed Amount</p>
                        <div class="payable-amount">₹ <?php echo number_format($total_payable, 2); ?></div>
                        <?php if (strtolower($status) !== 'paid'): ?>
                        <p class="payable-note">Please make the payment before<br><strong><?php echo $due_date; ?></strong> to avoid late fee.</p>
                        <?php else: ?>
                        <p class="payable-note" style="color: #10B981;">Payment received. Thank you!</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="table-section">
                <div class="table-header">
                    <h3>Charges Breakdown</h3>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                        <tr>
                            <th style="width: 40px;">#</th>
                            <th style="width: 180px;">Particulars</th>
                            <th>Description</th>
                            <th style="text-align: center;">Amount (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $idx = 1;
                        if ((float)$bill['rent_amount'] > 0) {
                            echo "<tr><td class='col-num'>{$idx}</td><td class='col-part'>Rent</td><td class='col-desc'>Monthly Rent for ".date('F Y', strtotime($bill['month']))."</td><td class='col-amt'>".number_format($bill['rent_amount'], 2)."</td></tr>";
                            $idx++;
                        }
                        if ((float)$bill['amount'] > 0) {
                            echo "<tr><td class='col-num'>{$idx}</td><td class='col-part'>Electricity Charges</td><td class='col-desc'>Electricity usage charges ({$bill['units_consumed']} units)</td><td class='col-amt'>".number_format($bill['amount'], 2)."</td></tr>";
                            $idx++;
                        }
                        if ((float)$bill['maintenance'] > 0) {
                            echo "<tr><td class='col-num'>{$idx}</td><td class='col-part'>Maintenance Charges</td><td class='col-desc'>Society maintenance for ".date('F Y', strtotime($bill['month']))."</td><td class='col-amt'>".number_format($bill['maintenance'], 2)."</td></tr>";
                            $idx++;
                        }
                        if ((float)$bill['extra_charges'] > 0) {
                            $desc = !empty($bill['extra_charges_desc']) ? htmlspecialchars($bill['extra_charges_desc']) : 'Other miscellaneous charges';
                            echo "<tr><td class='col-num'>{$idx}</td><td class='col-part'>Other Charges</td><td class='col-desc'>{$desc}</td><td class='col-amt'>".number_format($bill['extra_charges'], 2)."</td></tr>";
                            $idx++;
                        }
                        if ((float)$bill['dues'] > 0) {
                            echo "<tr class='danger-row'><td class='col-num'>{$idx}</td><td class='col-part'>Previous Dues</td><td class='col-desc'>Pending balance carried forward</td><td class='col-amt'>".number_format($bill['dues'], 2)."</td></tr>";
                            $idx++;
                        }
                        ?>
                    </tbody>
                </table>
                </div>
                <div class="table-footer-wrapper">
                    <div class="warning-box">
                        <i class='bx bx-info-circle'></i>
                        <p>Kindly clear your dues before the due date to avoid additional late fees.</p>
                    </div>
                    <div class="totals-box">
                        <div class="total-row final" style="margin-top: 0; padding-top: 0; border-top: none; align-items: center; gap: 12px; flex-wrap: wrap; justify-content: space-between;">
                            <span>Total Billed Amount</span>
                            <span style="white-space: nowrap;">₹&nbsp;<?php echo number_format($total_payable, 2); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Section -->
            <div class="bottom-section">
                <div class="payment-info" id="payment-qr-section">
                    <div class="section-title"><i class='bx bx-wallet-alt'></i> Payment Information</div>
                    <div class="qr-section" style="justify-content: center; text-align: center; flex-direction: column; align-items: center; gap: 12px; padding: 12px 0;">
                        
                        <div style="background: linear-gradient(135deg, #4F46E5, #9333EA); padding: 2px; border-radius: 14px; box-shadow: 0 8px 20px rgba(79, 70, 229, 0.15);">
                            <div style="background: white; padding: 10px; border-radius: 12px; display: inline-block;">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=130x130&data=upi://pay?pa=nikhil119124-1@oksbi&pn=<?php echo urlencode(HOUSE_NAME . ' Residence'); ?>&cu=INR&am=<?php echo $total_payable; ?>" alt="UPI QR Code" style="display: block; border-radius: 6px;">
                            </div>
                        </div>

                        <div class="bank-details" style="display: flex; flex-direction: column; align-items: center; gap: 2px;">
                            <div style="display: inline-flex; align-items: center; gap: 4px; background: #EEF2FF; padding: 4px 12px; border-radius: 12px;">
                                <i class='bx bx-check-shield' style="color: #4F46E5; font-size: 14px;"></i>
                                <span style="font-size: 11px; color: #4F46E5; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Verified UPI</span>
                            </div>
                            <p style="font-size: 15px; color: var(--text-dark); font-weight: 800; letter-spacing: 0.5px; margin-top: 4px;">nikhil119124-1@oksbi</p>
                            <p style="font-size: 11px; color: var(--text-gray); font-weight: 500;">Scan with any UPI App (GPay, PhonePe, Paytm)</p>
                        </div>
                    </div>
                    <p class="qr-note">Note: After payment, please upload screenshot or enter UTR number for verification.</p>
                </div>

                <div class="bill-summary">
                    <div class="section-title"><i class='bx bx-receipt'></i> Bill Summary</div>
                    <div class="summary-list">
                        <?php
                            $b_types = [];
                            if ((float)$bill['rent_amount'] > 0) $b_types[] = 'Rent';
                            if ((float)$bill['amount'] > 0) $b_types[] = 'Electricity';
                            if ((float)$bill['maintenance'] > 0) $b_types[] = 'Maintenance';
                            $dynamic_type = !empty($b_types) ? implode(' + ', $b_types) : 'Standard Invoice';
                        ?>
                        <div class="summary-item">
                            <div class="summary-item-label"><i class='bx bx-calendar'></i> Bill Month</div>
                            <div class="summary-item-val"><?php echo date('F Y', strtotime($bill['month'])); ?></div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-item-label"><i class='bx bx-file'></i> Bill Type</div>
                            <div class="summary-item-val"><?php echo $dynamic_type; ?></div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-item-label"><i class='bx bx-time-five'></i> Generated On</div>
                            <div class="summary-item-val"><?php echo date('d M Y h:i A', strtotime($bill['created_at'])); ?></div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-item-label"><i class='bx bx-calendar-exclamation'></i> Due Date</div>
                            <div class="summary-item-val">
                                <span style="background: #FEF2F2; color: #EF4444; padding: 2px 10px; border-radius: 12px; font-size: 11px; font-weight: 700; border: 1px solid rgba(239, 68, 68, 0.2);"><?php echo $due_date; ?></span>
                            </div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-item-label"><i class='bx bx-receipt'></i> Total Billed</div>
                            <div class="summary-item-val">₹ <?php echo number_format($total_payable, 2); ?></div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-item-label"><i class='bx bx-error-circle'></i> Extra Charges</div>
                            <div class="summary-item-val">₹ <?php echo number_format((float)$bill['dues'] + (float)$bill['extra_charges'], 2); ?></div>
                        </div>
                        <div class="summary-item remaining">
                            <div class="summary-item-label"><i class='bx bx-credit-card-front'></i> Payment Method</div>
                            <div class="summary-item-val" style="color: var(--primary);">UPI Preferred</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Banner -->
            <div class="footer-banner">
                <div class="banner-quote">
                    <i class='bx bxs-quote-alt-left'></i>
                    <p>Thank you for being a valued resident.<br><span>Timely payments help us serve you better.</span></p>
                </div>
                
                <div class="support-info">
                    <i class='bx bx-support'></i>
                    <div>
                        <h4>Need Help?</h4>
                        <p style="font-size: 13px;">+91 6206936907</p>
                        <span>madhavkunj@succorkart.in</span>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="actions-bar">
                <button class="btn btn-outline" onclick="window.print()"><i class='bx bx-download'></i> Download PDF</button>
                <button class="btn btn-outline" onclick="shareBill()"><i class='bx bx-share-alt'></i> Share Bill</button>
                <?php if ($remaining_amount > 0): ?>
                <button class="btn btn-primary" onclick="payNow()"><i class='bx bx-credit-card'></i> Pay Now</button>
                <?php else: ?>
                <button class="btn btn-primary" style="background:var(--success);"><i class='bx bx-check-circle'></i> Paid Successfully</button>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <script>
        function shareBill() {
            const shareData = {
                title: 'Bill Invoice - <?php echo $bill_invoice_id; ?>',
                text: 'Please find my latest rent & utility bill invoice for <?php echo date('F Y', strtotime($bill['month'])); ?>.',
                url: window.location.href
            };

            if (navigator.share) {
                navigator.share(shareData).catch((error) => console.log('Error sharing:', error));
            } else {
                navigator.clipboard.writeText(window.location.href).then(() => {
                    alert("Bill link copied to clipboard! You can now paste it anywhere to share.");
                }).catch(() => {
                    alert("Sharing is not supported on this browser. Please copy the URL manually.");
                });
            }
        }

        function payNow() {
            const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
            if (isMobile) {
                // Try to open UPI app directly
                window.location.href = "upi://pay?pa=nikhil119124-1@oksbi&pn=<?php echo urlencode(HOUSE_NAME . ' Residence'); ?>&cu=INR&am=<?php echo $total_payable; ?>";
                
                // Fallback scroll after a tiny delay in case UPI intent fails
                setTimeout(() => {
                    scrollToQR();
                }, 1000);
            } else {
                // Desktop: smoothly scroll to QR code
                scrollToQR();
            }
        }

        function scrollToQR() {
            const qrSection = document.getElementById('payment-qr-section');
            if(qrSection) {
                qrSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
                qrSection.style.transition = "box-shadow 0.5s ease-in-out, transform 0.3s";
                qrSection.style.boxShadow = "0 0 0 4px rgba(79, 70, 229, 0.3)";
                qrSection.style.transform = "scale(1.02)";
                
                setTimeout(() => {
                    qrSection.style.boxShadow = "none";
                    qrSection.style.transform = "scale(1)";
                }, 1200);
            }
        }
    </script>
</body>
</html>

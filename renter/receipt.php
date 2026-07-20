<?php
// Dummy data for initial design phase as requested
$receipt = [
    'date' => '20 Jul 2026, 10:35 AM',
    'user_name' => 'Test User',
    'room_no' => 'Room No. 502, Block B',
    'residence' => 'Madhav Kunj Residence',
    'phone' => '+91 98765 43210',
    
    'bill_id' => 'BIL-202607-000156',
    'bill_month' => 'July 2026',
    'bill_type' => 'Monthly Maintenance',
    'due_date' => '20 Jul 2026',
    
    'payment_id' => 'SYS_UPI_6A5D1EEEE485D',
    'payment_method' => 'UPI',
    'utr' => '516032108117',
    
    'total_amount' => '13,788.00',
    'amount_words' => '(Rupees Thirteen Thousand Seven Hundred Eighty Eight Only)',
    
    'charges' => [
        ['particular' => 'Rent', 'amount' => '10,000.00'],
        ['particular' => 'Electricity Charges', 'amount' => '2,856.00'],
        ['particular' => 'Maintenance Charges', 'amount' => '800.00'],
        ['particular' => 'Water Charges', 'amount' => '300.00'],
        ['particular' => 'Garbage Collection', 'amount' => '150.00'],
        ['particular' => 'Previous Dues (June 2026)', 'amount' => '1,482.00', 'is_red' => true],
        ['particular' => 'Late Payment Fee', 'amount' => '200.00'],
    ],
    
    'upi_id' => 'madhavkunj@upi',
    'bank_name' => 'HDFC Bank',
    'account_holder' => 'Madhav Kunj Residence'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #4A3AFF;
            --primary-light: #F0EDFF;
            --success: #10B981;
            --success-light: #D1FAE5;
            --danger: #EF4444;
            --text-dark: #1F2937;
            --text-gray: #6B7280;
            --border: #E5E7EB;
            --bg-body: #F3F4F6;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg-body);
            color: var(--text-dark);
            padding: 40px 20px;
        }

        .receipt-container {
            max-width: 1000px;
            margin: 0 auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.05);
            padding: 40px;
            position: relative;
        }

        /* HEADER */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 40px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 30px;
        }

        .h-left, .h-center, .h-right {
            flex: 1;
        }

        .h-center {
            text-align: center;
        }

        .h-right {
            text-align: right;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }

        .logo-text {
            margin-bottom: 20px;
        }

        .address-info {
            font-size: 13px;
            color: #374151;
            line-height: 1.6;
            margin-bottom: 16px;
            font-weight: 500;
        }

        .contact-info {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 12px;
            color: #374151;
            font-weight: 600;
        }

        .contact-info i {
            color: var(--primary);
            font-size: 16px;
        }
        
        .contact-info span {
            display: flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }

        .stamp {
            width: 140px;
            height: 140px;
            border: 3px solid #10B981;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #10B981;
            font-weight: 800;
            transform: rotate(-15deg);
            margin-bottom: 20px;
            position: relative;
        }
        
        .stamp::before {
            content: '';
            position: absolute;
            top: 6px; left: 6px; right: 6px; bottom: 6px;
            border: 1px dashed #10B981;
            border-radius: 50%;
        }

        .stamp .s-top { position: absolute; top: 12px; font-size: 13px; letter-spacing: 1px; font-weight: 800; }
        .stamp .s-main { font-size: 28px; font-weight: 900; background: #10B981; color: white; padding: 4px 20px; border-radius: 4px; z-index: 1; letter-spacing: 2px; }
        .stamp .s-bottom { position: absolute; bottom: 12px; font-size: 11px; letter-spacing: 1px; font-weight: 800; }
        .stamp .s-stars { position: absolute; display: flex; gap: 8px; font-size: 10px; width: 100%; justify-content: center; }
        .stamp .s-stars.top { top: 32px; }
        .stamp .s-stars.bottom { bottom: 32px; }

        .receipt-date {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            justify-content: flex-end;
        }
        
        .receipt-date i {
            color: var(--primary);
            font-size: 20px;
            margin-top: 2px;
        }

        .receipt-date .rd-text {
            text-align: left;
        }

        .receipt-date .rd-text h5 {
            font-size: 13px;
            color: #000;
            font-weight: 800;
            margin-bottom: 4px;
        }

        .receipt-date .rd-text p {
            font-size: 14px;
            color: #4B5563;
            font-weight: 500;
        }

        /* META GRID */
        .meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1.2fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .meta-card {
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 24px;
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }

        .card-icon {
            width: 40px;
            height: 40px;
            background: var(--primary-light);
            color: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        
        .meta-card:nth-child(3) .card-icon {
            background: var(--success-light);
            color: var(--success);
        }

        .card-title {
            font-size: 13px;
            font-weight: 700;
            color: var(--primary);
            letter-spacing: 0.5px;
        }

        .meta-card:nth-child(3) .card-title {
            color: var(--success);
        }

        .user-details h3 {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 8px;
        }

        .user-details p {
            font-size: 13px;
            color: var(--text-gray);
            line-height: 1.6;
        }
        
        .user-phone {
            margin-top: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-dark);
        }
        
        .user-phone i {
            color: var(--primary);
            font-size: 16px;
        }

        .dl-grid {
            display: grid;
            grid-template-columns: 100px 1fr;
            gap: 12px 16px;
            font-size: 13px;
        }

        .dl-label {
            color: var(--text-gray);
            font-weight: 500;
        }

        .dl-value {
            color: var(--text-dark);
            font-weight: 700;
            text-align: right;
        }
        
        .text-danger { color: var(--danger); }

        /* TOTAL SECTION */
        .total-section {
            background: rgba(16, 185, 129, 0.04);
            border: 1px solid rgba(16, 185, 129, 0.1);
            border-radius: 12px;
            padding: 24px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .ts-left h4 {
            font-size: 12px;
            font-weight: 700;
            color: var(--success);
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .ts-left h2 {
            font-size: 32px;
            font-weight: 800;
            color: var(--success);
            margin-bottom: 6px;
        }

        .ts-left p {
            font-size: 13px;
            color: var(--text-dark);
            font-weight: 500;
        }

        .ts-divider {
            width: 1px;
            height: 60px;
            background: rgba(16, 185, 129, 0.2);
            margin: 0 40px;
        }

        .ts-right {
            display: flex;
            align-items: center;
            gap: 20px;
            flex: 1;
        }

        .ts-right i {
            font-size: 48px;
            color: var(--success);
        }

        .ts-r-text h4 {
            font-size: 12px;
            font-weight: 700;
            color: var(--success);
            margin-bottom: 8px;
        }

        .status-pill {
            background: var(--success-light);
            color: var(--success);
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            display: inline-block;
        }

        /* TABLE SECTION */
        .table-section {
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 30px;
        }

        .table-title {
            padding: 20px 24px;
            font-size: 14px;
            font-weight: 700;
            color: var(--primary);
            border-bottom: 1px solid var(--border);
            letter-spacing: 0.5px;
        }

        .receipt-table {
            width: 100%;
            border-collapse: collapse;
        }

        .receipt-table th {
            background: rgba(74, 58, 255, 0.03);
            color: var(--primary);
            font-size: 12px;
            font-weight: 700;
            text-align: left;
            padding: 16px 24px;
        }

        .receipt-table th:last-child {
            text-align: right;
        }

        .receipt-table td {
            padding: 16px 24px;
            font-size: 14px;
            color: var(--text-dark);
            font-weight: 600;
            border-bottom: 1px solid var(--border);
        }

        .receipt-table td:last-child {
            text-align: right;
        }
        
        .receipt-table tr:last-child td {
            border-bottom: none;
        }

        .table-footer {
            background: #FAFAFB;
            padding: 20px 24px;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 40px;
            border-top: 1px solid var(--border);
        }
        
        .table-footer span {
            font-size: 15px;
            font-weight: 700;
            color: var(--primary);
        }
        
        .table-footer h3 {
            font-size: 20px;
            font-weight: 800;
            color: var(--primary);
        }

        /* PAYMENT METHOD DETAILS */
        .payment-method-box {
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 30px;
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .pm-title {
            font-size: 13px;
            font-weight: 700;
            color: var(--primary);
            position: absolute;
            top: -10px; left: 24px;
            background: white;
            padding: 0 10px;
        }
        
        .payment-method-box { position: relative; }

        .pm-logo {
            font-size: 40px;
            font-weight: 900;
            font-style: italic;
            color: #1F2937;
            width: 150px;
            text-align: center;
        }
        
        .pm-logo span { color: #FF7B00; }
        
        .pm-details {
            flex: 1;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            border-left: 1px solid var(--border);
            border-right: 1px solid var(--border);
            padding: 0 30px;
            margin: 0 30px;
        }

        .pm-item h5 {
            font-size: 12px;
            color: var(--text-gray);
            font-weight: 600;
            margin-bottom: 4px;
        }
        
        .pm-item p {
            font-size: 14px;
            color: var(--text-dark);
            font-weight: 600;
        }

        .pm-status {
            display: flex;
            align-items: center;
            gap: 16px;
            width: 250px;
        }
        
        .pm-status i {
            font-size: 28px;
            color: white;
            background: var(--success);
            border-radius: 50%;
        }
        
        .pm-status p {
            font-size: 14px;
            color: var(--text-dark);
            font-weight: 600;
            line-height: 1.5;
        }

        /* FOOTER INFO */
        .footer-info {
            background: var(--primary-light);
            border-radius: 12px;
            padding: 24px;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .fi-item {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            border-right: 1px solid rgba(74, 58, 255, 0.1);
            padding-right: 20px;
        }
        
        .fi-item:last-child { border: none; padding-right: 0; cursor: pointer; transition: 0.2s; }
        .fi-item:last-child:hover { opacity: 0.8; }

        .fi-icon {
            width: 40px; height: 40px;
            background: white;
            color: var(--primary);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
            box-shadow: 0 4px 10px rgba(74, 58, 255, 0.1);
        }

        .fi-text h4 {
            font-size: 14px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 4px;
        }

        .fi-text p {
            font-size: 13px;
            color: var(--text-dark);
            line-height: 1.5;
            font-weight: 500;
        }

        .bottom-thank {
            text-align: center;
            color: var(--primary);
            position: relative;
        }

        .bottom-thank::before, .bottom-thank::after {
            content: '♥';
            display: inline-block;
            margin: 0 16px;
            font-size: 12px;
        }

        .bottom-thank-inner {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            margin-bottom: 8px;
        }
        
        .bottom-thank-inner::before, .bottom-thank-inner::after {
            content: '';
            height: 1px;
            width: 100px;
            background: rgba(74, 58, 255, 0.2);
        }

        .bottom-thank h2 {
            font-family: cursive;
            font-size: 24px;
            font-weight: 600;
            margin: 0 16px;
        }

        .bottom-thank h3 {
            font-size: 14px;
            font-weight: 700;
        }

    </style>
</head>
<body>

    <div class="receipt-container">
        
        <!-- Header -->
        <div class="header">
            <div class="h-left">
                <div class="logo-text">
                    <div style="display: flex; align-items: center; gap: 16px;">
                        <!-- SVG Logo -->
                        <div style="width: 70px; height: 60px;">
                            <svg width="100%" height="100%" viewBox="0 0 100 80">
                                <!-- Roof & Body -->
                                <path d="M 10 50 L 50 10 L 90 50" fill="none" stroke="#4A3AFF" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M 30 40 L 30 70 L 70 70 L 70 40" fill="none" stroke="#4A3AFF" stroke-width="8"/>
                                <path d="M 20 40 L 20 20 L 30 20 L 30 30" fill="none" stroke="#4A3AFF" stroke-width="6"/>
                                <rect x="42" y="42" width="16" height="16" fill="none" stroke="#4A3AFF" stroke-width="4"/>
                                <line x1="50" y1="42" x2="50" y2="58" stroke="#4A3AFF" stroke-width="4"/>
                                <line x1="42" y1="50" x2="58" y2="50" stroke="#4A3AFF" stroke-width="4"/>
                                <!-- Leaves -->
                                <path d="M 15 70 Q 25 60 30 70 Q 25 80 15 70" fill="#10B981"/>
                                <path d="M 25 75 Q 35 65 40 75 Q 35 85 25 75" fill="#10B981"/>
                                <path d="M 85 70 Q 75 60 70 70 Q 75 80 85 70" fill="#10B981"/>
                                <path d="M 75 75 Q 65 65 60 75 Q 65 85 75 75" fill="#10B981"/>
                                <path d="M 30 73 Q 50 83 70 73" fill="none" stroke="#10B981" stroke-width="3"/>
                            </svg>
                        </div>
                        <div>
                            <h1 style="font-size: 22px; font-weight: 900; color: #000; letter-spacing: 0.5px; line-height: 1.1; margin-bottom: 4px;">MADHAV KUNJ</h1>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="height: 1px; background: #9CA3AF; flex: 1;"></div>
                                <span style="font-size: 11px; color: #4B5563; font-weight: 600; letter-spacing: 4px;">RESIDENCE</span>
                                <div style="height: 1px; background: #9CA3AF; flex: 1;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="address-info">
                    Madhav Kunj Society, Near Green Park,<br>
                    Indore, Madhya Pradesh - 452001
                </div>
                <div class="contact-info">
                    <span><i class='bx bx-phone-call'></i> +91 98765 43210</span>
                    <span style="color: #D1D5DB;">|</span>
                    <span><i class='bx bx-envelope'></i> madhavkunj@example.com</span>
                </div>
            </div>

            <div class="h-center">
                <div style="position: relative; width: 64px; height: 64px; margin: 0 auto 16px;">
                    <!-- Wavy Ribbon Base -->
                    <svg viewBox="0 0 100 100" style="width: 100%; height: 100%; color: #EEF2FF;">
                        <path fill="currentColor" d="M50 0 L58 8 L69 5 L75 14 L86 15 L88 26 L98 31 L95 41 L100 50 L95 59 L98 69 L88 74 L86 85 L75 86 L69 95 L58 92 L50 100 L42 92 L31 95 L25 86 L14 85 L12 74 L2 69 L5 59 L0 50 L5 41 L2 31 L12 26 L14 15 L25 14 L31 5 L42 8 Z"/>
                    </svg>
                    <!-- Inner Checkmark -->
                    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 32px; height: 32px; background: #4A3AFF; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 18px; font-weight: bold;">
                        <i class='bx bx-check'></i>
                    </div>
                    <!-- Ribbon Tails -->
                    <div style="position: absolute; bottom: -8px; left: 50%; transform: translateX(-50%); display: flex; gap: 4px; z-index: -1;">
                        <div style="width: 12px; height: 24px; background: #4A3AFF; clip-path: polygon(0 0, 100% 0, 100% 100%, 50% 75%, 0 100%);"></div>
                        <div style="width: 12px; height: 24px; background: #4A3AFF; clip-path: polygon(0 0, 100% 0, 100% 100%, 50% 75%, 0 100%);"></div>
                    </div>
                </div>
                <h2 style="font-size: 24px; font-weight: 900; color: #000; letter-spacing: 0.5px; margin-bottom: 8px;">PAYMENT RECEIPT</h2>
                <p style="font-size: 14px; color: #4B5563; font-weight: 500;">Thank you for your payment!</p>
                <div style="width: 32px; height: 2px; background: #4A3AFF; margin: 16px auto 0; border-radius: 2px;"></div>
            </div>

            <div class="h-right">
                <div class="stamp">
                    <div class="s-top">PAYMENT</div>
                    <div class="s-stars top"><i class='bx bxs-star'></i><i class='bx bxs-star'></i><i class='bx bxs-star'></i></div>
                    <div class="s-main">PAID</div>
                    <div class="s-stars bottom"><i class='bx bxs-star'></i><i class='bx bxs-star'></i><i class='bx bxs-star'></i></div>
                    <div class="s-bottom">SUCCESSFUL</div>
                </div>
                <div class="receipt-date">
                    <i class='bx bx-calendar'></i>
                    <div class="rd-text">
                        <h5>Receipt Date</h5>
                        <p><?= $receipt['date'] ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Meta Info Boxes -->
        <div class="meta-grid">
            <!-- Receipt To -->
            <div class="meta-card">
                <div class="card-header">
                    <div class="card-icon"><i class='bx bx-user'></i></div>
                    <div class="card-title">RECEIPT TO</div>
                </div>
                <div class="user-details">
                    <h3><?= $receipt['user_name'] ?></h3>
                    <p><?= $receipt['room_no'] ?><br><?= $receipt['residence'] ?></p>
                    <div class="user-phone">
                        <i class='bx bxs-phone'></i> <?= $receipt['phone'] ?>
                    </div>
                </div>
            </div>

            <!-- Bill Details -->
            <div class="meta-card">
                <div class="card-header">
                    <div class="card-icon"><i class='bx bx-file'></i></div>
                    <div class="card-title">BILL DETAILS</div>
                </div>
                <div class="dl-grid">
                    <div class="dl-label">Bill ID</div><div class="dl-value"><?= $receipt['bill_id'] ?></div>
                    <div class="dl-label">Bill Month</div><div class="dl-value"><?= $receipt['bill_month'] ?></div>
                    <div class="dl-label">Bill Type</div><div class="dl-value"><?= $receipt['bill_type'] ?></div>
                    <div class="dl-label">Due Date</div><div class="dl-value text-danger"><?= $receipt['due_date'] ?></div>
                </div>
            </div>

            <!-- Payment Details -->
            <div class="meta-card">
                <div class="card-header">
                    <div class="card-icon"><i class='bx bx-credit-card-front'></i></div>
                    <div class="card-title">PAYMENT DETAILS</div>
                </div>
                <div class="dl-grid">
                    <div class="dl-label">Payment ID</div><div class="dl-value"><?= $receipt['payment_id'] ?></div>
                    <div class="dl-label">Payment Date</div><div class="dl-value"><?= $receipt['date'] ?></div>
                    <div class="dl-label">Payment Method</div><div class="dl-value"><?= $receipt['payment_method'] ?></div>
                    <div class="dl-label">Transaction ID (UTR)</div><div class="dl-value"><?= $receipt['utr'] ?></div>
                </div>
            </div>
        </div>

        <!-- Total Box -->
        <div class="total-section">
            <div class="ts-left">
                <h4>TOTAL AMOUNT PAID</h4>
                <h2>₹<?= $receipt['total_amount'] ?></h2>
                <p><?= $receipt['amount_words'] ?></p>
            </div>
            <div class="ts-right">
                <div class="ts-divider"></div>
                <i class='bx bx-check-circle'></i>
                <div class="ts-r-text">
                    <h4>PAYMENT STATUS</h4>
                    <div class="status-pill">SUCCESSFULLY PAID</div>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="table-section">
            <div class="table-title">CHARGES SUMMARY</div>
            <table class="receipt-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>PARTICULARS</th>
                        <th>AMOUNT (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($receipt['charges'] as $i => $ch): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td <?= isset($ch['is_red']) ? 'class="text-danger"' : '' ?>><?= $ch['particular'] ?></td>
                        <td <?= isset($ch['is_red']) ? 'class="text-danger"' : '' ?>><?= $ch['amount'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="table-footer">
                <span>Total Amount Paid</span>
                <h3>₹<?= $receipt['total_amount'] ?></h3>
            </div>
        </div>

        <!-- Payment Method Details -->
        <div class="payment-method-box">
            <div class="pm-title">PAYMENT METHOD DETAILS</div>
            <div class="pm-logo">
                UPI<span>&#9658;</span>
                <div style="font-size: 8px; font-weight: 500; font-style: normal; color: #666;">UNIFIED PAYMENTS INTERFACE</div>
            </div>
            <div class="pm-details">
                <div class="pm-item">
                    <h5>UPI ID</h5>
                    <p><?= $receipt['upi_id'] ?></p>
                </div>
                <div class="pm-item">
                    <h5>Bank Name</h5>
                    <p><?= $receipt['bank_name'] ?></p>
                </div>
                <div class="pm-item">
                    <h5>Account Holder</h5>
                    <p><?= $receipt['account_holder'] ?></p>
                </div>
            </div>
            <div class="pm-status">
                <i class='bx bxs-check-circle'></i>
                <p>Your payment has been received successfully. Thank you!</p>
            </div>
        </div>

        <!-- Footer Info -->
        <div class="footer-info">
            <div class="fi-item">
                <div class="fi-icon"><i class='bx bx-shield-check'></i></div>
                <div class="fi-text">
                    <h4>Secure Payment</h4>
                    <p>This is a system generated receipt and does not require signature.</p>
                </div>
            </div>
            <div class="fi-item">
                <div class="fi-icon"><i class='bx bx-headphone'></i></div>
                <div class="fi-text">
                    <h4>Need Help?</h4>
                    <p><?= $receipt['phone'] ?><br>Mon - Sat (10:00 AM - 6:00 PM)</p>
                </div>
            </div>
            <div class="fi-item" onclick="window.print()">
                <div class="fi-icon"><i class='bx bx-download'></i></div>
                <div class="fi-text">
                    <h4>Download Receipt</h4>
                    <p>You can download this receipt for your records.</p>
                </div>
            </div>
        </div>

        <div class="bottom-thank">
            <div class="bottom-thank-inner">
                <h2>Thank you for being a valued resident!</h2>
            </div>
            <h3>Madhav Kunj Residence</h3>
        </div>

    </div>

</body>
</html>

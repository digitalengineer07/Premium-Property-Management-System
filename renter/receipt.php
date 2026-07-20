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
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }

        .logo-icon {
            width: 50px;
            height: 50px;
            background: var(--primary);
            color: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
        }

        .logo-text h1 {
            font-size: 20px;
            font-weight: 800;
            color: var(--text-dark);
            line-height: 1.2;
            letter-spacing: 1px;
        }

        .logo-text h1 span {
            display: block;
            font-size: 11px;
            color: var(--text-gray);
            font-weight: 500;
            letter-spacing: 4px;
        }

        .address-info {
            font-size: 13px;
            color: var(--text-gray);
            line-height: 1.6;
            margin-bottom: 12px;
        }

        .contact-info {
            display: flex;
            align-items: center;
            gap: 16px;
            font-size: 13px;
            color: var(--text-dark);
            font-weight: 500;
        }

        .contact-info i {
            color: var(--primary);
            font-size: 16px;
        }
        
        .contact-info span {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .h-center .badge-icon {
            width: 60px;
            height: 60px;
            background: var(--primary-light);
            color: var(--primary);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            margin-bottom: 12px;
        }

        .h-center h2 {
            font-size: 24px;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }

        .h-center p {
            font-size: 14px;
            color: var(--text-gray);
        }
        
        .h-center .dash-line {
            width: 40px;
            height: 3px;
            background: var(--primary);
            margin: 12px auto 0;
            border-radius: 2px;
        }

        .stamp {
            width: 140px;
            height: 140px;
            border: 4px solid var(--success);
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: var(--success);
            font-weight: 800;
            transform: rotate(-15deg);
            margin-bottom: 20px;
            position: relative;
        }
        
        .stamp::before {
            content: '';
            position: absolute;
            top: 5px; left: 5px; right: 5px; bottom: 5px;
            border: 1px dashed var(--success);
            border-radius: 50%;
        }

        .stamp .s-top { font-size: 14px; letter-spacing: 2px; margin-bottom: 4px; }
        .stamp .s-main { font-size: 32px; background: var(--success); color: white; padding: 2px 20px; border-radius: 4px; z-index: 1; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3); }
        .stamp .s-bottom { font-size: 11px; letter-spacing: 1px; margin-top: 4px; }

        .receipt-date {
            text-align: right;
        }
        
        .receipt-date p {
            font-size: 12px;
            color: var(--text-dark);
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 6px;
            margin-bottom: 4px;
        }
        
        .receipt-date p i {
            color: var(--primary);
            font-size: 16px;
        }

        .receipt-date span {
            font-size: 14px;
            color: var(--text-gray);
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
                    <div class="logo-icon">
                        <i class='bx bx-building-house'></i>
                    </div>
                    <div>
                        <h1>MADHAV KUNJ<br><span>RESIDENCE</span></h1>
                    </div>
                </div>
                <div class="address-info">
                    Madhav Kunj Society, Near Green Park,<br>
                    Indore, Madhya Pradesh - 452001
                </div>
                <div class="contact-info">
                    <span><i class='bx bxs-phone'></i> +91 98765 43210</span>
                    <span style="color: var(--border);">|</span>
                    <span><i class='bx bx-envelope'></i> madhavkunj@example.com</span>
                </div>
            </div>

            <div class="h-center">
                <div class="badge-icon"><i class='bx bxs-award'></i></div>
                <h2>PAYMENT RECEIPT</h2>
                <p>Thank you for your payment!</p>
                <div class="dash-line"></div>
            </div>

            <div class="h-right">
                <div class="stamp">
                    <div class="s-top">PAYMENT</div>
                    <div class="s-main">PAID</div>
                    <div class="s-bottom">SUCCESSFUL</div>
                </div>
                <div class="receipt-date">
                    <p><i class='bx bx-calendar'></i> Receipt Date</p>
                    <span><?= $receipt['date'] ?></span>
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

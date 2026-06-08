<?php
// admin/bill-generator.php - Light SaaS UI
require_once "../db.php";
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

// Fetch all renters for dropdown
$renters_query = mysqli_query($conn, "SELECT id, name, room_no, phone FROM users ORDER BY name ASC");
$renters = [];
while ($row = mysqli_fetch_assoc($renters_query)) {
    $renters[] = $row;
}

$admin_user = s($_SESSION['admin']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Bill Generator | <?php echo HOUSE_NAME; ?></title>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-design-system.css?v=2.0">
    <!-- Cropper.js CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
    <style>
        /* Layout Overhaul for Centering */
        .bill-page {
            max-width: 900px;
            margin: 0 auto;
            padding: 0 24px 40px;
            box-sizing: border-box;
        }

        .bill-grid {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .aesthetic-card {
            background: var(--white);
            border-radius: 24px;
            padding: 32px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.03);
            border: 1px solid var(--border);
            position: relative;
            margin-bottom: 24px;
        }

        .aesthetic-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--primary-purple);
            border-radius: 24px 24px 0 0;
        }

        .panel-header {
            border-bottom: 1px solid var(--border);
            padding-bottom: 16px;
            margin-bottom: 24px;
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 18px;
            font-weight: 700;
            color: var(--text-dark);
        }

        .section-title i {
            font-size: 22px;
            color: var(--primary-purple);
            background: rgba(98, 75, 255, 0.1);
            padding: 8px;
            border-radius: 12px;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            font-size: 12px;
            color: var(--text-gray);
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 14px 18px;
            border: 1.5px solid var(--border);
            border-radius: 14px;
            background: var(--bg-main);
            color: var(--text-dark);
            outline: none;
            transition: var(--transition);
            font-size: 15px;
            font-weight: 500;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: var(--primary-purple);
            background: var(--white);
            box-shadow: 0 0 0 4px rgba(98, 75, 255, 0.08);
        }

        .info-pill {
            background: #F8F9FA;
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 20px;
            display: none;
        }

        .info-pill.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        :root.dark-theme .info-pill {
            background: rgba(255, 255, 255, 0.02);
        }

        /* Premium Custom Select Component */
        .custom-select-wrapper {
            position: relative;
            user-select: none;
            width: 100%;
        }

        .custom-select-trigger {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 18px;
            border: 1.5px solid var(--border);
            border-radius: 14px;
            background: var(--bg-main);
            color: var(--text-dark);
            cursor: pointer;
            font-size: 15px;
            font-weight: 500;
            transition: var(--transition);
        }

        .custom-select-trigger:hover,
        .custom-select.open .custom-select-trigger {
            border-color: var(--primary-purple);
            background: var(--white);
            box-shadow: 0 0 0 4px rgba(98, 75, 255, 0.08);
        }

        .custom-options-container {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            margin-top: 8px;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 14px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.2s ease;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .custom-select.open .custom-options-container {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .custom-search-box {
            padding: 12px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 8px;
            background: var(--bg-main);
            flex-shrink: 0;
        }

        .custom-search-box i {
            color: var(--text-gray);
            font-size: 18px;
        }

        .custom-search-box input {
            border: none;
            background: transparent;
            outline: none;
            width: 100%;
            color: var(--text-dark);
            font-size: 14px;
        }

        .custom-options-list {
            max-height: 250px;
            overflow-y: auto;
        }

        .custom-option {
            padding: 12px 18px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            transition: all 0.2s ease;
            border-bottom: 1px solid rgba(0, 0, 0, 0.02);
            border-left: 3px solid transparent;
        }

        .custom-option:last-child {
            border-bottom: none;
        }

        .custom-option:hover {
            background: rgba(98, 75, 255, 0.08);
            border-left-color: var(--primary-purple);
            padding-left: 24px;
        }

        .custom-option.selected {
            background: rgba(98, 75, 255, 0.1);
            border-left-color: var(--primary-purple);
        }

        .custom-option.selected .opt-name {
            color: var(--primary-purple);
        }

        .opt-name {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-dark);
            transition: color 0.15s;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            flex: 1;
        }

        .opt-room {
            font-size: 12px;
            color: var(--text-gray);
            background: rgba(0, 0, 0, 0.04);
            padding: 3px 8px;
            border-radius: 10px;
            flex-shrink: 0;
            white-space: nowrap;
        }

        :root.dark-theme .custom-options-container {
            background: #1E293B;
            border-color: #334155;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        :root.dark-theme .custom-search-box {
            background: #0F172A;
            border-color: #334155;
        }

        :root.dark-theme .custom-option {
            border-bottom-color: rgba(255, 255, 255, 0.02);
        }

        :root.dark-theme .custom-option.selected {
            background: rgba(98, 75, 255, 0.15);
        }

        :root.dark-theme .opt-name {
            color: #F8FAFC;
        }

        :root.dark-theme .opt-room {
            background: rgba(255, 255, 255, 0.1);
            color: #CBD5E1;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(5px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* The Digital Wallet Card */
        .wallet-card {
            background: var(--primary-purple);
            border-radius: 28px;
            padding: 32px 28px;
            color: #fff;
            box-shadow: 0 10px 30px rgba(59, 130, 246, 0.2);
            position: relative;
            overflow: hidden;
            margin-bottom: 24px;
        }

        .wallet-card::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 250px;
            height: 250px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.2) 0%, transparent 70%);
            border-radius: 50%;
        }

        .wallet-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            position: relative;
            z-index: 1;
        }

        .wallet-header p {
            margin: 0 0 6px 0;
            font-size: 11px;
            opacity: 0.85;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }

        .wallet-header h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
            line-height: 1.3;
        }

        .wallet-total {
            margin-bottom: 30px;
            position: relative;
            z-index: 1;
        }

        .wallet-total p {
            margin: 0 0 8px 0;
            font-size: 12px;
            opacity: 0.85;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }

        .wallet-total h1 {
            margin: 0;
            font-size: 46px;
            font-weight: 800;
            letter-spacing: -1.5px;
            text-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .wallet-details {
            background: rgba(255, 255, 255, 0.15);
            border-radius: 20px;
            padding: 24px;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            position: relative;
            z-index: 1;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .wallet-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 14px;
            font-size: 14px;
        }

        .wallet-row:last-child {
            margin-bottom: 0;
        }

        .wallet-row span:first-child {
            opacity: 0.9;
            font-weight: 500;
        }

        .wallet-row span:last-child {
            font-weight: 700;
        }

        .wallet-divider {
            height: 1px;
            background: rgba(255, 255, 255, 0.2);
            margin: 16px 0;
        }

        .section-divider {
            margin-bottom: 24px;
            padding-top: 24px;
            border-top: 1px dashed var(--border);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-divider h4 {
            font-size: 13px;
            color: var(--primary-purple);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0;
            font-weight: 800;
        }

        .section-divider i {
            font-size: 18px;
            color: var(--primary-purple);
        }

        .section-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.25);
        }

        @media (max-width: 768px) {
            .aesthetic-card {
                padding: 20px;
            }

            .btn-primary,
            .btn-outline {
                width: 100% !important;
                justify-content: center !important;
            }

            .welcome h1 {
                font-size: 24px;
            }

            .form-grid {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
</head>

<body>

    <?php include "sidebar.php"; ?>

    <main class="main">
        <header class="header">
            <div class="header-content">
                <div class="search-bar">
                    <i class='bx bx-search'></i>
                    <input type="text" placeholder="Search billing details...">
                </div>
                <div class="user-profile">
                    <i class='bx bx-moon' id="themeToggle"></i>
                </div>
            </div>
        </header>

        <div id="alertBox"
            style="position: fixed; top: 20px; right: 20px; z-index: 10000; padding: 15px 25px; border-radius: 12px; display: none;"
            class="animate-up"></div>

        <div class="bill-page animate-up">
            <div class="welcome" style="margin-bottom: 32px;">
                <h1>Create New Bill</h1>
                <p>Generate a professional utility bill for your renters</p>
            </div>

            <div class="bill-grid">
                <div class="left-col">
                    <div class="aesthetic-card animate-up" style="margin-bottom: 24px; z-index: 50;">
                        <div class="panel-header">
                            <div class="section-title">
                                <i class='bx bx-user'></i>
                                <span>Select Resident Account</span>
                            </div>
                        </div>
                        <div class="form-grid"
                            style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px; align-items: end;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label>Choose Account</label>
                                <div class="custom-select-wrapper">
                                    <div class="custom-select" id="customRenterSelect">
                                        <div class="custom-select-trigger">
                                            <span id="customSelectText">-- Choose a Resident --</span>
                                            <i class='bx bx-chevron-down'></i>
                                        </div>
                                        <div class="custom-options-container">
                                            <div class="custom-search-box">
                                                <i class='bx bx-search'></i>
                                                <input type="text" id="customSelectSearch"
                                                    placeholder="Search resident...">
                                            </div>
                                            <div class="custom-options-list">
                                                <div class="custom-option" data-value="">-- Choose a Resident --</div>
                                                <?php foreach ($renters as $renter): ?>
                                                    <div class="custom-option" data-value="<?php echo $renter['id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($renter['name']); ?>"
                                                        data-room="<?php echo htmlspecialchars($renter['room_no']); ?>">
                                                        <div class="opt-name">
                                                            <?php echo htmlspecialchars($renter['name']); ?></div>
                                                        <div class="opt-room">
                                                            <?php echo htmlspecialchars($renter['room_no']); ?></div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Hidden native select to preserve backend logic -->
                                <select id="renterSelect" onchange="loadRenterInfo()" style="display: none;">
                                    <option value="">-- Choose a Resident --</option>
                                    <?php foreach ($renters as $renter): ?>
                                        <option value="<?php echo $renter['id']; ?>"
                                            data-name="<?php echo htmlspecialchars($renter['name']); ?>"
                                            data-room="<?php echo htmlspecialchars($renter['room_no']); ?>"
                                            data-phone="<?php echo htmlspecialchars($renter['phone']); ?>">
                                            <?php echo htmlspecialchars($renter['name']); ?> - Room
                                            <?php echo htmlspecialchars($renter['room_no']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div id="renterInfo" class="info-pill" style="margin-top: 0; padding: 15px 20px;">
                                <div style="display: flex; justify-content: space-between; gap: 15px; flex-wrap: wrap;">
                                    <div><small style="color:var(--text-gray)">Name</small>
                                        <div id="infoName" style="font-weight:600; font-size: 15px;">--</div>
                                    </div>
                                    <div><small style="color:var(--text-gray)">Room</small>
                                        <div id="infoRoom" style="font-weight:600; font-size: 15px;">--</div>
                                    </div>
                                    <div><small style="color:var(--text-gray)">Previous Reading</small>
                                        <div id="infoLastReading"
                                            style="font-weight:700; color:var(--primary-purple); font-size: 15px;">
                                            Loading...</div>
                                    </div>
                                    <div><small style="color:var(--text-gray)">Pending Balance</small>
                                        <div id="infoBalance" style="font-weight:700; font-size: 15px;">--</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="aesthetic-card">
                        <div class="panel-header">
                            <div class="section-title">
                                <i class='bx bx-bolt'></i>
                                <span>Electricity Details</span>
                            </div>
                        </div>
                        <!-- Main Form Grid: Clean 2-Column Layout -->
                        <div class="form-grid"
                            style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label>Bill Date</label>
                                <input type="date" id="billDate">
                            </div>

                            <div class="form-group" style="margin-bottom: 0;">
                                <label>Billing Month</label>
                                <div style="display: flex; gap: 10px;">
                                    <select id="selectMonth" onchange="updateMonthField()" style="flex: 1.5;">
                                        <option value="01">January</option>
                                        <option value="02">February</option>
                                        <option value="03">March</option>
                                        <option value="04">April</option>
                                        <option value="05">May</option>
                                        <option value="06">June</option>
                                        <option value="07">July</option>
                                        <option value="08">August</option>
                                        <option value="09">September</option>
                                        <option value="10">October</option>
                                        <option value="11">November</option>
                                        <option value="12">December</option>
                                    </select>
                                    <select id="selectYear" onchange="updateMonthField()" style="flex: 1;">
                                        <option value="2024">2024</option>
                                        <option value="2025">2025</option>
                                        <option value="2026">2026</option>
                                        <option value="2027">2027</option>
                                    </select>
                                </div>
                                <input type="hidden" id="billMonth">
                            </div>
                        </div>

                        <!-- Readings Section -->
                        <div class="section-divider">
                            <i class='bx bx-tachometer'></i>
                            <h4>Meter Readings</h4>
                        </div>

                        <div class="form-grid"
                            style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label>Last Reading</label>
                                <input type="number" id="previousReading" disabled
                                    style="background: var(--bg-main); border-color: transparent; cursor: not-allowed; color: var(--text-gray); font-weight: 600;">
                            </div>

                            <div class="form-group" style="margin-bottom: 0;">
                                <label>Current Reading <span style="color: #EF4444;">*</span></label>
                                <input type="number" id="currentReading" oninput="calculateBill()" placeholder="0"
                                    style="border-color: var(--primary-purple); box-shadow: 0 0 0 2px rgba(98, 75, 255, 0.05); font-weight: 600;">
                            </div>

                            <div class="form-group" style="margin-bottom: 0;">
                                <label>Rate (₹) / Unit</label>
                                <input type="number" id="ratePerUnit" value="<?php echo DEFAULT_RATE; ?>"
                                    oninput="calculateBill()">
                            </div>

                            <div class="form-group" style="margin-bottom: 0;">
                                <label>Past Dues (₹)</label>
                                <input type="number" id="dues" placeholder="0" oninput="calculateBill()">
                            </div>
                        </div>

                        <!-- Fixed Charges Section -->
                        <div class="section-divider">
                            <i class='bx bx-home-heart'></i>
                            <h4>Fixed Charges</h4>
                        </div>

                        <div class="form-grid"
                            style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label>Rent (₹)</label>
                                <input type="number" id="rentAmount" placeholder="0" oninput="calculateBill()">
                            </div>

                            <div class="form-group" style="margin-bottom: 0;">
                                <label>Maintenance (₹)</label>
                                <input type="number" id="maintenance" placeholder="0" oninput="calculateBill()">
                            </div>
                        </div>

                        <!-- Extra Charges Section -->
                        <div class="section-divider">
                            <i class='bx bx-slider-alt'></i>
                            <h4>Extra Adjustments</h4>
                        </div>

                        <div class="form-grid"
                            style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label>Extra Amount (₹)</label>
                                <input type="number" id="extraCharges" placeholder="0" oninput="calculateBill()">
                            </div>

                            <div class="form-group" style="margin-bottom: 0;">
                                <label>Extra Details / Reason</label>
                                <input type="text" id="extraChargesDesc" placeholder="e.g., Plumber fix"
                                    oninput="calculateBill()">
                            </div>
                        </div>

                        <!-- Attachment -->
                        <div class="section-divider" style="border-top-style: solid;">
                            <i class='bx bx-camera'></i>
                            <h4>Meter Screenshot</h4>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label>Attach Image (Optional)</label>
                            <input type="file" id="meterScreenshot" accept="image/*"
                                style="padding: 14px; border: 1.5px dashed var(--border); border-radius: 12px; height: auto; width: 100%; cursor: pointer; background: var(--bg-main);">
                        </div>
                    </div>
                </div>

                <div class="right-col bill-summary">
                    <!-- Digital Wallet Layout -->
                    <div class="wallet-card">
                        <div class="wallet-header">
                            <div style="flex: 1; padding-right: 15px;">
                                <p>Resident Info</p>
                                <h3 id="receiptRenter">Not Selected</h3>
                            </div>
                            <div style="text-align: right; flexShrink: 0;">
                                <p>Billing Cycle</p>
                                <h3 id="receiptMonthYear">--</h3>
                            </div>
                        </div>

                        <div class="test-fix" style="display:none;"></div>

                        <div class="wallet-total">
                            <p>Total Payable</p>
                            <h1 id="calcTotal">₹0</h1>
                        </div>

                        <div class="wallet-details">
                            <div class="wallet-row"><span>Units Consumed</span><span id="calcUnits">0</span></div>
                            <div class="wallet-row"><span>Electricity Cost</span><span id="calcElectricity">₹0</span>
                            </div>
                            <div class="wallet-divider"></div>
                            <div class="wallet-row"><span>Standard Rent</span><span id="calcRent">₹0</span></div>
                            <div class="wallet-row"><span>Maintenance</span><span id="calcMaintenance">₹0</span></div>
                            <div class="wallet-divider"></div>
                            <div class="wallet-row"><span>Arrears/Dues</span><span id="calcDues">₹0</span></div>
                            <div class="wallet-row" id="extraChargesDiv" style="display: none;"><span>Extra
                                    Charges</span><span id="calcExtraCharges">₹0</span></div>
                        </div>
                    </div>

                    <button class="btn-primary"
                        style="width: 100%; margin-top: 10px; display: flex; justify-content: center; align-items: center; gap: 8px; font-size: 15px; padding: 14px; background: #624BFF; border: none; box-shadow: 0 4px 15px rgba(98, 75, 255, 0.3);"
                        onclick="generateBill()">
                        <i class='bx bx-printer'></i> Generate & Print
                    </button>
                    <button class="btn-outline"
                        style="width: 100%; margin-top: 12px; display: flex; justify-content: center; align-items: center; gap: 8px; border: 1.5px solid var(--border);"
                        onclick="resetForm()">
                        <i class='bx bx-refresh'></i> Reset Form
                    </button>
                </div>
            </div> <!-- End .bill-grid -->
        </div> <!-- End .bill-page -->

        <!-- Crop Modal -->
        <div id="cropModal"
            style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 10000; flex-direction: column; align-items: center; justify-content: center; padding: 20px;">
            <div class="panel"
                style="max-width: 90%; width: 600px; height: 80vh; display: flex; flex-direction: column; padding: 0; overflow: hidden; border-radius: 20px;">
                <div
                    style="padding: 15px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="font-size: 16px; font-weight: 700;">Crop Meter Display</h3>
                    <button type="button" onclick="closeCropModal()"
                        style="background: none; border: none; color: var(--text-gray); cursor: pointer; font-size: 24px;"><i
                            class='bx bx-x'></i></button>
                </div>
                <div
                    style="flex: 1; background: #000; position: relative; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                    <img id="cropImage" src="" style="max-width: 100%; max-height: 100%;">
                </div>
                <div style="padding: 15px; background: var(--white); border-top: 1px solid var(--border);">
                    <div
                        style="display: flex; justify-content: center; gap: 15px; margin-bottom: 15px; flex-wrap: wrap;">
                        <button type="button" onclick="cropper.rotate(-90)" class="btn-outline" style="padding: 8px;"><i
                                class='bx bx-rotate-left'></i> -90°</button>
                        <button type="button" onclick="cropper.rotate(90)" class="btn-outline" style="padding: 8px;"><i
                                class='bx bx-rotate-right'></i> +90°</button>
                        <button type="button" onclick="cropper.zoom(0.1)" class="btn-outline" style="padding: 8px;"><i
                                class='bx bx-zoom-in'></i></button>
                        <button type="button" onclick="cropper.zoom(-0.1)" class="btn-outline" style="padding: 8px;"><i
                                class='bx bx-zoom-out'></i></button>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <button type="button" onclick="closeCropModal()" class="btn-outline"
                            style="flex: 1; justify-content: center;">Cancel</button>
                        <button type="button" onclick="saveCrop()" class="btn-primary"
                            style="flex: 2; justify-content: center; background: #10B981;">Confirm Crop</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cropper.js Script -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
    </main>

    <script>
        // Custom Select Logic Initialization
        document.addEventListener('DOMContentLoaded', () => {
            const customSelect = document.getElementById('customRenterSelect');
            const trigger = customSelect.querySelector('.custom-select-trigger');
            const options = customSelect.querySelectorAll('.custom-option');
            const searchInput = document.getElementById('customSelectSearch');
            const hiddenSelect = document.getElementById('renterSelect');
            const selectText = document.getElementById('customSelectText');

            trigger.addEventListener('click', () => {
                customSelect.classList.toggle('open');
                if (customSelect.classList.contains('open')) {
                    searchInput.focus();
                }
            });

            options.forEach(option => {
                option.addEventListener('click', function () {
                    options.forEach(opt => opt.classList.remove('selected'));
                    this.classList.add('selected');

                    const val = this.getAttribute('data-value');
                    if (val === '') {
                        selectText.textContent = '-- Choose a Resident --';
                    } else {
                        selectText.textContent = this.querySelector('.opt-name').textContent + ' - Room ' + this.querySelector('.opt-room').textContent;
                    }

                    // Sync with hidden select
                    hiddenSelect.value = val;
                    hiddenSelect.dispatchEvent(new Event('change'));

                    customSelect.classList.remove('open');
                    searchInput.value = '';
                    filterOptions('');
                });
            });

            searchInput.addEventListener('input', (e) => {
                filterOptions(e.target.value.toLowerCase());
            });

            function filterOptions(searchTerm) {
                options.forEach(option => {
                    if (option.getAttribute('data-value') === '') return;
                    const name = option.getAttribute('data-name').toLowerCase();
                    const room = option.getAttribute('data-room').toLowerCase();
                    if (name.includes(searchTerm) || room.includes(searchTerm)) {
                        option.style.display = 'flex';
                    } else {
                        option.style.display = 'none';
                    }
                });
            }

            // Close on click outside
            document.addEventListener('click', (e) => {
                if (!customSelect.contains(e.target)) {
                    customSelect.classList.remove('open');
                }
            });
        });

        // Init dates
        document.getElementById('billDate').valueAsDate = new Date();
        const now = new Date();
        const prevMonthDate = new Date(now.getFullYear(), now.getMonth() - 1, 1);
        const prevMonth = String(prevMonthDate.getMonth() + 1).padStart(2, '0');
        const prevYear = String(prevMonthDate.getFullYear());

        document.getElementById('selectMonth').value = prevMonth;
        document.getElementById('selectYear').value = prevYear;
        updateMonthField();

        let cropper = null;
        let croppedBlob = null;
        let originalFile = null;

        // Handle File Input
        document.getElementById('meterScreenshot').addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (!file) return;

            originalFile = file;
            const reader = new FileReader();
            reader.onload = function (event) {
                openCropModal(event.target.result);
            };
            reader.readAsDataURL(file);
        });

        function openCropModal(src) {
            const modal = document.getElementById('cropModal');
            const image = document.getElementById('cropImage');
            image.src = src;
            modal.style.display = 'flex';

            if (cropper) cropper.destroy();

            cropper = new Cropper(image, {
                aspectRatio: NaN, // Free crop for meter display
                viewMode: 2,
                autoCropArea: 0.8,
                guides: true,
                highlight: true,
                dragMode: 'move',
                cropBoxMovable: true,
                cropBoxResizable: true,
                toggleDragModeOnDblclick: true,
            });
        }

        function closeCropModal() {
            document.getElementById('cropModal').style.display = 'none';
            if (!croppedBlob) {
                document.getElementById('meterScreenshot').value = '';
            }
        }

        function saveCrop() {
            if (!cropper) return;

            // Get high quality crop
            const canvas = cropper.getCroppedCanvas({
                maxWidth: 2048,
                maxHeight: 2048,
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'high',
            });

            canvas.toBlob((blob) => {
                croppedBlob = blob;
                document.getElementById('cropModal').style.display = 'none';
                showMsg('Meter area cropped successfully!', 'success');

                // OCR Suggestion (Optional Mock)
                // runOCR(blob);
            }, 'image/jpeg', 0.9);
        }

        function updateMonthField() {
            const m = document.getElementById('selectMonth').value;
            const y = document.getElementById('selectYear').value;
            document.getElementById('billMonth').value = `${y}-${m}`;

            // Update Receipt preview
            const monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
            const mText = monthNames[parseInt(m) - 1];
            document.getElementById('receiptMonthYear').textContent = `${mText} ${y}`;
        }

        let selectedRenterId = null;

        async function loadRenterInfo() {
            const select = document.getElementById('renterSelect');
            const renterId = select.value;
            if (!renterId) {
                document.getElementById('renterInfo').classList.remove('active');
                document.getElementById('receiptRenter').textContent = `Not Selected`;
                return;
            }
            selectedRenterId = renterId;
            const option = select.options[select.selectedIndex];
            document.getElementById('infoName').textContent = option.dataset.name;
            document.getElementById('infoRoom').textContent = option.dataset.room;
            document.getElementById('renterInfo').classList.add('active');

            // Update Receipt Preview
            document.getElementById('receiptRenter').textContent = `${option.dataset.name} (Rm ${option.dataset.room})`;

            try {
                const res = await fetch(`get-last-reading.php?user_id=${renterId}`);
                const data = await res.json();
                const lastReading = data.last_reading || '0';
                document.getElementById('infoLastReading').textContent = lastReading;

                // Handle Pending Adjustment
                const adj = data.pending_adjustment || 0;
                const balanceDiv = document.getElementById('infoBalance');
                if (adj < 0) {
                    balanceDiv.textContent = '₹' + Math.abs(adj) + ' (Remaining)';
                    balanceDiv.style.color = '#FCA5A5';
                } else if (adj > 0) {
                    balanceDiv.textContent = '₹' + adj + ' (Extra)';
                    balanceDiv.style.color = '#6EE7B7';
                } else {
                    balanceDiv.textContent = '₹0';
                    balanceDiv.style.color = 'rgba(255,255,255,0.7)';
                }

                // Auto-fill Dues field (Dues = -Adjustment)
                // If adj is -2000 (Remaining), dues = 2000
                // If adj is 2000 (Extra), dues = -2000
                document.getElementById('dues').value = adj === 0 ? '' : -adj;

                const prevInput = document.getElementById('previousReading');
                prevInput.value = lastReading;

                // Auto fill Rent and Maintenance if missing or keep previous values
                document.getElementById('rentAmount').value = data.fixed_rent > 0 ? data.fixed_rent : '';
                document.getElementById('maintenance').value = data.fixed_maintenance > 0 ? data.fixed_maintenance : '';

                if (data.is_base) {
                    prevInput.disabled = false;
                } else {
                    prevInput.disabled = true;
                }
            } catch (e) { console.error(e); }
            calculateBill();
        }

        function calculateBill() {
            const prev = parseFloat(document.getElementById('previousReading').value) || 0;
            const curr = parseFloat(document.getElementById('currentReading').value) || 0;
            const rate = parseFloat(document.getElementById('ratePerUnit').value) || 0;
            const rent = parseFloat(document.getElementById('rentAmount').value) || 0;
            const maint = parseFloat(document.getElementById('maintenance').value) || 0;
            const dues = parseFloat(document.getElementById('dues').value) || 0;
            const extra = parseFloat(document.getElementById('extraCharges').value) || 0;

            const units = Math.max(0, curr - prev);
            const elecCost = units * rate;
            const total = elecCost + rent + maint + dues + extra;

            document.getElementById('calcUnits').textContent = units;
            document.getElementById('calcElectricity').textContent = '₹' + Math.round(elecCost).toLocaleString();
            document.getElementById('calcRent').textContent = '₹' + rent.toLocaleString();
            document.getElementById('calcMaintenance').textContent = '₹' + maint.toLocaleString();
            document.getElementById('calcDues').textContent = '₹' + dues.toLocaleString();

            if (extra > 0) {
                document.getElementById('extraChargesDiv').style.display = 'flex';
                document.getElementById('calcExtraCharges').textContent = '₹' + Math.round(extra).toLocaleString();
            } else {
                document.getElementById('extraChargesDiv').style.display = 'none';
            }

            document.getElementById('calcTotal').textContent = '₹' + Math.round(total).toLocaleString();
        }

        async function generateBill() {
            if (!selectedRenterId) { showMsg('Please select a renter', 'error'); return; }
            const curr = document.getElementById('currentReading').value;
            if (!curr) { showMsg('Enter current reading', 'error'); return; }

            const fd = new FormData();
            fd.append('csrf', '<?php echo getCsrfToken(); ?>');
            fd.append('user_id', selectedRenterId);
            fd.append('bill_date', document.getElementById('billDate').value);
            fd.append('bill_month', document.getElementById('billMonth').value);
            fd.append('previous_reading', document.getElementById('previousReading').value);
            fd.append('current_reading', curr);
            fd.append('rate_per_unit', document.getElementById('ratePerUnit').value);
            fd.append('rent_amount', document.getElementById('rentAmount').value);
            fd.append('maintenance', document.getElementById('maintenance').value);
            fd.append('dues', document.getElementById('dues').value);
            fd.append('extra_charges_desc', document.getElementById('extraChargesDesc').value);
            fd.append('extra_charges', document.getElementById('extraCharges').value);

            const screenshotFile = document.getElementById('meterScreenshot').files[0];
            if (originalFile) {
                fd.append('meter_original', originalFile);
            }
            if (croppedBlob) {
                fd.append('meter_crop', croppedBlob, 'meter_crop.jpg');
            }

            try {
                const res = await fetch('save-bill.php', { method: 'POST', body: fd });
                const text = await res.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error("Server raw response:", text);
                    showMsg('Server Error: Invalid response format. Check console.', 'error');
                    return;
                }

                if (data.success) {
                    showMsg('Bill generated successfully!', 'success');
                    // Refresh the "Last Reading" display without redirecting
                    document.getElementById('infoLastReading').textContent = curr;
                    document.getElementById('previousReading').value = curr;
                    document.getElementById('currentReading').value = '';

                    // Optionally add a "View Slip" link in the success message area
                    const slipLink = document.createElement('a');
                    slipLink.href = 'slip.php?elec_id=' + data.bill_id;
                    slipLink.target = '_blank';
                    slipLink.textContent = ' [View PDF Slip]';
                    slipLink.style.color = '#fff';
                    slipLink.style.fontWeight = 'bold';
                    slipLink.style.marginLeft = '10px';
                    document.getElementById('alertBox').appendChild(slipLink);

                    calculateBill();
                } else {
                    showMsg(data.message || 'Error saving bill', 'error');
                }
            } catch (e) {
                console.error(e);
                showMsg('Network error: Unable to connect to server.', 'error');
            }
        }

        function showMsg(m, t) {
            const box = document.getElementById('alertBox');
            box.textContent = m;
            box.style.display = 'block';
            box.style.background = (t == 'success') ? '#10B981' : '#EF4444';
            box.style.color = 'white';
            setTimeout(() => { box.style.display = 'none'; }, 4000);
        }

        function resetForm() {
            document.getElementById('renterSelect').value = '';
            document.getElementById('renterInfo').classList.remove('active');
            document.getElementById('currentReading').value = '';
            document.getElementById('previousReading').value = '';
            document.getElementById('rentAmount').value = '';
            document.getElementById('maintenance').value = '';
            document.getElementById('dues').value = '';
            document.getElementById('meterScreenshot').value = '';
            document.getElementById('extraCharges').value = '';
            document.getElementById('extraChargesDesc').value = '';
            calculateBill();
        }
    </script>
</body>

</html>
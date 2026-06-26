<?php
// admin/reports.php
session_start();
require_once "../db.php";

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

$admin_user = htmlspecialchars($_SESSION['admin'], ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics & Reports | <?php echo HOUSE_NAME; ?></title>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-design-system.css?v=<?php echo time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* SaaS Dashboard Core Styles */
        body { font-family: 'Inter', sans-serif; background-color: #F8FAFC; color: #0F172A; margin: 0; padding: 0; }
        
        /* Typography */
        .page-title { font-size: 24px; font-weight: 800; color: #0F172A; margin-bottom: 4px; letter-spacing: -0.5px; white-space: nowrap; }
        .page-subtitle { font-size: 13px; font-weight: 500; color: #64748B; margin-bottom: 0; white-space: nowrap; }
        .section-title { font-size: 16px; font-weight: 700; color: #0F172A; margin: 0; }
        
        /* Grid System */
        .saas-grid { display: grid; grid-template-columns: repeat(12, 1fr); gap: 24px; margin-bottom: 24px; }
        .col-12 { grid-column: span 12; }
        .col-8 { grid-column: span 8; }
        .col-6 { grid-column: span 6; }
        .col-4 { grid-column: span 4; }
        .col-3 { grid-column: span 3; }
        
        @media (max-width: 1200px) {
            .col-lg-12 { grid-column: span 12; }
            .col-lg-6 { grid-column: span 6; }
        }
        @media (max-width: 992px) {
            .saas-grid { gap: 16px; }
            .col-md-12 { grid-column: span 12; }
            .kpi-row { grid-template-columns: repeat(2, 1fr) !important; }
        }
        @media (max-width: 768px) {
            .main { padding: 20px; padding-top: 80px; }
            .header-actions { flex-direction: column; align-items: stretch !important; gap: 12px; mt-4; }
            .kpi-row { grid-template-columns: 1fr !important; }
        }

        /* Buttons & Controls */
        .header-actions { display: flex; gap: 8px; align-items: center; margin-left: auto; justify-content: flex-end; }
        .btn-saas { padding: 8px 14px; border-radius: 10px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; justify-content: center; gap: 6px; cursor: pointer; transition: all 0.2s; border: none; font-family: inherit; white-space: nowrap; }
        .btn-saas-outline { background: #fff; border: 1px solid #E2E8F0; color: #334155; }
        .btn-saas-outline:hover { background: #F8FAFC; border-color: #CBD5E1; }
        .btn-saas-primary { background: #6C4DFF; color: #fff; box-shadow: 0 4px 12px rgba(108, 77, 255, 0.2); }
        .btn-saas-primary:hover { background: #5a3df0; transform: translateY(-1px); box-shadow: 0 6px 16px rgba(108, 77, 255, 0.3); }
        
        .quick-filters { display: flex; gap: 4px; background: #fff; padding: 4px; border-radius: 12px; border: 1px solid #E2E8F0; width: max-content; margin-top: 24px; overflow-x: auto; max-width: 100%; scrollbar-width: none; }
        .filter-btn { padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; color: #64748B; border: none; background: transparent; cursor: pointer; transition: 0.2s; white-space: nowrap; }
        .filter-btn:hover { color: #0F172A; background: #F1F5F9; }
        .filter-btn.active { background: #6C4DFF; color: #fff; }

        /* SaaS Panel (Card) */
        .saas-panel { background: #fff; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); border: 1px solid rgba(226, 232, 240, 0.6); padding: 24px; display: flex; flex-direction: column; transition: box-shadow 0.3s; }
        .saas-panel:hover { box-shadow: 0 8px 30px rgba(0,0,0,0.05); }
        .saas-panel-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        
        /* KPI Cards */
        .kpi-row { display: grid; grid-template-columns: repeat(5, 1fr); gap: 16px; margin-bottom: 24px; }
        .kpi-card { background: #fff; padding: 16px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.02); border: 1px solid rgba(226, 232, 240, 0.8); position: relative; overflow: hidden; transition: transform 0.2s, box-shadow 0.2s; display: flex; flex-direction: column; justify-content: space-between; }
        .kpi-card:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .kpi-header { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
        .kpi-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; color: #fff; flex-shrink: 0; }
        .kpi-title-block { display: flex; flex-direction: column; }
        .kpi-label { font-size: 11px; font-weight: 600; color: #64748B; margin-bottom: 2px; }
        .kpi-val { font-size: 20px; font-weight: 800; color: #0F172A; }
        .kpi-trend-row { display: flex; align-items: center; gap: 6px; font-size: 11px; margin-bottom: 12px; }
        .kpi-trend { color: #10B981; font-weight: 700; display: inline-flex; align-items: center; gap: 2px; }
        .kpi-trend.down { color: #EF4444; }
        .kpi-trend-ctx { color: #94A3B8; }
        .kpi-sparkline { width: 100%; height: 36px; margin-top: auto; }

        /* Specific Elements */
        .receivable-card { background: #F8FAFC; border-radius: 12px; padding: 16px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border: 1px solid transparent; transition: 0.2s; }
        .receivable-card:hover { border-color: #E2E8F0; background: #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.02); }
        .r-title { display: flex; align-items: center; gap: 12px; font-weight: 600; font-size: 14px; color: #334155; }
        .r-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; }
        .r-amount { font-size: 16px; font-weight: 700; color: #0F172A; text-align: right; }
        .r-sub { font-size: 11px; color: #64748B; font-weight: 500; text-align: right; margin-top: 2px; }

        /* Profile Cards */
        .profile-card { display: flex; align-items: center; gap: 16px; padding: 16px 0; border-bottom: 1px solid #F1F5F9; }
        .profile-card:last-child { border-bottom: none; padding-bottom: 0; }
        .p-img { width: 48px; height: 48px; border-radius: 50%; object-fit: cover; border: 2px solid #E2E8F0; }
        .p-info { flex: 1; min-width: 0; }
        .p-name { font-size: 14px; font-weight: 700; color: #0F172A; margin: 0 0 2px 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .p-room { font-size: 12px; color: #64748B; font-weight: 500; }
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; letter-spacing: 0.5px; }

        /* Defaulters */
        .defaulter-action { width: 36px; height: 36px; border-radius: 10px; background: #F1F5F9; color: #64748B; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s; font-size: 18px; }
        .defaulter-action:hover { background: #6C4DFF; color: #fff; }

        /* Timeline */
        .timeline { position: relative; padding-left: 20px; }
        .timeline::before { content: ''; position: absolute; left: 8px; top: 0; bottom: 0; width: 2px; background: #E2E8F0; }
        .tl-item { position: relative; padding-bottom: 24px; padding-left: 24px; }
        .tl-item:last-child { padding-bottom: 0; }
        .tl-dot { position: absolute; left: -16px; top: 2px; width: 20px; height: 20px; border-radius: 50%; background: #fff; border: 2px solid #6C4DFF; display: flex; align-items: center; justify-content: center; font-size: 10px; color: #6C4DFF; }
        .tl-title { font-size: 13px; font-weight: 700; color: #0F172A; margin-bottom: 2px; }
        .tl-desc { font-size: 12px; color: #64748B; line-height: 1.4; }
        .tl-time { font-size: 11px; color: #94A3B8; font-weight: 600; margin-top: 4px; display: block; }

        /* AI Insights */
        .ai-card { background: linear-gradient(145deg, #F8FAFC, #F1F5F9); border: 1px solid #E2E8F0; }
        .ai-header { display: flex; align-items: center; gap: 8px; margin-bottom: 16px; font-weight: 700; color: #6C4DFF; font-size: 14px; }
        .ai-list { margin: 0; padding-left: 20px; font-size: 13px; color: #334155; line-height: 1.6; }
        .ai-list li { margin-bottom: 8px; }

        .spinner { border: 3px solid rgba(0,0,0,0.1); width: 24px; height: 24px; border-radius: 50%; border-left-color: #6C4DFF; animation: spin 1s linear infinite; margin: 0 auto; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

        /* Utilities */
        .text-right { text-align: right; }
        .w-100 { width: 100%; }
        .d-flex { display: flex; }
        .align-center { align-items: center; }
        .justify-between { justify-content: space-between; }
        
        /* Animations */
        .fade-in { animation: fadeIn 0.4s ease forwards; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>

<?php include "sidebar.php"; ?>

<main class="main">
    <?php include 'header.php'; ?>

    <!-- Header Section -->
    <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 8px; margin-top: 24px;" class="fade-in">
        <div>
            <h1 class="page-title">Financial & Operational Reports</h1>
            <p class="page-subtitle">Monitor performance, renter health, and billing insights in real time.</p>
        </div>
        <div class="header-actions">
            <button class="btn-saas btn-saas-outline"><i class='bx bx-export'></i> Export Report</button>
            <button class="btn-saas btn-saas-outline"><i class='bx bx-download'></i> Download PDF</button>
            <button class="btn-saas btn-saas-primary"><i class='bx bx-envelope'></i> Schedule Email Report</button>
        </div>
    </div>

    <!-- Quick Filters -->
    <div class="quick-filters fade-in" style="margin-bottom: 32px; animation-delay: 0.1s;">
        <button class="filter-btn">Today</button>
        <button class="filter-btn">This Week</button>
        <button class="filter-btn active">This Month</button>
        <button class="filter-btn">Last 3 Months</button>
        <button class="filter-btn">Year to Date</button>
        <button class="filter-btn">All Time</button>
        <button class="filter-btn"><i class='bx bx-calendar'></i> Custom Range</button>
    </div>

    <!-- KPI Overview Cards -->
    <div class="kpi-row fade-in" id="kpiContainer" style="animation-delay: 0.2s;">
        <!-- Injected via JS -->
        <div class="kpi-card" style="display:flex; justify-content:center; align-items:center; height:180px;"><div class="spinner"></div></div>
    </div>

    <!-- Analytics & Charts -->
    <div class="saas-grid fade-in" style="animation-delay: 0.3s;">
        <div class="col-8 col-md-12">
            <div class="saas-panel" style="height: 100%;">
                <div class="saas-panel-header">
                    <h2 class="section-title">Revenue Overview</h2>
                    <select style="padding: 6px 12px; border-radius: 8px; border: 1px solid #E2E8F0; font-size: 12px; font-weight: 600; outline:none;">
                        <option>Monthly</option>
                        <option>Quarterly</option>
                    </select>
                </div>
                <div style="flex: 1; position: relative; min-height: 250px;">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-4 col-md-12">
            <div class="saas-panel" style="height: 100%;">
                <div class="saas-panel-header">
                    <h2 class="section-title">Collection Distribution</h2>
                    <i class='bx bx-dots-horizontal-rounded' style="color:#94A3B8; font-size:20px; cursor:pointer;"></i>
                </div>
                <div style="flex: 1; position: relative; display: flex; justify-content: center; align-items: center; min-height: 250px;">
                    <canvas id="donutChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Receivables & Performance -->
    <div class="saas-grid fade-in" style="animation-delay: 0.4s;">
        <div class="col-4 col-md-12">
            <div class="saas-panel" style="height: 100%;">
                <div class="saas-panel-header">
                    <h2 class="section-title">Receivables Aging</h2>
                </div>
                <div id="agingContainer">
                    <div class="spinner" style="margin: 40px auto;"></div>
                </div>
            </div>
        </div>
        <div class="col-8 col-md-12">
            <div class="saas-panel" style="height: 100%;">
                <div class="saas-panel-header">
                    <h2 class="section-title">Resident Financial Performance</h2>
                    <a href="manage-renters.php" style="font-size:12px; font-weight:600; color:#6C4DFF; text-decoration:none;">View All</a>
                </div>
                <div id="perfContainer">
                    <div class="spinner" style="margin: 40px auto;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Electricity & Defaulters -->
    <div class="saas-grid fade-in" style="animation-delay: 0.5s;">
        <div class="col-4 col-md-12">
            <div class="saas-panel" style="height: 100%;">
                <div class="saas-panel-header">
                    <h2 class="section-title">Electricity Insights</h2>
                </div>
                <div id="elecStatsContainer" style="display:grid; grid-template-columns: 1fr 1fr; gap:16px; margin-bottom:24px;">
                    <!-- JS Injected -->
                </div>
                <h3 style="font-size:13px; font-weight:700; color:#64748B; margin-top:0; margin-bottom:16px; text-transform:uppercase; letter-spacing:0.5px;">Top Consumers</h3>
                <div style="height: 150px; position: relative;">
                    <canvas id="elecBarChart"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-4 col-md-12">
            <div class="saas-panel" style="height: 100%;">
                <div class="saas-panel-header">
                    <h2 class="section-title" style="color: #EF4444;"><i class='bx bxs-error-circle' style="vertical-align:middle; margin-right:4px;"></i> Top Defaulters</h2>
                </div>
                <div id="defaulterContainer">
                    <div class="spinner" style="margin: 40px auto;"></div>
                </div>
            </div>
        </div>

        <div class="col-4 col-md-12">
            <div class="saas-panel" style="height: 100%;">
                <div class="saas-panel-header">
                    <h2 class="section-title" style="color: #F59E0B;"><i class='bx bx-trending-up' style="vertical-align:middle; margin-right:4px;"></i> Anomaly Detection</h2>
                </div>
                <div id="anomalyContainer">
                    <div class="spinner" style="margin: 40px auto;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Expenses, Activity & Insights -->
    <div class="saas-grid fade-in" style="animation-delay: 0.6s;">
        <div class="col-4 col-md-12">
            <div class="saas-panel" style="height: 100%;">
                <div class="saas-panel-header">
                    <h2 class="section-title">Expense Summary</h2>
                </div>
                <div style="position: relative; height: 200px; display: flex; justify-content: center; align-items: center;">
                    <canvas id="expenseChart"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-4 col-md-12">
            <div class="saas-panel" style="height: 100%;">
                <div class="saas-panel-header">
                    <h2 class="section-title">Recent Financial Activity</h2>
                </div>
                <div class="timeline" id="activityContainer">
                    <div class="spinner" style="margin: 40px auto;"></div>
                </div>
            </div>
        </div>

        <div class="col-4 col-md-12">
            <div class="saas-panel ai-card" style="height: 100%;">
                <div class="ai-header">
                    <i class='bx bx-bot' style="font-size:20px;"></i> Quick Insights Panel
                </div>
                <ul class="ai-list" id="aiContainer">
                    <div class="spinner" style="margin: 40px auto;"></div>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="fade-in" style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid #E2E8F0; padding-top:20px; margin-top:20px; animation-delay: 0.7s;">
        <div style="font-size:12px; color:#64748B; font-weight:500;">Data source: Real-time Database sync active.</div>
        <div style="font-size:12px; color:#64748B; font-weight:500; display:flex; align-items:center; gap:6px;">
            <i class='bx bx-time'></i> Last updated: <span id="updateTime">Just now</span>
        </div>
    </div>
    
</main>

<script>
    // Theme setup (keeps sidebar sync)
    const themeToggle = document.getElementById('themeToggle');
    if (localStorage.getItem('theme') === 'dark') {
        document.documentElement.classList.add('dark-theme');
        themeToggle?.classList.replace('bx-moon', 'bx-sun');
    }
    
    // Formatting utils
    const formatCur = (val) => '₹' + parseFloat(val).toLocaleString('en-IN', { maximumFractionDigits: 0 });
    
    // Chart instances
    let charts = {};

    // Filter toggles
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            e.target.classList.add('active');
            loadDashboard(); // Re-trigger load in real scenario
        });
    });

    async function loadDashboard() {
        try {
            // 1. KPI
            const kpiRes = await fetch('api_reports_saas.php?endpoint=kpi');
            const kpi = await kpiRes.json();
            const sparklineSVG = (color, pathData) => `
                <svg class="kpi-sparkline" viewBox="0 0 100 30" preserveAspectRatio="none">
                    <defs>
                        <linearGradient id="grad-${color.replace('#', '')}" x1="0" x2="0" y1="0" y2="1">
                            <stop offset="0%" stop-color="${color}" stop-opacity="0.2"></stop>
                            <stop offset="100%" stop-color="${color}" stop-opacity="0"></stop>
                        </linearGradient>
                    </defs>
                    <path d="${pathData} L100,30 L0,30 Z" fill="url(#grad-${color.replace('#', '')})"></path>
                    <path d="${pathData}" fill="none" stroke="${color}" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
            `;

            document.getElementById('kpiContainer').innerHTML = `
                <div class="kpi-card">
                    <div class="kpi-header">
                        <div class="kpi-icon" style="background: #6366F1;"><i class='bx bx-wallet'></i></div>
                        <div class="kpi-title-block">
                            <div class="kpi-label">Total Revenue</div>
                            <div class="kpi-val">${formatCur(kpi.total_rent)}</div>
                        </div>
                    </div>
                    <div class="kpi-trend-row">
                        <span class="kpi-trend"><i class='bx bx-trending-up'></i> ${kpi.rent_growth}</span> 
                        <span class="kpi-trend-ctx">vs previous 3 months</span>
                    </div>
                    ${sparklineSVG('#6366F1', 'M0,25 C10,15 20,20 30,15 C40,10 50,22 60,18 C70,14 80,5 90,15 C95,20 100,22 100,22')}
                </div>
                <div class="kpi-card">
                    <div class="kpi-header">
                        <div class="kpi-icon" style="background: #10B981;"><i class='bx bxs-zap'></i></div>
                        <div class="kpi-title-block">
                            <div class="kpi-label">Electricity Profit</div>
                            <div class="kpi-val">${formatCur(kpi.electricity_profit)}</div>
                        </div>
                    </div>
                    <div class="kpi-trend-row">
                        <span class="kpi-trend"><i class='bx bx-trending-up'></i> ${kpi.elec_growth}</span> 
                        <span class="kpi-trend-ctx">vs previous 3 months</span>
                    </div>
                    ${sparklineSVG('#10B981', 'M0,22 C15,12 25,24 35,22 C45,20 50,5 60,15 C70,25 85,18 100,24')}
                </div>
                <div class="kpi-card">
                    <div class="kpi-header">
                        <div class="kpi-icon" style="background: #F59E0B;"><i class='bx bx-file'></i></div>
                        <div class="kpi-title-block">
                            <div class="kpi-label">Outstanding Dues</div>
                            <div class="kpi-val">${formatCur(kpi.outstanding)}</div>
                        </div>
                    </div>
                    <div class="kpi-trend-row">
                        <span class="kpi-trend"><i class='bx bx-trending-up'></i> ${kpi.out_growth}</span> 
                        <span class="kpi-trend-ctx">vs previous 3 months</span>
                    </div>
                    ${sparklineSVG('#F59E0B', 'M0,28 C15,22 25,28 35,20 C45,12 55,26 65,18 C80,5 90,20 100,26')}
                </div>
                <div class="kpi-card">
                    <div class="kpi-header">
                        <div class="kpi-icon" style="background: #3B82F6;"><i class='bx bx-group'></i></div>
                        <div class="kpi-title-block">
                            <div class="kpi-label">Total Tenants</div>
                            <div class="kpi-val">${kpi.active_residents}</div>
                        </div>
                    </div>
                    <div class="kpi-trend-row">
                        <span class="kpi-trend"><i class='bx bx-trending-up'></i> ${kpi.res_growth}</span> 
                        <span class="kpi-trend-ctx">New this month</span>
                    </div>
                    ${sparklineSVG('#3B82F6', 'M0,24 C10,24 20,16 30,22 C40,28 50,15 65,18 C75,20 85,5 100,18')}
                </div>
                <div class="kpi-card">
                    <div class="kpi-header">
                        <div class="kpi-icon" style="background: #F43F5E;"><i class='bx bx-error-alt'></i></div>
                        <div class="kpi-title-block">
                            <div class="kpi-label">Overdue Tenants</div>
                            <div class="kpi-val">${kpi.overdue_tenants}</div>
                        </div>
                    </div>
                    <div class="kpi-trend-row">
                        <span class="kpi-trend"><i class='bx bx-trending-up'></i> ${kpi.overdue_growth}</span> 
                        <span class="kpi-trend-ctx">more than last month</span>
                    </div>
                    ${sparklineSVG('#F43F5E', 'M0,22 C10,12 25,25 35,22 C45,18 55,26 70,12 C80,-2 90,20 100,24')}
                </div>
            `;

            // 2. Revenue Chart
            const revRes = await fetch('api_reports_saas.php?endpoint=revenue_chart');
            const revData = await revRes.json();
            if(charts.revenue) charts.revenue.destroy();
            charts.revenue = new Chart(document.getElementById('revenueChart'), {
                type: 'line',
                data: {
                    labels: revData.map(d => d.month),
                    datasets: [
                        { label: 'Rent', data: revData.map(d => d.rent), borderColor: '#6C4DFF', backgroundColor: 'rgba(108, 77, 255, 0.1)', tension: 0.4, fill: true },
                        { label: 'Electricity', data: revData.map(d => d.electricity), borderColor: '#10B981', tension: 0.4 },
                        { label: 'Other', data: revData.map(d => d.other), borderColor: '#F59E0B', tension: 0.4 }
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { position: 'top', labels: { usePointStyle: true, boxWidth: 6, font: { family: 'Inter', size: 12 } } } },
                    scales: {
                        x: { grid: { display: false } },
                        y: { grid: { color: '#F1F5F9', borderDash: [5, 5] }, border: { display: false } }
                    }
                }
            });

            // 3. Donut
            const distRes = await fetch('api_reports_saas.php?endpoint=distribution_donut');
            const distData = await distRes.json();
            if(charts.donut) charts.donut.destroy();
            charts.donut = new Chart(document.getElementById('donutChart'), {
                type: 'doughnut',
                data: {
                    labels: Object.keys(distData),
                    datasets: [{
                        data: Object.values(distData),
                        backgroundColor: ['#6C4DFF', '#10B981', '#3B82F6', '#F59E0B'],
                        borderWidth: 0,
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false, cutout: '75%',
                    plugins: {
                        legend: { position: 'right', labels: { usePointStyle: true, padding: 20, font: { family: 'Inter' } } }
                    }
                }
            });

            // 4. Receivables Aging
            const ageRes = await fetch('api_reports_saas.php?endpoint=receivables_aging');
            const ageData = await ageRes.json();
            let ageHTML = '';
            let totalOut = 0;
            ageData.forEach(a => {
                if(a.total) totalOut = a.total;
                else {
                    ageHTML += `
                    <div class="receivable-card">
                        <div class="r-title"><div class="r-icon" style="background:${a.color}20; color:${a.color};"><i class='bx bx-calendar'></i></div> ${a.bracket}</div>
                        <div>
                            <div class="r-amount" style="color:${a.color};">${formatCur(a.amount)}</div>
                            <div class="r-sub">${a.tenants} Residents</div>
                        </div>
                    </div>`;
                }
            });
            ageHTML += `<div style="display:flex; justify-content:space-between; margin-top:20px; font-weight:700; font-size:15px;"><span>Total Outstanding</span> <span>${formatCur(totalOut)}</span></div>`;
            document.getElementById('agingContainer').innerHTML = ageHTML;

            // 5. Resident Performance
            const perfRes = await fetch('api_reports_saas.php?endpoint=resident_performance');
            const perfData = await perfRes.json();
            document.getElementById('perfContainer').innerHTML = perfData.map(p => `
                <div class="profile-card">
                    <img src="${p.photo}" class="p-img">
                    <div class="p-info">
                        <h4 class="p-name">${p.name}</h4>
                        <div class="p-room">Room ${p.room}</div>
                    </div>
                    <div class="text-right" style="margin-right:24px;">
                        <div style="font-size:14px; font-weight:700;">${formatCur(p.paid)}</div>
                        <div style="font-size:11px; color:#64748B;">Total Paid</div>
                    </div>
                    <div class="text-right" style="margin-right:24px;">
                        <div style="font-size:14px; font-weight:700; color:#EF4444;">${formatCur(p.due)}</div>
                        <div style="font-size:11px; color:#64748B;">Pending</div>
                    </div>
                    <div style="width: 80px; text-align:right;">
                        <span class="badge" style="background:${p.statusBg}; color:${p.statusColor};">${p.status}</span>
                    </div>
                </div>
            `).join('');

            // 6. Elec Insights
            const eStatsRes = await fetch('api_reports_saas.php?endpoint=electricity_insights');
            const eStats = await eStatsRes.json();
            document.getElementById('elecStatsContainer').innerHTML = `
                <div style="background:#F8FAFC; padding:16px; border-radius:12px;">
                    <div style="font-size:11px; color:#64748B; font-weight:600; margin-bottom:4px; text-transform:uppercase;">Avg Units/Res</div>
                    <div style="font-size:20px; font-weight:800; color:#0F172A;">${eStats.avg_units}</div>
                </div>
                <div style="background:#F8FAFC; padding:16px; border-radius:12px;">
                    <div style="font-size:11px; color:#64748B; font-weight:600; margin-bottom:4px; text-transform:uppercase;">Highest Usage</div>
                    <div style="font-size:20px; font-weight:800; color:#EF4444;">${eStats.highest}</div>
                </div>
            `;
            
            const eBarRes = await fetch('api_reports_saas.php?endpoint=electricity_bar');
            const eBar = await eBarRes.json();
            if(charts.ebar) charts.ebar.destroy();
            charts.ebar = new Chart(document.getElementById('elecBarChart'), {
                type: 'bar',
                data: {
                    labels: eBar.map(d => d.label),
                    datasets: [{ data: eBar.map(d => d.units), backgroundColor: '#10B981', borderRadius: 4 }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { x: { grid: { display: false } }, y: { display: false } }
                }
            });

            // 7. Defaulters
            const defRes = await fetch('api_reports_saas.php?endpoint=top_defaulters');
            const defData = await defRes.json();
            document.getElementById('defaulterContainer').innerHTML = defData.map(d => `
                <div class="defaulter-card">
                    <div class="d-flex align-center" style="gap:12px;">
                        <img src="${d.photo}" style="width:40px; height:40px; border-radius:50%; object-fit:cover;">
                        <div>
                            <div class="p-name">${d.name} <span style="color:#64748B; font-weight:500; font-size:12px;">(Rm ${d.room})</span></div>
                            <div style="font-size:11px; color:#EF4444; font-weight:600;">${d.days_overdue} days overdue</div>
                        </div>
                    </div>
                    <div class="d-flex align-center" style="gap:16px;">
                        <div class="r-amount" style="color:#EF4444;">${formatCur(d.due)}</div>
                        <a href="manage-reminders.php" class="defaulter-action" title="Send Reminder"><i class='bx bx-bell'></i></a>
                    </div>
                </div>
            `).join('');

            // 8. Anomalies
            const anomRes = await fetch('api_reports_saas.php?endpoint=anomalies');
            const anomData = await anomRes.json();
            document.getElementById('anomalyContainer').innerHTML = anomData.map(a => `
                <div style="background:#FFFBEB; border:1px solid #FDE68A; padding:16px; border-radius:12px; margin-bottom:12px; display:flex; justify-content:space-between; align-items:center;">
                    <div>
                        <div style="font-size:13px; font-weight:700; color:#92400E;">${a.name} (Room ${a.room})</div>
                        <div style="font-size:12px; color:#B45309; margin-top:4px;">Used ${a.units} units (${a.increase} vs prev)</div>
                    </div>
                    <a href="electricity-list.php" style="background:#FDE68A; color:#92400E; padding:6px 12px; border-radius:8px; font-size:11px; font-weight:700; text-decoration:none; transition:0.2s;">Audit</a>
                </div>
            `).join('');

            // 9. Expenses
            const expRes = await fetch('api_reports_saas.php?endpoint=expense_donut');
            const expData = await expRes.json();
            const totalExp = expData.Total; delete expData.Total;
            if(charts.expense) charts.expense.destroy();
            charts.expense = new Chart(document.getElementById('expenseChart'), {
                type: 'doughnut',
                data: {
                    labels: Object.keys(expData),
                    datasets: [{
                        data: Object.values(expData).map(v => v.value),
                        backgroundColor: Object.values(expData).map(v => v.color),
                        borderWidth: 0, hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false, cutout: '75%',
                    plugins: { legend: { position: 'right', labels: { usePointStyle: true, padding: 16, font: { family: 'Inter', size: 11 } } } }
                }
            });

            // 10. Timeline
            const actRes = await fetch('api_reports_saas.php?endpoint=recent_activity');
            const actData = await actRes.json();
            document.getElementById('activityContainer').innerHTML = actData.map(a => `
                <div class="tl-item">
                    <div class="tl-dot" style="border-color:${a.color}; color:${a.color};"><i class='bx ${a.icon}'></i></div>
                    <div class="tl-title">${a.title}</div>
                    <div class="tl-desc">${a.desc}</div>
                    <span class="tl-time">${a.time}</span>
                </div>
            `).join('');

            // 11. Insights
            const aiRes = await fetch('api_reports_saas.php?endpoint=ai_insights');
            const aiData = await aiRes.json();
            document.getElementById('aiContainer').innerHTML = aiData.map(a => `<li>${a}</li>`).join('');

            document.getElementById('updateTime').innerText = new Date().toLocaleTimeString();

        } catch (e) {
            console.error("Dashboard Load Error", e);
        }
    }

    // Init
    loadDashboard();
</script>
</body>
</html>

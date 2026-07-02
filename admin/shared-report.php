<?php
// admin/shared-report.php
require_once "../db.php";

$token = $_GET['token'] ?? '';
if (empty($token)) {
    die("Invalid or missing sharing token.");
}

$q = "SELECT created_by, expires_at FROM shared_reports WHERE token = ? AND expires_at > NOW()";
$stmt = mysqli_prepare($conn, $q);
mysqli_stmt_bind_param($stmt, "s", $token);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$shared_info = mysqli_fetch_assoc($res);

if (!$shared_info) {
    die("This sharing link has expired or is invalid.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Analytics & Reports | <?php echo HOUSE_NAME; ?></title>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-design-system.css?v=<?php echo time(); ?>">
    <!-- Chart.js for data visualization -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Specific Report Layout Tweaks */
        .reports-topbar {
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;
        }
        .btn-group { display: flex; gap: 8px; }
        .filter-btn {
            background: var(--white); border: 1px solid var(--border); padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; color: var(--text-gray);
            transition: var(--transition);
        }
        .filter-btn.active, .filter-btn:hover { background: var(--primary-purple); color: var(--white); border-color: var(--primary-purple); }
        


        .charts-grid {
            display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-bottom: 24px;
        }
        .tables-grid {
            display: grid; grid-template-columns: 1fr 1fr; gap: 24px;
        }
        
        @media (max-width: 1024px) {
            .charts-grid { grid-template-columns: 1fr; }
            .tables-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .hide-mobile { display: none; }
            .reports-topbar .btn-group {
                flex-wrap: wrap;
                justify-content: center;
                width: 100%;
            }
            .reports-topbar .filter-btn {
                flex: 1 1 calc(50% - 8px);
                text-align: center;
                padding: 10px 8px;
            }
            .reports-topbar select.filter-btn {
                flex: 1 1 100%;
                margin-top: 8px;
            }
            .divider-mobile {
                display: none;
            }
        }

        .spinner {
            border: 3px solid rgba(0,0,0,0.1); width: 30px; height: 30px; border-radius: 50%; border-left-color: var(--primary-purple); animation: spin 1s linear infinite; margin: 0 auto;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .shimmer { background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; border-radius: 4px; height: 16px; margin: 4px 0; }
        @keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }

        /* Actions */
        .action-link { color: var(--primary-purple); font-weight: 600; text-decoration: none; font-size: 13px; display: inline-flex; align-items: center; gap: 4px; }
        .action-link:hover { text-decoration: underline; }
    </style>
</head>
<body style="padding: 20px;">

    <header class="header" style="left: 0; width: 100%; position: relative; border-radius: 12px; margin-bottom: 24px;">
        <div class="header-content" style="justify-content: flex-start;">
            <div class="brand" style="display:flex; align-items:center; gap: 12px;">
                <img src="../assets/img/logo.png" alt="Logo" style="width: 32px; height: 32px; border-radius: 8px;">
                <span style="font-weight:700; font-size: 18px;"><?php echo HOUSE_NAME; ?> Analytics</span>
            </div>
            <div style="margin-left: auto; text-align: right;">
                <div style="font-size: 12px; color: var(--text-gray);">Shared by: <?php echo htmlspecialchars($shared_info['created_by']); ?></div>
                <div style="font-size: 12px; color: #EF4444;">Expires: <?php echo date('M d, Y', strtotime($shared_info['expires_at'])); ?></div>
            </div>
            <i class='bx bx-moon' id="themeToggle" style="margin-left: 20px; cursor: pointer;"></i>
        </div>
    </header>

<main class="main" style="margin-left: 0; padding: 0;">


    <div class="welcome animate-up" style="margin-bottom: 20px;">
        <h1>Financial & Operational Reports</h1>
        <p>Monitor your performance, renter health, and billing anomalies</p>
    </div>

    <!-- Controls -->
    <div class="reports-topbar animate-up">
        <div class="btn-group" style="align-items: center;">
            <button class="filter-btn" data-range="this_month">This Month</button>
            <button class="filter-btn active" data-range="last_3_months">Last 3 Months</button>
            <button class="filter-btn" data-range="ytd">Year to Date</button>
            <button class="filter-btn" data-range="all">All Time</button>
            <div class="divider-mobile" style="width: 1px; height: 24px; background: var(--border); margin: 0 8px;"></div>
            <select id="monthSelector" class="filter-btn" style="padding-right: 32px; appearance: none; background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%2371717A%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.4-12.8z%22%2F%3E%3C%2Fsvg%3E'); background-repeat: no-repeat; background-position: right 12px top 50%; background-size: 10px auto;">
                <option value="all">Filter by Month...</option>
            </select>
        </div>
        <div class="btn-group">
            <!-- Buttons hidden for read-only view -->
        </div>
    </div>

    <!-- KPI Deck -->
    <div class="kpi-grid animate-up" id="kpiDeck">
        <!-- Rendered via JS -->
        <div class="kpi-card"><div class="shimmer"></div><div class="shimmer" style="height:30px;"></div></div>
        <div class="kpi-card"><div class="shimmer"></div><div class="shimmer" style="height:30px;"></div></div>
        <div class="kpi-card"><div class="shimmer"></div><div class="shimmer" style="height:30px;"></div></div>
    </div>

    <!-- Charts -->
    <div class="charts-grid animate-up">
        <div class="panel">
            <div class="panel-header">
                <h2>Revenue vs. Expenses (Time Series)</h2>
                <i class='bx bx-dots-horizontal-rounded' style="color:var(--text-gray); font-size:24px; cursor:pointer;"></i>
            </div>
            <canvas id="revenueChart" style="width: 100%; height: 300px; max-height: 300px;"></canvas>
        </div>
        
        <div class="panel">
            <div class="panel-header">
                <h2>Receivables Aging</h2>
            </div>
            <canvas id="agingChart" style="width: 100%; height: 300px; max-height: 300px;"></canvas>
            <div style="font-size: 12px; color: var(--text-gray); text-align: center; margin-top: 10px;">
                Shows brackets for unpaid rent and electricity dues.
            </div>
        </div>
    </div>

    <!-- Additional Charts -->
    <div class="charts-grid animate-up" style="margin-bottom: 24px;">
        <div class="panel">
            <div class="panel-header">
                <h2>Top 5 Energy Consumers (All-Time Units)</h2>
            </div>
            <canvas id="usageChart" style="width: 100%; height: 250px; max-height: 250px;"></canvas>
        </div>
        
        <div class="panel">
            <div class="panel-header">
                <h2>Revenue Breakdown</h2>
            </div>
            <div style="position: relative; height: 250px; width: 100%; display: flex; justify-content: center;">
                <canvas id="splitChart" style="max-height: 100%;"></canvas>
            </div>
        </div>
    </div>

    <!-- Tables -->
    <div class="tables-grid animate-up">
        <!-- Delinquent Renters -->
        <div class="panel">
            <div class="panel-header">
                <h2 style="color: #EF4444;"><i class='bx bxs-error-circle'></i> Top Delinquent Renters</h2>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Tenant</th>
                            <th>Room</th>
                            <th>Total Due</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="delinquentTable">
                        <tr><td colspan="4" style="text-align:center;"><div class="spinner"></div></td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Meter Anomalies -->
        <div class="panel">
            <div class="panel-header">
                <h2 style="color: #F59E0B;"><i class='bx bx-trending-up'></i> Electricity Anomalies (>250 Units)</h2>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Tenant / Room</th>
                            <th>Month</th>
                            <th>Units Logged</th>
                            <th>Verify</th>
                        </tr>
                    </thead>
                    <tbody id="anomaliesTable">
                        <tr><td colspan="4" style="text-align:center;"><div class="spinner"></div></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</main>

<script>
    // Theme Toggle Logic
    const themeToggle = document.getElementById('themeToggle');
    if (localStorage.getItem('theme') === 'dark') {
        document.documentElement.classList.add('dark-theme');
        themeToggle?.classList.replace('bx-moon', 'bx-sun');
    }
    

    // Chart instances
    let revChart, ageChart, usageChart, splitChart;

    // Filters
    const monthSelector = document.getElementById('monthSelector');
    document.querySelectorAll('.filter-btn[data-range]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            document.querySelectorAll('.filter-btn[data-range]').forEach(b => b.classList.remove('active'));
            e.target.classList.add('active');
            let range = e.target.getAttribute('data-range');
            monthSelector.value = 'all'; // reset month selector
            fetchData(range, 'all');
        });
    });

    monthSelector.addEventListener('change', (e) => {
        document.querySelectorAll('.filter-btn[data-range]').forEach(b => b.classList.remove('active'));
        fetchData('all', e.target.value);
    });

    function formatCur(val) {
        return '₹' + parseFloat(val).toLocaleString('en-IN', { maximumFractionDigits: 0 });
    }

    const authToken = "<?php echo $token; ?>";

    async function fetchData(range = 'last_3_months', month = 'all') {
        try {
            // 1. Fetch Summary
            const sumRes = await fetch(`api_reports.php?endpoint=summary&range=${range}&month=${month}&token=${authToken}`);
            const sum = await sumRes.json();
            
            // Build KPI Deck
            document.getElementById('kpiDeck').innerHTML = `
                <div class="kpi-card hover-lift">
                    <div class="kpi-header">
                        <i class='bx bx-wallet kpi-icon' style="color:#624BFF; background:rgba(98, 75, 255, 0.1);"></i>
                    </div>
                    <div class="kpi-value">${formatCur(sum.total_rent_collected)}</div>
                    <div class="kpi-label">Total Rent Collected</div>
                </div>
                <div class="kpi-card hover-lift">
                    <div class="kpi-header">
                        <i class='bx bx-bolt kpi-icon' style="color:#F59E0B; background:rgba(245, 158, 11, 0.2);"></i>
                    </div>
                    <div class="kpi-value">${formatCur(sum.electricity_profit)}</div>
                    <div class="kpi-label">Electricity Gross Profit</div>
                </div>
                <div class="kpi-card hover-lift">
                    <div class="kpi-header">
                        <i class='bx bx-error kpi-icon' style="color:#EF4444; background:rgba(239, 68, 68, 0.2);"></i>
                    </div>
                    <div class="kpi-value" style="color:#EF4444;">${formatCur(sum.outstanding_amount)}</div>
                    <div class="kpi-label">Outstanding Receivables</div>
                </div>
            `;

            // 2. Fetch Time Series
            const tsRes = await fetch(`api_reports.php?endpoint=timeseries&range=${range}&month=${month}&token=${authToken}`);
            const tsData = await tsRes.json();
            
            const labels = tsData.map(d => d.month);
            const rentData = tsData.map(d => d.rent);
            const elecData = tsData.map(d => d.electricity);

            const isDark = document.documentElement.classList.contains('dark-theme');
            const gridColor = isDark ? '#334155' : '#F1F1F4';
            const fontColor = isDark ? '#94A3B8' : '#71717A';

            if(revChart) revChart.destroy();
            const ctxRev = document.getElementById('revenueChart').getContext('2d');
            revChart = new Chart(ctxRev, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        { label: 'Rent Collection', data: rentData, backgroundColor: '#624BFF', borderRadius: 6 },
                        { label: 'Electricity Collection', data: elecData, backgroundColor: '#10B981', borderRadius: 6 }
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    scales: {
                        x: { stacked: true, grid: { display: false } },
                        y: { stacked: true, grid: { color: gridColor }, ticks: { color: fontColor } }
                    },
                    plugins: { legend: { labels: { color: fontColor } } }
                }
            });

            // 3. Fetch Aging
            const ageRes = await fetch(`api_reports.php?endpoint=aging&range=${range}&month=${month}&token=${authToken}`);
            const ageData = await ageRes.json();
            if(ageChart) ageChart.destroy();
            const ctxAge = document.getElementById('agingChart').getContext('2d');
            ageChart = new Chart(ctxAge, {
                type: 'bar',
                data: {
                    labels: ageData.map(d => d.bracket),
                    datasets: [{
                        label: 'Amount in Bracket',
                        data: ageData.map(d => d.amount),
                        backgroundColor: ['#624BFF', '#F59E0B', '#EF4444', '#991B1B', '#450A0A'],
                        borderRadius: 6
                    }]
                },
                options: {
                    indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                    scales: { x: { grid: { color: gridColor } }, y: { grid: { display: false } } },
                    plugins: { legend: { display: false } }
                }
            });

            // 4. Delinquent Table
            const delRes = await fetch(`api_reports.php?endpoint=delinquent&range=${range}&month=${month}&token=${authToken}`);
            const delinquentReq = await delRes.json();
            const delTable = document.getElementById('delinquentTable');
            if(delinquentReq.length === 0) {
                delTable.innerHTML = "<tr><td colspan='4'>No delinquent renters found.</td></tr>";
            } else {
                delTable.innerHTML = delinquentReq.map(d => `
                    <tr>
                        <td style="font-weight:600;">${d.name}</td>
                        <td>${d.room_no}</td>
                        <td style="color:#EF4444; font-weight:700;">${formatCur(d.total_due)}</td>
                        <td><a href="manage-reminders.php" class="action-link"><i class='bx bx-bell'></i> Remind</a></td>
                    </tr>
                `).join('');
            }

            // 5. Anomalies Table
            const anomRes = await fetch(`api_reports.php?endpoint=anomalies&range=${range}&month=${month}&token=${authToken}`);
            const anomaliesReq = await anomRes.json();
            const anomTable = document.getElementById('anomaliesTable');
            if(anomaliesReq.length === 0) {
                anomTable.innerHTML = "<tr><td colspan='4' style='color:#10B981;'><i class='bx bx-check-circle'></i> No severe spikes detected.</td></tr>";
            } else {
                anomTable.innerHTML = anomaliesReq.map(a => `
                    <tr>
                        <td style="font-weight:600;">${a.name} <span class="badge" style="background:#F1F5F9; color:#475569;">Room ${a.room_no}</span></td>
                        <td>${a.month}</td>
                        <td style="color:#F59E0B; font-weight:700;">+${a.units}</td>
                        <td><a href="electricity-list.php" class="action-link"><i class='bx bx-check-shield'></i> Audit Photo</a></td>
                    </tr>
                `).join('');
            }

            // 6. Usage Bar Chart
            const usageRes = await fetch(`api_reports.php?endpoint=usage_bar&range=${range}&month=${month}&token=${authToken}`);
            const usageData = await usageRes.json();
            if(usageChart) usageChart.destroy();
            const ctxUsage = document.getElementById('usageChart').getContext('2d');
            usageChart = new Chart(ctxUsage, {
                type: 'bar',
                data: {
                    labels: usageData.map(d => d.label),
                    datasets: [{
                        label: 'Total Units Consumed',
                        data: usageData.map(d => d.units),
                        backgroundColor: '#3B82F6',
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    scales: {
                        x: { grid: { display: false }, ticks: { color: fontColor } },
                        y: { grid: { color: gridColor }, ticks: { color: fontColor } }
                    },
                    plugins: { legend: { display: false } }
                }
            });

            // 7. Revenue Split Doughnut
            const splitRes = await fetch(`api_reports.php?endpoint=revenue_split&range=${range}&month=${month}&token=${authToken}`);
            const splitData = await splitRes.json();
            if(splitChart) splitChart.destroy();
            const ctxSplit = document.getElementById('splitChart').getContext('2d');
            splitChart = new Chart(ctxSplit, {
                type: 'doughnut',
                data: {
                    labels: ['Rent Collected', 'Electricity Collected'],
                    datasets: [{
                        data: [splitData.rent, splitData.electricity],
                        backgroundColor: ['#624BFF', '#10B981'],
                        borderWidth: 0,
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: { legend: { position: 'bottom', labels: { color: fontColor, padding: 20 } } }
                }
            });

        } catch (e) {
            console.error(e);
            alert("Error loading reporting data. Please try again.");
        }
    }

    function exportReport(type) {
        window.location.href = `monthly-report.php?export=${type}`;
    }

    // Initial load
    async function init() {
        try {
            const mRes = await fetch(`api_reports.php?endpoint=months&token=${authToken}`);
            const months = await mRes.json();
            const sel = document.getElementById('monthSelector');
            months.forEach(m => {
                let opt = document.createElement('option');
                opt.value = m;
                opt.innerText = m;
                sel.appendChild(opt);
            });
        } catch(e) { console.error("Could not load months"); }
        fetchData();
    }
    init();

</script>

</body>
</html>

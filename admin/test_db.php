<?php
$content = file_get_contents('reports.php');
$content = preg_replace('/fetch\(\'api_reports_saas\.php\?endpoint=([a-z_]+)\'\)/', 'fetch(\'api_reports_saas.php?endpoint=$1&range=\' + range)', $content);
$content = str_replace('async function loadDashboard() {', 'async function loadDashboard(range = \'this_month\') {', $content);
$search = '<div class="quick-filters fade-in" style="margin-bottom: 32px; animation-delay: 0.1s;">
        <button class="filter-btn">Today</button>
        <button class="filter-btn">This Week</button>
        <button class="filter-btn active">This Month</button>
        <button class="filter-btn">Last 3 Months</button>
        <button class="filter-btn">Year to Date</button>
        <button class="filter-btn">All Time</button>
        <button class="filter-btn"><i class=\'bx bx-calendar\'></i> Custom Range</button>
    </div>';
$replace = '<div class="quick-filters fade-in" id="reportFilters" style="margin-bottom: 32px; animation-delay: 0.1s;">
        <button class="filter-btn" data-range="today">Today</button>
        <button class="filter-btn" data-range="this_week">This Week</button>
        <button class="filter-btn active" data-range="this_month">This Month</button>
        <button class="filter-btn" data-range="last_3_months">Last 3 Months</button>
        <button class="filter-btn" data-range="ytd">Year to Date</button>
        <button class="filter-btn" data-range="all_time">All Time</button>
        <button class="filter-btn" data-range="custom"><i class=\'bx bx-calendar\'></i> Custom Range</button>
    </div>';
$content = str_replace($search, $replace, $content);

$script = "
    // Handle Filters
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            let r = this.getAttribute('data-range');
            if(r && r !== 'custom') {
                loadDashboard(r);
            }
        });
    });
";
$content = str_replace('loadDashboard();', "loadDashboard();\n".$script, $content);
file_put_contents('reports.php', $content);
echo "Done.";

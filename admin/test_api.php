<?php
$endpoints = ['distribution_donut', 'receivables_aging', 'resident_performance', 'electricity_insights', 'electricity_bar', 'top_defaulters', 'anomalies', 'expense_donut', 'recent_activity', 'ai_insights'];

foreach ($endpoints as $ep) {
    echo "Testing $ep:\n";
    $output = shell_exec("php -r \"\$_GET['endpoint']='$ep'; \$_GET['range']='this_month'; require 'api_reports_saas.php';\"");
    if (strpos($output, 'Fatal error') !== false || strpos($output, 'Parse error') !== false || empty(trim($output))) {
        echo "ERROR in $ep: $output\n";
    } else {
        echo "OK\n";
    }
}

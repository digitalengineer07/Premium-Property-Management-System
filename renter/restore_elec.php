<?php
$c = file_get_contents("electricity-record.php");
// Find where "// Fetch all electricity records" starts
$pos = strpos($c, "// Fetch all electricity records");
if ($pos !== false) {
    // Find where "--text-gray:" starts or "<style>" starts
    $pos2 = strpos($c, "--text-gray:");
    if ($pos2 !== false) {
        $replacement = "// Fetch all electricity records
\$records_q = mysqli_query(\$conn, \"SELECT * FROM electricity WHERE user_id = \$user_id ORDER BY id DESC\");
\$electricity_records = [];
while(\$row = mysqli_fetch_assoc(\$records_q)) {
    \$electricity_records[] = \$row;
}

// Chart Data (last 12 chronological)
\$chart_records = array_slice(\$electricity_records, 0, 12);
\$chart_records = array_reverse(\$chart_records);
\$chart_labels = [];
\$chart_data = [];
foreach(\$chart_records as \$cr) {
    \$dateObj = DateTime::createFromFormat('F Y', \$cr['month']);
    \$shortMonth = \$dateObj ? \$dateObj->format('M Y') : \$cr['month'];
    \$chart_labels[] = \$shortMonth;
    \$chart_data[] = \$cr['units_consumed'];
}

// Last Recorded Reading & Current Month Details
\$latest_record = \$electricity_records[0] ?? null;
\$last_reading = \$latest_record['current_reading'] ?? 0;
\$last_reading_date = \$latest_record ? date(\"d M Y\", strtotime(\$latest_record['created_at'])) : 'N/A';

function money(\$val) {
    return '₹' . number_format((float)\$val, 2);
}
?>
<!DOCTYPE html>
<html lang=\"en\">
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>Electricity Record - <?php echo HOUSE_NAME; ?></title>
    <link href=\"https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap\" rel=\"stylesheet\">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel=\"stylesheet\" href=\"../assets/css/admin-design-system.css?v=<?php echo time(); ?>\">
    <script src=\"https://cdn.jsdelivr.net/npm/chart.js\"></script>
    <style>
        :root {
            --bg-main: #FAFBFC;
            --sidebar-bg: #FFFFFF;
            --text-dark: #0F172A;
            ";
        $c = substr_replace($c, $replacement, $pos, $pos2 - $pos);
        file_put_contents("electricity-record.php", $c);
        echo "Successfully restored electricity-record.php\n";
    }
}
?>

<?php
require_once "../db.php";
session_start();

if (!isset($_SESSION['admin'])) {
    die("Unauthorized access.");
}

$schema = [
    'tables' => []
];

// 1. Get all tables
$tables_result = mysqli_query($conn, "SHOW TABLES");
while ($table_row = mysqli_fetch_array($tables_result)) {
    $table_name = $table_row[0];
    $schema['tables'][$table_name] = [
        'columns' => [],
        'primary_key' => null,
        'create_statement' => ''
    ];

    // 2. Get columns for this table
    $columns_result = mysqli_query($conn, "SHOW COLUMNS FROM `$table_name`");
    while ($col = mysqli_fetch_assoc($columns_result)) {
        // Construct the column definition for ALTER TABLE
        $def = $col['Type'];
        if ($col['Null'] === 'NO') {
            $def .= " NOT NULL";
        } else {
            $def .= " DEFAULT NULL";
        }
        
        if ($col['Default'] !== null) {
             if (strtoupper($col['Default']) === 'CURRENT_TIMESTAMP') {
                 $def .= " DEFAULT CURRENT_TIMESTAMP";
             } else {
                 $def .= " DEFAULT '" . mysqli_real_escape_string($conn, $col['Default']) . "'";
             }
        }
        
        if ($col['Extra']) {
            $def .= " " . strtoupper($col['Extra']);
        }

        $schema['tables'][$table_name]['columns'][$col['Field']] = $def;

        if ($col['Key'] === 'PRI') {
            $schema['tables'][$table_name]['primary_key'] = $col['Field'];
        }
    }

    // 3. Get exact CREATE TABLE statement for complete table generation
    $create_result = mysqli_query($conn, "SHOW CREATE TABLE `$table_name`");
    $create_row = mysqli_fetch_array($create_result);
    $schema['tables'][$table_name]['create_statement'] = $create_row[1];
}

$json = json_encode($schema, JSON_PRETTY_PRINT);
file_put_contents(__DIR__ . '/schema.json', $json);

echo "<div style='font-family: sans-serif; padding: 40px; background: #F8FAFC; border-radius: 12px; max-width: 600px; margin: 40px auto; border: 1px solid #E2E8F0; text-align: center;'>";
echo "<div style='font-size: 40px; margin-bottom: 20px;'>✅</div>";
echo "<h2 style='color: #1E293B; margin-top: 0;'>Schema Generated Successfully!</h2>";
echo "<p style='color: #64748B;'>The current database structure has been exported to <strong>schema.json</strong>.</p>";
echo "<a href='db-sync.php' style='display: inline-block; padding: 10px 20px; background: #624BFF; color: white; text-decoration: none; border-radius: 8px; font-weight: 600; margin-top: 20px;'>Go to Sync Tool</a>";
echo "</div>";
?>

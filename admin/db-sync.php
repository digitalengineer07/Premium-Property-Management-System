<?php
require_once "../db.php";
session_start();

if (!isset($_SESSION['admin'])) {
    die("Unauthorized access.");
}

$schema_file = __DIR__ . '/schema.json';
$schema = [];
if (file_exists($schema_file)) {
    $schema = json_decode(file_get_contents($schema_file), true);
}

$missing_tables = [];
$missing_columns = [];
$sync_results = [];

if (!empty($schema) && isset($schema['tables'])) {
    foreach ($schema['tables'] as $table_name => $table_data) {
        // Check if table exists
        $check_table = mysqli_query($conn, "SHOW TABLES LIKE '$table_name'");
        if (mysqli_num_rows($check_table) == 0) {
            $missing_tables[$table_name] = $table_data['create_statement'];
        } else {
            // Check columns
            $existing_cols = [];
            $col_res = mysqli_query($conn, "SHOW COLUMNS FROM `$table_name`");
            while ($c = mysqli_fetch_assoc($col_res)) {
                $existing_cols[] = $c['Field'];
            }
            
            foreach ($table_data['columns'] as $col_name => $def) {
                if (!in_array($col_name, $existing_cols)) {
                    if (!isset($missing_columns[$table_name])) {
                        $missing_columns[$table_name] = [];
                    }
                    $missing_columns[$table_name][$col_name] = $def;
                }
            }
        }
    }
}

// Check for missing data migrations
$has_missing_data = false;
$chk_elec = mysqli_query($conn, "SELECT e.id FROM electricity e WHERE e.status = 'Paid' AND e.id NOT IN (SELECT bill_id FROM payments WHERE bill_type IN ('electricity', 'elec_rent')) LIMIT 1");
if(mysqli_num_rows($chk_elec) > 0) $has_missing_data = true;

$chk_rent = mysqli_query($conn, "SELECT id FROM rent WHERE status = 'Paid' AND id NOT IN (SELECT bill_id FROM payments WHERE bill_type = 'rent') LIMIT 1");
if(mysqli_num_rows($chk_rent) > 0) $has_missing_data = true;

// Handle Sync Action

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'sync') {
    $success = true;
    
    // Create missing tables
    foreach ($missing_tables as $table => $create_sql) {
        if (mysqli_query($conn, $create_sql)) {
            $sync_results[] = "<span style='color:#10B981;'>✔ Created table: $table</span>";
        } else {
            $sync_results[] = "<span style='color:#EF4444;'>✘ Failed to create $table: " . mysqli_error($conn) . "</span>";
            $success = false;
        }
    }
    
    // Add missing columns
    foreach ($missing_columns as $table => $columns) {
        foreach ($columns as $col => $def) {
            $sql = "ALTER TABLE `$table` ADD COLUMN `$col` $def";
            if (mysqli_query($conn, $sql)) {
                $sync_results[] = "<span style='color:#10B981;'>✔ Added column $col to $table</span>";
            } else {
                $sync_results[] = "<span style='color:#EF4444;'>✘ Failed to add $col to $table: " . mysqli_error($conn) . "</span>";
                $success = false;
            }
        }
    }
    
    // Data Migrations
    // 1. Missing Electricity Payments
    $res = mysqli_query($conn, "SELECT e.id, e.user_id, e.month, e.amount, e.rent_amount, e.maintenance, e.extra_charges, e.dues 
    FROM electricity e WHERE e.status = 'Paid'");
    $inserted_elec = 0;
    while($r = mysqli_fetch_assoc($res)) {
        $bill_id = $r['id'];
        $chk = mysqli_query($conn, "SELECT id FROM payments WHERE bill_id = $bill_id AND bill_type IN ('electricity', 'elec_rent')");
        if(mysqli_num_rows($chk) == 0) {
            $user_id = $r['user_id'];
            $month = $r['month'];
            $total = $r['amount'] + $r['rent_amount'] + $r['maintenance'] + $r['extra_charges'] + $r['dues'];
            $date_str = date('Y-m-10', strtotime('1 ' . $month));
            $sys_tx_id = 'SYS_REC_' . strtoupper(substr(md5(uniqid()), 0, 8));
            
            $stmt = mysqli_prepare($conn, "INSERT INTO payments (user_id, bill_type, bill_id, month, total_amount, payment_mode, paid_amount, payment_date, transaction_id, sys_tx_id) VALUES (?, 'elec_rent', ?, ?, ?, 'Cash/Offline', ?, ?, 'Manual/Old', ?)");
            mysqli_stmt_bind_param($stmt, "iisddss", $user_id, $bill_id, $month, $total, $total, $date_str, $sys_tx_id);
            if(mysqli_stmt_execute($stmt)) {
                $inserted_elec++;
                mysqli_query($conn, "UPDATE electricity SET paid_date = '$date_str' WHERE id = $bill_id AND (paid_date IS NULL OR paid_date = '0000-00-00')");
            }
        }
    }
    if ($inserted_elec > 0) {
        $sync_results[] = "<span style='color:#10B981;'>✔ Restored $inserted_elec missing payment receipts for legacy electricity bills.</span>";
    }

    // 2. Missing Rent Payments
    $res_rent = mysqli_query($conn, "SELECT id, user_id, month, rent_amount FROM rent WHERE status = 'Paid'");
    $inserted_rent = 0;
    while($r = mysqli_fetch_assoc($res_rent)) {
        $bill_id = $r['id'];
        $chk = mysqli_query($conn, "SELECT id FROM payments WHERE bill_id = $bill_id AND bill_type = 'rent'");
        if(mysqli_num_rows($chk) == 0) {
            $user_id = $r['user_id'];
            $month = $r['month'];
            $total = $r['rent_amount'];
            $date_str = date('Y-m-10', strtotime('1 ' . $month));
            $sys_tx_id = 'SYS_REC_' . strtoupper(substr(md5(uniqid()), 0, 8));
            
            $stmt = mysqli_prepare($conn, "INSERT INTO payments (user_id, bill_type, bill_id, month, total_amount, payment_mode, paid_amount, payment_date, transaction_id, sys_tx_id) VALUES (?, 'rent', ?, ?, ?, 'Cash/Offline', ?, ?, 'Manual/Old', ?)");
            mysqli_stmt_bind_param($stmt, "iisddss", $user_id, $bill_id, $month, $total, $total, $date_str, $sys_tx_id);
            if(mysqli_stmt_execute($stmt)) {
                $inserted_rent++;
                mysqli_query($conn, "UPDATE rent SET paid_date = '$date_str' WHERE id = $bill_id AND (paid_date IS NULL OR paid_date = '0000-00-00')");
            }
        }
    }
    if ($inserted_rent > 0) {
        $sync_results[] = "<span style='color:#10B981;'>✔ Restored $inserted_rent missing payment receipts for legacy rent bills.</span>";
    }
    
    // 3. Auto-Healing Ledger Status Check
    $healed_count = 0;
    
    // Electricity bills auto-heal
    $e_query = mysqli_query($conn, "SELECT id, amount, rent_amount, maintenance, extra_charges, dues, status, elec_status, rent_status FROM electricity");
    while($e = mysqli_fetch_assoc($e_query)) {
        $b_id = $e['id'];
        $gross_amt = (float)$e['amount'] + (float)$e['rent_amount'] + (float)$e['maintenance'] + (float)$e['extra_charges'] + (float)$e['dues'];
        $p_query = mysqli_query($conn, "SELECT SUM(paid_amount) as tp FROM payments WHERE bill_id = $b_id AND bill_type IN ('electricity', 'elec_rent')");
        $tp = (float)mysqli_fetch_assoc($p_query)['tp'];
        
        $correct_st = 'Due';
        if ($tp >= $gross_amt && $gross_amt > 0) $correct_st = 'Paid';
        elseif ($tp > 0) $correct_st = 'Partial';
        elseif ($gross_amt == 0 && $tp == 0) $correct_st = 'Paid';
        
        if ($e['status'] !== $correct_st || $e['elec_status'] !== $correct_st || $e['rent_status'] !== $correct_st) {
            mysqli_query($conn, "UPDATE electricity SET status = '$correct_st', elec_status = '$correct_st', rent_status = '$correct_st' WHERE id = $b_id");
            $healed_count++;
        }
    }
    
    // Rent bills auto-heal
    $r_query = mysqli_query($conn, "SELECT id, rent_amount, status FROM rent");
    while($r = mysqli_fetch_assoc($r_query)) {
        $b_id = $r['id'];
        $gross_amt = (float)$r['rent_amount'];
        $p_query = mysqli_query($conn, "SELECT SUM(paid_amount) as tp FROM payments WHERE bill_id = $b_id AND bill_type = 'rent'");
        $tp = (float)mysqli_fetch_assoc($p_query)['tp'];
        
        $correct_st = 'Due';
        if ($tp >= $gross_amt && $gross_amt > 0) $correct_st = 'Paid';
        elseif ($tp > 0) $correct_st = 'Partial';
        elseif ($gross_amt == 0 && $tp == 0) $correct_st = 'Paid';
        
        if ($r['status'] !== $correct_st) {
            mysqli_query($conn, "UPDATE rent SET status = '$correct_st' WHERE id = $b_id");
            $healed_count++;
        }
    }
    
    if ($healed_count > 0) {
        $sync_results[] = "<span style='color:#3B82F6;'>✔ Auto-Healed $healed_count billing records to perfectly match the payment ledger.</span>";
    }
    // Clear the arrays so they don't show up again
    if ($success) {
        $missing_tables = [];
        $missing_columns = [];
        $has_missing_data = false;

    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Synchronization</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        body { font-family: 'Outfit', sans-serif; background: #F8FAFC; color: #1E293B; margin: 0; padding: 40px; }
        .container { max-width: 800px; margin: 0 auto; background: white; border-radius: 16px; padding: 30px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        h2 { margin-top: 0; display: flex; align-items: center; gap: 10px; }
        .badge { background: #FEE2E2; color: #EF4444; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 700; }
        .badge.ok { background: #D1FAE5; color: #10B981; }
        .alert { background: #F1F5F9; padding: 15px; border-radius: 8px; font-size: 14px; margin-bottom: 20px; border-left: 4px solid #624BFF; }
        .card { border: 1px solid #E2E8F0; border-radius: 8px; padding: 15px; margin-bottom: 15px; }
        .card h4 { margin: 0 0 10px 0; color: #334155; }
        .btn { background: #624BFF; color: white; border: none; padding: 12px 24px; border-radius: 8px; font-family: 'Outfit'; font-weight: 600; cursor: pointer; font-size: 15px; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; }
        .btn:hover { background: #4F39F6; }
        .btn:disabled { background: #CBD5E1; cursor: not-allowed; }
        ul { margin: 0; padding-left: 20px; color: #64748B; font-size: 14px; }
        li { margin-bottom: 5px; }
        .log-box { background: #1E293B; color: #F8FAFC; padding: 15px; border-radius: 8px; font-family: monospace; font-size: 13px; line-height: 1.6; }
    </style>
</head>
<body>
    <div class="container">
        <h2><i class='bx bx-data'></i> Database Synchronizer</h2>
        
        <?php if (!file_exists($schema_file)): ?>
            <div class="alert" style="border-left-color: #EF4444; background: #FEF2F2;">
                <strong>Error:</strong> <code>schema.json</code> not found. You must run the schema generator on your local machine first and upload it.
                <br><br>
                <a href="generate-schema.php" class="btn">Generate Local Schema Now</a>
            </div>
        <?php else: ?>
            <div class="alert">
                Schema loaded successfully. Analyzing structure...
            </div>
            
            <?php if (!empty($sync_results)): ?>
                <div class="log-box">
                    <strong>Sync Log:</strong><br>
                    <?php echo implode("<br>", $sync_results); ?>
                </div>
                <br>
            <?php endif; ?>
            
            <?php if (empty($missing_tables) && empty($missing_columns) && !$has_missing_data): ?>
                <div class="card" style="text-align: center; padding: 40px;">
                    <div style="font-size: 48px; color: #10B981;"><i class='bx bx-check-circle'></i></div>
                    <h3>Database is perfectly synchronized!</h3>
                    <p style="color: #64748B;">No missing tables, columns, or orphaned data were found.</p>
                </div>
            <?php else: ?>
                <form method="POST">
                    <input type="hidden" name="action" value="sync">
                    
                    <?php if (!empty($missing_tables)): ?>
                        <div class="card">
                            <h4>Missing Tables <span class="badge"><?php echo count($missing_tables); ?></span></h4>
                            <ul>
                                <?php foreach ($missing_tables as $t => $sql) echo "<li>$t</li>"; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($missing_columns)): ?>
                        <div class="card">
                            <h4>Missing Columns <span class="badge"><?php echo array_sum(array_map('count', $missing_columns)); ?></span></h4>
                            <ul>
                                <?php foreach ($missing_columns as $t => $cols): ?>
                                    <li><strong><?php echo $t; ?>:</strong> <?php echo implode(", ", array_keys($cols)); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($has_missing_data): ?>
                        <div class="card">
                            <h4>Legacy Data Migration <span class="badge" style="background:#FEF3C7; color:#D97706;">Required</span></h4>
                            <ul>
                                <li>Missing payment receipts detected for old 'Paid' bills. A migration will be performed.</li>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <button type="submit" class="btn" style="width: 100%; justify-content: center; margin-top: 20px;">
                        <i class='bx bx-sync'></i> Synchronize Now
                    </button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="dashboard.php" style="color: #64748B; text-decoration: none; font-size: 14px; font-weight: 500;">&larr; Back to Dashboard</a>
        </div>
    </div>
</body>
</html>

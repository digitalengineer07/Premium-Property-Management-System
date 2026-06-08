<?php
// admin/api_reconcile.php
require_once "../db.php";
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['admin'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['bank_statement'])) {
    
    $fileTmpPath = $_FILES['bank_statement']['tmp_name'];
    $fileName = $_FILES['bank_statement']['name'];
    
    // Check if valid CSV
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    if ($fileExtension !== 'csv') {
        echo json_encode(['error' => 'Only .csv files are supported for reconciliation.']);
        exit;
    }

    $csvData = [];
    $isFirstRow = true;
    
    if (($handle = fopen($fileTmpPath, "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if ($isFirstRow) {
                // Heuristic mapping could go here
                $isFirstRow = false;
                continue;
            }
            $csvData[] = $data;
        }
        fclose($handle);
    }

    // A Real engine would try to match amounts on specific columns and dates.
    // For this implementation, we simply extract any numeric values that might represent "Credit / Deposit" amounts.
    // We'll scan each row for numbers > 0, and search for exactly those amounts in "payment_notifications" WHERE status='Pending'

    $matches = [];
    $matched_notification_ids = [];

    // Pre-fetch all pending notifications to do matching in memory (O(N) vs O(N*M))
    $qPending = mysqli_query($conn, "
        SELECT p.id, u.name as user_name, u.room_no, p.amount, p.payment_date, p.reference_number 
        FROM payment_notifications p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.status = 'Pending'
    ");
    
    $pending_payments = [];
    while ($row = mysqli_fetch_assoc($qPending)) {
        $pending_payments[] = $row;
    }

    foreach ($csvData as $idx => $row) {
        // Try to identify a deposit amount. Usually col 3, 4, or 5 in Indian Bank Statements (HDFC, SBI, ICICI)
        // We will heuristically grab all values, strip commas, check if numeric.
        $detected_amount = 0;
        $row_desc = "";
        
        foreach ($row as $col_idx => $val) {
            $clean_val = str_replace(',', '', trim($val));
            if (is_numeric($clean_val) && $clean_val > 0) {
                // It might be a balance or an amount. Usually amounts come before balance.
                // We'll just collect the first reasonable > 0 amount or assume the last is balance.
                // But let's just use it to see if it perfectly matches a pending rent/elec amount
                $detected_amount = max($detected_amount, (float)$clean_val);
            }
            if (strlen($val) > 4 && strlen($row_desc) < 100 && !is_numeric($clean_val)) {
                $row_desc .= $val . " ";
            }
        }
        
        // Match against pending
        foreach ($pending_payments as $p_idx => $pending) {
            if (in_array($pending['id'], $matched_notification_ids)) continue;
            
            // Allow integer matching or exactly equal
            if ($detected_amount == (float)$pending['amount']) {
                $matches[] = [
                    'csv_row' => $idx + 2, // 1 for 1-index, 1 for header
                    'csv_desc' => substr(trim($row_desc), 0, 40) . '...',
                    'bank_amount' => $detected_amount,
                    'resident' => $pending['user_name'] . ' (Room ' . $pending['room_no'] . ')',
                    'sys_amount' => (float)$pending['amount'],
                    'date' => $pending['payment_date'],
                    'sys_ref' => $pending['reference_number'],
                    'notification_id' => $pending['id']
                ];
                $matched_notification_ids[] = $pending['id'];
                break; // One bank row matches one pending payment
            }
        }
    }

    echo json_encode([
        'success' => true,
        'rows_scanned' => count($csvData),
        'matches_found' => count($matches),
        'matches' => $matches
    ]);
    
} else {
    echo json_encode(['error' => 'No file uploaded.']);
}

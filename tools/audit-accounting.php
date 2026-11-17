<?php
// Read-only CLI audit: connects independently of web sessions and application bootstrap.
if (PHP_SAPI !== 'cli') exit;
require_once __DIR__ . '/../includes/accounting_store.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = mysqli_connect(getenv('AUDIT_DB_HOST') ?: '127.0.0.1', getenv('AUDIT_DB_USER') ?: 'root', getenv('AUDIT_DB_PASSWORD') ?: '', getenv('AUDIT_DB_NAME') ?: 'renter_system', (int)(getenv('AUDIT_DB_PORT') ?: 3306));
accounting_query($conn, 'START TRANSACTION READ ONLY');
$issues = [];
$users = accounting_query($conn, 'SELECT * FROM users');
while ($user = mysqli_fetch_assoc($users)) {
    $id = (int)$user['id'];
    if (empty($user['rent_billing_mode'])) $issues[] = ['user'=>$id, 'issue'=>'Rent billing mode needs agreement review'];
    if (accounting_paise($user['pending_adjustment'] ?? 0) !== 0) $issues[] = ['user'=>$id, 'issue'=>'Legacy adjustment requires reconciliation against original invoices'];
    foreach (['rent','electricity'] as $table) {
        $rows = accounting_query($conn, "SELECT * FROM $table WHERE user_id=$id");
        while ($bill = mysqli_fetch_assoc($rows)) {
            $total = accounting_paise($bill[$table === 'rent' ? 'rent_amount' : 'total_amount']);
            $paid = accounting_bill_paid($conn, $id, $table, (int)$bill['id']);
            $expected = accounting_status($total, $paid);
            if ($expected !== $bill['status']) $issues[] = ['table'=>$table, 'bill'=>$bill['id'], 'issue'=>'Stored status differs from ledger', 'expected'=>$expected];
            if ($paid > $total) $issues[] = ['table'=>$table, 'bill'=>$bill['id'], 'issue'=>'Overpayment requires credit reconciliation'];
            if ($table === 'electricity') {
                if (array_sum(accounting_components($bill, 0)) !== $total) $issues[] = ['table'=>$table, 'bill'=>$bill['id'], 'issue'=>'Invoice total differs from its components'];
                if (accounting_paise($bill['dues'] ?? 0) !== 0) $issues[] = ['table'=>$table, 'bill'=>$bill['id'], 'issue'=>'Check whether carried dues duplicate earlier invoices'];
            }
        }
    }
}
$orphaned = accounting_query($conn, "SELECT p.id FROM payments p LEFT JOIN rent r ON p.bill_type='rent' AND p.bill_id=r.id AND p.user_id=r.user_id LEFT JOIN electricity e ON p.bill_type IN ('electricity','elec_rent') AND p.bill_id=e.id AND p.user_id=e.user_id WHERE (p.bill_type='rent' AND r.id IS NULL) OR (p.bill_type IN ('electricity','elec_rent') AND e.id IS NULL)");
while ($row = mysqli_fetch_assoc($orphaned)) $issues[] = ['payment'=>$row['id'], 'issue'=>'Payment lacks matching owned invoice (including legacy joining receipts)'];
mysqli_rollback($conn);
echo json_encode(['issues'=>$issues, 'count'=>count($issues)], JSON_PRETTY_PRINT) . PHP_EOL;
exit(count($issues) ? 2 : 0);

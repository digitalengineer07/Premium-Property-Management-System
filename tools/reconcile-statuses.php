<?php
// Only derived status labels change. Original invoices and receipts retain their amounts.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
$options = getopt('', ['database:', 'apply', 'backup:']);
$target = $options['database'] ?? '';
if (!preg_match('/^[A-Za-z0-9_]+$/D', $target)) { fwrite(STDERR, "Explicit --database=NAME required. Add --apply --backup=PATH to write.\n"); exit(1); }
$apply = array_key_exists('apply', $options);
if ($apply && (empty($options['backup']) || !is_file($options['backup']) || filesize($options['backup']) === 0)) { fwrite(STDERR, "A non-empty backup is required.\n"); exit(1); }
putenv('RMS_DB_NAME=' . $target);
require_once __DIR__ . '/../includes/database_connection.php';
require_once __DIR__ . '/../includes/accounting_store.php';
$conn = rms_database_connection();
if (mysqli_fetch_row(accounting_query($conn, 'SELECT DATABASE()'))[0] !== $target) throw new RuntimeException('Target mismatch.');
$changes = accounting_atomic($conn, function () use ($conn, $apply) {
    $changes = [];
    $users = accounting_query($conn, 'SELECT id FROM users ORDER BY id FOR UPDATE');
    while ($user = mysqli_fetch_assoc($users)) {
        $uid = (int)$user['id'];
        foreach (['rent','electricity'] as $table) {
            $rows = accounting_query($conn, "SELECT * FROM $table WHERE user_id=$uid FOR UPDATE");
            while ($bill = mysqli_fetch_assoc($rows)) {
                $id = (int)$bill['id'];
                $total = accounting_paise($bill[$table === 'rent' ? 'rent_amount' : 'total_amount']);
                $expected = accounting_status($total, accounting_bill_paid($conn, $uid, $table, $id));
                if ($expected === $bill['status']) continue;
                $detail = ['table'=>$table,'bill'=>$id,'before'=>$bill['status'],'after'=>$expected];
                $changes[] = $detail;
                if ($apply) {
                    $status = mysqli_real_escape_string($conn, $expected);
                    accounting_query($conn, "UPDATE $table SET status='$status' WHERE id=$id AND user_id=$uid");
                    accounting_event($conn, 'status_reconciliation', $uid, "status:$table:$id", $detail);
                }
            }
        }
    }
    return $changes;
});
echo json_encode(['database'=>$target,'applied'=>$apply,'changes'=>$changes], JSON_PRETTY_PRINT) . PHP_EOL;
